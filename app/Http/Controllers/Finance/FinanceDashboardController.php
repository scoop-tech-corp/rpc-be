<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceDashboardController extends Controller
{
    // Pasangan: tabel payment → tabel transaksi utama
    private const PAYMENT_PAIRS = [
        'Pet Clinic' => ['pt' => 'transaction_pet_clinic_payment_totals', 't' => 'transactionPetClinics'],
        'Pet Hotel'  => ['pt' => 'transaction_pet_hotel_payment_totals',  't' => 'transaction_pet_hotels'],
        'Pet Salon'  => ['pt' => 'transaction_pet_salon_payment_totals',  't' => 'transaction_pet_salons'],
        'Breeding'   => ['pt' => 'transaction_breeding_payment_totals',   't' => 'transaction_breedings'],
    ];

    public function index(Request $request)
    {
        $periods      = $this->resolvePeriods($request);
        $cs           = $periods['currentStart'];
        $ce           = $periods['currentEnd'];
        $ps           = $periods['prevStart'];
        $pe           = $periods['prevEnd'];
        $branchesId   = $request->filled('branchesId') ? $request->branchesId : null;

        $currRevenue = $this->sumRevenue($cs, $ce, $branchesId);
        $prevRevenue = $this->sumRevenue($ps, $pe, $branchesId);
        $currSales   = $this->countSales($cs, $ce, $branchesId);
        $prevSales   = $this->countSales($ps, $pe, $branchesId);

        $data = [
            'charts'              => $this->buildRevenueChart($cs, $ce, $ps, $pe, $branchesId),
            'numberSales'         => $this->buildAmountCard($currSales, $prevSales),
            'totalSalesValue'     => $this->buildAmountCard($currRevenue, $prevRevenue, true),
            'averageSalesValue'   => $this->buildAmountCard(
                $currSales > 0 ? $currRevenue / $currSales : 0,
                $prevSales > 0 ? $prevRevenue / $prevSales : 0,
                true
            ),
            'salesByItemType'     => $this->salesByItemType($cs, $ce, $branchesId),
            'salesByLocation'     => $this->salesByLocation($cs, $ce, $branchesId),
            'salesByReportingGroup' => $this->salesByReportingGroup($cs, $ce, $branchesId),
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

    // ── Builders for cards ─────────────────────────────────────────────────────

    /**
     * Builds { amount, isLoss, percentage } — Finance dashboard uses 'amount' key.
     */
    private function buildAmountCard(float $current, float $prev, bool $currency = false): array
    {
        $percentage = 0;
        if ($prev > 0) {
            $percentage = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $percentage = 100;
        }

        return [
            'amount'     => $currency
                ? number_format($current, 0, ',', '.')
                : (string) (int) $current,
            'percentage' => round(abs($percentage), 2),
            'isLoss'     => $current >= $prev ? 0 : 1,
        ];
    }

    // ── Revenue aggregation ────────────────────────────────────────────────────

    private function sumRevenue(Carbon $start, Carbon $end, ?array $branchesId): float
    {
        $total = 0;

        foreach (self::PAYMENT_PAIRS as $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0);
            if ($branchesId) $q->whereIn('t.locationId', $branchesId);
            $total += (float) $q->sum('pt.amountPaid');
        }

        // Pet Shop
        $psQ = DB::table('transactionpetshop')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0);
        if ($branchesId) $psQ->whereIn('locationId', $branchesId);
        $total += (float) $psQ->sum('totalPayment');

        return $total;
    }

    private function countSales(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        $total = 0;
        $serviceTablesWithStatus = array_column(self::PAYMENT_PAIRS, 't');

        foreach ($serviceTablesWithStatus as $table) {
            $q = DB::table($table)
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0)
                ->where('status', 'Selesai');
            if ($branchesId) $q->whereIn('locationId', $branchesId);
            $total += $q->count();
        }

        $psQ = DB::table('transactionpetshop')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->where('isPayed', 2);
        if ($branchesId) $psQ->whereIn('locationId', $branchesId);
        $total += $psQ->count();

        return $total;
    }

    // ── Charts ────────────────────────────────────────────────────────────────

    private function buildRevenueChart(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        $currData = $this->dailyRevenue($cs, $ce, $branchesId);
        $prevData = $this->dailyRevenue($ps, $pe, $branchesId);

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

    private function dailyRevenue(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $result = [];

        foreach (self::PAYMENT_PAIRS as $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->selectRaw("DATE(t.created_at) as trxDate, SUM(pt.amountPaid) as revenue")
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0)
                ->groupByRaw('DATE(t.created_at)');
            if ($branchesId) $q->whereIn('t.locationId', $branchesId);

            foreach ($q->get() as $row) {
                $result[$row->trxDate] = ($result[$row->trxDate] ?? 0) + (float) $row->revenue;
            }
        }

        $psQ = DB::table('transactionpetshop')
            ->selectRaw("DATE(created_at) as trxDate, SUM(totalPayment) as revenue")
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->groupByRaw('DATE(created_at)');
        if ($branchesId) $psQ->whereIn('locationId', $branchesId);
        foreach ($psQ->get() as $row) {
            $result[$row->trxDate] = ($result[$row->trxDate] ?? 0) + (float) $row->revenue;
        }

        return $result;
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

    // ── Pie charts ─────────────────────────────────────────────────────────────

    /**
     * Revenue by transaction source (Pet Clinic, Pet Hotel, Pet Salon, Breeding, Pet Shop).
     */
    private function salesByItemType(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $labels = [];
        $series = [];

        foreach (self::PAYMENT_PAIRS as $label => $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0);
            if ($branchesId) $q->whereIn('t.locationId', $branchesId);
            $labels[] = $label;
            $series[] = (int) $q->sum('pt.amountPaid');
        }

        // Pet Shop
        $psQ = DB::table('transactionpetshop')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0);
        if ($branchesId) $psQ->whereIn('locationId', $branchesId);
        $labels[] = 'Pet Shop';
        $series[] = (int) $psQ->sum('totalPayment');

        return compact('labels', 'series');
    }

    /**
     * Revenue by branch/location.
     */
    private function salesByLocation(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $locationRevenue = collect();

        foreach (self::PAYMENT_PAIRS as $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->select('t.locationId', DB::raw('SUM(pt.amountPaid) as revenue'))
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0)
                ->groupBy('t.locationId');
            if ($branchesId) $q->whereIn('t.locationId', $branchesId);

            foreach ($q->get() as $row) {
                $locationRevenue->push(['locationId' => $row->locationId, 'revenue' => (float) $row->revenue]);
            }
        }

        // Pet Shop
        $psQ = DB::table('transactionpetshop')
            ->select('locationId', DB::raw('SUM(totalPayment) as revenue'))
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->groupBy('locationId');
        if ($branchesId) $psQ->whereIn('locationId', $branchesId);
        foreach ($psQ->get() as $row) {
            $locationRevenue->push(['locationId' => $row->locationId, 'revenue' => (float) $row->revenue]);
        }

        $aggregated = $locationRevenue
            ->groupBy('locationId')
            ->map(fn($rows, $locId) => ['locationId' => $locId, 'total' => $rows->sum('revenue')])
            ->values();

        if ($aggregated->isEmpty()) {
            return ['labels' => [], 'series' => []];
        }

        $locationIds = $aggregated->pluck('locationId')->toArray();
        $locations   = DB::table('location')->whereIn('id', $locationIds)->pluck('locationName', 'id');

        $sorted = $aggregated->sortByDesc('total')->values();

        return [
            'labels' => $sorted->map(fn($r) => $locations[$r['locationId']] ?? 'Unknown')->values()->toArray(),
            'series' => $sorted->map(fn($r) => (int) $r['total'])->values()->toArray(),
        ];
    }

    /**
     * Revenue by customer group (reporting group).
     */
    private function salesByReportingGroup(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $groupRevenue = collect();

        // Service transactions: JOIN payment → transaction → customer → customerGroups
        foreach (self::PAYMENT_PAIRS as $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->join('customer as c', 'c.id', '=', 't.customerId')
                ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
                ->select(
                    DB::raw('COALESCE(cg.customerGroup, "Umum") as groupName'),
                    DB::raw('SUM(pt.amountPaid) as revenue')
                )
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0)
                ->groupBy(DB::raw('COALESCE(cg.customerGroup, "Umum")'));
            if ($branchesId) $q->whereIn('t.locationId', $branchesId);

            foreach ($q->get() as $row) {
                $groupRevenue->push(['groupName' => $row->groupName, 'revenue' => (float) $row->revenue]);
            }
        }

        // Pet Shop
        $psQ = DB::table('transactionpetshop as t')
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->select(
                DB::raw('COALESCE(cg.customerGroup, "Umum") as groupName'),
                DB::raw('SUM(t.totalPayment) as revenue')
            )
            ->whereBetween('t.created_at', [$start, $end])
            ->where('t.isDeleted', 0)
            ->groupBy(DB::raw('COALESCE(cg.customerGroup, "Umum")'));
        if ($branchesId) $psQ->whereIn('t.locationId', $branchesId);
        foreach ($psQ->get() as $row) {
            $groupRevenue->push(['groupName' => $row->groupName, 'revenue' => (float) $row->revenue]);
        }

        $aggregated = $groupRevenue
            ->groupBy('groupName')
            ->map(fn($rows, $name) => ['groupName' => $name, 'total' => $rows->sum('revenue')])
            ->sortByDesc('total')
            ->values();

        return [
            'labels' => $aggregated->pluck('groupName')->toArray(),
            'series' => $aggregated->map(fn($r) => (int) $r['total'])->toArray(),
        ];
    }
}
