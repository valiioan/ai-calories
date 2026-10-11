<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'fdc_id',
    'description',
    'data_type',
    'brand_owner',
    'brand_name',
    'serving_size',
    'serving_unit',
    'household_serving',
    'gram_weight',
    'energy_kcal',
    'protein_g',
    'carbohydrate_g',
    'fat_g',
    'fiber_g',
    'fetched_at',
])]
class Food extends Model
{
    protected $table = 'foods';

    protected function casts(): array
    {
        return [
            'fdc_id' => 'integer',
            'serving_size' => 'decimal:2',
            'gram_weight' => 'decimal:2',
            'energy_kcal' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbohydrate_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
            'fetched_at' => 'datetime',
        ];
    }
}
