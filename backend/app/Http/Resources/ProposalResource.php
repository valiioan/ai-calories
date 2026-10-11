<?php

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Proposal */
class ProposalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kind = match ($this->kind) {
            'meal_create', 'meal_update' => 'meal',
            'meal_delete' => 'delete-meal',
            'activity' => 'activity',
            'weight' => 'weight',
            default => $this->kind,
        };

        return [
            'id' => $this->id,
            'kind' => $kind,
            'record_kind' => $this->kind,
            'status' => $this->status,
            'target_id' => $this->target_id,
            'payload' => $this->payload,
        ];
    }
}
