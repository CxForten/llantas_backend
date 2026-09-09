<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'qty'                 => 'integer',
            'unit_cost_cents'     => 'integer',
            'unit_price_cents'    => 'integer',
            'line_discount_cents' => 'integer',
            'iva_rate'            => 'integer',
            'iva_cents'           => 'integer',
            'line_total_cents'    => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
