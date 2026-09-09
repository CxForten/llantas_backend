<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $canSeeCost = $request->user()?->hasPermission('products.cost') ?? false;

        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,

            // Snapshot: estos datos son los del momento de la venta
            'sku'           => $this->sku,
            'name'          => $this->name,
            'category_name' => $this->category_name,
            'spec'          => $this->spec,
            'brand'         => $this->brand,

            'qty'                 => (int) $this->qty,
            'unit_cost_cents'     => $this->when($canSeeCost, (int) $this->unit_cost_cents),
            'unit_price_cents'    => (int) $this->unit_price_cents,
            'line_discount_cents' => (int) $this->line_discount_cents,
            'iva_rate'            => (int) $this->iva_rate,
            'iva_cents'           => (int) $this->iva_cents,
            'line_total_cents'    => (int) $this->line_total_cents,
        ];
    }
}
