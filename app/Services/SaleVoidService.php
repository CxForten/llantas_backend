<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Anular una venta. Nunca se borra: se marca como anulada y, si se pide,
 * el stock vuelve al inventario con su movimiento de kardex.
 */
class SaleVoidService
{
    public function __construct(private StockService $stock) {}

    public function void(Sale $sale, string $reason, int $userId, bool $restoreStock = true): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $userId, $restoreStock) {

            if ($sale->status === 'anulada') {
                throw new RuntimeException('Esta venta ya fue anulada.');
            }

            // Si ya tiene factura autorizada, se necesita nota de crédito
            $doc = $sale->document()->first();

            if ($doc && $doc->status === 'autorizado') {
                throw new RuntimeException(
                    'Esta venta tiene una factura autorizada. Debes emitir una nota de crédito.'
                );
            }

            if ($restoreStock) {
                foreach ($sale->items()->whereNotNull('product_id')->get() as $item) {
                    $this->stock->register(
                        productId:     $item->product_id,
                        qty:           (int) $item->qty,
                        type:          'anulacion',
                        businessId:    $sale->business_id,
                        userId:        $userId,
                        referenceType: 'sale',
                        referenceId:   $sale->id,
                        reason:        "Anulación de venta #{$sale->number}: {$reason}",
                    );
                }
            }

            $sale->update([
                'status'          => 'anulada',
                'override_reason' => trim(
                    ($sale->override_reason ? $sale->override_reason . ' | ' : '')
                    . "ANULADA: {$reason}"
                ),
            ]);

            return $sale->fresh();
        });
    }
}