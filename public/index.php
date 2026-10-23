<?php
declare(strict_types=1);

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\CardController;
use App\Controllers\FavoriteController;
use App\Models\Glossary;
use App\Support\SessionAuth;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (str_starts_with($relative, 'Config\\')) {
        $file = dirname(__DIR__) . '/config/' . substr(str_replace('\\', '/', $relative), 7) . '.php';
    }
    if (is_file($file)) {
        require $file;
    }
});

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function readJson(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_starts_with($type, 'application/json')) {
        respond(['erro' => 'Envie Content-Type: application/json'], 415);
    }
    $raw = file_get_contents('php://input', false, null, 0, 16385);
    if ($raw === false || strlen($raw) > 16384) {
        respond(['erro' => 'Corpo da requisição muito grande'], 413);
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || array_is_list($data)) {
        respond(['erro' => 'JSON deve ser um objeto'], 400);
    }
    return $data;
}

SessionAuth::start();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$apiAt = strpos($path, '/api/');
$route = $apiAt === false ? $path : substr($path, $apiAt);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET' && $route === '/api/sessao') {
        respond(['usuario' => SessionAuth::publicUser(), 'csrf' => SessionAuth::token()]);
    }
    if ($method === 'POST' && in_array($route, ['/api/registrar', '/api/login', '/api/logout'], true)) {
        SessionAuth::requireCsrf();
        $auth = new AuthController(Database::connect());
        if ($route === '/api/logout') {
            respond($auth->logout());
        }
        $data = readJson();
        respond($route === '/api/registrar' ? $auth->register($data) : $auth->login($data), $route === '/api/registrar' ? 201 : 200);
    }
    if ($route === '/api/favoritos') {
        $id = SessionAuth::requireUser();
        $favorites = new FavoriteController(Database::connect());
        if ($method === 'GET') {
            respond($favorites->index($id));
        }
        if ($method === 'POST') {
            SessionAuth::requireCsrf();
            respond($favorites->add($id, readJson()), 201);
        }
    }
    if ($method === 'DELETE' && preg_match('~^/api/favoritos/([A-Za-z0-9-]{2,32})$~D', $route, $matches)) {
        SessionAuth::requireCsrf();
        $id = SessionAuth::requireUser();
        respond((new FavoriteController(Database::connect()))->remove($id, $matches[1]));
    }
    if ($method === 'GET' && $route === '/api/health') {
        Database::connect()->query('SELECT 1');
        respond(['status' => 'ok', 'banco' => 'conectado']);
    }
    if ($method === 'GET' && $route === '/api/cartas') {
        respond((new CardController(Database::connect()))->index($_GET));
    }
    if ($method === 'GET' && preg_match('~^/api/cartas/([A-Za-z0-9-]{2,32})$~D', $route, $matches)) {
        $card = (new CardController(Database::connect()))->show($matches[1]);
        respond($card ? ['dados' => $card] : ['erro' => 'Carta não encontrada'], $card ? 200 : 404);
    }
    if ($method === 'GET' && $route === '/api/glossario') {
        $search = $_GET['busca'] ?? '';
        if (!is_string($search) || mb_strlen($search, 'UTF-8') > 100) {
            respond(['erro' => 'Busca inválida'], 422);
        }
        respond(['dados' => (new Glossary(Database::connect()))->all(trim($search))]);
    }
    if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
        header('Allow: GET, POST, DELETE');
        respond(['erro' => 'Método não permitido'], 405);
    }
    respond(['erro' => 'Rota não encontrada'], 404);
} catch (InvalidArgumentException $e) {
    respond(['erro' => $e->getMessage()], 422);
} catch (DomainException $e) {
    respond(['erro' => $e->getMessage()], (int) $e->getCode() ?: 400);
} catch (PDOException $e) {
    error_log('Grand Line Archives: falha no banco.');
    respond(['erro' => 'Banco indisponível. Confira schema e migração.'], 503);
} catch (Throwable $e) {
    error_log('Grand Line Archives: ' . $e->getMessage());
    respond(['erro' => 'Erro interno do servidor'], 500);
}
