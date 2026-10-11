<?php

namespace App\Http\Resources;

use App\Models\Profile;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Profile */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'birth_date' => $this->birth_date instanceof DateTimeInterface
                ? $this->birth_date->format('Y-m-d')
                : $this->birth_date,
            'sex' => $this->sex,
            'height_cm' => $this->decimal($this->height_cm),
            'activity_level' => $this->activity_level,
            'goal' => $this->goal,
            'calorie_target' => $this->calorie_target,
            'protein_target_g' => $this->decimal($this->protein_target_g),
            'carbohydrate_target_g' => $this->decimal($this->carbohydrate_target_g),
            'fat_target_g' => $this->decimal($this->fat_target_g),
            'target_weight_kg' => $this->decimal($this->target_weight_kg),
            'timezone' => $this->timezone,
        ];
    }

    private function decimal(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
