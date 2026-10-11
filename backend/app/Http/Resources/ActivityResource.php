<?php

namespace App\Http\Resources;

use App\Models\ActivityEntry;
use App\Services\Journal\LocalDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityEntry */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $days = app(LocalDay::class);
        $user = $request->user() ?? $this->user;

        return [
            'id' => $this->id,
            'performed_at' => $this->performed_at?->toIso8601String(),
            'date' => $user ? $days->localDate($user, $this->performed_at) : null,
            'time' => $user ? $days->localTime($user, $this->performed_at) : null,
            'name' => $this->name,
            'duration_minutes' => $this->duration_minutes,
            'intensity' => $this->intensity,
            'energy_kcal' => (float) $this->energy_kcal,
            'note' => $this->note,
            'source' => $this->source,
        ];
    }
}
