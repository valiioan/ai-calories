<?php

namespace App\Http\Resources;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Food */
class FoodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fdc_id' => $this->fdc_id,
            'description' => $this->description,
            'energy_kcal_per_100g' => $this->nullable($this->energy_kcal),
            'protein_g_per_100g' => $this->nullable($this->protein_g),
            'carbohydrate_g_per_100g' => $this->nullable($this->carbohydrate_g),
            'fat_g_per_100g' => $this->nullable($this->fat_g),
            'fiber_g_per_100g' => $this->nullable($this->fiber_g),
            'serving' => [
                'size' => $this->nullable($this->serving_size),
                'unit' => $this->serving_unit,
                'gram_weight' => $this->nullable($this->gram_weight),
                'household' => $this->household_serving,
            ],
        ];
    }

    private function nullable(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
