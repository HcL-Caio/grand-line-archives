<?php
declare(strict_types=1);

// Roteador para php -S localhost:8000 -t public public/router.php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$real = realpath(__DIR__ . rawurldecode($path));
$root = realpath(__DIR__);
if ($real && ($real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR)) && is_file($real)) {
    return false;
}
if ($path === '/' || $path === '') {
    readfile(__DIR__ . '/index.html');
    return true;
}
require __DIR__ . '/index.php';
