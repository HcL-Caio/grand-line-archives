<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Support/CardFilters.php';

use App\Support\CardFilters;

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException('FALHOU: ' . $message);
    }
}
$defaults = CardFilters::fromQuery([]);
check($defaults['pagina'] === 1 && $defaults['limite'] === 12, 'valores padrão');
check($defaults['custo'] === null, 'custo ausente');
$valid = CardFilters::fromQuery(['pagina' => '2', 'limite' => '24', 'custo' => '0', 'cor' => 'Blue']);
check($valid['pagina'] === 2 && $valid['custo'] === 0 && $valid['cor'] === 'Blue', 'filtros combinados');
foreach ([['pagina' => '-1'], ['limite' => '500'], ['custo' => '-1'], ['tipo' => ['invalido']]] as $case) {
    try {
        CardFilters::fromQuery($case);
        throw new RuntimeException('Entrada inválida aceita');
    } catch (InvalidArgumentException $e) {
        // Esperado
    }
}
echo "OK: filtros validados.\n";
