<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $data       = $request->validated();
        $businessId = $request->user()->business_id;

        $product = Product::where('business_id', $businessId)
            ->findOrFail($data['product_id']);

        // 'entrada' suma, 'salida' resta, 'ajuste' fija el valor exacto
        $qty = match ($data['type']) {
            'entrada' => (int) $data['qty'],
            'salida'  => -1 * (int) $data['qty'],
            'ajuste'  => (int) $data['qty'],
        };

        $movement = $this->stock->register(
            productId:     $product->id,
            qty:           $qty,
            type:          $data['type'],
            businessId:    $businessId,
            userId:        $request->user()->id,
            referenceType: 'manual',
            reason:        $data['reason'],
        );

        return response()->json([
            'message'  => 'Inventario actualizado.',
            'movement' => new StockMovementResource($movement),
            'product'  => new ProductResource($product->fresh()->load('category')),
        ]);
    }

    /** Kardex general del negocio, con filtros. */
    public function movements(Request $request): AnonymousResourceCollection
    {
        $movements = \App\Models\StockMovement::with('product:id,name,sku', 'user:id,name')
            ->where('business_id', $request->user()->business_id)
            ->when($request->query('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('from'), fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->where('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 50));

        return StockMovementResource::collection($movements);
    }
}
