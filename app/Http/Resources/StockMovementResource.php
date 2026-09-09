<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'qty'             => (int) $this->qty,
            'stock_before'    => (int) $this->stock_before,
            'stock_after'     => (int) $this->stock_after,
            'unit_cost_cents' => (int) $this->unit_cost_cents,
            'reason'          => $this->reason,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'user'            => $this->whenLoaded('user', fn () => $this->user?->name),
            'product'         => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'sku'  => $this->product->sku,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
