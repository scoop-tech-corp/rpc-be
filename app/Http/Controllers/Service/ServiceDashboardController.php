<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceDashboardController extends Controller
{
    private const PAYMENT_PAIRS = [
        ['pt' => 'transaction_pet_clinic_payment_totals', 't' => 'transactionPetClinics'],
        ['pt' => 'transaction_pet_hotel_payment_totals',  't' => 'transaction_pet_hotels'],
        ['pt' => 'transaction_pet_salon_payment_totals',  't' => 'transaction_pet_salons'],
        ['pt' => 'transaction_breeding_payment_totals',   't' => 'transaction_breedings'],
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
            'charts'             => $this->buildBookingsChart($cs, $ce, $ps, $pe, $branchesId),
            'bookings'           => $this->bookingsStat($cs, $ce, $ps, $pe, $branchesId),
            'bookingsQty'        => $this->completedTransactionsStat($cs, $ce, $ps, $pe, $branchesId),
            'bookingsValue'      => $this->bookingsValueStat($cs, $ce, $ps, $pe, $branchesId),
            'mostPopular'        => $this->mostPopular($cs, $ce, $branchesId),
            'bookingsByCategory' => $this->bookingsByCategory($cs, $ce, $branchesId),
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

    private function bookingsStat(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->countBookings($cs, $ce, $branchesId),
            $this->countBookings($ps, $pe, $branchesId)
        );
    }

    private function countBookings(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        return DB::table('bookings')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->where('isCancelled', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();
    }

    private function completedTransactionsStat(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->countCompletedTransactions($cs, $ce, $branchesId),
            $this->countCompletedTransactions($ps, $pe, $branchesId)
        );
    }

    private function countCompletedTransactions(Carbon $start, Carbon $end, ?array $branchesId): int
    {
        $tables = ['transactionPetClinics', 'transaction_pet_hotels', 'transaction_pet_salons', 'transaction_breedings'];
        $total  = 0;

        foreach ($tables as $table) {
            $q = DB::table($table)
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0)
                ->where('status', 'Selesai');
            if ($branchesId) $q->whereIn('locationId', $branchesId);
            $total += $q->count();
        }

        return $total;
    }

    private function bookingsValueStat(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        return $this->buildCardMetric(
            $this->sumServiceRevenue($cs, $ce, $branchesId),
            $this->sumServiceRevenue($ps, $pe, $branchesId),
            true
        );
    }

    private function sumServiceRevenue(Carbon $start, Carbon $end, ?array $branchesId): float
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
        return $total;
    }

    private function mostPopular(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        return DB::table('bookings')
            ->select('serviceType as serviceName', DB::raw('COUNT(*) as bookings'))
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->where('isCancelled', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->groupBy('serviceType')
            ->orderByDesc('bookings')
            ->get()
            ->toArray();
    }

    private function bookingsByCategory(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $rows = DB::table('bookings')
            ->select('serviceType', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->where('isCancelled', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->groupBy('serviceType')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('serviceType')->toArray(),
            'series' => $rows->map(fn($r) => (int) $r->total)->toArray(),
        ];
    }

    private function buildBookingsChart(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branchesId): array
    {
        $currData = $this->dailyBookings($cs, $ce, $branchesId);
        $prevData = $this->dailyBookings($ps, $pe, $branchesId);

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

    private function dailyBookings(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        return DB::table('bookings')
            ->selectRaw('DATE(created_at) as trxDate, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->where('isCancelled', 0)
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->groupByRaw('DATE(created_at)')
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
