<?php

namespace App\Services\Food;

use App\Models\Food;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class UsdaClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $page = 1): array
    {
        $payload = $this->get('/v1/foods/search', [
            'query' => $query,
            'pageSize' => 5,
            'pageNumber' => max(1, $page),
        ]);

        $foods = $payload['foods'] ?? [];

        return is_array($foods) ? array_values($foods) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function food(int $fdcId): array
    {
        return $this->get('/v1/food/'.$fdcId);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $key = (string) config('services.usda.key');
        if ($key === '') {
            throw new RuntimeException('USDA is not configured.');
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get(rtrim((string) config('services.usda.base_url'), '/').$path, [
                    ...$query,
                    'api_key' => $key,
                ]);
        } catch (Throwable) {
            throw new RuntimeException('USDA request failed.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('USDA request failed.');
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $food
     */
    public function upsert(array $food): Food
    {
        $normalized = $this->normalize($food);

        return Food::query()->updateOrCreate(
            ['fdc_id' => $normalized['fdc_id']],
            [...$normalized, 'fetched_at' => CarbonImmutable::now()],
        );
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array<string, mixed>
     */
    public function normalize(array $food): array
    {
        $dataType = (string) ($food['dataType'] ?? 'Unknown');
        $servingSize = $this->number($food['servingSize'] ?? null);
        $servingUnit = isset($food['servingSizeUnit']) ? (string) $food['servingSizeUnit'] : null;
        $portions = is_array($food['foodPortions'] ?? null) ? $food['foodPortions'] : [];
        $portionGrams = isset($portions[0]['gramWeight']) ? $this->number($portions[0]['gramWeight']) : null;
        $gramWeight = $portionGrams;
        if ($gramWeight === null && $servingSize !== null && in_array(strtolower((string) $servingUnit), ['g', 'gr', 'grm'], true)) {
            $gramWeight = $servingSize;
        }

        $basis = $dataType === 'Branded' ? ($gramWeight ?? ($servingUnit !== null && strtolower($servingUnit) === 'g' ? $servingSize : null)) : 100.0;
        $nutrients = $this->nutrients($food);

        return [
            'fdc_id' => (int) ($food['fdcId'] ?? 0),
            'description' => (string) ($food['description'] ?? ''),
            'data_type' => $dataType,
            'brand_owner' => $this->text($food['brandOwner'] ?? null),
            'brand_name' => $this->text($food['brandName'] ?? null),
            'serving_size' => $servingSize,
            'serving_unit' => $servingUnit,
            'household_serving' => $this->text($food['householdServingFullText'] ?? null),
            'gram_weight' => $gramWeight,
            'energy_kcal' => $this->per100($nutrients['208'] ?? $nutrients['1008'] ?? null, $basis),
            'protein_g' => $this->per100($nutrients['203'] ?? null, $basis),
            'carbohydrate_g' => $this->per100($nutrients['205'] ?? null, $basis),
            'fat_g' => $this->per100($nutrients['204'] ?? null, $basis),
            'fiber_g' => $this->per100($nutrients['291'] ?? null, $basis),
        ];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array<string, float>
     */
    private function nutrients(array $food): array
    {
        $rows = is_array($food['foodNutrients'] ?? null) ? $food['foodNutrients'] : [];
        $found = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $number = (string) ($row['nutrientNumber'] ?? $row['nutrient']['number'] ?? '');
            $amount = $row['value'] ?? $row['amount'] ?? null;
            $value = $this->number($amount);
            if ($number !== '' && $value !== null) {
                $found[$number] = $value;
            }
        }

        return $found;
    }

    private function per100(?float $amount, ?float $basisGrams): ?float
    {
        if ($amount === null || $basisGrams === null || $basisGrams <= 0) {
            return null;
        }

        return round($amount * 100 / $basisGrams, 2);
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
