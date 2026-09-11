<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{
    Business, Category, Product, StockMovement, Customer,
    CashSession, Sale, SaleItem, Payment, Document, Setting
};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BackupController extends Controller
{
    /** Orden en que se exportan e importan: las dependencias primero. */
    private const TABLES = [
        'categories'      => Category::class,
        'products'        => Product::class,
        'customers'       => Customer::class,
        'cash_sessions'   => CashSession::class,
        'sales'           => Sale::class,
        'sale_items'      => SaleItem::class,
        'payments'        => Payment::class,
        'stock_movements' => StockMovement::class,
        'documents'       => Document::class,
        'settings'        => Setting::class,
    ];

    /** Descarga todo el negocio en un JSON. */
    public function export(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $payload = [
            'version'     => 1,
            'exported_at' => now()->toIso8601String(),
            'business'    => Business::findOrFail($businessId)->toArray(),
            'tables'      => [],
        ];

        foreach (self::TABLES as $name => $model) {
            $query = $model::query();

            // Las tablas hijas no tienen business_id: se filtran por su padre
            if (in_array($name, ['sale_items', 'payments'], true)) {
                $saleIds = Sale::where('business_id', $businessId)->pluck('id');
                $query->whereIn('sale_id', $saleIds);
            } else {
                $query->where('business_id', $businessId);
            }

            if ($name === 'products') {
                $query->withTrashed();
            }

            $payload['tables'][$name] = $query->get()->toArray();
        }

        return response()->json($payload);
    }

    /**
     * Reemplaza TODOS los datos del negocio por los del archivo.
     * Exige la palabra REEMPLAZAR para que no se dispare por accidente.
     */
    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirm' => ['required', 'in:REEMPLAZAR'],
            'payload' => ['required', 'array'],
            'payload.tables' => ['required', 'array'],
        ], [
            'confirm.in' => 'Escribe REEMPLAZAR para confirmar.',
        ]);

        $businessId = $request->user()->business_id;
        $tables     = $data['payload']['tables'];

        DB::transaction(function () use ($businessId, $tables) {
            $this->wipe($businessId, 'todo');

            foreach (self::TABLES as $name => $model) {
                foreach ($tables[$name] ?? [] as $row) {
                    // Nunca dejamos que el archivo escoja a qué negocio pertenece
                    if (array_key_exists('business_id', $row)) {
                        $row['business_id'] = $businessId;
                    }

                    $model::withoutEvents(fn () => $model::insert($row));
                }
            }
        });

        \App\Support\BusinessSettings::forget($businessId);

        return response()->json(['message' => 'Datos restaurados desde el respaldo.']);
    }

    /**
     * Borra datos. scope 'ventas' deja el catálogo; 'todo' lo borra también.
     */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirm' => ['required', 'in:BORRAR'],
            'scope'   => ['required', 'in:ventas,todo'],
        ], [
            'confirm.in' => 'Escribe BORRAR para confirmar.',
        ]);

        $businessId = $request->user()->business_id;

        DB::transaction(fn () => $this->wipe($businessId, $data['scope']));

        return response()->json([
            'message' => $data['scope'] === 'todo'
                ? 'Se borraron las ventas y el catálogo.'
                : 'Se borraron las ventas, el kardex y las cajas. El catálogo quedó intacto.',
        ]);
    }

    private function wipe(int $businessId, string $scope): void
    {
        $saleIds = Sale::where('business_id', $businessId)->pluck('id');

        Payment::whereIn('sale_id', $saleIds)->delete();
        SaleItem::whereIn('sale_id', $saleIds)->delete();
        Document::where('business_id', $businessId)->delete();
        Sale::where('business_id', $businessId)->delete();
        StockMovement::where('business_id', $businessId)->delete();
        CashSession::where('business_id', $businessId)->delete();

        if ($scope === 'todo') {
            Product::withTrashed()->where('business_id', $businessId)->forceDelete();
            Category::where('business_id', $businessId)->delete();
            Customer::where('business_id', $businessId)->delete();
        } else {
            // El catálogo se queda, pero el stock vuelve a cero:
            // si borras el kardex, dejar stock sería mentir.
            Product::where('business_id', $businessId)->update(['stock' => 0]);
        }
    }
}
