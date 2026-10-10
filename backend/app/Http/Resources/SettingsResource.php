<?php

namespace App\Http\Resources;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserSetting */
class SettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'locale' => $this->locale,
            'appearance' => $this->appearance,
            'units' => $this->units,
            'notify_meals' => $this->notify_meals,
            'notify_weight' => $this->notify_weight,
            'notify_weekly' => $this->notify_weekly,
        ];
    }
}
