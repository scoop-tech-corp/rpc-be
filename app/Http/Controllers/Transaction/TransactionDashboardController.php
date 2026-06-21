<?php

namespace App\Http\Controllers\Transaction;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;

class TransactionDashboardController extends Controller
{
    // ── Tabel transaksi utama yang dimiliki semua layanan ─────────────────────
    private const TRANSACTION_TABLES = [
        'Pet Clinic' => 'transactionPetClinics',
        'Pet Hotel'  => 'transaction_pet_hotels',
        'Pet Salon'  => 'transaction_pet_salons',
        'Breeding'   => 'transaction_breedings',
        'Pet Shop'   => 'transactionpetshop',
    ];

    public function index(Request $request)
    {
        $periods = $this->resolvePeriods($request);
        $cs      = $periods['currentStart'];
        $ce      = $periods['currentEnd'];
        $ps      = $periods['prevStart'];
        $pe      = $periods['prevEnd'];
        $branches = $request->filled('branchesId') ? $request->branchesId : null;

        $data = [
            'totalTransaksi'   => $this->totalTransaksi($cs, $ce, $ps, $pe, $branches),
            'totalRevenue'     => $this->totalRevenue($cs, $ce, $ps, $pe, $branches),
            'transaksiSelesai' => $this->transaksiSelesai($cs, $ce, $ps, $pe, $branches),
            'customerBaru'     => $this->customerBaru($cs, $ce, $ps, $pe),

            'chartsVolume'  => $this->chartsVolume($cs, $ce, $ps, $pe, $branches),
            'chartsRevenue' => $this->chartsRevenue($cs, $ce, $ps, $pe, $branches),
            'chartsLayanan' => $this->chartsLayanan($cs, $ce, $branches),
            'chartsCabang'  => $this->chartsCabang($cs, $ce, $branches),
        ];

        return response()->json($data);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
            $year  = $request->year  ?? $now->year;
            $month = $request->month ?? $now->month;
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

    /** COUNT semua transaksi dari 5 layanan. */
    private function countAllTransactions(Carbon $start, Carbon $end, ?array $branches): int
    {
        $total = 0;
        foreach (self::TRANSACTION_TABLES as $table) {
            $q = DB::table($table)
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0);
            if ($branches) $q->whereIn('locationId', $branches);
            $total += $q->count();
        }
        return $total;
    }

    private function trendCard(int|float $current, int|float $prev, bool $formatCurrency = false): array
    {
        $percentage = 0;
        if ($prev > 0) {
            $percentage = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $percentage = 100;
        }

        $total = $formatCurrency
            ? number_format($current, 0, ',', '.')
            : (string) $current;

        return [
            'total'      => $total,
            'percentage' => (string) round(abs($percentage), 1),
            'isLoss'     => $current >= $prev ? 0 : 1,
        ];
    }

    // ── Kartu Statistik ───────────────────────────────────────────────────────

    /**
     * Total Transaksi = COUNT semua transaksi dari 5 layanan.
     */
    private function totalTransaksi(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branches): array
    {
        return $this->trendCard(
            $this->countAllTransactions($cs, $ce, $branches),
            $this->countAllTransactions($ps, $pe, $branches)
        );
    }

    /**
     * Total Revenue = SUM amountPaid dari semua tabel payment totals + petshop.totalPayment.
     */
    private function totalRevenue(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branches): array
    {
        return $this->trendCard(
            $this->sumRevenue($cs, $ce, $branches),
            $this->sumRevenue($ps, $pe, $branches),
            true
        );
    }

    private function sumRevenue(Carbon $start, Carbon $end, ?array $branches): float
    {
        $paymentTables = [
            ['pt' => 'transaction_pet_clinic_payment_totals', 't' => 'transactionPetClinics'],
            ['pt' => 'transaction_pet_hotel_payment_totals',  't' => 'transaction_pet_hotels'],
            ['pt' => 'transaction_pet_salon_payment_totals',  't' => 'transaction_pet_salons'],
            ['pt' => 'transaction_breeding_payment_totals',   't' => 'transaction_breedings'],
        ];

        $total = 0;
        foreach ($paymentTables as $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0);
            if ($branches) $q->whereIn('t.locationId', $branches);
            $total += (float) $q->sum('pt.amountPaid');
        }

        // Pet Shop: totalPayment langsung di tabel utama
        $psQ = DB::table('transactionpetshop')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0);
        if ($branches) $psQ->whereIn('locationId', $branches);
        $total += (float) $psQ->sum('totalPayment');

        return $total;
    }

    /**
     * Transaksi Selesai:
     * - Clinic/Hotel/Salon/Breeding: status = 'Selesai'
     * - Pet Shop: isPayed = 2 (payment confirmed)
     */
    private function transaksiSelesai(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branches): array
    {
        return $this->trendCard(
            $this->countCompletedTransactions($cs, $ce, $branches),
            $this->countCompletedTransactions($ps, $pe, $branches)
        );
    }

    private function countCompletedTransactions(Carbon $start, Carbon $end, ?array $branches): int
    {
        $serviceTablesWithStatus = [
            'transactionPetClinics',
            'transaction_pet_hotels',
            'transaction_pet_salons',
            'transaction_breedings',
        ];

        $total = 0;
        foreach ($serviceTablesWithStatus as $table) {
            $q = DB::table($table)
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0)
                ->where('status', 'Selesai');
            if ($branches) $q->whereIn('locationId', $branches);
            $total += $q->count();
        }

        // Pet Shop: selesai = isPayed = 2
        $psQ = DB::table('transactionpetshop')
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->where('isPayed', 2);
        if ($branches) $psQ->whereIn('locationId', $branches);
        $total += $psQ->count();

        return $total;
    }

    /**
     * Customer Baru = customer yang terdaftar dalam periode ini.
     */
    private function customerBaru(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe): array
    {
        $curr = DB::table('customer')->whereBetween('created_at', [$cs, $ce])->count();
        $prev = DB::table('customer')->whereBetween('created_at', [$ps, $pe])->count();
        return $this->trendCard($curr, $prev);
    }

    // ── Charts ────────────────────────────────────────────────────────────────

    /**
     * Pie chart: jumlah transaksi per jenis layanan.
     */
    private function chartsLayanan(Carbon $start, Carbon $end, ?array $branches): array
    {
        $labels = [];
        $series = [];

        foreach (self::TRANSACTION_TABLES as $label => $table) {
            $q = DB::table($table)
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0);
            if ($branches) $q->whereIn('locationId', $branches);

            $labels[] = $label;
            $series[] = $q->count();
        }

        return compact('labels', 'series');
    }

    /**
     * Column chart: jumlah transaksi per cabang/lokasi.
     */
    private function chartsCabang(Carbon $start, Carbon $end, ?array $branches): array
    {
        // Kumpulkan locationId + count dari semua tabel
        $locationCounts = collect();

        foreach (self::TRANSACTION_TABLES as $table) {
            $q = DB::table("{$table} as t")
                ->select('t.locationId', DB::raw('COUNT(*) as cnt'))
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0)
                ->groupBy('t.locationId');
            if ($branches) $q->whereIn('t.locationId', $branches);

            foreach ($q->get() as $row) {
                $locationCounts->push(['locationId' => $row->locationId, 'cnt' => $row->cnt]);
            }
        }

        // Agregasi per locationId
        $aggregated = $locationCounts
            ->groupBy('locationId')
            ->map(fn($rows, $locId) => [
                'locationId' => $locId,
                'total'      => $rows->sum('cnt'),
            ])
            ->values();

        // JOIN ke tabel location untuk nama cabang, sort descending
        $locationIds = $aggregated->pluck('locationId')->toArray();

        if (empty($locationIds)) {
            return ['categories' => [], 'series' => [['name' => 'Transaksi', 'data' => []]]];
        }

        $locations = DB::table('location')
            ->whereIn('id', $locationIds)
            ->pluck('locationName', 'id');

        $sorted = $aggregated->sortByDesc('total')->values();

        return [
            'categories' => $sorted->map(fn($r) => $locations[$r['locationId']] ?? 'Unknown')->values()->toArray(),
            'series'     => [
                ['name' => 'Transaksi', 'data' => $sorted->pluck('total')->map(fn($v) => (int) $v)->values()->toArray()],
            ],
        ];
    }

    /**
     * Area/Column chart: volume transaksi harian (current vs previous).
     * Kategori x-axis dibuat dari tanggal dalam periode current.
     */
    private function chartsVolume(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branches): array
    {
        $currData = $this->dailyTransactionCount($cs, $ce, $branches);
        $prevData = $this->dailyTransactionCount($ps, $pe, $branches);

        $days       = (int) $cs->diffInDays($ce) + 1;
        $categories = collect(range(0, $days - 1))
            ->map(fn($d) => $cs->copy()->addDays($d)->format('j M'))
            ->values()
            ->toArray();

        $currSeries = $this->fillDailySeries($cs, $ce, $currData);
        $prevSeries = $this->fillDailySeries($ps, $pe, $prevData);

        return [
            'categories' => $categories,
            'series'     => [
                ['name' => 'Periode Lalu',    'data' => $prevSeries],
                ['name' => 'Periode Ini',     'data' => $currSeries],
            ],
        ];
    }

    /**
     * Area chart: revenue harian (current vs previous).
     */
    private function chartsRevenue(Carbon $cs, Carbon $ce, Carbon $ps, Carbon $pe, ?array $branches): array
    {
        $currData = $this->dailyRevenue($cs, $ce, $branches);
        $prevData = $this->dailyRevenue($ps, $pe, $branches);

        $days       = (int) $cs->diffInDays($ce) + 1;
        $categories = collect(range(0, $days - 1))
            ->map(fn($d) => $cs->copy()->addDays($d)->format('j M'))
            ->values()
            ->toArray();

        $currSeries = $this->fillDailySeries($cs, $ce, $currData);
        $prevSeries = $this->fillDailySeries($ps, $pe, $prevData);

        return [
            'categories' => $categories,
            'series'     => [
                ['name' => 'Periode Lalu', 'data' => $prevSeries],
                ['name' => 'Periode Ini',  'data' => $currSeries],
            ],
        ];
    }

    /** COUNT transaksi per tanggal dari semua layanan. */
    private function dailyTransactionCount(Carbon $start, Carbon $end, ?array $branches): array
    {
        $result = [];
        foreach (self::TRANSACTION_TABLES as $table) {
            $q = DB::table($table)
                ->selectRaw("DATE(created_at) as trxDate, COUNT(*) as cnt")
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0)
                ->groupByRaw('DATE(created_at)');
            if ($branches) $q->whereIn('locationId', $branches);

            foreach ($q->get() as $row) {
                $result[$row->trxDate] = ($result[$row->trxDate] ?? 0) + $row->cnt;
            }
        }
        return $result;
    }

    /** SUM amountPaid per tanggal dari semua layanan. */
    private function dailyRevenue(Carbon $start, Carbon $end, ?array $branches): array
    {
        $result       = [];
        $paymentPairs = [
            ['pt' => 'transaction_pet_clinic_payment_totals', 't' => 'transactionPetClinics'],
            ['pt' => 'transaction_pet_hotel_payment_totals',  't' => 'transaction_pet_hotels'],
            ['pt' => 'transaction_pet_salon_payment_totals',  't' => 'transaction_pet_salons'],
            ['pt' => 'transaction_breeding_payment_totals',   't' => 'transaction_breedings'],
        ];

        foreach ($paymentPairs as $pair) {
            $q = DB::table("{$pair['pt']} as pt")
                ->join("{$pair['t']} as t", 't.id', '=', 'pt.transactionId')
                ->selectRaw("DATE(t.created_at) as trxDate, SUM(pt.amountPaid) as revenue")
                ->whereBetween('t.created_at', [$start, $end])
                ->where('t.isDeleted', 0)
                ->groupByRaw('DATE(t.created_at)');
            if ($branches) $q->whereIn('t.locationId', $branches);

            foreach ($q->get() as $row) {
                $result[$row->trxDate] = ($result[$row->trxDate] ?? 0) + (float) $row->revenue;
            }
        }

        // Pet Shop
        $psQ = DB::table('transactionpetshop')
            ->selectRaw("DATE(created_at) as trxDate, SUM(totalPayment) as revenue")
            ->whereBetween('created_at', [$start, $end])
            ->where('isDeleted', 0)
            ->groupByRaw('DATE(created_at)');
        if ($branches) $psQ->whereIn('locationId', $branches);
        foreach ($psQ->get() as $row) {
            $result[$row->trxDate] = ($result[$row->trxDate] ?? 0) + (float) $row->revenue;
        }

        return $result;
    }

    /**
     * Buat array nilai per hari (0 untuk hari tanpa transaksi)
     * sesuai urutan tanggal dalam periode.
     */
    private function fillDailySeries(Carbon $start, Carbon $end, array $dailyData): array
    {
        $series = [];
        $days   = (int) $start->diffInDays($end) + 1;
        for ($i = 0; $i < $days; $i++) {
            $dateKey  = $start->copy()->addDays($i)->format('Y-m-d');
            $series[] = (int) ($dailyData[$dateKey] ?? 0);
        }
        return $series;
    }
}
