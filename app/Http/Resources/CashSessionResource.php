<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,

            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'opened_by' => $this->whenLoaded('openedBy', fn () => $this->openedBy?->name),
            'closed_by' => $this->whenLoaded('closedBy', fn () => $this->closedBy?->name),

            'opening_cents'    => (int) $this->opening_cents,
            'expected_cents'   => (int) $this->expected_cents,
            'counted_cents'    => $this->counted_cents !== null ? (int) $this->counted_cents : null,
            'difference_cents' => $this->difference_cents !== null ? (int) $this->difference_cents : null,

            'notes' => $this->notes,
        ];
    }
}
