<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductDashboardController extends Controller
{
    public function index(Request $request)
    {
        $periods    = $this->resolvePeriods($request);
        $cs         = $periods['currentStart'];
        $ce         = $periods['currentEnd'];
        $ps         = $periods['prevStart'];
        $pe         = $periods['prevEnd'];
        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;

        $data = [
            'charts'          => $this->buildSalesChart($cs, $ce, $ps, $pe, $branchesId),
            'productSold'     => $this->productSold($cs, $ce, $ps, $pe, $branchesId),
            'productSoldQty'  => $this->productSoldQty($cs, $ce, $ps, $pe, $branchesId),
            'productSoldValue'=> $this->productSoldValue($cs, $ce, $ps, $pe, $branchesId),
            'topSeller'       => $this->topSeller($cs, $ce, $branchesId),
            'salesByCategory' => $this->salesByCategory($cs, $ce, $branchesId),
        ];

        return response()->json($data);
    }

    // ── Period resolver ────────────────────────────────────────────────────────

    private function resolvePeriods(Request $request): array
    {
        $now = Carbon::now();

        if ($request->dateRange === 'dateRange') {
            $currentStart = Carbon::parse($request->dateFrom)->startOfDay();
            $currentEnd   = Carbon::parse($request->dateTo)->endOfDay();
            $days         = $currentStart->diffInDays($currentEnd);
            $prevEnd      = $currentStart->copy()->subDay()->endOfDay();
            $prevStart    = $prevEnd->copy()->subDays($days)->startOfDay();
        } else {
            $year           = $request->year  ?? $now->year;
            $month          = $request->month ?? $now->month;
            $isCurrentMonth = ($year == $now->year && $month == $now->month);
            $firstOfMonth   = Carbon::createFromDate($year, $month, 1);
            $currentStart   = $firstOfMonth->copy()->startOfMonth();
            $currentEnd     = $isCurrentMonth ? $now->copy() : $firstOfMonth->copy()->endOfMonth();
            $prevStart      = $firstOfMonth->copy()->subMonth()->startOfMonth();
            $prevEnd        = $isCurrentMonth
                ? $now->copy()->subMonth()
                : $firstOfMonth->copy()->subMonth()->endOfMonth();
        }

        return compact('currentStart', 'currentEnd', 'prevStart', 'prevEnd');
    }

    // ── Card metrics ───────────────────────────────────────────────────────────

    private function buildCardMetric(float $current, float $prev, bool $currency = false): array
    {
        $percentage = 0;
        if ($prev > 0) {
            $percentage = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $percentage = 100;
        }

        return [
            'total'      => $currency
                ? number_format($current, 0, ',', '.')
                : (string) (int) $current,
            'percentage' => (string) round(abs($percentage), 2),
            'isLoss'     => $current >= $prev ? 0 : 1,
        ];
    }

    /**
     * Jumlah produk unik yang terjual dari petshop.
     */
    private function productSold(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->countDistinctProducts($cs, $ce, $branchesId),
            $this->countDistinctProducts($ps, $pe, $branchesId)
        );
    }

    private function countDistinctProducts(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        return DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->distinct('tpd.productId')
            ->count('tpd.productId');
    }

    /**
     * Total qty produk yang terjual.
     */
    private function productSoldQty(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->sumQty($cs, $ce, $branchesId),
            $this->sumQty($ps, $pe, $branchesId)
        );
    }

    private function sumQty(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        return (int) DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->sum('tpd.quantity');
    }

    /**
     * Total nilai penjualan produk (total_final_price jika ada, else price * quantity).
     */
    private function productSoldValue(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->sumValue($cs, $ce, $branchesId),
            $this->sumValue($ps, $pe, $branchesId),
            true
        );
    }

    private function sumValue(Carbon $start, Carbon $end, ?array $branchesId): float
    {
        return (float) DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->sum(DB::raw('COALESCE(tpd.total_final_price, tpd.price * tpd.quantity)'));
    }

    // ── Top seller ─────────────────────────────────────────────────────────────

    private function topSeller(Carbon $start, Carbon $end, ?array $branchesId)
    {
        return DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->join('products as p', 'p.id', '=', 'tpd.productId')
            ->leftJoin('location as l', 'l.id', '=', 'tp.locationId')
            ->select(
                'p.id as productId',
                'p.fullName as productName',
                'p.category as productType',
                'l.locationName',
                DB::raw('SUM(tpd.quantity) as total')
            )
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->where('p.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->groupBy('p.id', 'p.fullName', 'p.category', 'l.locationName')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    // ── Pie chart: by category ─────────────────────────────────────────────────

    private function salesByCategory(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $rows = DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->join('products as p', 'p.id', '=', 'tpd.productId')
            ->select(
                'p.category as category',
                DB::raw('SUM(COALESCE(tpd.total_final_price, tpd.price * tpd.quantity)) as revenue')
            )
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->where('p.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->groupBy('p.category')
            ->orderByDesc('revenue')
            ->get();

        return [
            'labels' => $rows->pluck('category')->toArray(),
            'series' => $rows->map(fn($r) => (int) $r->revenue)->toArray(),
        ];
    }

    // ── Column chart: daily quantity sold ─────────────────────────────────────

    private function buildSalesChart(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        $currData = $this->dailySalesQty($cs, $ce, $branchesId);
        $prevData = $this->dailySalesQty($ps, $pe, $branchesId);

        $days       = (int) $cs->diffInDays($ce) + 1;
        $categories = collect(range(0, $days - 1))
            ->map(fn($d) => $cs->copy()->addDays($d)->format('j M'))
            ->toArray();

        return [
            'series' => [
                ['name' => 'Previous', 'data' => $this->fillDailySeries($ps, $pe, $prevData)],
                ['name' => 'Current',  'data' => $this->fillDailySeries($cs, $ce, $currData)],
            ],
            'categories' => $categories,
        ];
    }

    private function dailySalesQty(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $rows = DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->selectRaw('DATE(tp.created_at) as trxDate, SUM(tpd.quantity) as qty')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->groupByRaw('DATE(tp.created_at)')
            ->get();

        return $rows->pluck('qty', 'trxDate')->toArray();
    }

    private function fillDailySeries(Carbon $start, Carbon $end, array $daily): array
    {
        $series = [];
        $days   = (int) $start->diffInDays($end) + 1;
        for ($i = 0; $i < $days; $i++) {
            $key      = $start->copy()->addDays($i)->format('Y-m-d');
            $series[] = (int) ($daily[$key] ?? 0);
        }
        return $series;
    }
}
