<?php
declare(strict_types=1);

namespace App\Support;

use DomainException;

final class SessionAuth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        session_name('gla_session');
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function publicUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id' => (int) $_SESSION['user_id'],
            'nome' => (string) $_SESSION['user_name'],
            'perfil' => (string) $_SESSION['user_role'],
        ];
    }

    public static function token(): string
    {
        return (string) $_SESSION['csrf'];
    }

    public static function requireUser(): int
    {
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id <= 0) {
            throw new DomainException('Faça login para acessar seus favoritos.', 401);
        }
        return $id;
    }

    public static function requireCsrf(): void
    {
        $supplied = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($supplied) || !hash_equals(self::token(), $supplied)) {
            throw new DomainException('Token CSRF inválido. Atualize a página.', 403);
        }
    }

    public static function signIn(int $id, string $name, string $role): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function signOut(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}
