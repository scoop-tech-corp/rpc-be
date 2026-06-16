<?php

namespace App\Http\Controllers\Staff;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StaffDashboardController extends Controller
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
            'totalStaff'              => $this->calcTotalStaff($currentEnd, $prevEnd, $branchesId),
            'staffBaru'               => $this->calcStaffBaru($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'tingkatKehadiran'        => $this->calcTingkatKehadiran($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'rataRataJamKerja'        => $this->calcRataRataJamKerja($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'chartsKehadiran'         => $this->buildKehadiranChart($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'chartsPertumbuhanStaff'  => $this->buildPertumbuhanChart($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId),
            'chartsCabang'            => $this->buildCabangChart($branchesId),
            'chartsPerforma'          => $this->buildPerformaChart($currentStart, $currentEnd, $branchesId),
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

    // ── Analytic cards ─────────────────────────────────────────────────────────

    private function calcTotalStaff(Carbon $currentEnd, Carbon $prevEnd, ?array $branchesId): array
    {
        $current = $this->countActiveStaff($currentEnd, $branchesId);
        $prev    = $this->countActiveStaff($prevEnd, $branchesId);

        return $this->buildCardMetric($current, $prev);
    }

    private function countActiveStaff(Carbon $asOf, ?array $branchesId): int
    {
        return DB::table('users as u')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('u.isDeleted', 0)
            ->where('u.status', 1)
            ->where('u.created_at', '<=', $asOf)
            ->count('u.id');
    }

    private function calcStaffBaru(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $current = $this->countNewStaff($currentStart, $currentEnd, $branchesId);
        $prev    = $this->countNewStaff($prevStart, $prevEnd, $branchesId);

        return $this->buildCardMetric($current, $prev);
    }

    private function countNewStaff(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        return DB::table('users as u')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('u.isDeleted', 0)
            ->whereBetween('u.created_at', [$start, $end])
            ->count('u.id');
    }

    private function calcTingkatKehadiran(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $currentRate = $this->getAttendanceRate($currentStart, $currentEnd, $branchesId);
        $prevRate    = $this->getAttendanceRate($prevStart, $prevEnd, $branchesId);

        $percentage = 0;
        if ($prevRate > 0) {
            $percentage = (($currentRate - $prevRate) / $prevRate) * 100;
        } elseif ($currentRate > 0) {
            $percentage = 100;
        }

        return [
            'total'      => (string) round($currentRate, 2),
            'percentage' => (string) round(abs($percentage), 2),
            'isLoss'     => $currentRate >= $prevRate ? 0 : 1,
        ];
    }

    private function getAttendanceRate(Carbon $start, Carbon $end, ?array $branchesId): float
    {
        $totalStaff = $this->countActiveStaff($end, $branchesId);
        if ($totalStaff === 0) {
            return 0;
        }

        $hadirCount = DB::table('staffAbsents as sa')
            ->join('users as u', 'u.id', 'sa.userId')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('sa.isDeleted', 0)
            ->where('u.isDeleted', 0)
            ->where('sa.statusPresent', 1)
            ->whereBetween('sa.presentTime', [$start, $end])
            ->distinct('sa.userId')
            ->count('sa.userId');

        return ($hadirCount / $totalStaff) * 100;
    }

    private function calcRataRataJamKerja(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $current = $this->getAvgWorkingHours($currentStart, $currentEnd, $branchesId);
        $prev    = $this->getAvgWorkingHours($prevStart, $prevEnd, $branchesId);

        $percentage = 0;
        if ($prev > 0) {
            $percentage = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $percentage = 100;
        }

        return [
            'total'      => (string) round($current, 2),
            'percentage' => (string) round(abs($percentage), 2),
            'isLoss'     => $current >= $prev ? 0 : 1,
        ];
    }

    private function getAvgWorkingHours(Carbon $start, Carbon $end, ?array $branchesId): float
    {
        $result = DB::table('staffAbsents as sa')
            ->join('users as u', 'u.id', 'sa.userId')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('sa.isDeleted', 0)
            ->where('u.isDeleted', 0)
            ->where('sa.statusPresent', 1)
            ->whereNotNull('sa.duration')
            ->whereBetween('sa.presentTime', [$start, $end])
            ->selectRaw('AVG(TIME_TO_SEC(sa.duration)) as avg_seconds')
            ->value('avg_seconds');

        return $result ? round($result / 3600, 2) : 0;
    }

    // ── Charts ─────────────────────────────────────────────────────────────────

    private function buildKehadiranChart(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $categories  = $this->buildDateLabels($currentStart, $currentEnd);
        $currentData = $this->getAttendanceByDay($currentStart, $currentEnd, $branchesId);
        $prevData    = $this->getAttendanceByDay($prevStart, $prevEnd, $branchesId);

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

    private function getAttendanceByDay(Carbon $start, Carbon $end, ?array $branchesId): \Illuminate\Support\Collection
    {
        return DB::table('staffAbsents as sa')
            ->join('users as u', 'u.id', 'sa.userId')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('sa.isDeleted', 0)
            ->where('u.isDeleted', 0)
            ->where('sa.statusPresent', 1)
            ->whereBetween('sa.presentTime', [$start, $end])
            ->select(DB::raw('DATE(sa.presentTime) as date'), DB::raw('COUNT(DISTINCT sa.userId) as total'))
            ->groupBy(DB::raw('DATE(sa.presentTime)'))
            ->get()
            ->keyBy('date');
    }

    private function buildPertumbuhanChart(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId): array
    {
        $categories    = $this->buildDateLabels($currentStart, $currentEnd);
        $currentSeries = $this->buildStaffCumulativeSeries($currentStart, $currentEnd, $branchesId);
        $prevSeries    = $this->buildStaffCumulativeSeries($prevStart, $prevEnd, $branchesId);

        return [
            'series' => [
                ['name' => 'Previous', 'data' => $prevSeries],
                ['name' => 'Current',  'data' => $currentSeries],
            ],
            'categories' => $categories,
        ];
    }

    private function buildStaffCumulativeSeries(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $baseline = DB::table('users as u')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('u.isDeleted', 0)
            ->where('u.created_at', '<', $start->copy()->startOfDay())
            ->count('u.id');

        $daily = DB::table('users as u')
            ->when($branchesId, fn($q) => $q
                ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                ->whereIn('ul.locationId', $branchesId)
                ->where('ul.isDeleted', 0)
            )
            ->where('u.isDeleted', 0)
            ->whereBetween('u.created_at', [$start, $end])
            ->select(DB::raw('DATE(u.created_at) as date'), DB::raw('COUNT(u.id) as total'))
            ->groupBy(DB::raw('DATE(u.created_at)'))
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

    private function buildCabangChart(?array $branchesId): array
    {
        $data = DB::table('usersLocation as ul')
            ->join('location as l', 'l.id', 'ul.locationId')
            ->join('users as u', 'u.id', 'ul.usersId')
            ->where('ul.isDeleted', 0)
            ->where('u.isDeleted', 0)
            ->where('u.status', 1)
            ->when($branchesId, fn($q) => $q->whereIn('ul.locationId', $branchesId))
            ->select('l.locationName', DB::raw('COUNT(DISTINCT ul.usersId) as total'))
            ->groupBy('l.id', 'l.locationName')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $data->pluck('locationName')->toArray(),
            'series' => $data->pluck('total')->map(fn($v) => (int) $v)->toArray(),
        ];
    }

    private function buildPerformaChart(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $transactions = $this->countTransactionsByDoctor($start, $end, $branchesId);

        $top = $transactions->sortByDesc('total')->take(10)->values();

        return [
            'series' => [
                ['name' => 'Transaksi', 'data' => $top->pluck('total')->map(fn($v) => (int) $v)->toArray()],
            ],
            'categories' => $top->pluck('staffName')->toArray(),
        ];
    }

    private function countTransactionsByDoctor(Carbon $start, Carbon $end, ?array $branchesId): \Illuminate\Support\Collection
    {
        $tables = [
            ['table' => 'transactionPetClinics', 'location' => 'locationId'],
            ['table' => 'transaction_pet_hotels', 'location' => 'locationId'],
            ['table' => 'transaction_pet_salons', 'location' => 'locationId'],
            ['table' => 'transaction_breedings',  'location' => 'locationId'],
        ];

        $query = null;
        foreach ($tables as $i => $t) {
            $sub = DB::table($t['table'])
                ->select('doctorId', DB::raw('COUNT(*) as total'))
                ->where('isDeleted', 0)
                ->whereBetween('created_at', [$start, $end])
                ->when($branchesId, fn($q) => $q->whereIn($t['location'], $branchesId))
                ->groupBy('doctorId');

            $query = $i === 0 ? $sub : $query->unionAll($sub);
        }

        return DB::table(DB::raw("({$query->toSql()}) as all_trans"))
            ->mergeBindings($query)
            ->join('users as u', 'u.id', 'all_trans.doctorId')
            ->where('u.isDeleted', 0)
            ->select(
                DB::raw("CONCAT(u.firstName, IFNULL(CONCAT(' ', u.lastName), '')) as staffName"),
                DB::raw('SUM(all_trans.total) as total')
            )
            ->groupBy('u.id', 'u.firstName', 'u.lastName')
            ->get();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function buildCardMetric(int $current, int $prev): array
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
}
