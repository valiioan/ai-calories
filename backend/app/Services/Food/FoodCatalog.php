<?php

namespace App\Services\Food;

use App\Models\Food;
use InvalidArgumentException;
use RuntimeException;

class FoodCatalog
{
    public function __construct(private UsdaClient $usda) {}

    /**
     * @return list<Food>
     */
    public function search(string $query, int $page = 1): array
    {
        $rows = $this->usda->search($query, $page);
        $foods = [];
        foreach (array_slice($rows, 0, 5) as $row) {
            if (! is_array($row) || ! isset($row['fdcId'])) {
                continue;
            }
            $normalized = $this->usda->normalize($row);
            if ($normalized['energy_kcal'] === null) {
                try {
                    $row = $this->usda->food((int) $row['fdcId']);
                } catch (RuntimeException) {
                    $row = $row;
                }
            }
            if (! is_array($row)) {
                continue;
            }
            $foods[] = $this->usda->upsert($row);
        }

        return $foods;
    }

    public function findOrFetch(int $foodId): Food
    {
        $food = Food::query()->find($foodId);
        if ($food instanceof Food) {
            return $food;
        }

        throw new InvalidArgumentException('Food was not found.');
    }
}
