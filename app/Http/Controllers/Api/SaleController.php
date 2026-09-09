<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\VoidSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\SaleVoidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $sales = Sale::with('items', 'payments', 'user:id,name')
            ->where('business_id', $request->user()->business_id)
            ->when($request->query('method'), fn ($q, $m) => $q->where('payment_method', $m))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('cash_session_id'), fn ($q, $id) => $q->where('cash_session_id', $id))
            ->when($request->query('from'), fn ($q, $from) => $q->where('sold_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->where('sold_at', '<=', $to))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_ident', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 25));

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request, SaleService $service): JsonResponse
    {
        try {
            $sale = $service->create(
                $request->validated(),
                $request->user()->business_id,
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            // Stock insuficiente, caja cerrada, motivo de override faltante
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new SaleResource($sale))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Sale $sale): SaleResource
    {
        $this->assertBelongsToBusiness($request, $sale);

        return new SaleResource(
            $sale->load('items', 'payments', 'user:id,name', 'document')
        );
    }

    public function void(VoidSaleRequest $request, Sale $sale, SaleVoidService $service): JsonResponse
    {
        $this->assertBelongsToBusiness($request, $sale);

        try {
            $sale = $service->void(
                $sale,
                $request->validated('reason'),
                $request->user()->id,
                $request->boolean('restore_stock', true),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Venta #{$sale->number} anulada.",
            'sale'    => new SaleResource($sale->load('items', 'payments')),
        ]);
    }

    private function assertBelongsToBusiness(Request $request, Sale $sale): void
    {
        abort_if($sale->business_id !== $request->user()->business_id, 404);
    }
}
