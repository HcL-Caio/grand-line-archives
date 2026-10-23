<?php
declare(strict_types=1);

namespace App\Controllers;

use DomainException;
use InvalidArgumentException;
use PDO;

final class FavoriteController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(int $userId): array
    {
        $query = $this->pdo->prepare(
            'SELECT c.codigo, c.nome, c.tipo, c.raridade, c.poder, f.criado_em,
                    (SELECT i.url_imagem FROM impressoes_cartas i WHERE i.carta_codigo = c.codigo ORDER BY i.id ASC LIMIT 1) AS imagem
             FROM favoritos f INNER JOIN cartas c ON c.codigo = f.carta_codigo
             WHERE f.usuario_id = :usuario ORDER BY f.criado_em DESC, c.codigo ASC'
        );
        $query->execute([':usuario' => $userId]);
        return ['dados' => $query->fetchAll()];
    }

    public function add(int $userId, array $data): array
    {
        $code = $data['codigo'] ?? null;
        if (!is_string($code) || !preg_match('/^[A-Za-z0-9-]{2,32}$/D', $code)) {
            throw new InvalidArgumentException('Código de carta inválido.');
        }
        $check = $this->pdo->prepare('SELECT 1 FROM cartas WHERE codigo = :codigo');
        $check->execute([':codigo' => $code]);
        if (!$check->fetchColumn()) {
            throw new DomainException('Carta não encontrada.', 404);
        }
        $insert = $this->pdo->prepare(
            'INSERT IGNORE INTO favoritos (usuario_id, carta_codigo) VALUES (:usuario, :carta)'
        );
        $insert->execute([':usuario' => $userId, ':carta' => $code]);
        return ['sucesso' => true, 'codigo' => $code];
    }

    public function remove(int $userId, string $code): array
    {
        $remove = $this->pdo->prepare(
            'DELETE FROM favoritos WHERE usuario_id = :usuario AND carta_codigo = :carta'
        );
        $remove->execute([':usuario' => $userId, ':carta' => $code]);
        return ['sucesso' => true, 'codigo' => $code];
    }
}
