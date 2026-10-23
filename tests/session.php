<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Support/SessionAuth.php';

use App\Support\SessionAuth;

function ensure(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException('FALHOU: ' . $description);
    }
}

SessionAuth::start();
$firstToken = SessionAuth::token();
ensure((bool) preg_match('/^[a-f0-9]{64}$/D', $firstToken), 'token aleatório válido');
ensure(SessionAuth::publicUser() === null, 'sessão sem login');
$_SERVER['HTTP_X_CSRF_TOKEN'] = $firstToken;
SessionAuth::requireCsrf();
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalido';
try {
    SessionAuth::requireCsrf();
    throw new RuntimeException('CSRF inválido foi aceito');
} catch (DomainException $exception) {
    ensure($exception->getCode() === 403, 'CSRF retorna 403');
}
SessionAuth::signIn(42, 'Pessoa Teste', 'usuario');
ensure(SessionAuth::publicUser()['id'] === 42, 'usuário de sessão');
SessionAuth::signOut();
ensure(SessionAuth::publicUser() === null, 'logout apaga login');
ensure(SessionAuth::token() !== $firstToken, 'CSRF foi renovado');
echo "OK: sessão e CSRF validados.\n";
