<?php

namespace App\Services\Journal;

use App\Services\Food\FoodCatalog;
use InvalidArgumentException;

class MealInput
{
    public function __construct(
        private FoodCatalog $foods,
        private NutrientSnapshot $snapshots,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function prepare(array $payload): array
    {
        $payload['items'] = array_map(function (array $item) {
            if (! empty($item['food_id'])) {
                return $this->snapshots->fromFood(
                    $this->foods->findOrFetch((int) $item['food_id']),
                    (float) $item['quantity'],
                    (string) $item['unit'],
                    isset($item['custom_name']) ? (string) $item['custom_name'] : null,
                );
            }

            if (! isset($item['energy_kcal']) || $item['energy_kcal'] === null || $item['energy_kcal'] === '') {
                throw new InvalidArgumentException('A manual food needs calories.');
            }

            return $this->snapshots->manual($item);
        }, $payload['items']);

        return $payload;
    }
}
