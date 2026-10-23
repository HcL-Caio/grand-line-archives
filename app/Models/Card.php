<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Card
{
    public function __construct(private PDO $pdo)
    {
    }

    public function search(array $filters): array
    {
        $where = [];
        $params = [];

        if ($filters['busca'] !== '') {
            $where[] = '(c.nome LIKE :busca OR c.codigo LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }
        if ($filters['tipo'] !== '') {
            $where[] = 'c.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if ($filters['custo'] !== null) {
            $where[] = 'c.custo = :custo';
            $params[':custo'] = $filters['custo'];
        }
        if ($filters['cor'] !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM carta_cores cc JOIN cores co ON co.id = cc.cor_id WHERE cc.carta_codigo = c.codigo AND co.nome = :cor)';
            $params[':cor'] = $filters['cor'];
        }

        $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $totalQuery = $this->pdo->prepare('SELECT COUNT(*) FROM cartas c' . $clause);
        $this->bind($totalQuery, $params);
        $totalQuery->execute();
        $total = (int) $totalQuery->fetchColumn();

        $limit = $filters['limite'];
        $offset = ($filters['pagina'] - 1) * $limit;
        $sql = 'SELECT c.codigo, c.nome, c.tipo, c.raridade, c.cores, c.custo, c.poder, c.efeito, c.url_fonte,
                       (SELECT i.url_imagem FROM impressoes_cartas i WHERE i.carta_codigo = c.codigo ORDER BY i.id ASC LIMIT 1) AS imagem
                FROM cartas c' . $clause . ' ORDER BY c.codigo ASC LIMIT :limite OFFSET :deslocamento';
        $statement = $this->pdo->prepare($sql);
        $this->bind($statement, $params);
        $statement->bindValue(':limite', $limit, PDO::PARAM_INT);
        $statement->bindValue(':deslocamento', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'dados' => $statement->fetchAll(),
            'paginacao' => [
                'pagina' => $filters['pagina'],
                'limite' => $limit,
                'total' => $total,
                'paginas' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function find(string $code): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT codigo, nome, tipo, raridade, cores, atributo, custo, poder, contra_ataque,
                    vida, bloco, colecao_codigo, efeito, gatilho, url_fonte
             FROM cartas WHERE codigo = :codigo LIMIT 1'
        );
        $statement->execute([':codigo' => $code]);
        $card = $statement->fetch();
        if (!$card) {
            return null;
        }
        $printings = $this->pdo->prepare(
            'SELECT id, idioma, colecao_codigo, arte, url_imagem
             FROM impressoes_cartas WHERE carta_codigo = :codigo ORDER BY id ASC'
        );
        $printings->execute([':codigo' => $code]);
        $card['impressoes'] = $printings->fetchAll();
        return $card;
    }

    private function bind(\PDOStatement $statement, array $params): void
    {
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }
}
