<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
            'proposal_id' => $this->proposal_id,
            'revision_id' => $this->revision_id,
            'proposal' => $this->proposal ? (new ProposalResource($this->proposal))->resolve() : null,
        ];
    }
}
