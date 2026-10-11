<?php

namespace App\Http\Resources;

use App\Models\WeightEntry;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WeightEntry */
class WeightResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $recorded = $this->recorded_on;

        return [
            'id' => $this->id,
            'recorded_on' => $recorded instanceof DateTimeInterface ? $recorded->format('Y-m-d') : (string) $recorded,
            'weight_kg' => (float) $this->weight_kg,
            'note' => $this->note,
        ];
    }
}
