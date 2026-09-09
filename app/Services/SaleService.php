<?php

namespace App\Services;

use App\Models\Sale;
use App\Support\Money;
use App\Models\CashSession;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

use RuntimeException;

class SaleService
{
    public function __construct(private StockService $stock){}

    /** 
     * @param array $data{
     *  items: [{product_id, qty}],
     *  margin_pct, discount_cents, payment_method,
     *  doc_type, customer: {...},k
     *  received_cents, override_total_cents_?, override_reason?
     * }
     */

    public function create (array $data, int $businessId, int $userId): Sale
    {
        return DB::transaction (function() use ($data, $businessId, $userId){
            $session = CashSession::where('business_id', $businessId)
            ->where('status', 'open')
            ->first();

            if (! $session){
                throw new RuntimeException ('No hay una sesión de caja abierta');
            }

            $marginPct = $data['margin_pct'] ?? null;
            $lines = [];
            $subtotal = 0;
            $costTotal = 0;
            $ivaTotal = 0;

            foreach ($data['items'] as $row){
                $product = Product::where ('business_id', $businessId)
                    ->lockForUpdate()
                    ->findOrFail($row['product_id']);

                $qty = (int) $row['qty'];

                if ($product->track_stock && $product->stock < $qty){
                    throw new RuntimeException("Stock insuficiente de {$product->name}.");
                }

                $effectiveMargin = $margin_pct ?? $product->margin_pct ?: config ('llantera.margin_main'); 
                $unitPrice = Money::withMargin($product->cost_cents, $effectiveMargin);
                $lineTotal = $unitPrice * $qty;
                $lineCost = $product->cost_cents * $qty;

                $lineIva = (int) round(
                    $lineTotal - ($lineTotal / (1 + $product->iva_rate / 100))
                );

                $lines[] = [ 
                    'product_id'        => $product->id, 
                    'sku'               => $product->sku,
                    'name'              => $product->name,
                    'category_name'     => $product->category?->name,
                    'spec'              => $product->spec,
                    'brand'             => $product->brand,
                    'qty'               => $qty,
                    'unit_cost_cents'   => $product->cost_cents,
                    'unit_price_cents'  => $unitPrice,
                    'iva_rate'          => $product->iva_rate,
                    'iva_cents'         => $lineIva,
                    'line_total_cents'  => $lineTotal,
                ];

                $subtotal += $lineTotal;
                $costTotal += $lineCost;
                $ivaTotal += $lineIva;
            }

            $discount = min((int) ($data['discount_cents'] ?? 0), $subtotal);
            $base     = $subtotal - $discount;

            $method = $data['payment_method'] ?? 'efectivo';
            $cardFee = $method === 'tarjeta'
                ? Money::percent($base, config('llantera.card_fee_pct'))
                : 0;

            $total = $base + $cardFee;

            $overridden = false;
            $reason = null;
            if (! empty($data['override_total_cents'])){
                if (empty($data['override_reason'])){
                    throw new RuntimeException('Deves escribir el motivo del cambio de total.');
                }
                $total      = (int) $data['override_total_cents'];
                $overridden = true;
                $reason     = $data['override_reason'];
            }

            $income = $total - $cardFee;
            $margin = $income - $costTotal;

            $sale = Sale::create([
                'business_id'           => $businessId,
                'user_id'               => $userId,
                'cash_session_id'       => $session->id,
                'number'                => $this->nextNumber($businessId),
                'sold_at'               => now(),
                'customer_name'         => $data['customer']['name'] ?? 'Consumidor Final',
                'customer_ident'        => $data['customer']['ident']?? '9999999999',
                'customer_email'        => $data['customer']['email'] ?? null,
                'cost_total_cents'      => $costTotal,
                'subtotal_cents'        => $subtotal,
                'discount_cents'        => $discount,
                'card_fee_cents'        => $cardFee,
                'iva_cents'             => $ivaTotal,
                'total_cents'           => $total,
                'business_income_cents' => $income,
                'gross_margin_cents'    => $margin,
                'margin_pct'            => $marginPct,
                'payment_method'        => $method,
                'doc_type'              => $data['doc_type'] ?? 'consumidor_final',
                'status'                => 'completada',
                'total_overridden'      => $overridden,
                'override_reason'       => $reason,
            ]);

            $sale->items()->createMany($lines);

            $received = (int) ($data['received_cents'] ?? $total);
            
            if($method === 'efectivo' && $received < $total){
                throw new RuntimeException ('El monto recibido es menor al total de la venta');
            }
            
            $sale->payments()->create([
                'method'            => $method,
                'amount_cents'      => $total,
                'received_cents'    => $received,
                'change_cents'      => $received - $total,
                'fee_cents'         => $cardFee,
            ]);

            foreach ($lines as $line) {
                $this->stock->register (
                    productId: $line['product_id'],
                    qty: -$line['qty'],
                    type: 'venta',
                    businessId: $businessId,
                    userId: $userId,
                    referenceType: 'sale',
                    referenceId: $sale->id,
                    reason: "Venta #{$sale->number}",
                );
            }

            return $sale->load('items', 'payments');
        });
    }

    private function nextNumber(int $businessId): string
    {
        $last = Sale::where('business_id', $businessId)
            ->orderByDesc('id')
            ->value('number');

        return str_pad((string) (((int) $last) + 1), 6, '0', STR_PAD_LEFT);
    }
}
