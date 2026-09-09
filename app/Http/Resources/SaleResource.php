<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray($request): array
    {
        $canSeeCost = $request->user()?->hasPermission('products.cost') ?? false;

        return [
            'id'      => $this->id,
            'number'  => $this->number,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'status'  => $this->status,

            'customer_name'  => $this->customer_name,
            'customer_ident' => $this->customer_ident,
            'customer_email' => $this->customer_email,
            'doc_type'       => $this->doc_type,
            'payment_method' => $this->payment_method,
            'margin_pct'     => (int) $this->margin_pct,

            // Las tres cifras, siempre separadas
            'total_cents'           => (int) $this->total_cents,
            'business_income_cents' => (int) $this->business_income_cents,
            'gross_margin_cents'    => $this->when($canSeeCost, (int) $this->gross_margin_cents),

            'subtotal_cents'   => (int) $this->subtotal_cents,
            'discount_cents'   => (int) $this->discount_cents,
            'card_fee_cents'   => (int) $this->card_fee_cents,
            'iva_cents'        => (int) $this->iva_cents,
            'cost_total_cents' => $this->when($canSeeCost, (int) $this->cost_total_cents),

            'total_overridden' => (bool) $this->total_overridden,
            'override_reason'  => $this->override_reason,
            'notes'            => $this->notes,

            'cash_session_id' => $this->cash_session_id,
            'user'            => $this->whenLoaded('user', fn () => $this->user?->name),

            'items'    => SaleItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),

            'document' => $this->whenLoaded('document', fn () => $this->document ? [
                'id'          => $this->document->id,
                'full_number' => $this->document->full_number,
                'status'      => $this->document->status,
                'access_key'  => $this->document->access_key,
            ] : null),
        ];
    }
}
