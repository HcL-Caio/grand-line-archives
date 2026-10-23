<?php
declare(strict_types=1);

use App\Config\Database;
use App\Controllers\CardController;
use App\Models\Glossary;

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
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$apiAt = strpos($path, '/api/');
$route = $apiAt === false ? $path : substr($path, $apiAt);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    respond(['erro' => 'Método não permitido'], 405);
}

try {
    if ($route === '/api/health') {
        Database::connect()->query('SELECT 1');
        respond(['status' => 'ok', 'banco' => 'conectado']);
    }
    if ($route === '/api/cartas') {
        respond((new CardController(Database::connect()))->index($_GET));
    }
    if (preg_match('~^/api/cartas/([A-Za-z0-9-]{2,32})$~D', $route, $matches)) {
        $card = (new CardController(Database::connect()))->show($matches[1]);
        respond($card ? ['dados' => $card] : ['erro' => 'Carta não encontrada'], $card ? 200 : 404);
    }
    if ($route === '/api/glossario') {
        $search = $_GET['busca'] ?? '';
        if (!is_string($search) || mb_strlen($search, 'UTF-8') > 100) {
            respond(['erro' => 'Busca inválida'], 422);
        }
        respond(['dados' => (new Glossary(Database::connect()))->all(trim($search))]);
    }
    respond(['erro' => 'Rota não encontrada'], 404);
} catch (InvalidArgumentException $e) {
    respond(['erro' => $e->getMessage()], 422);
} catch (PDOException $e) {
    error_log('Grand Line Archives: falha na consulta ao banco.');
    respond(['erro' => 'Banco indisponível. Confira a instalação e as migrações.'], 503);
} catch (Throwable $e) {
    error_log('Grand Line Archives: ' . $e->getMessage());
    respond(['erro' => 'Erro interno do servidor'], 500);
}
