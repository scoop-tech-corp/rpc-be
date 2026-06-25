<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ReportCustomerController extends Controller
{
    public function indexGrowth(Request $request)
    {
        // 1. Setup Tanggal & Threshold
        $dateFrom = $request->input('dateFrom') ?: Carbon::today()->subDays(9)->format('Y-m-d');
        $dateTo = $request->input('dateTo') ?: Carbon::today()->format('Y-m-d');

        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();

        $diffInDays = $start->diffInDays($end);

        $daysLabel = collect(range(0, $diffInDays))->map(function ($offset) use ($start) {
            return $start->copy()->addDays($offset)->format('j M');
        });

        $daysIso = collect(range(0, $diffInDays))->map(function ($offset) use ($start) {
            return $start->copy()->addDays($offset)->format('Y-m-d');
        });

        // Threshold 14 hari sesuai permintaan Anda
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // 2. Query Locations
        $locations = DB::table('location')
            ->where('isDeleted', 0)
            ->when($request->input('locationId'), function ($query, $ids) {
                return $query->whereIn('id', $ids);
            })
            ->get(['id', 'locationName']);

        $locationIds = $locations->pluck('id')->toArray();

        // 3. Query Customers
        $customers = DB::table('customer as c')
            ->select(
                'c.id',
                'c.locationId',
                'c.isDeleted',
                'l.locationName',
                'c.created_at',
                'c.lastTransaction', // Kolom transaksi terakhir
                DB::raw("DATE(c.created_at) as date"),
                DB::raw("DATE(c.lastTransaction) as lastTransactionDate")
            )
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->whereIn('l.id', $locationIds)
            // Filter Group jika ada
            ->when($request->input('customerGroup'), function ($query, $group) {
                return $query->whereIn('c.customerGroupId', $group);
            })
            ->get();

        // 4. Mapping Data Table
        $tableData = $locations->map(function ($loc) use ($customers, $fourteenDaysAgo) {

            $locCustomers = $customers->where('locationId', $loc->id);

            return [
                'location' => $loc->locationName,

                // NEW: Transaksi terakhir masih dalam rentang 14 hari terakhir
                'new' => $locCustomers->where('isDeleted', 0)
                    ->filter(function ($c) use ($fourteenDaysAgo) {
                        return !is_null($c->lastTransaction) && Carbon::parse($c->lastTransaction)->gte($fourteenDaysAgo);
                    })->count(),

                // INACTIVE: Transaksi terakhir sudah lebih dari 14 hari yang lalu (atau tidak pernah transaksi)
                'inactive' => $locCustomers->where('isDeleted', 0)
                    ->filter(function ($c) use ($fourteenDaysAgo) {
                        return is_null($c->lastTransaction) || Carbon::parse($c->lastTransaction)->lt($fourteenDaysAgo);
                    })->count(),

                // DELETED: Tetap berdasarkan flag isDeleted
                'deleted' => $locCustomers->where('isDeleted', 1)->count(),
            ];
        });

        // 5. Accumulate Totals
        $totalData = [
            'new'      => $tableData->sum('new'),
            'inactive' => $tableData->sum('inactive'),
            'deleted'  => $tableData->sum('deleted'),
        ];


        $customerData = [];
        $selectedLocationIds = $request->input('locationId');
        $locationNames = DB::table('location')
            ->where('isDeleted', 0)
            ->when($selectedLocationIds, function ($query, $selectedLocationIds) {
                return $query->whereIn('id', $selectedLocationIds);
            })
            ->pluck('locationName')
            ->toArray();
        foreach ($locationNames as $locationName) {
            // Filter customer berdasarkan lokasi ini dari hasil get() di atas
            $customerInLocation = $customers->where('locationName', $locationName);

            $customerData[$locationName] = $daysIso->map(function ($date) use ($customerInLocation) {
                // Sekarang where('date', $date) akan berfungsi karena kolom 'date' ada di select
                return $customerInLocation->where('date', $date)->count();
            })->toArray();
        }

        $series = collect($locationNames)->map(function ($location) use ($customerData) {
            return [
                'name' => $location,
                'data' => $customerData[$location],
            ];
        })->values();

        // 6. Response Construction
        $data = [
            'charts' => [
                'series' => $series,
                'categories' => $daysLabel,
            ],
            'table' => [
                'data' => $tableData->values(),
                'totalData' => $totalData,
            ]
        ];

        return response()->json($data);
    }

    public function exportGrowth(Request $request)
    {
        $selectedLocationIds   = array_values(array_filter((array) $request->input('locationId', []),   fn($v) => $v !== '' && $v !== null));
        $selectedCustomerGroup = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));

        // Threshold 14 hari sesuai permintaan Anda
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // 2. Ambil daftar lokasi aktif
        $locations = DB::table('location')
            ->where('isDeleted', 0)
            ->when(!empty($selectedLocationIds), function ($query) use ($selectedLocationIds) {
                return $query->whereIn('id', $selectedLocationIds);
            })
            ->get(['id', 'locationName']);

        $locationIds = $locations->pluck('id')->toArray();

        // 3. Ambil data customer (ambil yang aktif maupun yang deleted untuk hitungan kolom deleted)
        $customers = DB::table('customer as c')
            ->select('c.id', 'c.locationId', 'c.isDeleted', 'c.lastTransaction')
            ->whereIn('c.locationId', $locationIds)
            ->when(!empty($selectedCustomerGroup), function ($query) use ($selectedCustomerGroup) {
                return $query->whereIn('c.customerGroupId', $selectedCustomerGroup);
            })
            ->get();

        // 4. Proses Mapping Data per Lokasi
        $data = $locations->map(function ($loc) use ($customers, $fourteenDaysAgo) {
            // Ambil semua customer di lokasi ini (termasuk yang deleted)
            $locCustomers = $customers->where('locationId', $loc->id);

            // Customer yang belum dihapus (untuk kategori New dan Inactive)
            $activeLocCustomers = $locCustomers->where('isDeleted', 0);

            return [
                'location' => $loc->locationName,

                // NEW: Transaksi terakhir <= 14 hari yang lalu
                'new' => $activeLocCustomers->filter(function ($c) use ($fourteenDaysAgo) {
                    return !is_null($c->lastTransaction) && Carbon::parse($c->lastTransaction)->gte($fourteenDaysAgo);
                })->count(),

                // INACTIVE: Transaksi terakhir > 14 hari yang lalu ATAU tidak pernah transaksi
                'inactive' => $activeLocCustomers->filter(function ($c) use ($fourteenDaysAgo) {
                    return is_null($c->lastTransaction) || Carbon::parse($c->lastTransaction)->lt($fourteenDaysAgo);
                })->count(),

                // DELETED: Customer dengan flag isDeleted = 1
                'deleted' => $locCustomers->where('isDeleted', 1)->count(),
            ];
        })->values()->toArray();

        // 5. Hitung Akumulasi Total
        $totalData = [
            'new'      => collect($data)->sum('new'),
            'inactive' => collect($data)->sum('inactive'),
            'deleted'  => collect($data)->sum('deleted'),
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Growth.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['location']);
            $sheet->setCellValue("B{$row}", $item['new']);
            $sheet->setCellValue("C{$row}", $item['inactive']);
            $sheet->setCellValue("D{$row}", $item['deleted']);

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['new']);
        $sheet->setCellValue("C{$row}", $totalData['inactive']);
        $sheet->setCellValue("D{$row}", $totalData['deleted']);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $timestamp = now()->format('Ymd');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Growth ' . $timestamp . '.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Growth ' . $timestamp . '.xlsx"',
        ]);
    }

    public function indexGrowthByGroup(Request $request)
    {
        $selectedLocationIds   = array_values(array_filter((array) $request->input('locationId', []),   fn($v) => $v !== '' && $v !== null));
        $selectedCustomerGroup = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // 2. Ambil Daftar Group yang Aktif
        $groups = DB::table('customerGroups')
            // ->where('isDeleted', 0)
            ->when(!empty($selectedCustomerGroup), function ($query) use ($selectedCustomerGroup) {
                return $query->whereIn('id', $selectedCustomerGroup);
            })
            ->get(['id', 'customerGroup']);

        $groupIds = $groups->pluck('id')->toArray();

        // 3. Query Customer (Satu kali tarik data untuk performa)
        $customers = !empty($groupIds)
            ? DB::table('customer as c')
                ->select('c.id', 'c.customerGroupId', 'c.isDeleted', 'c.lastTransaction')
                ->when(!empty($selectedLocationIds), function ($query) use ($selectedLocationIds) {
                    return $query->whereIn('c.locationId', $selectedLocationIds);
                })
                ->whereIn('c.customerGroupId', $groupIds)
                ->get()
            : collect();

        // 4. Olah Data untuk Table & Chart
        $tableData = $groups->map(function ($group) use ($customers, $fourteenDaysAgo) {
            // Filter customer berdasarkan group ini
            $groupCustomers = $customers->where('customerGroupId', $group->id);

            // Customer Aktif (tidak di-delete) untuk hitungan Total, New, & Inactive
            $activeCustomers = $groupCustomers->where('isDeleted', 0);

            $newCount = $activeCustomers->filter(function ($c) use ($fourteenDaysAgo) {
                return !is_null($c->lastTransaction) && Carbon::parse($c->lastTransaction)->gte($fourteenDaysAgo);
            })->count();

            $inactiveCount = $activeCustomers->filter(function ($c) use ($fourteenDaysAgo) {
                return is_null($c->lastTransaction) || Carbon::parse($c->lastTransaction)->lt($fourteenDaysAgo);
            })->count();

            return [
                'reportingGroup' => $group->customerGroup,
                'total'          => $activeCustomers->count(),
                'new'            => $newCount,
                'inactive'       => $inactiveCount,
                'deleted'        => $groupCustomers->where('isDeleted', 1)->count(),
            ];
        });

        // 5. Susun Format Response Akhir
        $result = [
            'charts' => [
                'labels' => $tableData->pluck('reportingGroup')->toArray(),
                'series' => $tableData->pluck('total')->toArray(),
            ],
            'table' => [
                'data' => $tableData->values()->toArray(),
                'totalData' => [
                    'total'    => $tableData->sum('total'),
                    'new'      => $tableData->sum('new'),
                    'inactive' => $tableData->sum('inactive'),
                    'deleted'  => $tableData->sum('deleted'),
                ],
            ]
        ];

        return response()->json($result);
    }

    public function exportGrowthByGroup(Request $request)
    {
        $selectedLocationIds   = array_values(array_filter((array) $request->input('locationId', []),   fn($v) => $v !== '' && $v !== null));
        $selectedCustomerGroup = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // 2. Ambil Daftar Group (tanpa filter isDeleted, sama seperti index)
        $groups = DB::table('customerGroups')
            ->when(!empty($selectedCustomerGroup), function ($query) use ($selectedCustomerGroup) {
                return $query->whereIn('id', $selectedCustomerGroup);
            })
            ->get(['id', 'customerGroup']);

        $groupIds = $groups->pluck('id')->toArray();

        // 3. Query Customer (Satu kali tarik data untuk semua grup)
        $customers = !empty($groupIds)
            ? DB::table('customer as c')
                ->select('c.id', 'c.customerGroupId', 'c.isDeleted', 'c.lastTransaction')
                ->when(!empty($selectedLocationIds), function ($query) use ($selectedLocationIds) {
                    return $query->whereIn('c.locationId', $selectedLocationIds);
                })
                ->whereIn('c.customerGroupId', $groupIds)
                ->get()
            : collect();

        // 4. Mapping Data Utama ($data)
        $data = $groups->map(function ($group) use ($customers, $fourteenDaysAgo) {
            // Filter customer milik grup ini
            $groupCustomers = $customers->where('customerGroupId', $group->id);

            // Customer Aktif (isDeleted = 0)
            $activeCustomers = $groupCustomers->where('isDeleted', 0);

            return [
                'reportingGroup' => $group->customerGroup,
                'total'          => $activeCustomers->count(),
                'new'            => $activeCustomers->filter(function ($c) use ($fourteenDaysAgo) {
                    return !is_null($c->lastTransaction) && Carbon::parse($c->lastTransaction)->gte($fourteenDaysAgo);
                })->count(),
                'inactive'       => $activeCustomers->filter(function ($c) use ($fourteenDaysAgo) {
                    return is_null($c->lastTransaction) || Carbon::parse($c->lastTransaction)->lt($fourteenDaysAgo);
                })->count(),
                'deleted'        => $groupCustomers->where('isDeleted', 1)->count(),
            ];
        })->values()->toArray();

        // 5. Hitung Total Akumulasi ($totalData)
        $totalData = [
            'total'    => collect($data)->sum('total'),
            'new'      => collect($data)->sum('new'),
            'inactive' => collect($data)->sum('inactive'),
            'deleted'  => collect($data)->sum('deleted'),
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Growth_by_group.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['reportingGroup']);
            $sheet->setCellValue("B{$row}", $item['total']);
            $sheet->setCellValue("C{$row}", $item['new']);
            $sheet->setCellValue("D{$row}", $item['inactive']);
            $sheet->setCellValue("E{$row}", $item['deleted']);

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['total']);
        $sheet->setCellValue("C{$row}", $totalData['new']);
        $sheet->setCellValue("D{$row}", $totalData['inactive']);
        $sheet->setCellValue("E{$row}", $totalData['deleted']);

        $timestamp = now()->format('Ymd');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Growth by Group ' . $timestamp . '.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Growth by Group.xlsx"',
        ]);
    }

    public function indexTotal(Request $request)
    {
        $dateFrom = $request->input('dateFrom') ?: Carbon::today()->subDays(9)->format('Y-m-d');
        $dateTo = $request->input('dateTo') ?: Carbon::today()->format('Y-m-d');

        // $startDate = Carbon::today()->subDays(9);
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        $diffInDays = $start->diffInDays($end);

        $daysLabel = collect(range(0, $diffInDays))->map(function ($offset) use ($start) {
            return $start->copy()->addDays($offset)->format('j M');
        });

        $daysIso = collect(range(0, $diffInDays))->map(function ($offset) use ($start) {
            return $start->copy()->addDays($offset)->format('Y-m-d');
        });

        $selectedLocationIds = $request->input('locationId');
        $selectedCustomerGroup = $request->input('customerGroup');

        $locationNames = DB::table('location')
            ->where('isDeleted', 0)
            ->when($selectedLocationIds, function ($query, $selectedLocationIds) {
                return $query->whereIn('id', $selectedLocationIds);
            })
            ->pluck('locationName')
            ->toArray();

        // Query untuk CHART — hanya customer yang daftar dalam rentang tanggal (trend harian)
        $customersInRange = DB::table('customer as c')
            ->select('l.locationName', DB::raw("DATE(c.created_at) as date"))
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.isDeleted', 0)
            ->whereIn('l.locationName', $locationNames)
            ->when($selectedCustomerGroup, function ($query, $grp) {
                return $query->whereIn('c.customerGroupId', $grp);
            })
            ->whereBetween('c.created_at', [$daysIso->first() . ' 00:00:00', $daysIso->last() . ' 23:59:59'])
            ->get();

        // Query untuk TABLE TOTAL — semua customer tanpa filter tanggal
        $allCustomers = DB::table('customer as c')
            ->select('l.locationName', DB::raw('COUNT(*) as total'))
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.isDeleted', 0)
            ->whereIn('l.locationName', $locationNames)
            ->when($selectedCustomerGroup, function ($query, $grp) {
                return $query->whereIn('c.customerGroupId', $grp);
            })
            ->groupBy('l.locationName')
            ->pluck('total', 'locationName');

        // Bangun data chart (trend harian per lokasi)
        $customerData = [];
        foreach ($locationNames as $locationName) {
            $customerInLocation = $customersInRange->where('locationName', $locationName);
            $customerData[$locationName] = $daysIso->map(function ($date) use ($customerInLocation) {
                return $customerInLocation->where('date', $date)->count();
            })->toArray();
        }

        $series = collect($locationNames)->map(function ($location) use ($customerData) {
            return [
                'name' => $location,
                'data' => $customerData[$location],
            ];
        })->values();

        // Bangun data tabel (total semua customer per lokasi)
        $tableData = collect($locationNames)->map(function ($locationName) use ($allCustomers) {
            return [
                'location' => $locationName,
                'total'    => $allCustomers[$locationName] ?? 0,
            ];
        })->values();

        $grandTotal = $tableData->sum('total');

        $data = [
            'charts' => [
                'series' => $series,
                'categories' => $daysLabel,
            ],
            'table' => [
                'data' => $tableData,
                'totalData' => [
                    'total' => $grandTotal,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportTotal(Request $request)
    {
        $selectedLocationIds   = array_values(array_filter((array) $request->input('locationId', []),   fn($v) => $v !== '' && $v !== null));
        $selectedCustomerGroup = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));

        $locationNames = DB::table('location')
            ->where('isDeleted', 0)
            ->when(!empty($selectedLocationIds), function ($query) use ($selectedLocationIds) {
                return $query->whereIn('id', $selectedLocationIds);
            })
            ->pluck('locationName')
            ->toArray();

        // Total per lokasi tanpa filter tanggal
        $allCustomers = DB::table('customer as c')
            ->select('l.locationName', DB::raw('COUNT(*) as total'))
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.isDeleted', 0)
            ->whereIn('l.locationName', $locationNames)
            ->when(!empty($selectedCustomerGroup), function ($query) use ($selectedCustomerGroup) {
                return $query->whereIn('c.customerGroupId', $selectedCustomerGroup);
            })
            ->groupBy('l.locationName')
            ->pluck('total', 'locationName');

        $tableData = collect($locationNames)->map(function ($locationName) use ($allCustomers) {
            return [
                'location' => $locationName,
                'total'    => $allCustomers[$locationName] ?? 0,
            ];
        })->values();

        $totalData = [
            'total' => $tableData->sum('total'),
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Total.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($tableData as $item) {

            $sheet->setCellValue("A{$row}", $item['location']);
            $sheet->setCellValue("B{$row}", $item['total']);

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['total']);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $timestamp = now()->format('Ymd');

        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Total ' . $timestamp . '.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Total ' . $timestamp . '.xlsx"',
        ]);
    }

    public function indexLeaving(Request $request)
    {
        $itemPerPage  = (int) ($request->input('rowPerPage') ?: 5);
        $page         = (int) ($request->input('goToPage')   ?: 1);
        $orderColumn  = $request->input('orderColumn') ?: 'c.lastTransaction';
        $orderValue   = strtolower($request->input('orderValue') ?: 'desc');
        $dateFrom     = $request->input('dateFrom');
        $dateTo       = $request->input('dateTo');
        $status       = $request->input('status'); // '1' aktif, '0' inaktif, '' semua
        $locationIds  = $request->input('locationId', []);
        $groupIds     = $request->input('customerGroup', []);

        if (!is_array($locationIds)) $locationIds = [$locationIds];
        $locationIds = array_values(array_filter($locationIds, fn($v) => $v !== '' && $v !== null));

        if (!is_array($groupIds)) $groupIds = [$groupIds];
        $groupIds = array_values(array_filter($groupIds, fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['c.lastTransaction', 'l.locationName', 'cg.customerGroup', 'c.joinDate'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'c.lastTransaction';
        if (!in_array($orderValue, ['asc', 'desc'])) $orderValue = 'desc';

        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // Subquery total spend dari 4 tabel payment
        $totalSubquery = "
            COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_pet_clinic_payment_totals pt
                JOIN   transactionPetClinics t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_pet_hotel_payment_totals pt
                JOIN   transaction_pet_hotels t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_pet_salon_payment_totals pt
                JOIN   transaction_pet_salons t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_breeding_payment_totals pt
                JOIN   transaction_breedings t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
        ";

        $query = DB::table('customer as c')
            ->join('location as l',          'l.id',  '=', 'c.locationId')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->where('c.isDeleted', 0)
            ->select([
                DB::raw("TRIM(CONCAT(c.firstName, ' ',
                    COALESCE(NULLIF(c.middleName,''), ''), ' ',
                    COALESCE(NULLIF(c.lastName,''), ''))) as name"),
                'l.locationName as location',
                DB::raw("COALESCE(DATE_FORMAT(c.lastTransaction, '%d %b %Y'), '-') as date"),
                DB::raw("COALESCE(cg.customerGroup, '-') as customerGroup"),
                'c.joinDate',
                DB::raw("($totalSubquery) as total"),
            ]);

        if ($dateFrom) $query->whereDate('c.lastTransaction', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('c.lastTransaction', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('c.locationId', $locationIds);
        if (!empty($groupIds))    $query->whereIn('c.customerGroupId', $groupIds);

        if ($status === '1') {
            // Aktif: lastTransaction dalam 14 hari terakhir
            $query->where('c.lastTransaction', '>=', $fourteenDaysAgo);
        } elseif ($status === '0') {
            // Inaktif: lastTransaction > 14 hari atau belum pernah transaksi
            $query->where(function ($q) use ($fourteenDaysAgo) {
                $q->whereNull('c.lastTransaction')
                  ->orWhere('c.lastTransaction', '<', $fourteenDaysAgo);
            });
        }

        $total   = (clone $query)->count();
        $results = $query->orderBy($orderColumn, $orderValue)
            ->limit($itemPerPage)
            ->offset(($page - 1) * $itemPerPage)
            ->get();

        // Hitung customerFor di PHP menggunakan joinDate
        $data = $results->map(function ($row) {
            $joinDate = $row->joinDate ? Carbon::parse($row->joinDate) : null;
            if ($joinDate) {
                $diff = $joinDate->diff(Carbon::now());
                if ($diff->y > 0)      $customerFor = $diff->y . ' Year'  . ($diff->y > 1 ? 's' : '');
                elseif ($diff->m > 0)  $customerFor = $diff->m . ' Month' . ($diff->m > 1 ? 's' : '');
                else                   $customerFor = $diff->d . ' Day'   . ($diff->d != 1 ? 's' : '');
            } else {
                $customerFor = '-';
            }

            return [
                'name'          => $row->name,
                'location'      => $row->location,
                'date'          => $row->date,
                'customerGroup' => $row->customerGroup,
                'customerFor'   => $customerFor,
                'total'         => (float) $row->total,
            ];
        });

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportLeaving(Request $request)
    {
        $orderColumn = $request->input('orderColumn') ?: 'c.lastTransaction';
        $orderValue  = strtolower($request->input('orderValue') ?: 'desc');
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $status      = $request->input('status');

        $locationIds = array_values(array_filter((array) $request->input('locationId', []),    fn($v) => $v !== '' && $v !== null));
        $groupIds    = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['c.lastTransaction', 'l.locationName', 'cg.customerGroup', 'c.joinDate'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'c.lastTransaction';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'desc';

        $fourteenDaysAgo = Carbon::now()->subDays(14);

        $totalSubquery = "
            COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_pet_clinic_payment_totals pt
                JOIN   transactionPetClinics t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_pet_hotel_payment_totals pt
                JOIN   transaction_pet_hotels t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_pet_salon_payment_totals pt
                JOIN   transaction_pet_salons t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM   transaction_breeding_payment_totals pt
                JOIN   transaction_breedings t ON t.id = pt.transactionId
                WHERE  t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
        ";

        $query = DB::table('customer as c')
            ->join('location as l',           'l.id',  '=', 'c.locationId')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->where('c.isDeleted', 0)
            ->select([
                DB::raw("TRIM(CONCAT(c.firstName, ' ',
                    COALESCE(NULLIF(c.middleName,''), ''), ' ',
                    COALESCE(NULLIF(c.lastName,''), ''))) as name"),
                'l.locationName as location',
                DB::raw("COALESCE(DATE_FORMAT(c.lastTransaction, '%d %b %Y'), '-') as date"),
                DB::raw("COALESCE(cg.customerGroup, '-') as customerGroup"),
                'c.joinDate',
                DB::raw("($totalSubquery) as total"),
            ]);

        if ($dateFrom) $query->whereDate('c.lastTransaction', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('c.lastTransaction', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('c.locationId', $locationIds);
        if (!empty($groupIds))    $query->whereIn('c.customerGroupId', $groupIds);

        if ($status === '1') {
            $query->where('c.lastTransaction', '>=', $fourteenDaysAgo);
        } elseif ($status === '0') {
            $query->where(function ($q) use ($fourteenDaysAgo) {
                $q->whereNull('c.lastTransaction')
                  ->orWhere('c.lastTransaction', '<', $fourteenDaysAgo);
            });
        }

        $results = $query->orderBy($orderColumn, $orderValue)->get();

        $data = $results->map(function ($row) {
            $joinDate = $row->joinDate ? Carbon::parse($row->joinDate) : null;
            if ($joinDate) {
                $diff = $joinDate->diff(Carbon::now());
                if ($diff->y > 0)      $customerFor = $diff->y . ' Year'  . ($diff->y > 1 ? 's' : '');
                elseif ($diff->m > 0)  $customerFor = $diff->m . ' Month' . ($diff->m > 1 ? 's' : '');
                else                   $customerFor = $diff->d . ' Day'   . ($diff->d != 1 ? 's' : '');
            } else {
                $customerFor = '-';
            }

            return [
                'name'          => $row->name,
                'location'      => $row->location,
                'date'          => $row->date,
                'customerGroup' => $row->customerGroup,
                'customerFor'   => $customerFor,
                'total'         => (float) $row->total,
            ];
        });

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Leaving.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['date']);
            $sheet->setCellValue("D{$row}", $item['customerGroup']);
            $sheet->setCellValue("E{$row}", $item['customerFor']);
            $sheet->setCellValue("F{$row}", $item['total']);
            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $timestamp = now()->format('Ymd');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Leaving ' . $timestamp . '.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Leaving ' . $timestamp . '.xlsx"',
        ]);
    }

    public function indexList(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);
        $orderColumn = $request->input('orderColumn') ?: 'c.memberNo';
        $orderValue  = $request->input('orderValue')  ?: 'asc';

        [$data, $total] = $this->fetchCustomerList($request, $orderColumn, $orderValue, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportList(Request $request)
    {
        [$data] = $this->fetchCustomerList($request, 'c.memberNo', 'asc');

        $spreadsheet = IOFactory::load(public_path() . '/template/report/Template_Report_Customer_List.xlsx');
        $sheet       = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['memberNo']);
            $sheet->setCellValue("B{$row}", $item['name']);
            $sheet->setCellValue("C{$row}", $item['location']);
            $sheet->setCellValue("D{$row}", $item['status']);
            $sheet->setCellValue("E{$row}", $item['gender']);
            $sheet->setCellValue("F{$row}", $item['telephone']);
            $sheet->setCellValue("G{$row}", $item['email']);
            $row++;
        }

        $writer      = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/Export Report Customer List.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer List.xlsx"',
        ]);
    }

    /**
     * Shared query untuk indexList() dan exportList().
     * Return: [$collection, $totalCount]
     * Export memanggil tanpa pagination (itemPerPage=0 → ambil semua).
     */
    private function fetchCustomerList(Request $request, string $orderColumn, string $orderValue, int $itemPerPage = 0, int $page = 1): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $status      = $request->input('status');    // '1' aktif, '0' inaktif, '' semua
        $search      = $request->input('search');
        $gender      = $request->input('gender');    // 'P' atau 'W'
        $locationIds = $request->input('locationId', []);
        $groupIds    = $request->input('customerGroup', []);
        $typeIds     = $request->input('idType', []);

        if (!is_array($locationIds)) $locationIds = [$locationIds];
        $locationIds = array_values(array_filter($locationIds, fn($v) => $v !== '' && $v !== null));

        if (!is_array($groupIds)) $groupIds = [$groupIds];
        $groupIds = array_values(array_filter($groupIds, fn($v) => $v !== '' && $v !== null));

        if (!is_array($typeIds)) $typeIds = [$typeIds];
        $typeIds = array_values(array_filter($typeIds, fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['c.memberNo', 'c.firstName', 'l.locationName', 'c.gender', 'c.created_at'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'c.memberNo';
        if (!in_array(strtolower($orderValue), ['asc', 'desc'])) $orderValue = 'asc';

        $fourteenDaysAgo = Carbon::now()->subDays(14);

        $query = DB::table('customer as c')
            ->join('location as l',           'l.id',  '=', 'c.locationId')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->where('c.isDeleted', 0)
            ->select([
                'c.memberNo',
                DB::raw("TRIM(CONCAT(c.firstName, ' ',
                    COALESCE(NULLIF(c.middleName,''), ''), ' ',
                    COALESCE(NULLIF(c.lastName,''), ''))) as name"),
                'l.locationName as location',
                DB::raw("CASE
                    WHEN c.lastTransaction IS NOT NULL
                         AND c.lastTransaction >= '{$fourteenDaysAgo->toDateTimeString()}'
                    THEN 'Active'
                    ELSE 'Inactive'
                END as status"),
                DB::raw("CASE c.gender WHEN 'P' THEN 'Male' WHEN 'W' THEN 'Female' ELSE '-' END as gender"),
                DB::raw("(SELECT ct.phoneNumber FROM customerTelephones ct
                          WHERE ct.customerId = c.id ORDER BY ct.id LIMIT 1) as telephone"),
                DB::raw("(SELECT ce.email FROM customerEmails ce
                          WHERE ce.customerId = c.id ORDER BY ce.id LIMIT 1) as email"),
            ]);

        if ($dateFrom) $query->whereDate('c.created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('c.created_at', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('c.locationId', $locationIds);
        if (!empty($groupIds))    $query->whereIn('c.customerGroupId', $groupIds);
        if (!empty($typeIds))     $query->whereIn('c.typeId', $typeIds);
        if ($gender)              $query->where('c.gender', $gender);

        if ($status === '1') {
            $query->where('c.lastTransaction', '>=', $fourteenDaysAgo);
        } elseif ($status === '0') {
            $query->where(function ($q) use ($fourteenDaysAgo) {
                $q->whereNull('c.lastTransaction')
                  ->orWhere('c.lastTransaction', '<', $fourteenDaysAgo);
            });
        }

        if ($search) {
            $query->where(DB::raw("TRIM(CONCAT(c.firstName, ' ',
                COALESCE(NULLIF(c.middleName,''), ''), ' ',
                COALESCE(NULLIF(c.lastName,''), '')))"), 'like', "%{$search}%");
        }

        $total = $query->count();

        $query->orderBy($orderColumn, $orderValue);

        if ($itemPerPage > 0) {
            $offset = max(0, ($page - 1) * $itemPerPage);
            $query->offset($offset)->limit($itemPerPage);
        }

        $rows = $query->get()->map(function ($row) {
            return [
                'memberNo'  => $row->memberNo  ?? '-',
                'name'      => $row->name,
                'location'  => $row->location,
                'status'    => $row->status,
                'gender'    => $row->gender,
                'telephone' => $row->telephone ?? '-',
                'email'     => $row->email     ?? '-',
            ];
        });

        return [$rows, $total];
    }

    public function indexRefSpend(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 10);
        $page        = (int) ($request->input('goToPage')   ?: 1);
        $orderColumn = $request->input('orderColumn') ?: 'totalSpend';
        $orderValue  = strtolower($request->input('orderValue') ?: 'desc');

        [$data, $total] = $this->fetchRefSpend($request, $orderColumn, $orderValue, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportRefSpend(Request $request)
    {
        [$data] = $this->fetchRefSpend($request, 'totalSpend', 'desc');

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Ref_Spend.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['referenceBy']);
            $sheet->setCellValue("B{$row}", $item['reference']);
            $sheet->setCellValue("C{$row}", $item['customer']);
            $sheet->setCellValue("D{$row}", $item['totalSpend']);
            $row++;
        }

        $writer    = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $timestamp = now()->format('Ymd');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Referral Spend ' . $timestamp . '.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Referral Spend ' . now()->format('Ymd') . '.xlsx"',
        ]);
    }

    /**
     * Shared query untuk indexRefSpend() dan exportRefSpend().
     * Menghitung total spend per customer dari 4 tabel payment,
     * dikelompokkan berdasarkan sumber referral (referenceCustomer).
     */
    private function fetchRefSpend(Request $request, string $orderColumn, string $orderValue, int $itemPerPage = 0, int $page = 1): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $search      = $request->input('search');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['totalSpend', 'rc.referenceName', 'customerName'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'totalSpend';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'desc';

        $totalSpendSql = "
            COALESCE((
                SELECT SUM(pt.amount)
                FROM transaction_pet_clinic_payment_totals pt
                JOIN transactionPetClinics t ON t.id = pt.transactionId
                WHERE t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM transaction_pet_hotel_payment_totals pt
                JOIN transaction_pet_hotels t ON t.id = pt.transactionId
                WHERE t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM transaction_pet_salon_payment_totals pt
                JOIN transaction_pet_salons t ON t.id = pt.transactionId
                WHERE t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
            + COALESCE((
                SELECT SUM(pt.amount)
                FROM transaction_breeding_payment_totals pt
                JOIN transaction_breedings t ON t.id = pt.transactionId
                WHERE t.customerId = c.id AND (pt.isDeleted IS NULL OR pt.isDeleted = 0)
            ), 0)
        ";

        $query = DB::table('customer as c')
            ->join('referenceCustomer as rc', 'rc.id', '=', 'c.referenceCustomerId')
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.isDeleted', 0)
            ->select([
                'rc.referenceName as referenceBy',
                'rc.referenceName as reference',
                DB::raw("TRIM(CONCAT(c.firstName, ' ',
                    COALESCE(NULLIF(c.middleName,''), ''), ' ',
                    COALESCE(NULLIF(c.lastName,''), ''))) as customer"),
                DB::raw("($totalSpendSql) as totalSpend"),
            ]);

        if ($dateFrom)             $query->whereDate('c.created_at', '>=', $dateFrom);
        if ($dateTo)               $query->whereDate('c.created_at', '<=', $dateTo);
        if (!empty($locationIds))  $query->whereIn('c.locationId', $locationIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('rc.referenceName', 'like', "%{$search}%")
                  ->orWhere(DB::raw("TRIM(CONCAT(c.firstName, ' ',
                      COALESCE(NULLIF(c.middleName,''), ''), ' ',
                      COALESCE(NULLIF(c.lastName,''), '')))"), 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();

        $query->orderBy($orderColumn, $orderValue);

        if ($itemPerPage > 0) {
            $query->limit($itemPerPage)->offset(($page - 1) * $itemPerPage);
        }

        $rows = $query->get()->map(fn($row) => [
            'referenceBy' => $row->referenceBy ?? '-',
            'reference'   => $row->reference   ?? '-',
            'customer'    => $row->customer,
            'totalSpend'  => (float) $row->totalSpend,
        ]);

        return [$rows, $total];
    }

    public function indexSubAccount(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);
        $orderColumn = $request->input('orderColumn') ?: 'c.firstName';
        $orderValue  = $request->input('orderValue')  ?: 'asc';

        [$data, $total] = $this->fetchSubAccountList($request, $orderColumn, $orderValue, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportSubAccount(Request $request)
    {
        [$data] = $this->fetchSubAccountList($request, 'c.firstName', 'asc');

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Sub_Account.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['customerName']);
            $sheet->setCellValue("B{$row}", $item['petName']);
            $sheet->setCellValue("C{$row}", $item['condition']);
            $sheet->setCellValue("D{$row}", $item['type']);
            $sheet->setCellValue("E{$row}", $item['race']);
            $sheet->setCellValue("F{$row}", $item['gender']);
            $sheet->setCellValue("G{$row}", $item['sterile']);
            $sheet->setCellValue("H{$row}", $item['birthDate']);
            $sheet->setCellValue("I{$row}", $item['color']);
            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $timestamp = now()->format('Ymd');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Sub Account List ' . $timestamp . '.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Sub Account List ' . $timestamp . '.xlsx"',
        ]);
    }

    private function fetchSubAccountList(Request $request, string $orderColumn, string $orderValue, int $itemPerPage = 0, int $page = 1): array
    {
        $dateFrom  = $request->input('dateFrom');
        $dateTo    = $request->input('dateTo');
        $gender    = $request->input('gender');
        $sterile   = $request->input('sterile');
        $search    = $request->input('search');

        $locationIds = array_values(array_filter((array) $request->input('locationId', []),    fn($v) => $v !== '' && $v !== null));
        $groupIds    = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['c.firstName', 'p.petName', 'pc.petCategoryName', 'p.condition', 'p.dateOfBirth'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'c.firstName';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'asc';

        $query = DB::table('customerPets as p')
            ->join('customer as c',      'c.id',  '=', 'p.customerId')
            ->join('petCategory as pc',  'pc.id', '=', 'p.petCategoryId')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->where('p.isDeleted', 0)
            ->where('c.isDeleted', 0)
            ->select([
                DB::raw("TRIM(CONCAT(c.firstName, ' ',
                    COALESCE(NULLIF(c.middleName,''), ''), ' ',
                    COALESCE(NULLIF(c.lastName,''), ''))) as customerName"),
                'p.petName',
                'p.condition',
                'pc.petCategoryName as type',
                DB::raw("COALESCE(p.races, '-') as race"),
                DB::raw("CASE p.petGender WHEN 'J' THEN 'Jantan' WHEN 'B' THEN 'Betina' ELSE '-' END as gender"),
                DB::raw("CASE p.isSteril WHEN 1 THEN 'Sudah' ELSE 'Belum' END as sterile"),
                DB::raw("COALESCE(DATE_FORMAT(p.dateOfBirth, '%d/%m/%Y'), '-') as birthDate"),
                DB::raw("COALESCE(p.color, '-') as color"),
            ]);

        // Map nilai gender dari filter customer (P/W) ke nilai petGender (J/B)
        $petGenderMap = ['P' => 'J', 'W' => 'B'];
        $petGender = $petGenderMap[$gender] ?? null;

        if ($dateFrom) $query->whereDate('p.created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('p.created_at', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('c.locationId', $locationIds);
        if (!empty($groupIds))    $query->whereIn('c.customerGroupId', $groupIds);
        if ($petGender !== null) $query->where('p.petGender', $petGender);
        if ($sterile !== null && $sterile !== '') $query->where('p.isSteril', (int) $sterile);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("TRIM(CONCAT(c.firstName, ' ',
                        COALESCE(NULLIF(c.middleName,''), ''), ' ',
                        COALESCE(NULLIF(c.lastName,''), '')))"), 'like', "%{$search}%")
                  ->orWhere('p.petName', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();

        $query->orderBy($orderColumn, $orderValue);

        if ($itemPerPage > 0) {
            $query->limit($itemPerPage)->offset(($page - 1) * $itemPerPage);
        }

        $rows = $query->get()->map(fn($row) => [
            'customerName' => $row->customerName,
            'petName'      => $row->petName,
            'condition'    => $row->condition    ?? '-',
            'type'         => $row->type,
            'race'         => $row->race,
            'gender'       => $row->gender,
            'sterile'      => $row->sterile,
            'birthDate'    => $row->birthDate,
            'color'        => $row->color,
        ]);

        return [$rows, $total];
    }
}
