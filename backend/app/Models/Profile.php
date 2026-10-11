<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'birth_date',
    'sex',
    'height_cm',
    'activity_level',
    'goal',
    'calorie_target',
    'protein_target_g',
    'carbohydrate_target_g',
    'fat_target_g',
    'target_weight_kg',
    'timezone',
])]
class Profile extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'height_cm' => 'decimal:2',
            'calorie_target' => 'integer',
            'protein_target_g' => 'decimal:1',
            'carbohydrate_target_g' => 'decimal:1',
            'fat_target_g' => 'decimal:1',
            'target_weight_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
