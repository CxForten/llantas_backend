<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

use RuntimeException;

class StockService
{
    public function register (
        int $productId,
        int $qty,
        string $type,
        int $businessId,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $reason = null,
    ): StockMovement {
        return DB::transaction(function() use(
            $productId, $qty, $type, $businessId, $userId, $referenceType, $referenceId, $reason
            ) {
            $product = Product::where('business_id', $businessId)
                ->lockForUpdate()
                ->findOrFail($productId);

            $before = $product->stock;
            $after = $type === 'ajuste' ? $qty : $before + $qty;

            if ($after < 0){
                throw new RuntimeException(
                    "El movimiento dejaría el stock de {$product->name} en negativo" 
                    . "(actual: {$before}, movimiento: {$qty})."
                );
            }
            
            $product->stock =  $after;
            $product->save();

            return StockMovement::create([
                'business_id'    => $businessId,
                'product_id'     => $product->id,
                'user_id'        => $userId,
                'type'           => $type,
                'qty'            => $type === 'ajuste' ? $after - $before : $qty,
                'stock_before'   => $before,
                'stock_after'    => $product->stock,
                'unit_cost_cents'=> $product->cost_cents,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'reason'         => $reason,
            ]);
        });
    }
}