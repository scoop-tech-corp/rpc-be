<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionDashboardController extends Controller
{
    // Mapping integer type promotionMasters → label
    private const PROMO_TYPE_LABELS = [
        1 => 'Discount',
        2 => 'Free Item',
        3 => 'Bundle',
        4 => 'Based Sales',
    ];

    public function index(Request $request)
    {
        $periods    = $this->resolvePeriods($request);
        $cs         = $periods['currentStart'];
        $ce         = $periods['currentEnd'];
        $ps         = $periods['prevStart'];
        $pe         = $periods['prevEnd'];
        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;

        $data = [
            'charts'               => $this->buildPromoChart($cs, $ce, $ps, $pe, $branchesId),
            'promotions'           => $this->activePromosStat($cs, $ce, $ps, $pe),
            'promotionsQty'        => $this->promoUsageStat($cs, $ce, $ps, $pe, $branchesId),
            'promotionsValue'      => $this->promoDiscountValueStat($cs, $ce, $ps, $pe, $branchesId),
            'mostPopular'          => $this->mostPopular($cs, $ce, $branchesId),
            'promotionsByCategory' => $this->promotionsByCategory($cs, $ce, $branchesId),
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

    // ── Card builders ──────────────────────────────────────────────────────────

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
     * Jumlah promosi yang aktif (startDate <= periodEnd AND endDate >= periodStart).
     */
    private function activePromosStat(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe): array
    {
        return $this->buildCardMetric(
            $this->countActivePromos($cs, $ce),
            $this->countActivePromos($ps, $pe)
        );
    }

    private function countActivePromos(Carbon $start, Carbon $end): int
    {
        return DB::table('promotionMasters')
            ->where('isDeleted', 0)
            ->where('status', 1)
            ->where('startDate', '<=', $end)
            ->where('endDate', '>=', $start)
            ->count();
    }

    /**
     * Jumlah pemakaian promo dari petshop detail (baris dengan promoId).
     */
    private function promoUsageStat(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->countPromoUsage($cs, $ce, $branchesId),
            $this->countPromoUsage($ps, $pe, $branchesId)
        );
    }

    private function countPromoUsage(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        return DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->whereNotNull('tpd.promoId')
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->count();
    }

    /**
     * Total nilai diskon yang diberikan dari pemakaian promo di petshop.
     * Discount = SUM(price * quantity - COALESCE(total_final_price, price*quantity))
     */
    private function promoDiscountValueStat(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->sumDiscountValue($cs, $ce, $branchesId),
            $this->sumDiscountValue($ps, $pe, $branchesId),
            true
        );
    }

    private function sumDiscountValue(Carbon $start, Carbon $end, ?array $branchesId): float
    {
        $result = DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->selectRaw('SUM(tpd.price * tpd.quantity - COALESCE(tpd.total_final_price, tpd.price * tpd.quantity)) as discount')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->whereNotNull('tpd.promoId')
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->value('discount');

        return max(0, (float) $result);
    }

    // ── Most popular promotions ────────────────────────────────────────────────

    private function mostPopular(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        return DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->join('promotionMasters as pm', 'pm.id', '=', 'tpd.promoId')
            ->select('pm.name as promotionName', DB::raw('COUNT(*) as promotions'))
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->whereNotNull('tpd.promoId')
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->groupBy('pm.id', 'pm.name')
            ->orderByDesc('promotions')
            ->limit(10)
            ->get()
            ->toArray();
    }

    // ── Pie chart: by promotion type ───────────────────────────────────────────

    private function promotionsByCategory(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $rows = DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->join('promotionMasters as pm', 'pm.id', '=', 'tpd.promoId')
            ->select('pm.type', DB::raw('COUNT(*) as total'))
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->whereNotNull('tpd.promoId')
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->groupBy('pm.type')
            ->orderByDesc('total')
            ->get();

        $typeLabels = self::PROMO_TYPE_LABELS;

        return [
            'labels' => $rows->map(fn($r) => $typeLabels[$r->type] ?? "Type {$r->type}")->toArray(),
            'series' => $rows->map(fn($r) => (int) $r->total)->toArray(),
        ];
    }

    // ── Column chart: daily promo usage ───────────────────────────────────────

    private function buildPromoChart(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        $currData = $this->dailyPromoUsage($cs, $ce, $branchesId);
        $prevData = $this->dailyPromoUsage($ps, $pe, $branchesId);

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

    private function dailyPromoUsage(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        return DB::table('transactionpetshopdetail as tpd')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'tpd.transactionpetshopId')
            ->selectRaw('DATE(tp.created_at) as trxDate, COUNT(*) as total')
            ->whereBetween('tp.created_at', [$start, $end])
            ->where('tp.isDeleted', 0)
            ->where('tpd.isDeleted', 0)
            ->whereNotNull('tpd.promoId')
            ->when($branchesId, fn($q) => $q->whereIn('tp.locationId', $branchesId))
            ->groupByRaw('DATE(tp.created_at)')
            ->get()
            ->pluck('total', 'trxDate')
            ->toArray();
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
