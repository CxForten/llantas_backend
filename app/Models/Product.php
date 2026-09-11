<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'cost_cents' => 'integer',
            'price_cents' => 'integer',
            'price_alt_cents' => 'integer',
            'margin_pct' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'iva_rate' => 'integer',
            'track_stock' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function recalculatePrices(?int $main = null, ?int $alt = null): void
    {
        $main ??= $this->margin_pct ?: config('llantera.margin_main');
        $alt  ??= config('llantera.margin_alt');

        $this->price_cents     = intdiv($this->cost_cents * (100 + $main) + 50, 100);
        $this->price_alt_cents = intdiv($this->cost_cents * (100 + $alt) + 50, 100);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeLowStock($query){
    
        return $query->whereColumn('stock', '<=', 'min_stock')->where('track_stock', true);
    }


}
