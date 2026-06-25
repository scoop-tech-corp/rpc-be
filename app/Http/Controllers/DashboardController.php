<?php

namespace App\Http\Controllers;

use App\Models\bookings;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $periods    = $this->resolvePeriods($request);
        $currentStart = $periods['currentStart'];
        $currentEnd   = $periods['currentEnd'];
        $prevStart    = $periods['prevStart'];
        $prevEnd      = $periods['prevEnd'];

        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;

        $chartsBookingCategory = $this->chartsBookingCategory($branchesId, $currentStart, $currentEnd);
        $reportingGroup        = collect($this->reportingGroup($branchesId, $currentStart, $currentEnd));
        $bookings              = $this->bookings($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId);
        $newCustomer           = $this->newCustomer($currentStart, $currentEnd, $prevStart, $prevEnd);
        $rebookRate            = $this->calculateRebookRateTrend($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId);
        $saleMetrics           = $this->saleMetrics($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId);
        $customerRetention     = $this->customerRetention($currentStart, $currentEnd, $prevStart, $prevEnd, $branchesId);

        $data = [
            'chartsBookingCategory' => [
                'labels' => $chartsBookingCategory->pluck('serviceType')->toArray(),
                'series' => $chartsBookingCategory->pluck('total')->map(fn($value) => (int) $value)->toArray(),
            ],
            'chartsReportingGroup' => [
                'labels' => $reportingGroup->pluck('group')->toArray(),
                'series' => $reportingGroup->pluck('total')->map(fn($value) => (int) $value)->toArray(),
            ],
            'bookings' => [
                'percentage' => (string) $bookings['percentageBookings'],
                'total'      => (string) $bookings['bookings'],
                'isLoss'     => $bookings['isLoss']
            ],
            'totalSaleValue' => $saleMetrics['totalSaleValue'],
            'newCustomer' => [
                'percentage' => (string) $newCustomer['percentageNewCustomer'],
                'total'      => (string) $newCustomer['newCustomer'],
                'isLoss'     => $newCustomer['isLoss']
            ],
            'rebookRate' => [
                'percentage' => (string) $rebookRate['percentage'],
                'total'      => (string) $rebookRate['rebookCount'],
                'isLoss'     => $rebookRate['isLoss']
            ],
            'customerRetention' => $customerRetention,
            'avgSaleValue' => $saleMetrics['avgSaleValue'],
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
            $year  = $request->year  ?? $now->year;
            $month = $request->month ?? $now->month;
            $isCurrentMonth = ($year == $now->year && $month == $now->month);
            $firstOfMonth   = Carbon::createFromDate($year, $month, 1);
            $currentStart   = $firstOfMonth->copy()->startOfMonth();
            $currentEnd     = $isCurrentMonth ? $now->copy() : $firstOfMonth->copy()->endOfMonth();
            $prevStart      = $firstOfMonth->copy()->subMonth()->startOfMonth();
            $prevEnd        = $isCurrentMonth ? $now->copy()->subMonth() : $firstOfMonth->copy()->subMonth()->endOfMonth();
        }

        return compact('currentStart', 'currentEnd', 'prevStart', 'prevEnd');
    }

    private function calculateRebookRateTrend(Carbon $startOfCurrentMonth, Carbon $endOfCurrentMonth, Carbon $startOfLastMonth, Carbon $endOfLastMonthCompare, ?array $branchesId = null)
    {
        $currentData = $this->getRebookMetrics($startOfCurrentMonth, $endOfCurrentMonth, $branchesId);
        $currentRate = $currentData['rate'];

        $prevData = $this->getRebookMetrics($startOfLastMonth, $endOfLastMonthCompare, $branchesId);
        $prevRate = $prevData['rate'];

        $trendPercentage = 0;
        if ($prevRate > 0) {
            $trendPercentage = (($currentRate - $prevRate) / $prevRate) * 100;
        } elseif ($currentRate > 0) {
            $trendPercentage = 100;
        }

        return [
            'rebookCount' => (string) $currentData['rebooked_count'],
            'percentage'  => (string) round($trendPercentage, 1),
            'isLoss'      => $currentRate >= $prevRate ? 0 : 1
        ];
    }

    private function getRebookMetrics(Carbon $startDate, Carbon $endDate, ?array $branchesId = null)
    {
        $baseQuery = fn() => Bookings::whereBetween('created_at', [$startDate, $endDate])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId));

        $totalUnique = $baseQuery()->distinct('customerId')->count('customerId');

        $rebookedCount = $baseQuery()
            ->select('customerId')
            ->groupBy('customerId')
            ->havingRaw('COUNT(customerId) > 1')
            ->get()
            ->count();

        return [
            'rebooked_count' => $rebookedCount,
            'rate'           => $totalUnique > 0 ? ($rebookedCount / $totalUnique) * 100 : 0
        ];
    }

    private function newCustomer(Carbon $startOfCurrentMonth, Carbon $endOfCurrentMonth, Carbon $startOfLastMonth, Carbon $endOfLastMonthCompare)
    {
        $newCustomer = DB::table('customer')
            ->whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
            ->count();

        $prevNewCustomer = DB::table('customer')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonthCompare])
            ->count();

        $percentageNewCustomer = 0;
        if ($prevNewCustomer > 0) {
            $percentageNewCustomer = (($newCustomer - $prevNewCustomer) / $prevNewCustomer) * 100;
        } elseif ($newCustomer > 0) {
            $percentageNewCustomer = 100;
        }

        return [
            'newCustomer'           => (string) $newCustomer,
            'percentageNewCustomer' => (string) round($percentageNewCustomer, 2),
            'isLoss'                => $newCustomer >= $prevNewCustomer ? 0 : 1
        ];
    }

    /**
     * Hitung total sale value & avg sale value dari 5 sumber transaksi.
     * - Clinic / Hotel / Salon / Breeding: SUM(amountPaid) dari tabel payment_totals
     *   di-JOIN ke tabel utama transaksi untuk filter lokasi + tanggal.
     * - PetShop: langsung dari kolom totalPayment di tabel transactionpetshop.
     */
    private function saleMetrics(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId = null): array
    {
        $current = $this->querySaleMetrics($currentStart, $currentEnd, $branchesId);
        $prev    = $this->querySaleMetrics($prevStart, $prevEnd, $branchesId);

        // ── Total Sale Value ─────────────────────────────────────
        $curSale  = $current['totalSale'];
        $prevSale = $prev['totalSale'];

        $salePercentage = 0;
        if ($prevSale > 0) {
            $salePercentage = (($curSale - $prevSale) / $prevSale) * 100;
        } elseif ($curSale > 0) {
            $salePercentage = 100;
        }

        // ── Avg Sale Value ────────────────────────────────────────
        $curAvg  = $current['totalTrx'] > 0 ? $curSale / $current['totalTrx'] : 0;
        $prevAvg = $prev['totalTrx']    > 0 ? $prevSale / $prev['totalTrx']   : 0;

        $avgPercentage = 0;
        if ($prevAvg > 0) {
            $avgPercentage = (($curAvg - $prevAvg) / $prevAvg) * 100;
        } elseif ($curAvg > 0) {
            $avgPercentage = 100;
        }

        return [
            'totalSaleValue' => [
                'total'      => number_format($curSale, 0, ',', '.'),
                'percentage' => (string) round(abs($salePercentage), 1),
                'isLoss'     => $curSale >= $prevSale ? 0 : 1,
            ],
            'avgSaleValue' => [
                'total'      => number_format($curAvg, 0, ',', '.'),
                'percentage' => (string) round(abs($avgPercentage), 1),
                'isLoss'     => $curAvg >= $prevAvg ? 0 : 1,
            ],
        ];
    }

    /**
     * Ambil agregat (totalSale, totalTrx) dari semua layanan untuk satu periode.
     */
    private function querySaleMetrics(Carbon $start, Carbon $end, ?array $branchesId = null): array
    {
        $totalSale = 0;
        $totalTrx  = 0;

        // ── 1. Pet Clinic ─────────────────────────────────────────
        $clinic = DB::table('transaction_pet_clinic_payment_totals as pt')
            ->join('transactionPetClinics as t', 't.id', '=', 'pt.transactionId')
            ->whereBetween('t.created_at', [$start, $end])
            ->where('t.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('t.locationId', $branchesId))
            ->selectRaw('COALESCE(SUM(pt.amountPaid), 0) as totalSale, COUNT(DISTINCT t.id) as totalTrx')
            ->first();

        // ── 2. Pet Hotel ──────────────────────────────────────────
        $hotel = DB::table('transaction_pet_hotel_payment_totals as pt')
            ->join('transaction_pet_hotels as t', 't.id', '=', 'pt.transactionId')
            ->whereBetween('t.created_at', [$start, $end])
            ->where('t.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('t.locationId', $branchesId))
            ->selectRaw('COALESCE(SUM(pt.amountPaid), 0) as totalSale, COUNT(DISTINCT t.id) as totalTrx')
            ->first();

        // ── 3. Pet Salon ──────────────────────────────────────────
        $salon = DB::table('transaction_pet_salon_payment_totals as pt')
            ->join('transaction_pet_salons as t', 't.id', '=', 'pt.transactionId')
            ->whereBetween('t.created_at', [$start, $end])
            ->where('t.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('t.locationId', $branchesId))
            ->selectRaw('COALESCE(SUM(pt.amountPaid), 0) as totalSale, COUNT(DISTINCT t.id) as totalTrx')
            ->first();

        // ── 4. Breeding ───────────────────────────────────────────
        $breeding = DB::table('transaction_breeding_payment_totals as pt')
            ->join('transaction_breedings as t', 't.id', '=', 'pt.transactionId')
            ->whereBetween('t.created_at', [$start, $end])
            ->where('t.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('t.locationId', $branchesId))
            ->selectRaw('COALESCE(SUM(pt.amountPaid), 0) as totalSale, COUNT(DISTINCT t.id) as totalTrx')
            ->first();

        // ── 5. Pet Shop ───────────────────────────────────────────
        // Tidak punya tabel payment_totals terpisah → pakai kolom totalPayment langsung
        $petshop = DB::table('transactionpetshop as t')
            ->whereBetween('t.created_at', [$start, $end])
            ->where('t.isDeleted', 0)
            ->when($branchesId, fn($q) => $q->whereIn('t.locationId', $branchesId))
            ->selectRaw('COALESCE(SUM(t.totalPayment), 0) as totalSale, COUNT(t.id) as totalTrx')
            ->first();

        // ── Agregasi semua sumber ─────────────────────────────────
        foreach ([$clinic, $hotel, $salon, $breeding, $petshop] as $row) {
            $totalSale += (float) ($row->totalSale ?? 0);
            $totalTrx  += (int)   ($row->totalTrx  ?? 0);
        }

        return compact('totalSale', 'totalTrx');
    }

    /**
     * Customer Retention = customer yang bertransaksi di periode ini
     * DAN juga pernah bertransaksi di periode sebelumnya.
     * Trend dibanding periode sebelumnya (prev vs prev-prev).
     */
    private function customerRetention(Carbon $currentStart, Carbon $currentEnd, Carbon $prevStart, Carbon $prevEnd, ?array $branchesId = null): array
    {
        $currRetained = $this->countRetainedCustomers($currentStart, $currentEnd, $prevStart, $branchesId);
        $prevRetained = $this->countRetainedCustomers($prevStart, $prevEnd, $prevStart->copy()->subDays($prevStart->diffInDays($prevEnd)), $branchesId);

        $percentage = 0;
        if ($prevRetained > 0) {
            $percentage = (($currRetained - $prevRetained) / $prevRetained) * 100;
        } elseif ($currRetained > 0) {
            $percentage = 100;
        }

        return [
            'total'      => (string) $currRetained,
            'percentage' => (string) round(abs($percentage), 1),
            'isLoss'     => $currRetained >= $prevRetained ? 0 : 1,
        ];
    }

    /**
     * Hitung jumlah customer yang:
     * 1. Bertransaksi dalam [$start, $end]
     * 2. Sebelumnya pernah bertransaksi sebelum $start
     */
    private function countRetainedCustomers(Carbon $start, Carbon $end, Carbon $prevStart, ?array $branchesId): int
    {
        $periodIds = $this->getTransactionCustomerIds($start, $end, $branchesId);

        if (empty($periodIds)) {
            return 0;
        }

        // Dari customer periode ini, cari yang pernah aktif sebelum $start
        $tables = [
            'transactionPetClinics',
            'transaction_pet_hotels',
            'transaction_pet_salons',
            'transaction_breedings',
            'transactionpetshop',
        ];

        $priorIds = collect();
        foreach ($tables as $table) {
            $q = DB::table($table)
                ->select('customerId')
                ->whereIn('customerId', $periodIds)
                ->where('created_at', '<', $start)
                ->where('isDeleted', 0);
            if ($branchesId) {
                $q->whereIn('locationId', $branchesId);
            }
            $priorIds = $priorIds->merge($q->pluck('customerId'));
        }

        return $priorIds->unique()->count();
    }

    /**
     * Ambil array distinct customerId dari semua transaksi dalam periode.
     */
    private function getTransactionCustomerIds(Carbon $start, Carbon $end, ?array $branchesId): array
    {
        $tables = [
            'transactionPetClinics',
            'transaction_pet_hotels',
            'transaction_pet_salons',
            'transaction_breedings',
            'transactionpetshop',
        ];

        $ids = collect();
        foreach ($tables as $table) {
            $q = DB::table($table)
                ->select('customerId')
                ->whereBetween('created_at', [$start, $end])
                ->where('isDeleted', 0);
            if ($branchesId) {
                $q->whereIn('locationId', $branchesId);
            }
            $ids = $ids->merge($q->pluck('customerId'));
        }

        return $ids->unique()->values()->toArray();
    }

    private function reportingGroup($branchesId = null, $start = null, $end = null)
    {
        return DB::query()
            ->from(function ($query) use ($branchesId, $start, $end) {
                $query->select('customerId')->from('transactionPetClinics')
                    ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
                    ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
                    ->unionAll(function ($q) use ($branchesId, $start, $end) {
                        $q->select('customerId')->from('transaction_pet_hotels')
                            ->when($branchesId, fn($q2) => $q2->whereIn('locationId', $branchesId))
                            ->when($start && $end, fn($q2) => $q2->whereBetween('created_at', [$start, $end]));
                    })
                    ->unionAll(function ($q) use ($branchesId, $start, $end) {
                        $q->select('customerId')->from('transaction_breedings')
                            ->when($branchesId, fn($q2) => $q2->whereIn('locationId', $branchesId))
                            ->when($start && $end, fn($q2) => $q2->whereBetween('created_at', [$start, $end]));
                    })
                    ->unionAll(function ($q) use ($branchesId, $start, $end) {
                        $q->select('customerId')->from('transaction_pet_salons')
                            ->when($branchesId, fn($q2) => $q2->whereIn('locationId', $branchesId))
                            ->when($start && $end, fn($q2) => $q2->whereBetween('created_at', [$start, $end]));
                    });
            }, 'transactions')
            ->join('customer as b', 'transactions.customerId', 'b.id')
            ->leftJoin('customerGroups as c', 'b.customerGroupId', 'c.id')
            ->select(
                DB::raw("COALESCE(c.customerGroup, 'Uncategorized') as `group`"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('group')
            ->get();
    }

    private function chartsBookingCategory($branchesId = null, $start = null, $end = null)
    {
        return DB::table('bookings')
            ->select('serviceType', DB::raw('COUNT(*) as total'))
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->groupBy('serviceType')
            ->get();
    }

    private function bookings(Carbon $startOfCurrentMonth, Carbon $endOfCurrentMonth, Carbon $startOfLastMonth, Carbon $endOfLastMonthCompare, ?array $branchesId = null)
    {
        $bookings = Bookings::whereBetween('created_at', [$startOfCurrentMonth, $endOfCurrentMonth])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $prevBookings = Bookings::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonthCompare])
            ->when($branchesId, fn($q) => $q->whereIn('locationId', $branchesId))
            ->count();

        $percentageBookings = 0;
        if ($prevBookings > 0) {
            $percentageBookings = (($bookings - $prevBookings) / $prevBookings) * 100;
        } elseif ($bookings > 0) {
            $percentageBookings = 100;
        }

        return [
            'bookings'           => (string) $bookings,
            'percentageBookings' => (string) round($percentageBookings, 2),
            'isLoss'             => $bookings >= $prevBookings ? 0 : 1
        ];
    }

    public function upcomingBookingHotel(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;
        $periods    = $this->resolvePeriods($request);

        $data = DB::table('bookings as b')
            ->join('bookingsPetHotels as p', 'b.id', 'p.bookingId')
            ->join('location as l', 'l.id', 'b.locationId')
            ->join('customer as c', 'c.id', 'b.customerId')
            ->join('users as u', 'u.id', 'b.doctorId')
            ->select([
                'b.id',
                DB::raw("DATE_FORMAT(b.bookingTime, '%d/%m/%Y') as bookingTime"),
                'l.locationName as location',
                DB::raw("CONCAT(c.firstName, IFNULL(CONCAT(' ', c.lastName), '')) as customer"),
                DB::raw("'Pet Hotel' as serviceName"),
                'u.firstName as staff',
                'b.status',
                'p.additionalInfo as bookingNote',
            ])
            ->where('b.isDeleted', '=', 0)
            ->where('b.status', '=', 0)
            ->when($branchesId, fn($q) => $q->whereIn('b.locationId', $branchesId))
            ->whereBetween('b.bookingTime', [$periods['currentStart'], $periods['currentEnd']]);

        return response()->json([
            'data' => $data->get(),
        ]);
    }

    public function upcomingBookingClinic(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;
        $periods    = $this->resolvePeriods($request);

        $data = DB::table('bookings as b')
            ->join('bookingsPetClinics as p', 'b.id', 'p.bookingId')
            ->join('location as l', 'l.id', 'b.locationId')
            ->join('customer as c', 'c.id', 'b.customerId')
            ->join('users as u', 'u.id', 'b.doctorId')
            ->select([
                'b.id',
                DB::raw("DATE_FORMAT(b.bookingTime, '%d/%m/%Y') as bookingTime"),
                'l.locationName as location',
                DB::raw("CONCAT(c.firstName, IFNULL(CONCAT(' ', c.lastName), '')) as customer"),
                DB::raw("'Pet Clinic' as serviceName"),
                'u.firstName as staff',
                'b.status',
                'p.additionalInfo as bookingNote',
            ])
            ->where('b.isDeleted', '=', 0)
            ->where('b.status', '=', 0)
            ->when($branchesId, fn($q) => $q->whereIn('b.locationId', $branchesId))
            ->whereBetween('b.bookingTime', [$periods['currentStart'], $periods['currentEnd']]);

        return response()->json([
            'data' => $data->get(),
        ]);
    }

    public function upcomingBookingSalon(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;
        $periods    = $this->resolvePeriods($request);

        $data = DB::table('bookings as b')
            ->join('bookingsPetSalons as p', 'b.id', 'p.bookingId')
            ->join('location as l', 'l.id', 'b.locationId')
            ->join('customer as c', 'c.id', 'b.customerId')
            ->join('users as u', 'u.id', 'b.doctorId')
            ->select([
                'b.id',
                DB::raw("DATE_FORMAT(b.bookingTime, '%d/%m/%Y') as bookingTime"),
                'l.locationName as location',
                DB::raw("CONCAT(c.firstName, IFNULL(CONCAT(' ', c.lastName), '')) as customer"),
                DB::raw("'Pet Salon' as serviceName"),
                'u.firstName as staff',
                'b.status',
                'p.additionalInfo as bookingNote',
            ])
            ->where('b.isDeleted', '=', 0)
            ->where('b.status', '=', 0)
            ->when($branchesId, fn($q) => $q->whereIn('b.locationId', $branchesId))
            ->whereBetween('b.bookingTime', [$periods['currentStart'], $periods['currentEnd']]);

        return response()->json([
            'data' => $data->get(),
        ]);
    }

    public function upcomingBookingBreeding(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $branchesId = $request->filled('branchesId') ? $request->branchesId : null;
        $periods    = $this->resolvePeriods($request);

        $data = DB::table('bookings as b')
            ->join('bookingsBreedings as p', 'b.id', 'p.bookingId')
            ->join('location as l', 'l.id', 'b.locationId')
            ->join('customer as c', 'c.id', 'b.customerId')
            ->join('users as u', 'u.id', 'b.doctorId')
            ->select([
                'b.id',
                DB::raw("DATE_FORMAT(b.bookingTime, '%d/%m/%Y') as bookingTime"),
                'l.locationName as location',
                DB::raw("CONCAT(c.firstName, IFNULL(CONCAT(' ', c.lastName), '')) as customer"),
                DB::raw("'Breeding' as serviceName"),
                'u.firstName as staff',
                'b.status',
                'p.additionalInfo as bookingNote',
            ])
            ->where('b.isDeleted', '=', 0)
            ->where('b.status', '=', 0)
            ->when($branchesId, fn($q) => $q->whereIn('b.locationId', $branchesId))
            ->whereBetween('b.bookingTime', [$periods['currentStart'], $periods['currentEnd']]);

        return response()->json([
            'data' => $data->get(),
        ]);
    }

    public function recentActivity(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $itemPerPage = $request->rowPerPage;
        $page        = $request->goToPage;
        $branchesId  = $request->filled('branchesId') ? $request->branchesId : null;
        $periods     = $this->resolvePeriods($request);

        $data = DB::table('recentActivities as ra')
            ->join('users as u', 'ra.userId', 'u.id')
            ->select(
                'ra.id as id',
                'ra.module as module',
                'ra.event as event',
                'ra.details as detail',
                'u.firstName as staff',
                DB::raw("DATE_FORMAT(ra.created_at, '%d %b, %Y %l:%i %p') as date")
            )
            ->whereBetween('ra.created_at', [$periods['currentStart'], $periods['currentEnd']])
            ->when($branchesId, fn($q) => $q->whereIn('ra.locationId', $branchesId));

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {
                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                return response()->json([
                    'totalPagination' => 0,
                    'data'            => []
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('ra.updated_at', 'desc');

        if (!$itemPerPage) {
            $result = $data->get();
            return response()->json(['totalPagination' => 0, 'data' => $result], 200);
        }

        $offset       = ($page - 1) * $itemPerPage;
        $count_data   = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $result = $data->limit($itemPerPage)->offset(0)->get();
        } else {
            $result = $data->limit($itemPerPage)->offset($offset)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data'            => $result
        ], 200);
    }
}
