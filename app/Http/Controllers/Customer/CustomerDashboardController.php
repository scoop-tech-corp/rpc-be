<?php

namespace App\Http\Controllers\Customer;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $periods      = $this->resolvePeriods($request);
        $currentStart = $periods['currentStart'];
        $currentEnd   = $periods['currentEnd'];
        $prevStart    = $periods['prevStart'];
        $prevEnd      = $periods['prevEnd'];

        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;

        $data = [
            'chartsCustomerGrowth' => $this->buildGrowthChart($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'chartsTotalCustomer'  => $this->buildTotalCustomerChart($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'newCustomer'          => $this->calcCardMetric($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'feedback'             => $this->calcFeedbackMetric($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'supportRequested'     => $this->calcSupportMetric($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
        ];

        return response()->json($data);
    }

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

    private function buildGrowthChart(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $categories  = $this->buildDateLabels($currentStart, $currentEnd);
        $currentData = $this->getNewCustomersByDay($currentStart, $currentEnd, $branchesId);
        $prevData    = $this->getNewCustomersByDay($prevStart, $prevEnd, $branchesId);

        $currentSeries = [];
        $prevSeries    = [];
        $currentDate   = $currentStart->copy()->startOfDay();
        $prevDate      = $prevStart->copy()->startOfDay();

        foreach ($categories as $_) {
            $currentSeries[] = (int) ($currentData[$currentDate->format('Y-m-d')]->total ?? 0);
            $prevSeries[]    = (int) ($prevData[$prevDate->format('Y-m-d')]->total ?? 0);
            $currentDate->addDay();
            $prevDate->addDay();
        }

        return [
            'series' => [
                ['name' => 'Previous', 'data' => $prevSeries],
                ['name' => 'Current',  'data' => $currentSeries],
            ],
            'categories' => $categories,
        ];
    }

    private function buildTotalCustomerChart(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $categories    = $this->buildDateLabels($currentStart, $currentEnd);
        $currentSeries = $this->buildCumulativeSeries($currentStart, $currentEnd, $branchesId);
        $prevSeries    = $this->buildCumulativeSeries($prevStart, $prevEnd, $branchesId);

        return [
            'series' => [
                ['name' => 'Previous', 'data' => $prevSeries],
                ['name' => 'Current',  'data' => $currentSeries],
            ],
            'categories' => $categories,
        ];
    }

    private function buildCumulativeSeries(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $baseline = DB::table('customer')
            ->where('created_at', '<', $start->copy()->startOfDay())
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $daily = DB::table('customer')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $result  = [];
        $running = $baseline;
        $current = $start->copy()->startOfDay();
        $endDay  = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            $running  += (int) ($daily[$current->format('Y-m-d')]->total ?? 0);
            $result[]  = $running;
            $current->addDay();
        }

        return $result;
    }

    private function calcCardMetric(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $current = DB::table('customer')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $prev = DB::table('customer')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $percentage = 0;
        if ($prev > 0) {
            $percentage = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $percentage = 100;
        }

        return [
            'total'      => (string) $current,
            'percentage' => (string) round($percentage, 2),
            'isLoss'     => $current >= $prev ? 0 : 1,
        ];
    }

    private function getNewCustomersByDay(Carbon $start, Carbon $end, ?array $branchesId): \Illuminate\Support\Collection
    {
        return DB::table('customer')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');
    }

    private function buildDateLabels(Carbon $start, Carbon $end): array
    {
        $labels  = [];
        $current = $start->copy()->startOfDay();
        $endDay  = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            $labels[] = $current->format('j M');
            $current->addDay();
        }

        return $labels;
    }

    /**
     * Jumlah feedback yang masuk dalam periode (current vs prev).
     */
    private function calcFeedbackMetric(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        $current = DB::table('customer_feedbacks')
            ->whereBetween('created_at', [$cs, $ce])
            ->where('isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $prev = DB::table('customer_feedbacks')
            ->whereBetween('created_at', [$ps, $pe])
            ->where('isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        return $this->buildTrendCard($current, $prev);
    }

    /**
     * Jumlah permintaan support dalam periode (current vs prev).
     */
    private function calcSupportMetric(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        $current = DB::table('customer_support_requests')
            ->whereBetween('created_at', [$cs, $ce])
            ->where('isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $prev = DB::table('customer_support_requests')
            ->whereBetween('created_at', [$ps, $pe])
            ->where('isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        return $this->buildTrendCard($current, $prev);
    }

    /**
     * Helper: bangun array { total, percentage, isLoss } dari dua nilai.
     */
    private function buildTrendCard(int $current, int $prev): array
    {
        $percentage = 0;
        if ($prev > 0) {
            $percentage = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $percentage = 100;
        }

        return [
            'total'      => (string) $current,
            'percentage' => (string) round(abs($percentage), 2),
            'isLoss'     => $current >= $prev ? 0 : 1,
        ];
    }
}
