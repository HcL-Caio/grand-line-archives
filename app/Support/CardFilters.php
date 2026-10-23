<?php
declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class CardFilters
{
    public static function fromQuery(array $query): array
    {
        $page = self::integer($query, 'pagina', 1, 1, 1000000);
        $limit = self::integer($query, 'limite', 12, 1, 60);
        $cost = self::integer($query, 'custo', null, 0, 99);
        $search = self::string($query, 'busca', 100);
        $type = self::string($query, 'tipo', 40);
        $color = self::string($query, 'cor', 32);

        return [
            'pagina' => $page,
            'limite' => $limit,
            'custo' => $cost,
            'busca' => $search,
            'tipo' => $type,
            'cor' => $color,
        ];
    }

    private static function string(array $query, string $key, int $max): string
    {
        $value = $query[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException("Parâmetro inválido: {$key}");
        }
        $value = trim($value);
        if (mb_strlen($value, 'UTF-8') > $max) {
            throw new InvalidArgumentException("Parâmetro muito longo: {$key}");
        }
        return $value;
    }

    private static function integer(array $query, string $key, ?int $default, int $min, int $max): ?int
    {
        if (!array_key_exists($key, $query) || $query[$key] === '') {
            return $default;
        }
        $value = filter_var($query[$key], FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            throw new InvalidArgumentException("Parâmetro inválido: {$key}");
        }
        return $value;
    }
}
