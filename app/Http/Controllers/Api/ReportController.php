<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /** KPIs de la pantalla principal. Todo en centavos. */
    public function dashboard(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $today      = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();

        $todayTotals = $this->totals($businessId, $today->startOfDay(), $today->endOfDay());
        $monthTotals = $this->totals($businessId, $monthStart, $today->endOfDay());

        // Serie de los últimos 7 días: una sola consulta, agrupada en PHP
        // (evita funciones de fecha que cambian entre SQLite y PostgreSQL)
        $from = $today->subDays(6)->startOfDay();

        $recent = Sale::where('business_id', $businessId)
            ->where('status', 'completada')
            ->whereBetween('sold_at', [$from, $today->endOfDay()])
            ->get(['sold_at', 'total_cents', 'business_income_cents']);

        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->subDays($i);
            $ofDay = $recent->filter(
                fn ($s) => $s->sold_at->isSameDay($day)
            );

            $series[] = [
                'date'          => $day->toDateString(),
                'label'         => ucfirst($day->locale('es')->isoFormat('ddd')),
                'total_cents'   => (int) $ofDay->sum('total_cents'),
                'income_cents'  => (int) $ofDay->sum('business_income_cents'),
                'sales_count'   => $ofDay->count(),
            ];
        }

        $lowStock = Product::where('business_id', $businessId)
            ->where('active', true)
            ->lowStock()
            ->orderBy('stock')
            ->limit(10)
            ->get(['id', 'name', 'sku', 'spec', 'stock', 'min_stock']);

        return response()->json([
            'today'     => $todayTotals,
            'month'     => $monthTotals,
            'series'    => $series,
            'low_stock' => $lowStock,
        ]);
    }

    /** Reporte de ventas en un rango, con desglose por método de pago. */
    public function sales(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;
        [$from, $to] = $this->range($request);

        $totals = $this->totals($businessId, $from, $to);

        $byMethod = Sale::where('business_id', $businessId)
            ->where('status', 'completada')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as sales_count, SUM(total_cents) as total_cents')
            ->get()
            ->map(fn ($row) => [
                'method'      => $row->payment_method,
                'sales_count' => (int) $row->sales_count,
                'total_cents' => (int) $row->total_cents,
            ]);

        $byCategory = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.status', 'completada')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->groupBy('sale_items.category_name')
            ->selectRaw('sale_items.category_name, SUM(sale_items.qty) as qty, SUM(sale_items.line_total_cents) as total_cents')
            ->orderByDesc('total_cents')
            ->get()
            ->map(fn ($row) => [
                'category'    => $row->category_name ?? 'Sin categoría',
                'qty'         => (int) $row->qty,
                'total_cents' => (int) $row->total_cents,
            ]);

        return response()->json([
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'totals'      => $totals,
            'by_method'   => $byMethod,
            'by_category' => $byCategory,
        ]);
    }

    /** Productos más vendidos, sobre el snapshot de las líneas de venta. */
    public function topProducts(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_id', $request->user()->business_id)
            ->where('sales.status', 'completada')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->groupBy('sale_items.sku', 'sale_items.name')
            ->selectRaw('sale_items.sku, sale_items.name, SUM(sale_items.qty) as qty, SUM(sale_items.line_total_cents) as total_cents')
            ->orderByDesc('qty')
            ->limit((int) $request->query('limit', 10))
            ->get()
            ->map(fn ($row) => [
                'sku'         => $row->sku,
                'name'        => $row->name,
                'qty'         => (int) $row->qty,
                'total_cents' => (int) $row->total_cents,
            ]);

        return response()->json(['data' => $rows]);
    }

    /** Medidas de llanta más rotadas. */
    public function topSpecs(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_id', $request->user()->business_id)
            ->where('sales.status', 'completada')
            ->where('sale_items.category_name', 'Llantas')
            ->whereNotNull('sale_items.spec')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->groupBy('sale_items.spec')
            ->selectRaw('sale_items.spec, SUM(sale_items.qty) as qty')
            ->orderByDesc('qty')
            ->limit((int) $request->query('limit', 10))
            ->get()
            ->map(fn ($row) => [
                'spec' => $row->spec,
                'qty'  => (int) $row->qty,
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * Las tres cifras separadas, siempre juntas:
     * cobrado / ingreso del negocio / margen bruto.
     */
    private function totals(int $businessId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = Sale::where('business_id', $businessId)
            ->where('status', 'completada')
            ->whereBetween('sold_at', [$from, $to])
            ->selectRaw('
                COUNT(*)                     as sales_count,
                COALESCE(SUM(total_cents), 0)           as total_cents,
                COALESCE(SUM(business_income_cents), 0) as income_cents,
                COALESCE(SUM(gross_margin_cents), 0)    as margin_cents,
                COALESCE(SUM(card_fee_cents), 0)        as card_fee_cents,
                COALESCE(SUM(cost_total_cents), 0)      as cost_cents,
                COALESCE(SUM(discount_cents), 0)        as discount_cents
            ')
            ->first();

        return [
            'sales_count'    => (int) $row->sales_count,
            'total_cents'    => (int) $row->total_cents,     // lo que entró a caja
            'income_cents'   => (int) $row->income_cents,    // total menos recargo de tarjeta
            'margin_cents'   => (int) $row->margin_cents,    // ingreso menos costo
            'card_fee_cents' => (int) $row->card_fee_cents,
            'cost_cents'     => (int) $row->cost_cents,
            'discount_cents' => (int) $row->discount_cents,
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(Request $request): array
    {
        $from = $request->query('from')
            ? CarbonImmutable::parse($request->query('from'))->startOfDay()
            : CarbonImmutable::today()->subDays(29)->startOfDay();

        $to = $request->query('to')
            ? CarbonImmutable::parse($request->query('to'))->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        return [$from, $to];
    }
}
