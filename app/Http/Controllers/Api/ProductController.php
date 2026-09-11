<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\StockService;
use App\Support\BusinessSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search     = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');

        $products = Product::with('category')
            ->where('business_id', $request->user()->business_id)
            ->when($request->boolean('only_active', true), fn ($q) => $q->where('active', true))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('spec', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 50));

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $data       = $request->validated();
        $stockInit  = (int) ($data['stock'] ?? 0);
        unset($data['stock']);

        $product = new Product([
            ...$data,
            'business_id' => $request->user()->business_id,
            'stock'       => 0,
        ]);

        // Si no mandan margen, usamos el de la configuración
        $product->margin_pct = $data['margin_pct']?? 0;

        $product->recalculatePrices(
            BusinessSettings::int($request->user()->business_id, 'margin_main', 25),
            BusinessSettings::int($request->user()->business_id, 'margin_alt', 20),
        );
        $product->save();

        // El stock inicial entra por el kardex, nunca directo a la columna
        if ($stockInit > 0) {
            $this->stock->register(
                productId:     $product->id,
                qty:           $stockInit,
                type:          'entrada',
                businessId:    $request->user()->business_id,
                userId:        $request->user()->id,
                referenceType: 'manual',
                reason:        'Stock inicial',
            );
            $product->refresh();
        }

        return new ProductResource($product->load('category'));
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->assertBelongsToBusiness($request, $product);

        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->assertBelongsToBusiness($request, $product);

        $data = $request->validated();

        // El stock NO se toca por aquí: solo por ajuste de inventario
        unset($data['stock']);

        $product->fill($data);

        // Si cambió el costo o el margen, recalculamos precios
        if ($product->isDirty(['cost_cents', 'margin_pct'])) {
            $product->recalculatePrices(
                BusinessSettings::int($request->user()->business_id, 'margin_main', 25),
                BusinessSettings::int($request->user()->business_id, 'margin_alt', 20),
            );
        }

        $product->save();

        return new ProductResource($product->load('category'));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->assertBelongsToBusiness($request, $product);

        // Soft delete: el historial de ventas sigue intacto
        $product->update(['active' => false]);
        $product->delete();

        return response()->json(['message' => 'Producto eliminado.']);
    }

    /** Kardex: historial de movimientos de un producto. */
    public function kardex(Request $request, Product $product): AnonymousResourceCollection
    {
        $this->assertBelongsToBusiness($request, $product);

        $movements = $product->movements()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 50));

        return StockMovementResource::collection($movements);
    }

    private function assertBelongsToBusiness(Request $request, Product $product): void
    {
        abort_if($product->business_id !== $request->user()->business_id, 404);
    }
}
