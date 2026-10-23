<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Card;
use App\Support\CardFilters;
use PDO;

final class CardController
{
    private Card $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new Card($pdo);
    }

    public function index(array $query): array
    {
        return $this->model->search(CardFilters::fromQuery($query));
    }

    public function show(string $code): ?array
    {
        return $this->model->find($code);
    }
}
