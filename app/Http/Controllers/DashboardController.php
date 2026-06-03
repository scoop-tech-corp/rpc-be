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
            'totalSaleValue' => [
                'percentage' => '27.5',
                'total'      => '250',
                'isLoss'     => 0
            ],
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
            'customerRetention' => [
                'percentage' => '40',
                'total'      => '400',
                'isLoss'     => 1
            ],
            'avgSaleValue' => [
                'percentage' => '68',
                'total'      => '1,400',
                'isLoss'     => 0
            ],
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

        $offset       = ($page - 1) * $itemPerPage;
        $count_data   = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data'            => $data
        ], 200);
    }
}
