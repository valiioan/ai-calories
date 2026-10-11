<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meal_id',
    'food_id',
    'custom_name',
    'quantity',
    'unit',
    'grams',
    'energy_kcal',
    'protein_g',
    'carbohydrate_g',
    'fat_g',
    'fiber_g',
    'nutrition_source',
])]
class MealItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'grams' => 'decimal:2',
            'energy_kcal' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbohydrate_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
