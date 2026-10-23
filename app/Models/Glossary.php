<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Glossary
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(string $search = ''): array
    {
        $sql = "SELECT termo, traducao, definicao, fonte_url
                FROM glossario_termos WHERE situacao = 'publicado'";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (termo LIKE :busca OR definicao LIKE :busca)';
            $params[':busca'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY termo ASC LIMIT 100';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }
}
