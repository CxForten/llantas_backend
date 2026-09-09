<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        // El costo solo se muestra a quien tenga el permiso.
        // Hoy el admin ve todo; cuando actives roles esto ya funciona solo.
        $canSeeCost = $request->user()?->hasPermission('products.cost') ?? false;

        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'brand'       => $this->brand,
            'spec'        => $this->spec,
            'description' => $this->description,

            'category_id'   => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),

            'cost_cents'      => $this->when($canSeeCost, (int) $this->cost_cents),
            'price_cents'     => (int) $this->price_cents,
            'price_alt_cents' => (int) $this->price_alt_cents,
            'margin_pct'      => (int) $this->margin_pct,

            'stock'       => (int) $this->stock,
            'min_stock'   => (int) $this->min_stock,
            'track_stock' => (bool) $this->track_stock,
            'is_low'      => $this->track_stock && $this->stock <= $this->min_stock,
            'is_out'      => $this->track_stock && $this->stock <= 0,

            'iva_rate' => (int) $this->iva_rate,
            'active'   => (bool) $this->active,

            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
