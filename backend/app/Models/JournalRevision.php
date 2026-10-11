<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'subject_type',
    'subject_id',
    'action',
    'before',
    'after',
    'undone_at',
])]
class JournalRevision extends Model
{
    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'before' => 'array',
            'after' => 'array',
            'undone_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
