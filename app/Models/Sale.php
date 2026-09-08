<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return[
            'sold_at' => 'datetime',
            'cost_total_cents' => 'integer',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'card_fee_cents' => 'integer',
            'iva_cents' => 'integer',
            'total_cents' => 'integer',
            'business_income_cents' => 'integer',
            'gross_margin_cents' => 'integer',
            'total_overridden' => 'boolean',
        ];
    }

    public function items(): HasMany {return $this->hasMany(SaleItem::class);}
    public function payments(): HasMany {return $this->hasMany(Payment::class);}
    public function document() {return $this->hasOne(Document::class);}
    public function user(): BelongsTo {return $this->belongsTo(User::class);}
    public function cashSession(): BelongsTo {return $this->belongsTo(CashSession::class);}


}
