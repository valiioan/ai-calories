<?php

namespace App\Services\Journal;

use App\Models\Food;
use InvalidArgumentException;

class NutrientSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function fromFood(Food $food, float $quantity, string $unit, ?string $displayName = null): array
    {
        if ($food->energy_kcal === null) {
            throw new InvalidArgumentException('The food has no energy value.');
        }

        $grams = $this->grams($food, $quantity, $unit);

        return [
            'food_id' => $food->id,
            'custom_name' => $displayName !== null && trim($displayName) !== '' ? trim($displayName) : $food->description,
            'quantity' => round($quantity, 2),
            'unit' => $unit,
            'grams' => $grams === null ? null : round($grams, 2),
            'energy_kcal' => $this->scale($food->energy_kcal, $grams),
            'protein_g' => $this->scale($food->protein_g, $grams),
            'carbohydrate_g' => $this->scale($food->carbohydrate_g, $grams),
            'fat_g' => $this->scale($food->fat_g, $grams),
            'fiber_g' => $this->scale($food->fiber_g, $grams),
            'nutrition_source' => 'usda',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function manual(array $item): array
    {
        $name = trim((string) ($item['custom_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('A manual food needs a name.');
        }

        $quantity = (float) $item['quantity'];
        $unit = (string) $item['unit'];

        return [
            'food_id' => null,
            'custom_name' => $name,
            'quantity' => round($quantity, 2),
            'unit' => $unit,
            'grams' => $unit === 'g' || $unit === 'ml' ? round($quantity, 2) : null,
            'energy_kcal' => $this->optionalNumber($item['energy_kcal'] ?? null),
            'protein_g' => $this->optionalNumber($item['protein_g'] ?? null),
            'carbohydrate_g' => $this->optionalNumber($item['carbohydrate_g'] ?? null),
            'fat_g' => $this->optionalNumber($item['fat_g'] ?? null),
            'fiber_g' => $this->optionalNumber($item['fiber_g'] ?? null),
            'nutrition_source' => 'manual',
        ];
    }

    private function grams(Food $food, float $quantity, string $unit): ?float
    {
        if ($unit === 'g') {
            return $quantity;
        }

        if ($unit === 'ml') {
            $servingUnit = strtolower((string) $food->serving_unit);
            $servingSize = (float) $food->serving_size;
            $gramWeight = (float) $food->gram_weight;
            if ($servingUnit === 'ml' && $servingSize > 0 && $gramWeight > 0) {
                return $quantity * $gramWeight / $servingSize;
            }

            return $quantity;
        }

        if ($unit === 'serving') {
            $gramWeight = (float) $food->gram_weight;
            if ($gramWeight <= 0) {
                throw new InvalidArgumentException('This food has no gram weight for a serving.');
            }

            return $quantity * $gramWeight;
        }

        throw new InvalidArgumentException('Unknown unit.');
    }

    private function scale(mixed $per100, ?float $grams): ?float
    {
        if ($per100 === null || $grams === null) {
            return null;
        }

        return round(((float) $per100) * $grams / 100, 2);
    }

    private function optionalNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
