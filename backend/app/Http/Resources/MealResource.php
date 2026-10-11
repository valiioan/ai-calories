<?php

namespace App\Http\Resources;

use App\Models\Meal;
use App\Models\MealItem;
use App\Services\Journal\LocalDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Meal */
class MealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $days = app(LocalDay::class);
        $user = $request->user() ?? $this->user;
        $items = $this->items;

        return [
            'id' => $this->id,
            'eaten_at' => $this->eaten_at?->toIso8601String(),
            'date' => $user ? $days->localDate($user, $this->eaten_at) : null,
            'time' => $user ? $days->localTime($user, $this->eaten_at) : null,
            'meal_type' => $this->meal_type,
            'note' => $this->note,
            'original_text' => $this->original_text,
            'assumptions' => $this->assumptions,
            'energy_kcal' => round((float) $items->sum(fn (MealItem $item) => (float) $item->energy_kcal), 2),
            'items' => $items->map(fn (MealItem $item) => [
                'id' => $item->id,
                'food_id' => $item->food_id,
                'custom_name' => $item->custom_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'grams' => $item->grams === null ? null : (float) $item->grams,
                'energy_kcal' => $item->energy_kcal === null ? null : (float) $item->energy_kcal,
                'protein_g' => $item->protein_g === null ? null : (float) $item->protein_g,
                'carbohydrate_g' => $item->carbohydrate_g === null ? null : (float) $item->carbohydrate_g,
                'fat_g' => $item->fat_g === null ? null : (float) $item->fat_g,
                'fiber_g' => $item->fiber_g === null ? null : (float) $item->fiber_g,
                'nutrition_source' => $item->nutrition_source,
            ])->values(),
        ];
    }
}
