<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\SessionAuth;
use DomainException;
use InvalidArgumentException;
use PDO;
use PDOException;

final class AuthController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function register(array $data): array
    {
        if (!is_string($data['nome'] ?? null) || !is_string($data['email'] ?? null)) {
            throw new InvalidArgumentException('Nome e e-mail devem ser textos.');
        }
        $name = trim($data['nome']);
        $email = mb_strtolower(trim($data['email']), 'UTF-8');
        $password = $data['senha'] ?? null;
        if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 120) {
            throw new InvalidArgumentException('Nome deve ter de 2 a 120 caracteres.');
        }
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }
        if (!is_string($password) || strlen($password) < 10 || strlen($password) > 72) {
            throw new InvalidArgumentException('Senha deve ter entre 10 e 72 bytes.');
        }

        $statement = $this->pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, 'usuario')"
        );
        try {
            $statement->execute([
                ':nome' => $name,
                ':email' => $email,
                ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainException('E-mail já cadastrado.', 409);
            }
            throw $e;
        }
        SessionAuth::signIn((int) $this->pdo->lastInsertId(), $name, 'usuario');
        return ['usuario' => SessionAuth::publicUser(), 'csrf' => SessionAuth::token()];
    }

    public function login(array $data): array
    {
        if (!is_string($data['email'] ?? null)) {
            throw new DomainException('Credenciais inválidas.', 401);
        }
        $email = mb_strtolower(trim($data['email']), 'UTF-8');
        $password = $data['senha'] ?? null;
        if (!is_string($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Credenciais inválidas.', 401);
        }
        $statement = $this->pdo->prepare(
            'SELECT id, nome, senha_hash, perfil, ativo FROM usuarios WHERE email = :email LIMIT 1'
        );
        $statement->execute([':email' => $email]);
        $user = $statement->fetch();
        if (!$user || !(bool) $user['ativo'] || !password_verify($password, $user['senha_hash'])) {
            throw new DomainException('Credenciais inválidas.', 401);
        }
        if (password_needs_rehash($user['senha_hash'], PASSWORD_DEFAULT)) {
            $rehash = $this->pdo->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
            $rehash->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => (int) $user['id']]);
        }
        SessionAuth::signIn((int) $user['id'], $user['nome'], $user['perfil']);
        return ['usuario' => SessionAuth::publicUser(), 'csrf' => SessionAuth::token()];
    }

    public function logout(): array
    {
        SessionAuth::signOut();
        return ['usuario' => null, 'csrf' => SessionAuth::token()];
    }
}
