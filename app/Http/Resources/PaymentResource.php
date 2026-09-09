<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'method'         => $this->method,
            'amount_cents'   => (int) $this->amount_cents,
            'received_cents' => (int) $this->received_cents,
            'change_cents'   => (int) $this->change_cents,
            'fee_cents'      => (int) $this->fee_cents,
            'reference'      => $this->reference,
        ];
    }
}
