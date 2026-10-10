<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'locale',
    'appearance',
    'units',
    'notify_meals',
    'notify_weight',
    'notify_weekly',
])]
class UserSetting extends Model
{
    public const DEFAULTS = [
        'locale' => 'ro',
        'appearance' => 'light',
        'units' => 'metric',
        'notify_meals' => true,
        'notify_weight' => false,
        'notify_weekly' => true,
    ];

    protected function casts(): array
    {
        return [
            'notify_meals' => 'boolean',
            'notify_weight' => 'boolean',
            'notify_weekly' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
