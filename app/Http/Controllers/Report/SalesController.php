<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SalesController extends Controller
{
    public function indexItems(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchSalesItemsData($request, $perPage, $page);

        return response()->json([
            'totalPagination' => $total,
            'data'            => $data,
        ]);
    }

    public function exportItems(Request $request)
    {
        [$data] = $this->fetchSalesItemsData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_Items.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Sales ID');
        $sheet->setCellValue('B1', 'Location');
        $sheet->setCellValue('C1', 'Sale Date');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Items');
        $sheet->setCellValue('F1', 'Quantity');
        $sheet->setCellValue('G1', 'Unit Price (Rp)');
        $sheet->setCellValue('H1', 'Total (Rp)');
        $sheet->setCellValue('I1', 'Payment');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:I1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['saleDate']);
            $sheet->setCellValue("D{$row}", $item['status']);
            $sheet->setCellValue("E{$row}", $item['items']);
            $sheet->setCellValue("F{$row}", $item['quantity']);
            $sheet->setCellValue("G{$row}", $item['price']);
            $sheet->setCellValue("H{$row}", $item['totalAmount']);
            $sheet->setCellValue("I{$row}", $item['payment']);
            $sheet->getStyle("A{$row}:I{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Sales Items.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Items.xlsx"',
        ]);
    }

    /**
     * Fetch paginated sales line-item rows from all sources.
     *
     * Each row = one product or service sold in a transaction.
     * Sources: clinic/hotel/salon payment items + petshop detail.
     *
     * itemTypeId: 1 = product, 2 = service (clinic/hotel/salon only)
     * productCategoryId: filters by products.category (varchar)
     *
     * Returns: [$data, $total]
     */
    private function fetchSalesItemsData(Request $request, int $perPage, int $page): array
    {
        $dateFrom        = $request->input('dateFrom');
        $dateTo          = $request->input('dateTo');
        $locationIds     = array_values(array_filter((array) $request->input('locationId',        []), fn($v) => $v !== '' && $v !== null));
        $statusIds       = array_values(array_filter((array) $request->input('statusId',          []), fn($v) => $v !== '' && $v !== null));
        $paymentIds      = array_values(array_filter((array) $request->input('paymentId',         []), fn($v) => $v !== '' && $v !== null));
        $staffIds        = array_values(array_filter((array) $request->input('staffId',           []), fn($v) => $v !== '' && $v !== null));
        $itemTypeIds     = array_values(array_filter((array) $request->input('itemTypeId',        []), fn($v) => $v !== '' && $v !== null));
        $productCatIds   = array_values(array_filter((array) $request->input('productCategoryId', []), fn($v) => $v !== '' && $v !== null));
        $search          = $request->input('search');
        $orderColumn     = $request->input('orderColumn') ?: 'saleDate';
        $orderValue      = $request->input('orderValue')  ?: 'desc';

        // itemType: 1=product, 2=service. Empty = all.
        $onlyProducts = !empty($itemTypeIds) && in_array('1', $itemTypeIds) && !in_array('2', $itemTypeIds);
        $onlyServices = !empty($itemTypeIds) && in_array('2', $itemTypeIds) && !in_array('1', $itemTypeIds);
        // If only services requested, petshop (products only) is excluded
        $includePetshop = !$onlyServices;

        $allItems = collect();

        // ---- Clinic / Hotel / Salon payment line items ----
        $sources = [
            ['payTable' => 'transaction_pet_clinic_payments', 'txTable' => 'transactionPetClinics',  'ptTable' => 'transaction_pet_clinic_payment_totals',  'type' => 'clinic'],
            ['payTable' => 'transaction_pet_hotel_payments',  'txTable' => 'transaction_pet_hotels',  'ptTable' => 'transaction_pet_hotel_payment_totals',   'type' => 'hotel'],
            ['payTable' => 'transaction_pet_salon_payments',  'txTable' => 'transaction_pet_salons',  'ptTable' => 'transaction_pet_salon_payment_totals',   'type' => 'salon'],
        ];

        foreach ($sources as $src) {
            $query = DB::table("{$src['payTable']} as pmt")
                ->join("{$src['txTable']} as t", 't.id', '=', 'pmt.transactionId')
                ->join('location as l', 'l.id', '=', 't.locationId')
                ->leftJoin('products as p', 'p.id', '=', 'pmt.productId')
                ->leftJoin('services as s', 's.id', '=', 'pmt.serviceId')
                ->where(function ($q) { $q->where('pmt.isDeleted', 0)->orWhereNull('pmt.isDeleted'); })
                ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($staffIds),    fn($q) => $q->whereIn('t.doctorId', $staffIds))
                // itemType filter
                ->when($onlyProducts, fn($q) => $q->whereNotNull('pmt.productId'))
                ->when($onlyServices, fn($q) => $q->whereNotNull('pmt.serviceId'))
                // product category filter (products.category is varchar)
                ->when(!empty($productCatIds), fn($q) => $q->whereIn('p.category', $productCatIds))
                // search by item name or registrationNo
                ->when($search, fn($q) => $q->where(function ($sq) use ($search) {
                    $sq->where('p.fullName',         'like', "%{$search}%")
                       ->orWhere('s.fullName',       'like', "%{$search}%")
                       ->orWhere('t.registrationNo', 'like', "%{$search}%");
                }))
                // payment method filter: transaction must have a matching payment_total
                ->when(!empty($paymentIds), function ($q) use ($src, $paymentIds) {
                    $q->whereExists(function ($sub) use ($src, $paymentIds) {
                        $sub->from("{$src['ptTable']} as pt")
                            ->whereColumn('pt.transactionId', 't.id')
                            ->whereIn('pt.paymentMethodId', $paymentIds)
                            ->where(function ($q2) { $q2->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); });
                    });
                })
                ->select([
                    't.id as transactionId',
                    't.registrationNo as saleId',
                    'l.locationName as location',
                    't.status',
                    DB::raw('DATE(t.created_at) as saleDate'),
                    DB::raw('COALESCE(p.fullName, s.fullName) as itemName'),
                    'pmt.quantity',
                    'pmt.price',
                    DB::raw('pmt.priceOverall as totalAmount'),
                    DB::raw("'{$src['type']}' as sourceType"),
                ]);

            $allItems = $allItems->concat($query->get());
        }

        // ---- Petshop detail items (products only) ----
        if ($includePetshop) {
            $petshopQuery = DB::table('transactionpetshopdetail as d')
                ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
                ->join('location as l', 'l.id', '=', 't.locationId')
                ->join('products as p', 'p.id', '=', 'd.productId')
                ->where(function ($q) { $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'); })
                ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($paymentIds),  fn($q) => $q->whereIn('t.paymentMethod', $paymentIds))
                ->when(!empty($productCatIds), fn($q) => $q->whereIn('p.category', $productCatIds))
                ->when($search, fn($q) => $q->where(function ($sq) use ($search) {
                    $sq->where('p.fullName',         'like', "%{$search}%")
                       ->orWhere('t.registrationNo', 'like', "%{$search}%");
                }))
                ->select([
                    't.id as transactionId',
                    't.registrationNo as saleId',
                    'l.locationName as location',
                    DB::raw("t.verificationStatus as status"),
                    DB::raw('DATE(t.created_at) as saleDate'),
                    'p.fullName as itemName',
                    'd.quantity',
                    'd.price',
                    DB::raw('COALESCE(d.total_final_price, d.quantity * d.price) as totalAmount'),
                    DB::raw("'petshop' as sourceType"),
                ]);

            $allItems = $allItems->concat($petshopQuery->get());
        }

        // Sort
        $allowedCols = ['saleId', 'location', 'saleDate', 'status', 'itemName', 'quantity'];
        if (!in_array($orderColumn, $allowedCols)) $orderColumn = 'saleDate';
        $sorted = $orderValue === 'asc'
            ? $allItems->sortBy(fn($r) => $r->$orderColumn ?? '')
            : $allItems->sortByDesc(fn($r) => $r->$orderColumn ?? '');

        $total = $sorted->count();

        // Paginate
        $pageItems = $perPage > 0
            ? $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            : $sorted->values();

        if ($pageItems->isEmpty()) return [[], $total];

        // ---- Batch-fetch payment status for page transactions ----
        // Group by sourceType
        $idsByType = ['clinic' => [], 'hotel' => [], 'salon' => [], 'petshop' => []];
        foreach ($pageItems as $item) {
            $idsByType[$item->sourceType][] = (int) $item->transactionId;
        }

        // Paid transaction IDs per type (all payment_totals.isPayed = 1 → paid)
        $paidTxIds = []; // key = sourceType_txId

        foreach (['clinic', 'hotel', 'salon'] as $type) {
            if (empty($idsByType[$type])) continue;
            $ptTable = "transaction_pet_{$type}_payment_totals";
            // Get all payment_totals for these transactions
            $rows = DB::table("{$ptTable} as pt")
                ->whereIn('pt.transactionId', array_unique($idsByType[$type]))
                ->where(function ($q) { $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); })
                ->select(['pt.transactionId', 'pt.isPayed'])
                ->get()
                ->groupBy('transactionId');

            foreach ($rows as $txId => $payments) {
                $allPaid = $payments->every(fn($p) => $p->isPayed == 1);
                if ($allPaid) $paidTxIds["{$type}_{$txId}"] = true;
            }
        }

        // Petshop: check isPayed directly on the transaction
        if (!empty($idsByType['petshop'])) {
            $rows = DB::table('transactionpetshop')
                ->whereIn('id', array_unique($idsByType['petshop']))
                ->select(['id', 'isPayed'])
                ->get();
            foreach ($rows as $r) {
                if ($r->isPayed == 1) $paidTxIds["petshop_{$r->id}"] = true;
            }
        }

        // ---- Assemble result ----
        $result = $pageItems->map(function ($item) use ($paidTxIds) {
            $key    = $item->sourceType . '_' . $item->transactionId;
            $isPaid = isset($paidTxIds[$key]);

            return [
                'saleId'      => $item->saleId,
                'location'    => $item->location,
                'saleDate'    => $item->saleDate,
                'status'      => $item->status,
                'items'       => $item->itemName ?? '-',
                'quantity'    => (int) ($item->quantity ?? 0),
                'price'       => (float) ($item->price ?? 0),
                'totalAmount' => (float) ($item->totalAmount ?? 0),
                'payment'     => $isPaid ? 'Paid' : 'Unpaid',
            ];
        })->values()->toArray();

        return [$result, $total];
    }

    public function indexSummary(Request $request)
    {
        [$tableData, $totalData, $merged, $locations] = $this->fetchSalesSummaryData($request);

        $dateFrom = $request->input('dateFrom');
        $dateTo   = $request->input('dateTo');

        $chartStart = Carbon::parse($dateFrom ?? Carbon::today()->subDays(9)->format('Y-m-d'));
        $chartEnd   = Carbon::parse($dateTo   ?? Carbon::today()->format('Y-m-d'));

        // Cap chart range to 60 days to avoid huge payloads
        if ($chartStart->diffInDays($chartEnd) > 59) {
            $chartEnd = $chartStart->copy()->addDays(59);
        }

        $chartDates = [];
        $cur = $chartStart->copy();
        while ($cur->lte($chartEnd)) {
            $chartDates[] = $cur->format('j M');
            $cur->addDay();
        }

        $series = [];
        foreach ($locations as $locId => $loc) {
            $locId = (int) $locId;
            if (!isset($merged[$locId])) continue;

            $seriesData = [];
            $cur = $chartStart->copy();
            while ($cur->lte($chartEnd)) {
                $dateStr      = $cur->format('Y-m-d');
                $seriesData[] = isset($merged[$locId][$dateStr])
                    ? (int) round($merged[$locId][$dateStr]['totalAmount'])
                    : 0;
                $cur->addDay();
            }
            $series[] = ['name' => $loc->locationName, 'data' => $seriesData];
        }

        return response()->json([
            'charts' => ['series' => $series, 'categories' => $chartDates],
            'table'  => ['data' => $tableData, 'totalData' => $totalData],
            'totalPagination' => count($tableData),
        ]);
    }

    public function exportSummary(Request $request)
    {
        [$tableData, $totalData] = $this->fetchSalesSummaryData($request);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_Summary.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Location');
        $sheet->setCellValue('B1', 'Gross Amount (Rp)');
        $sheet->setCellValue('C1', 'Discount (Rp)');
        $sheet->setCellValue('D1', 'Net Amount (Rp)');
        $sheet->setCellValue('E1', 'Taxes (Rp)');
        $sheet->setCellValue('F1', 'Charges (Rp)');
        $sheet->setCellValue('G1', 'Total (Rp)');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($tableData as $item) {
            $sheet->setCellValue("A{$row}", $item['location']);
            $sheet->setCellValue("B{$row}", $item['grossAmount']);
            $sheet->setCellValue("C{$row}", $item['discounts']);
            $sheet->setCellValue("D{$row}", $item['netAmount']);
            $sheet->setCellValue("E{$row}", $item['taxesAmount']);
            $sheet->setCellValue("F{$row}", $item['chargesAmount']);
            $sheet->setCellValue("G{$row}", $item['totalAmount']);
            $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("A{$row}", 'Total');
        $sheet->setCellValue("B{$row}", $totalData['grossAmount']);
        $sheet->setCellValue("C{$row}", $totalData['discounts']);
        $sheet->setCellValue("D{$row}", $totalData['netAmount']);
        $sheet->setCellValue("E{$row}", $totalData['taxesAmount']);
        $sheet->setCellValue("F{$row}", $totalData['chargesAmount']);
        $sheet->setCellValue("G{$row}", $totalData['totalAmount']);
        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Sales Summary.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Summary.xlsx"',
        ]);
    }

    /**
     * Fetch aggregated sales summary data from all transaction sources
     * (clinic, hotel, salon, petshop) grouped by location and date.
     *
     * Returns: [$tableData, $totalData, $merged, $locations]
     *   - $tableData:  array of per-location rows for the summary table
     *   - $totalData:  array of grand totals
     *   - $merged:     [locationId][sale_date] = [grossAmount, discounts, totalAmount]
     *   - $locations:  Collection keyed by location id
     */
    private function fetchSalesSummaryData(Request $request): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $statusIds   = array_values(array_filter((array) $request->input('statusId',   []), fn($v) => $v !== '' && $v !== null));
        $paymentIds  = array_values(array_filter((array) $request->input('paymentId',  []), fn($v) => $v !== '' && $v !== null));
        $staffIds    = array_values(array_filter((array) $request->input('staffId',    []), fn($v) => $v !== '' && $v !== null));

        $locations = DB::table('location')
            ->when(!empty($locationIds), fn($q) => $q->whereIn('id', $locationIds))
            ->select('id', 'locationName')
            ->get()
            ->keyBy('id');

        $allRows = collect();

        // ---- Clinic ----
        $clinicPayAgg = DB::table('transaction_pet_clinic_payments as p')
            ->where(function ($q) { $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'); })
            ->select(['p.transactionId', DB::raw('SUM(p.priceOverall) as grossAmount'), DB::raw('SUM(p.discountAmount) as discounts')])
            ->groupBy('p.transactionId');

        $clinicTotalAgg = DB::table('transaction_pet_clinic_payment_totals as pt')
            ->where(function ($q) { $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); })
            ->when(!empty($paymentIds), fn($q) => $q->whereIn('pt.paymentMethodId', $paymentIds))
            ->select(['pt.transactionId', DB::raw('SUM(pt.amountPaid) as totalAmount')])
            ->groupBy('pt.transactionId');

        $allRows = $allRows->concat(
            DB::table('transactionPetClinics as t')
                ->leftJoinSub($clinicPayAgg,   'p_agg',  fn($j) => $j->on('p_agg.transactionId', '=', 't.id'))
                ->leftJoinSub($clinicTotalAgg, 'pt_agg', fn($j) => $j->on('pt_agg.transactionId', '=', 't.id'))
                ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($staffIds),    fn($q) => $q->whereIn('t.doctorId', $staffIds))
                ->when(!empty($paymentIds),  fn($q) => $q->whereNotNull('pt_agg.totalAmount'))
                ->select([
                    't.locationId',
                    DB::raw('DATE(t.created_at) as sale_date'),
                    DB::raw('SUM(COALESCE(p_agg.grossAmount, 0)) as grossAmount'),
                    DB::raw('SUM(COALESCE(p_agg.discounts, 0)) as discounts'),
                    DB::raw('SUM(COALESCE(pt_agg.totalAmount, 0)) as totalAmount'),
                ])
                ->groupBy('t.locationId', DB::raw('DATE(t.created_at)'))
                ->get()
        );

        // ---- Hotel ----
        $hotelPayAgg = DB::table('transaction_pet_hotel_payments as p')
            ->where(function ($q) { $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'); })
            ->select(['p.transactionId', DB::raw('SUM(p.priceOverall) as grossAmount'), DB::raw('SUM(p.discountAmount) as discounts')])
            ->groupBy('p.transactionId');

        $hotelTotalAgg = DB::table('transaction_pet_hotel_payment_totals as pt')
            ->where(function ($q) { $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); })
            ->when(!empty($paymentIds), fn($q) => $q->whereIn('pt.paymentMethodId', $paymentIds))
            ->select(['pt.transactionId', DB::raw('SUM(pt.amountPaid) as totalAmount')])
            ->groupBy('pt.transactionId');

        $allRows = $allRows->concat(
            DB::table('transaction_pet_hotels as t')
                ->leftJoinSub($hotelPayAgg,   'p_agg',  fn($j) => $j->on('p_agg.transactionId', '=', 't.id'))
                ->leftJoinSub($hotelTotalAgg, 'pt_agg', fn($j) => $j->on('pt_agg.transactionId', '=', 't.id'))
                ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($staffIds),    fn($q) => $q->whereIn('t.doctorId', $staffIds))
                ->when(!empty($paymentIds),  fn($q) => $q->whereNotNull('pt_agg.totalAmount'))
                ->select([
                    't.locationId',
                    DB::raw('DATE(t.created_at) as sale_date'),
                    DB::raw('SUM(COALESCE(p_agg.grossAmount, 0)) as grossAmount'),
                    DB::raw('SUM(COALESCE(p_agg.discounts, 0)) as discounts'),
                    DB::raw('SUM(COALESCE(pt_agg.totalAmount, 0)) as totalAmount'),
                ])
                ->groupBy('t.locationId', DB::raw('DATE(t.created_at)'))
                ->get()
        );

        // ---- Salon ----
        $salonPayAgg = DB::table('transaction_pet_salon_payments as p')
            ->where(function ($q) { $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'); })
            ->select(['p.transactionId', DB::raw('SUM(p.priceOverall) as grossAmount'), DB::raw('SUM(p.discountAmount) as discounts')])
            ->groupBy('p.transactionId');

        $salonTotalAgg = DB::table('transaction_pet_salon_payment_totals as pt')
            ->where(function ($q) { $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); })
            ->when(!empty($paymentIds), fn($q) => $q->whereIn('pt.paymentMethodId', $paymentIds))
            ->select(['pt.transactionId', DB::raw('SUM(pt.amountPaid) as totalAmount')])
            ->groupBy('pt.transactionId');

        $allRows = $allRows->concat(
            DB::table('transaction_pet_salons as t')
                ->leftJoinSub($salonPayAgg,   'p_agg',  fn($j) => $j->on('p_agg.transactionId', '=', 't.id'))
                ->leftJoinSub($salonTotalAgg, 'pt_agg', fn($j) => $j->on('pt_agg.transactionId', '=', 't.id'))
                ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($staffIds),    fn($q) => $q->whereIn('t.doctorId', $staffIds))
                ->when(!empty($paymentIds),  fn($q) => $q->whereNotNull('pt_agg.totalAmount'))
                ->select([
                    't.locationId',
                    DB::raw('DATE(t.created_at) as sale_date'),
                    DB::raw('SUM(COALESCE(p_agg.grossAmount, 0)) as grossAmount'),
                    DB::raw('SUM(COALESCE(p_agg.discounts, 0)) as discounts'),
                    DB::raw('SUM(COALESCE(pt_agg.totalAmount, 0)) as totalAmount'),
                ])
                ->groupBy('t.locationId', DB::raw('DATE(t.created_at)'))
                ->get()
        );

        // ---- Petshop (totals stored directly on the transaction row) ----
        // Petshop has no doctorId concept; exclude it when staffId filter is active.
        if (empty($staffIds)) {
            $allRows = $allRows->concat(
                DB::table('transactionpetshop as t')
                    ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                    ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                    ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                    ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                    ->when(!empty($paymentIds),  fn($q) => $q->whereIn('t.paymentMethod', $paymentIds))
                    ->select([
                        't.locationId',
                        DB::raw('DATE(t.created_at) as sale_date'),
                        DB::raw('COALESCE(SUM(t.totalAmount), 0) as grossAmount'),
                        DB::raw('COALESCE(SUM(t.totalDiscount), 0) as discounts'),
                        DB::raw('COALESCE(SUM(t.totalPayment), 0) as totalAmount'),
                    ])
                    ->groupBy('t.locationId', DB::raw('DATE(t.created_at)'))
                    ->get()
            );
        }

        // Merge all rows into [locationId][sale_date] = [grossAmount, discounts, totalAmount]
        $merged = [];
        foreach ($allRows as $row) {
            $locId = (int) $row->locationId;
            $date  = $row->sale_date;
            if (!isset($merged[$locId][$date])) {
                $merged[$locId][$date] = ['grossAmount' => 0.0, 'discounts' => 0.0, 'totalAmount' => 0.0];
            }
            $merged[$locId][$date]['grossAmount'] += (float) $row->grossAmount;
            $merged[$locId][$date]['discounts']   += (float) $row->discounts;
            $merged[$locId][$date]['totalAmount'] += (float) $row->totalAmount;
        }

        // Build per-location table rows
        $tableData = [];
        $totalData = [
            'grossAmount'   => 0.0,
            'discounts'     => 0.0,
            'netAmount'     => 0.0,
            'taxesAmount'   => 0,
            'chargesAmount' => 0,
            'totalAmount'   => 0.0,
        ];

        foreach ($locations as $locId => $loc) {
            $locId = (int) $locId;
            if (!isset($merged[$locId])) continue;

            $gross = array_sum(array_column($merged[$locId], 'grossAmount'));
            $disc  = array_sum(array_column($merged[$locId], 'discounts'));
            $total = array_sum(array_column($merged[$locId], 'totalAmount'));
            $net   = $gross - $disc;

            $tableData[] = [
                'location'      => $loc->locationName,
                'grossAmount'   => round($gross, 2),
                'discounts'     => round($disc, 2),
                'netAmount'     => round($net, 2),
                'taxesAmount'   => 0,
                'chargesAmount' => 0,
                'totalAmount'   => round($total, 2),
            ];

            $totalData['grossAmount']  += $gross;
            $totalData['discounts']    += $disc;
            $totalData['netAmount']    += $net;
            $totalData['totalAmount']  += $total;
        }

        $totalData['grossAmount'] = round($totalData['grossAmount'], 2);
        $totalData['discounts']   = round($totalData['discounts'],   2);
        $totalData['netAmount']   = round($totalData['netAmount'],   2);
        $totalData['totalAmount'] = round($totalData['totalAmount'], 2);

        return [$tableData, $totalData, $merged, $locations];
    }

    public function indexSalesByService(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchSalesByServiceData($request, $perPage, $page);

        return response()->json([
            'totalPagination' => $total,
            'data'            => $data,
        ]);
    }

    public function exportSalesByService(Request $request)
    {
        [$data] = $this->fetchSalesByServiceData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_By_Service.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Service');
        $sheet->setCellValue('B1', 'Quantity');
        $sheet->setCellValue('C1', 'Total (Rp)');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:C1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['serviceName']);
            $sheet->setCellValue("B{$row}", $item['quantity']);
            $sheet->setCellValue("C{$row}", $item['totalAmount']);

            $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'C') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales By Service.xlsx"',
        ]);
    }

    /**
     * Fetch aggregated service sales: {serviceName, quantity, totalAmount}.
     * Sources: clinic/hotel/salon payment items where serviceId IS NOT NULL.
     * Petshop excluded (no services). Returns: [$data, $total]
     */
    private function fetchSalesByServiceData(Request $request, int $perPage, int $page): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $paymentIds  = array_values(array_filter((array) $request->input('paymentId',  []), fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('categoryId', []), fn($v) => $v !== '' && $v !== null));
        $orderColumn = $request->input('orderColumn') ?: 'totalAmount';
        $orderValue  = $request->input('orderValue')  ?: 'desc';

        // Petshop (categoryId=4) has no services — only include clinic/hotel/salon
        $includeClinic = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel  = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon  = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);

        $sources = [];
        if ($includeClinic) $sources[] = ['tx' => 'transactionPetClinics', 'pay' => 'transaction_pet_clinic_payments', 'pt' => 'transaction_pet_clinic_payment_totals'];
        if ($includeHotel)  $sources[] = ['tx' => 'transaction_pet_hotels', 'pay' => 'transaction_pet_hotel_payments',  'pt' => 'transaction_pet_hotel_payment_totals'];
        if ($includeSalon)  $sources[] = ['tx' => 'transaction_pet_salons', 'pay' => 'transaction_pet_salon_payments',  'pt' => 'transaction_pet_salon_payment_totals'];

        // Aggregate by serviceId across sources
        $serviceAgg = []; // serviceId => [serviceName, quantity, totalAmount]

        foreach ($sources as $src) {
            $query = DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->join('services as s', 's.id', '=', 'p.serviceId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->whereNotNull('p.serviceId')
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds));

            if (!empty($paymentIds)) {
                $ptTable = $src['pt'];
                $query->whereExists(function ($sub) use ($ptTable, $paymentIds) {
                    $sub->from("{$ptTable} as pt")
                        ->whereColumn('pt.transactionId', 't.id')
                        ->whereIn('pt.paymentMethodId', $paymentIds)
                        ->where(fn($q2) => $q2->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'));
                });
            }

            $rows = $query
                ->select([
                    'p.serviceId',
                    's.fullName as serviceName',
                    DB::raw('SUM(COALESCE(p.quantity, 1)) as qty'),
                    DB::raw('SUM(COALESCE(p.priceOverall, 0)) as totalAmount'),
                ])
                ->groupBy('p.serviceId', 's.fullName')
                ->get();

            foreach ($rows as $r) {
                $sid = $r->serviceId;
                if (!isset($serviceAgg[$sid])) {
                    $serviceAgg[$sid] = ['serviceName' => $r->serviceName, 'quantity' => 0, 'totalAmount' => 0.0];
                }
                $serviceAgg[$sid]['quantity']    += (int) $r->qty;
                $serviceAgg[$sid]['totalAmount'] += (float) $r->totalAmount;
            }
        }

        $allRows = collect(array_values($serviceAgg));

        $allowedCols = ['serviceName', 'quantity', 'totalAmount'];
        if (!in_array($orderColumn, $allowedCols)) $orderColumn = 'totalAmount';
        $sorted = $orderValue === 'asc'
            ? $allRows->sortBy(fn($r)     => $r[$orderColumn])
            : $allRows->sortByDesc(fn($r) => $r[$orderColumn]);

        $total = $sorted->count();
        $items = $perPage > 0
            ? $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            : $sorted->values();

        $result = $items->map(fn($r) => [
            'serviceName' => $r['serviceName'],
            'quantity'    => (int) $r['quantity'],
            'totalAmount' => round($r['totalAmount'], 2),
        ])->values()->toArray();

        return [$result, $total];
    }

    public function indexSalesByProduct(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchSalesByProductData($request, $perPage, $page);

        return response()->json([
            'totalPagination' => $total,
            'data'            => $data,
        ]);
    }

    public function exportSalesByProduct(Request $request)
    {
        [$data] = $this->fetchSalesByProductData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_By_Product.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Product');
        $sheet->setCellValue('B1', 'Quantity');
        $sheet->setCellValue('C1', 'Total (Rp)');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:C1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['productName']);
            $sheet->setCellValue("B{$row}", $item['quantity']);
            $sheet->setCellValue("C{$row}", $item['totalAmount']);

            $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'C') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales By Product.xlsx"',
        ]);
    }

    /**
     * Fetch aggregated product sales: {productName, quantity, totalAmount}.
     * Sources: clinic/hotel/salon payment items (productId IS NOT NULL) + petshop detail.
     * Returns: [$data, $total]
     */
    private function fetchSalesByProductData(Request $request, int $perPage, int $page): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $paymentIds  = array_values(array_filter((array) $request->input('paymentId',  []), fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('categoryId', []), fn($v) => $v !== '' && $v !== null));
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'totalAmount';
        $orderValue  = $request->input('orderValue')  ?: 'desc';

        $includeClinic  = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel   = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon   = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);
        $includePetshop = empty($catIds) || in_array('4', $catIds) || in_array(4, $catIds);

        // Aggregate by productId => [productName, quantity, totalAmount]
        $productAgg = [];

        // ---- Clinic / Hotel / Salon payment items (productId IS NOT NULL) ----
        $sources = [];
        if ($includeClinic) $sources[] = ['tx' => 'transactionPetClinics', 'pay' => 'transaction_pet_clinic_payments', 'pt' => 'transaction_pet_clinic_payment_totals'];
        if ($includeHotel)  $sources[] = ['tx' => 'transaction_pet_hotels', 'pay' => 'transaction_pet_hotel_payments',  'pt' => 'transaction_pet_hotel_payment_totals'];
        if ($includeSalon)  $sources[] = ['tx' => 'transaction_pet_salons', 'pay' => 'transaction_pet_salon_payments',  'pt' => 'transaction_pet_salon_payment_totals'];

        foreach ($sources as $src) {
            $query = DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->join('products as pr', 'pr.id', '=', 'p.productId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->whereNotNull('p.productId')
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when($search, fn($q) => $q->where('pr.fullName', 'like', "%{$search}%"));

            if (!empty($paymentIds)) {
                $ptTable = $src['pt'];
                $query->whereExists(function ($sub) use ($ptTable, $paymentIds) {
                    $sub->from("{$ptTable} as pt")
                        ->whereColumn('pt.transactionId', 't.id')
                        ->whereIn('pt.paymentMethodId', $paymentIds)
                        ->where(fn($q2) => $q2->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'));
                });
            }

            $rows = $query
                ->select([
                    'p.productId',
                    'pr.fullName as productName',
                    DB::raw('SUM(COALESCE(p.quantity, 1)) as qty'),
                    DB::raw('SUM(COALESCE(p.priceOverall, 0)) as totalAmount'),
                ])
                ->groupBy('p.productId', 'pr.fullName')
                ->get();

            foreach ($rows as $r) {
                $pid = $r->productId;
                if (!isset($productAgg[$pid])) {
                    $productAgg[$pid] = ['productName' => $r->productName, 'quantity' => 0, 'totalAmount' => 0.0];
                }
                $productAgg[$pid]['quantity']    += (int) $r->qty;
                $productAgg[$pid]['totalAmount'] += (float) $r->totalAmount;
            }
        }

        // ---- Petshop: transactionpetshopdetail ----
        if ($includePetshop) {
            $psQuery = DB::table('transactionpetshopdetail as d')
                ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
                ->join('products as pr', 'pr.id', '=', 'd.productId')
                ->where(fn($q) => $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($paymentIds),  fn($q) => $q->whereIn('t.paymentMethod', $paymentIds))
                ->when($search, fn($q) => $q->where('pr.fullName', 'like', "%{$search}%"));

            $psRows = $psQuery
                ->select([
                    'd.productId',
                    'pr.fullName as productName',
                    DB::raw('SUM(COALESCE(d.quantity, 1)) as qty'),
                    DB::raw('SUM(COALESCE(d.total_final_price, d.quantity * d.price, 0)) as totalAmount'),
                ])
                ->groupBy('d.productId', 'pr.fullName')
                ->get();

            foreach ($psRows as $r) {
                $pid = $r->productId;
                if (!isset($productAgg[$pid])) {
                    $productAgg[$pid] = ['productName' => $r->productName, 'quantity' => 0, 'totalAmount' => 0.0];
                }
                $productAgg[$pid]['quantity']    += (int) $r->qty;
                $productAgg[$pid]['totalAmount'] += (float) $r->totalAmount;
            }
        }

        $allRows = collect(array_values($productAgg));

        $allowedCols = ['productName', 'quantity', 'totalAmount'];
        if (!in_array($orderColumn, $allowedCols)) $orderColumn = 'totalAmount';
        $sorted = $orderValue === 'asc'
            ? $allRows->sortBy(fn($r)     => $r[$orderColumn])
            : $allRows->sortByDesc(fn($r) => $r[$orderColumn]);

        $total = $sorted->count();
        $items = $perPage > 0
            ? $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            : $sorted->values();

        $result = $items->map(fn($r) => [
            'productName' => $r['productName'],
            'quantity'    => (int) $r['quantity'],
            'totalAmount' => round($r['totalAmount'], 2),
        ])->values()->toArray();

        return [$result, $total];
    }

    public function indexPaymentList(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchPaymentListData($request, $perPage, $page);

        return response()->json([
            'totalPagination' => $total,
            'data'            => $data,
        ]);
    }

    public function exportPaymentList(Request $request)
    {
        [$data] = $this->fetchPaymentListData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_Payment_List.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Sale');
        $sheet->setCellValue('B1', 'Location');
        $sheet->setCellValue('C1', 'Method');
        $sheet->setCellValue('D1', 'Paid');
        $sheet->setCellValue('E1', 'Created By');
        $sheet->setCellValue('F1', 'Created At');
        $sheet->setCellValue('G1', 'Amount (Rp)');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $paidAt    = $item['paidAt']    ? Carbon::parse($item['paidAt'])->locale('en')->isoFormat('D MMMM YYYY HH:mm')    : '';
            $createdAt = $item['createdAt'] ? Carbon::parse($item['createdAt'])->locale('en')->isoFormat('D MMMM YYYY HH:mm') : '';

            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['paymentMethod']);
            $sheet->setCellValue("D{$row}", $paidAt);
            $sheet->setCellValue("E{$row}", $item['createdBy']);
            $sheet->setCellValue("F{$row}", $createdAt);
            $sheet->setCellValue("G{$row}", $item['totalAmount']);

            $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Payment List.xlsx"',
        ]);
    }

    public function indexDetails(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchSalesDetailsData($request, $perPage, $page);

        return response()->json([
            'totalPagination' => $total,
            'data'            => $data,
        ]);
    }

    public function exportDetails(Request $request)
    {
        [$data] = $this->fetchSalesDetailsData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_Details.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Sale');
        $sheet->setCellValue('B1', 'Reference');
        $sheet->setCellValue('C1', 'Location');
        $sheet->setCellValue('D1', 'Sale Date');
        $sheet->setCellValue('E1', 'Status');
        $sheet->setCellValue('F1', 'Items');
        $sheet->setCellValue('G1', 'Total (Rp)');
        $sheet->setCellValue('H1', 'Payments (Rp)');
        $sheet->setCellValue('I1', 'Payment');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:I1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $itemsString = implode(', ', $item['items']);

            $paymentMethodStrings = [];
            foreach ($item['paymentMethod'] as $payment) {
                $paymentMethodStrings[] = $payment['method'] . ' (' . number_format($payment['amount'], 0, ',', '.') . ' Rp) - ' . $payment['date'];
            }
            $paymentMethodString = implode('; ', $paymentMethodStrings);

            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['refNumber']);
            $sheet->setCellValue("C{$row}", $item['location']);
            $sheet->setCellValue("D{$row}", $item['saleDate']);
            $sheet->setCellValue("E{$row}", $item['status']);
            $sheet->setCellValue("F{$row}", $itemsString);
            $sheet->setCellValue("G{$row}", $item['totalAmount']);
            $sheet->setCellValue("H{$row}", $paymentMethodString);
            $sheet->setCellValue("I{$row}", $item['payment']);

            $sheet->getStyle("A{$row}:I{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Sales Details.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Details.xlsx"',
        ]);
    }

    /**
     * Fetch paginated transaction detail rows from all sources (clinic, hotel, salon, petshop).
     *
     * invoiceCategoryId filter: 1=clinic, 2=hotel, 3=salon, 4=petshop
     *
     * Returns: [$data, $total]
     */
    private function fetchSalesDetailsData(Request $request, int $perPage, int $page): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId',          []), fn($v) => $v !== '' && $v !== null));
        $statusIds   = array_values(array_filter((array) $request->input('statusId',            []), fn($v) => $v !== '' && $v !== null));
        $paymentIds  = array_values(array_filter((array) $request->input('paymentId',           []), fn($v) => $v !== '' && $v !== null));
        $staffIds    = array_values(array_filter((array) $request->input('staffId',             []), fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('invoiceCategoryId',   []), fn($v) => $v !== '' && $v !== null));
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'saleDate';
        $orderValue  = $request->input('orderValue')  ?: 'desc';

        // Map invoiceCategoryId: 1=clinic, 2=hotel, 3=salon, 4=petshop
        $includeClinic  = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel   = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon   = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);
        $includePetshop = empty($catIds) || in_array('4', $catIds) || in_array(4, $catIds);

        $allTx = collect();

        // ---- Clinic/Hotel/Salon share the same query shape ----
        $sources = [];
        if ($includeClinic) $sources[] = ['table' => 'transactionPetClinics',  'type' => 'clinic',  'refCol' => 'bookingId'];
        if ($includeHotel)  $sources[] = ['table' => 'transaction_pet_hotels',  'type' => 'hotel',   'refCol' => 'bookingId'];
        if ($includeSalon)  $sources[] = ['table' => 'transaction_pet_salons',  'type' => 'salon',   'refCol' => 'bookingId'];

        foreach ($sources as $src) {
            $query = DB::table("{$src['table']} as t")
                ->join('location as l', 't.locationId', '=', 'l.id')
                ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($staffIds),    fn($q) => $q->whereIn('t.doctorId', $staffIds))
                ->when($search, fn($q) => $q->where('t.registrationNo', 'like', "%{$search}%"))
                ->select([
                    't.id',
                    't.registrationNo as saleId',
                    DB::raw("IFNULL(t.{$src['refCol']}, '') as refNumber"),
                    'l.locationName as location',
                    't.status',
                    DB::raw('DATE(t.created_at) as saleDate'),
                    DB::raw("'{$src['type']}' as sourceType"),
                ]);

            // Filter by payment method existence in payment_totals
            if (!empty($paymentIds)) {
                $ptTable = "transaction_pet_{$src['type']}_payment_totals";
                $query->whereExists(function ($sub) use ($ptTable, $paymentIds) {
                    $sub->from("{$ptTable} as pt")
                        ->whereColumn('pt.transactionId', 't.id')
                        ->whereIn('pt.paymentMethodId', $paymentIds)
                        ->where(function ($q2) { $q2->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); });
                });
            }

            $allTx = $allTx->concat($query->get());
        }

        // ---- Petshop (different structure, no doctorId) ----
        if ($includePetshop && empty($staffIds)) {
            $allTx = $allTx->concat(
                DB::table('transactionpetshop as t')
                    ->join('location as l', 't.locationId', '=', 'l.id')
                    ->where(function ($q) { $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'); })
                    ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                    ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                    ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                    ->when(!empty($paymentIds),  fn($q) => $q->whereIn('t.paymentMethod', $paymentIds))
                    ->when($search, fn($q) => $q->where('t.registrationNo', 'like', "%{$search}%"))
                    ->select([
                        't.id',
                        't.registrationNo as saleId',
                        DB::raw("IFNULL(t.no_nota, '') as refNumber"),
                        'l.locationName as location',
                        DB::raw("t.verificationStatus as status"),
                        DB::raw('DATE(t.created_at) as saleDate'),
                        DB::raw("'petshop' as sourceType"),
                    ])
                    ->get()
            );
        }

        // Sort in PHP
        $allowedCols = ['saleId', 'location', 'saleDate', 'status', 'refNumber'];
        if (!in_array($orderColumn, $allowedCols)) $orderColumn = 'saleDate';
        $sorted = $orderValue === 'asc'
            ? $allTx->sortBy(fn($r) => $r->$orderColumn ?? '')
            : $allTx->sortByDesc(fn($r) => $r->$orderColumn ?? '');

        $total = $sorted->count();

        // Paginate
        $page_items = $perPage > 0
            ? $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            : $sorted->values();

        if ($page_items->isEmpty()) return [[], $total];

        // Group IDs by source type for batch queries
        $idsByType = ['clinic' => [], 'hotel' => [], 'salon' => [], 'petshop' => []];
        foreach ($page_items as $tx) {
            $idsByType[$tx->sourceType][] = (int) $tx->id;
        }

        // ---- Batch-fetch items (products + services) ----
        $itemsMap = []; // key = sourceType_id => string[]

        // Clinic items
        if (!empty($idsByType['clinic'])) {
            // From payments (products + services referenced in payment line items)
            $rows = DB::table('transaction_pet_clinic_payments as pmt')
                ->leftJoin('products as p', 'p.id', '=', 'pmt.productId')
                ->leftJoin('services as s', 's.id', '=', 'pmt.serviceId')
                ->whereIn('pmt.transactionId', $idsByType['clinic'])
                ->where(function ($q) { $q->where('pmt.isDeleted', 0)->orWhereNull('pmt.isDeleted'); })
                ->select(['pmt.transactionId', 'p.fullName as productName', 's.fullName as serviceName'])
                ->get();
            foreach ($rows as $r) {
                $key = 'clinic_' . $r->transactionId;
                if ($r->productName) $itemsMap[$key][] = $r->productName;
                if ($r->serviceName) $itemsMap[$key][] = $r->serviceName;
            }
            // Also from dedicated services table
            $rows2 = DB::table('transaction_pet_clinic_services as cs')
                ->join('services as s', 's.id', '=', 'cs.serviceId')
                ->whereIn('cs.transactionPetClinicId', $idsByType['clinic'])
                ->where(function ($q) { $q->where('cs.isDeleted', 0)->orWhereNull('cs.isDeleted'); })
                ->select(['cs.transactionPetClinicId as transactionId', 's.fullName as serviceName'])
                ->get();
            foreach ($rows2 as $r) {
                $key = 'clinic_' . $r->transactionId;
                if ($r->serviceName) $itemsMap[$key][] = $r->serviceName;
            }
        }

        // Hotel items
        if (!empty($idsByType['hotel'])) {
            $rows = DB::table('transaction_pet_hotel_payments as pmt')
                ->leftJoin('products as p', 'p.id', '=', 'pmt.productId')
                ->leftJoin('services as s', 's.id', '=', 'pmt.serviceId')
                ->whereIn('pmt.transactionId', $idsByType['hotel'])
                ->where(function ($q) { $q->where('pmt.isDeleted', 0)->orWhereNull('pmt.isDeleted'); })
                ->select(['pmt.transactionId', 'p.fullName as productName', 's.fullName as serviceName'])
                ->get();
            foreach ($rows as $r) {
                $key = 'hotel_' . $r->transactionId;
                if ($r->productName) $itemsMap[$key][] = $r->productName;
                if ($r->serviceName) $itemsMap[$key][] = $r->serviceName;
            }
            $rows2 = DB::table('transactionPetHotelTreatmentServices as ts')
                ->join('services as s', 's.id', '=', 'ts.serviceId')
                ->whereIn('ts.transactionId', $idsByType['hotel'])
                ->where(function ($q) { $q->where('ts.isDeleted', 0)->orWhereNull('ts.isDeleted'); })
                ->select(['ts.transactionId', 's.fullName as serviceName'])
                ->get();
            foreach ($rows2 as $r) {
                $key = 'hotel_' . $r->transactionId;
                if ($r->serviceName) $itemsMap[$key][] = $r->serviceName;
            }
        }

        // Salon items
        if (!empty($idsByType['salon'])) {
            $rows = DB::table('transaction_pet_salon_payments as pmt')
                ->leftJoin('products as p', 'p.id', '=', 'pmt.productId')
                ->leftJoin('services as s', 's.id', '=', 'pmt.serviceId')
                ->whereIn('pmt.transactionId', $idsByType['salon'])
                ->where(function ($q) { $q->where('pmt.isDeleted', 0)->orWhereNull('pmt.isDeleted'); })
                ->select(['pmt.transactionId', 'p.fullName as productName', 's.fullName as serviceName'])
                ->get();
            foreach ($rows as $r) {
                $key = 'salon_' . $r->transactionId;
                if ($r->productName) $itemsMap[$key][] = $r->productName;
                if ($r->serviceName) $itemsMap[$key][] = $r->serviceName;
            }
            $rows2 = DB::table('transactionPetSalonTreatmentServices as ts')
                ->join('services as s', 's.id', '=', 'ts.serviceId')
                ->whereIn('ts.transactionId', $idsByType['salon'])
                ->where(function ($q) { $q->where('ts.isDeleted', 0)->orWhereNull('ts.isDeleted'); })
                ->select(['ts.transactionId', 's.fullName as serviceName'])
                ->get();
            foreach ($rows2 as $r) {
                $key = 'salon_' . $r->transactionId;
                if ($r->serviceName) $itemsMap[$key][] = $r->serviceName;
            }
        }

        // Petshop items
        if (!empty($idsByType['petshop'])) {
            $rows = DB::table('transactionpetshopdetail as d')
                ->join('products as p', 'p.id', '=', 'd.productId')
                ->whereIn('d.transactionpetshopId', $idsByType['petshop'])
                ->where(function ($q) { $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'); })
                ->select(['d.transactionpetshopId as transactionId', 'p.fullName as productName'])
                ->get();
            foreach ($rows as $r) {
                $key = 'petshop_' . $r->transactionId;
                if ($r->productName) $itemsMap[$key][] = $r->productName;
            }
        }

        // Remove duplicate item names per transaction
        foreach ($itemsMap as $k => $names) {
            $itemsMap[$k] = array_values(array_unique($names));
        }

        // ---- Batch-fetch payment methods + total amounts ----
        $paymentsMap = []; // key = sourceType_id => [{amount, method, date, isPaid}]
        $amountsMap  = []; // key = sourceType_id => float

        // Clinic/Hotel/Salon payment totals
        foreach (['clinic', 'hotel', 'salon'] as $type) {
            if (empty($idsByType[$type])) continue;
            $ptTable = "transaction_pet_{$type}_payment_totals";
            $rows = DB::table("{$ptTable} as pt")
                ->join('paymentmethod as pm', 'pm.id', '=', 'pt.paymentMethodId')
                ->whereIn('pt.transactionId', $idsByType[$type])
                ->where(function ($q) { $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'); })
                ->select([
                    'pt.transactionId',
                    'pm.name as method',
                    'pt.amountPaid as amount',
                    DB::raw('DATE(pt.created_at) as payDate'),
                    'pt.isPayed',
                ])
                ->get();
            foreach ($rows as $r) {
                $key = "{$type}_{$r->transactionId}";
                $paymentsMap[$key][] = [
                    'method' => $r->method,
                    'amount' => (float) $r->amount,
                    'date'   => $r->payDate ?? '',
                    'isPaid' => (bool) $r->isPayed,
                ];
                $amountsMap[$key] = ($amountsMap[$key] ?? 0) + (float) $r->amount;
            }
        }

        // Petshop payment (single method from main transaction)
        if (!empty($idsByType['petshop'])) {
            $rows = DB::table('transactionpetshop as t')
                ->leftJoin('paymentmethod as pm', 'pm.id', '=', 't.paymentMethod')
                ->whereIn('t.id', $idsByType['petshop'])
                ->select([
                    't.id as transactionId',
                    'pm.name as method',
                    't.totalPayment as amount',
                    DB::raw('DATE(t.created_at) as payDate'),
                    't.isPayed',
                ])
                ->get();
            foreach ($rows as $r) {
                $key = 'petshop_' . $r->transactionId;
                $paymentsMap[$key][] = [
                    'method' => $r->method ?? '-',
                    'amount' => (float) $r->amount,
                    'date'   => $r->payDate ?? '',
                    'isPaid' => (bool) $r->isPayed,
                ];
                $amountsMap[$key] = (float) $r->amount;
            }
        }

        // ---- Assemble final rows ----
        $result = $page_items->map(function ($tx) use ($itemsMap, $paymentsMap, $amountsMap) {
            $key      = $tx->sourceType . '_' . $tx->id;
            $payments = $paymentsMap[$key] ?? [];
            $isPaid   = !empty($payments) && collect($payments)->every(fn($p) => $p['isPaid'] ?? false);

            return [
                'saleId'        => $tx->saleId,
                'refNumber'     => $tx->refNumber,
                'location'      => $tx->location,
                'saleDate'      => $tx->saleDate,
                'status'        => $tx->status,
                'items'         => $itemsMap[$key] ?? [],
                'totalAmount'   => round($amountsMap[$key] ?? 0, 2),
                'paymentMethod' => array_map(fn($p) => [
                    'amount' => $p['amount'],
                    'method' => $p['method'],
                    'date'   => $p['date'],
                ], $payments),
                'payment' => $isPaid ? 'Paid' : 'Unpaid',
            ];
        })->values()->toArray();

        return [$result, $total];
    }

    public function indexUnpaid(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchUnpaidData($request, $perPage, $page);

        return response()->json([
            'totalPagination' => $total,
            'data'            => $data,
        ]);
    }

    public function exportUnpaid(Request $request)
    {
        [$data] = $this->fetchUnpaidData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_Unpaid.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Sale ID');
        $sheet->setCellValue('B1', 'Location');
        $sheet->setCellValue('C1', 'Due Date');
        $sheet->setCellValue('D1', 'Overdue');
        $sheet->setCellValue('E1', 'Customer');
        $sheet->setCellValue('F1', 'Phone');
        $sheet->setCellValue('G1', 'Total (Rp)');
        $sheet->setCellValue('H1', 'Paid (Rp)');
        $sheet->setCellValue('I1', 'Outstanding (Rp)');
        $sheet->setCellValue('J1', 'Reference');

        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:J1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $dueDateFmt = $item['dueDate'] ? Carbon::parse($item['dueDate'])->locale('en')->isoFormat('D MMMM YYYY') : '';

            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $dueDateFmt);
            $sheet->setCellValue("D{$row}", $item['overDue']);
            $sheet->setCellValue("E{$row}", $item['customerName']);
            $sheet->setCellValue("F{$row}", $item['phoneNo']);
            $sheet->setCellValue("G{$row}", $item['totalAmount']);
            $sheet->setCellValue("H{$row}", $item['paidAmount']);
            $sheet->setCellValue("I{$row}", $item['outstandingAmount']);
            $sheet->setCellValue("J{$row}", $item['refNum']);

            $sheet->getStyle("A{$row}:J{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Unpaid.xlsx"',
        ]);
    }

    public function indexDiscountSummary(Request $request)
    {
        // Current period: last 30 days; Previous period: 30 days before that
        $currentEnd    = Carbon::today();
        $currentStart  = Carbon::today()->subDays(29);
        $previousEnd   = Carbon::today()->subDays(30);
        $previousStart = Carbon::today()->subDays(59);

        $currentFmt  = [$currentStart->format('Y-m-d'),  $currentEnd->format('Y-m-d')];
        $previousFmt = [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')];

        // Clinic/Hotel/Salon payment tables: discountAmount column
        $clhsSources = [
            ['pay' => 'transaction_pet_clinic_payments', 'tx' => 'transactionPetClinics'],
            ['pay' => 'transaction_pet_hotel_payments',  'tx' => 'transaction_pet_hotels'],
            ['pay' => 'transaction_pet_salon_payments',  'tx' => 'transaction_pet_salons'],
        ];

        $currentDayDiscount  = []; // date => float
        $previousDayDiscount = [];
        $staffDiscount       = []; // doctorId => float (current period only)

        foreach ($clhsSources as $src) {
            // Current period: group by date + doctorId
            $rows = DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->where('p.discountAmount', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
                ->select([
                    DB::raw('DATE(t.created_at) as sale_date'),
                    DB::raw('COALESCE(t.doctorId, 0) as doctorId'),
                    DB::raw('SUM(p.discountAmount) as disc'),
                ])
                ->groupBy(DB::raw('DATE(t.created_at)'), DB::raw('COALESCE(t.doctorId, 0)'))
                ->get();

            foreach ($rows as $r) {
                $currentDayDiscount[$r->sale_date] = ($currentDayDiscount[$r->sale_date] ?? 0) + (float) $r->disc;
                $dId = (int) $r->doctorId;
                $staffDiscount[$dId] = ($staffDiscount[$dId] ?? 0) + (float) $r->disc;
            }

            // Previous period: group by date only
            $prevRows = DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->where('p.discountAmount', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), $previousFmt)
                ->select([DB::raw('DATE(t.created_at) as sale_date'), DB::raw('SUM(p.discountAmount) as disc')])
                ->groupBy(DB::raw('DATE(t.created_at)'))
                ->get();
            foreach ($prevRows as $r) {
                $previousDayDiscount[$r->sale_date] = ($previousDayDiscount[$r->sale_date] ?? 0) + (float) $r->disc;
            }
        }

        // Petshop: discount column in transactionpetshopdetail
        $psCurrent = DB::table('transactionpetshopdetail as d')
            ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
            ->where(fn($q) => $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'))
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->where('d.discount', '>', 0)
            ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
            ->select([DB::raw('DATE(t.created_at) as sale_date'), DB::raw('SUM(d.discount) as disc')])
            ->groupBy(DB::raw('DATE(t.created_at)'))
            ->get();
        foreach ($psCurrent as $r) {
            $currentDayDiscount[$r->sale_date] = ($currentDayDiscount[$r->sale_date] ?? 0) + (float) $r->disc;
        }

        $psPrevious = DB::table('transactionpetshopdetail as d')
            ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
            ->where(fn($q) => $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'))
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->where('d.discount', '>', 0)
            ->whereBetween(DB::raw('DATE(t.created_at)'), $previousFmt)
            ->select([DB::raw('DATE(t.created_at) as sale_date'), DB::raw('SUM(d.discount) as disc')])
            ->groupBy(DB::raw('DATE(t.created_at)'))
            ->get();
        foreach ($psPrevious as $r) {
            $previousDayDiscount[$r->sale_date] = ($previousDayDiscount[$r->sale_date] ?? 0) + (float) $r->disc;
        }

        // Build 30-day chart series
        $chartDates     = [];
        $currentSeries  = [];
        $previousSeries = [];
        for ($i = 29; $i >= 0; $i--) {
            $cDate = Carbon::today()->subDays($i)->format('Y-m-d');
            $pDate = Carbon::today()->subDays($i + 30)->format('Y-m-d');
            $chartDates[]     = Carbon::today()->subDays($i)->format('d M');
            $currentSeries[]  = $currentDayDiscount[$cDate]  ?? 0;
            $previousSeries[] = $previousDayDiscount[$pDate] ?? 0;
        }

        $currentTotal  = array_sum($currentSeries);
        $previousTotal = array_sum($previousSeries);
        $pct    = $previousTotal > 0 ? round(abs($currentTotal - $previousTotal) / $previousTotal * 100, 2) : 0;
        $isLoss = $currentTotal < $previousTotal ? 1 : 0;

        // itemsDicounted: count items with discount in current period
        $itemsDiscCount = 0;
        foreach ($clhsSources as $src) {
            $itemsDiscCount += (int) DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where('p.discountAmount', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
                ->count();
        }
        $itemsDiscCount += (int) DB::table('transactionpetshopdetail as d')
            ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
            ->where(fn($q) => $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'))
            ->where('d.discount', '>', 0)
            ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
            ->count();

        // salesDiscounted: count distinct transactions with any discount in current period
        $salesDiscCount = 0;
        foreach ($clhsSources as $src) {
            $salesDiscCount += (int) DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where('p.discountAmount', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
                ->distinct()->count('p.transactionId');
        }
        $salesDiscCount += (int) DB::table('transactionpetshopdetail as d')
            ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
            ->where(fn($q) => $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'))
            ->where('d.discount', '>', 0)
            ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
            ->distinct()->count('d.transactionpetshopId');

        // chartsDiscountValueByStaff: top 6 doctors by discount, rest = "Other"
        $staffLabels = [];
        $staffSeries = [];
        if (!empty($staffDiscount)) {
            arsort($staffDiscount);
            $doctorIds = array_values(array_filter(array_keys($staffDiscount), fn($id) => $id > 0));
            $doctorsMap = [];
            if (!empty($doctorIds)) {
                $doctorsMap = DB::table('users')
                    ->whereIn('id', $doctorIds)
                    ->select(['id', 'firstName', 'lastName'])
                    ->get()
                    ->keyBy('id')
                    ->toArray();
            }

            $topN = 6;
            $count = 0;
            $otherDisc = 0.0;
            foreach ($staffDiscount as $dId => $disc) {
                if ($count < $topN) {
                    if ($dId === 0 || !isset($doctorsMap[$dId])) {
                        $staffLabels[] = 'Not Set';
                    } else {
                        $d = $doctorsMap[$dId];
                        $staffLabels[] = trim("{$d->firstName} {$d->lastName}");
                    }
                    $staffSeries[] = round($disc, 2);
                    $count++;
                } else {
                    $otherDisc += $disc;
                }
            }
            if ($otherDisc > 0) {
                $staffLabels[] = 'Other';
                $staffSeries[] = round($otherDisc, 2);
            }
        }

        return response()->json([
            'charts' => [
                'series'     => [
                    ['name' => 'Previous', 'data' => $previousSeries],
                    ['name' => 'Current',  'data' => $currentSeries],
                ],
                'categories' => $chartDates,
            ],
            'totalDiscount'   => ['total' => round($currentTotal, 2),  'percentage' => $pct,  'isLoss' => $isLoss],
            'itemsDicounted'  => ['total' => $itemsDiscCount,           'percentage' => 0,     'isLoss' => null],
            'salesDiscounted' => ['total' => $salesDiscCount,           'percentage' => 0,     'isLoss' => null],
            'chartsDiscountValueByStaff' => ['labels' => $staffLabels, 'series' => $staffSeries],
        ]);
    }

    public function indexPaymentSummary(Request $request)
    {
        // Current month vs previous month
        $currentStart  = Carbon::now()->startOfMonth();
        $currentEnd    = Carbon::now()->endOfMonth();
        $previousStart = Carbon::now()->subMonth()->startOfMonth();
        $previousEnd   = Carbon::now()->subMonth()->endOfMonth();

        $currentFmt  = [$currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d')];
        $previousFmt = [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')];

        $currentByMethod  = []; // methodId => totalAmount
        $previousByMethod = [];

        // Clinic / Hotel / Salon: from payment_totals
        foreach (['clinic', 'hotel', 'salon'] as $type) {
            $txTable = $type === 'clinic' ? 'transactionPetClinics' : "transaction_pet_{$type}s";
            $ptTable = "transaction_pet_{$type}_payment_totals";

            $rows = DB::table("{$ptTable} as pt")
                ->join("{$txTable} as t", 't.id', '=', 'pt.transactionId')
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->whereBetween(DB::raw('DATE(pt.created_at)'), $currentFmt)
                ->select(['pt.paymentMethodId', DB::raw('SUM(pt.amountPaid) as total')])
                ->groupBy('pt.paymentMethodId')
                ->get();
            foreach ($rows as $r) {
                $currentByMethod[$r->paymentMethodId] = ($currentByMethod[$r->paymentMethodId] ?? 0) + (float) $r->total;
            }

            $prevRows = DB::table("{$ptTable} as pt")
                ->join("{$txTable} as t", 't.id', '=', 'pt.transactionId')
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->whereBetween(DB::raw('DATE(pt.created_at)'), $previousFmt)
                ->select(['pt.paymentMethodId', DB::raw('SUM(pt.amountPaid) as total')])
                ->groupBy('pt.paymentMethodId')
                ->get();
            foreach ($prevRows as $r) {
                $previousByMethod[$r->paymentMethodId] = ($previousByMethod[$r->paymentMethodId] ?? 0) + (float) $r->total;
            }
        }

        // Petshop: totalPayment grouped by paymentMethod
        $psRows = DB::table('transactionpetshop as t')
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->whereBetween(DB::raw('DATE(t.created_at)'), $currentFmt)
            ->whereNotNull('t.paymentMethod')
            ->select(['t.paymentMethod as paymentMethodId', DB::raw('SUM(t.totalPayment) as total')])
            ->groupBy('t.paymentMethod')
            ->get();
        foreach ($psRows as $r) {
            $currentByMethod[$r->paymentMethodId] = ($currentByMethod[$r->paymentMethodId] ?? 0) + (float) $r->total;
        }

        $psPrevRows = DB::table('transactionpetshop as t')
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->whereBetween(DB::raw('DATE(t.created_at)'), $previousFmt)
            ->whereNotNull('t.paymentMethod')
            ->select(['t.paymentMethod as paymentMethodId', DB::raw('SUM(t.totalPayment) as total')])
            ->groupBy('t.paymentMethod')
            ->get();
        foreach ($psPrevRows as $r) {
            $previousByMethod[$r->paymentMethodId] = ($previousByMethod[$r->paymentMethodId] ?? 0) + (float) $r->total;
        }

        // Load all payment methods for the table/chart
        $allMethods = DB::table('paymentmethod')
            ->where(fn($q) => $q->where('isDeleted', 0)->orWhereNull('isDeleted'))
            ->select(['id', 'name'])
            ->get();

        $tableData   = [];
        $chartLabels = [];
        $chartSeries = [];
        foreach ($allMethods as $method) {
            $amount = $currentByMethod[$method->id] ?? 0;
            $tableData[]   = ['method' => $method->name, 'totalAmount' => round($amount, 2), 'refundAmount' => 0, 'netAmount' => round($amount, 2)];
            $chartLabels[] = $method->name;
            $chartSeries[] = round($amount, 2);
        }

        $currentTotal  = array_sum($currentByMethod);
        $previousTotal = array_sum($previousByMethod);
        $pct    = $previousTotal > 0 ? round(abs($currentTotal - $previousTotal) / $previousTotal * 100, 2) : 0;
        $isLoss = $currentTotal < $previousTotal ? 1 : 0;

        return response()->json([
            'totalPayments' => ['total' => round($currentTotal, 2),  'percentage' => $pct,  'isLoss' => $isLoss],
            'totalRefunds'  => ['total' => 0,                         'percentage' => 0,     'isLoss' => 0],
            'netPayments'   => ['total' => round($currentTotal, 2),  'percentage' => $pct,  'isLoss' => $isLoss],
            'chartsDiscountValueByStaff' => ['labels' => $chartLabels, 'series' => $chartSeries],
            'table' => ['data' => $tableData, 'totalPagination' => count($tableData)],
        ]);
    }

    public function indexNetIncome(Request $request)
    {
        // ---- Revenue: aggregate amountPaid from payment_totals (clinic/hotel/salon) + totalPayment (petshop) ----
        $revenueByMonth = []; // "YYYY-MM" => float

        foreach (['clinic', 'hotel', 'salon'] as $type) {
            $txTable = $type === 'clinic' ? 'transactionPetClinics' : "transaction_pet_{$type}s";
            $ptTable = "transaction_pet_{$type}_payment_totals";

            $rows = DB::table("{$ptTable} as pt")
                ->join("{$txTable} as t", 't.id', '=', 'pt.transactionId')
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->select([
                    DB::raw('DATE_FORMAT(pt.created_at, "%Y-%m") as ym'),
                    DB::raw('SUM(pt.amountPaid) as total'),
                ])
                ->groupBy(DB::raw('DATE_FORMAT(pt.created_at, "%Y-%m")'))
                ->get();

            foreach ($rows as $r) {
                $revenueByMonth[$r->ym] = ($revenueByMonth[$r->ym] ?? 0) + (float) $r->total;
            }
        }

        $psRows = DB::table('transactionpetshop as t')
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->select([
                DB::raw('DATE_FORMAT(t.created_at, "%Y-%m") as ym'),
                DB::raw('SUM(t.totalPayment) as total'),
            ])
            ->groupBy(DB::raw('DATE_FORMAT(t.created_at, "%Y-%m")'))
            ->get();
        foreach ($psRows as $r) {
            $revenueByMonth[$r->ym] = ($revenueByMonth[$r->ym] ?? 0) + (float) $r->total;
        }

        // ---- Expenses: aggregate grandTotal from expenses table ----
        $expensesByMonth = []; // "YYYY-MM" => float
        $expRows = DB::table('expenses')
            ->where(fn($q) => $q->where('isDeleted', 0)->orWhereNull('isDeleted'))
            ->select([
                DB::raw('DATE_FORMAT(transactionDate, "%Y-%m") as ym'),
                DB::raw('SUM(grandTotal) as total'),
            ])
            ->groupBy(DB::raw('DATE_FORMAT(transactionDate, "%Y-%m")'))
            ->get();
        foreach ($expRows as $r) {
            $expensesByMonth[$r->ym] = (float) $r->total;
        }

        // ---- Merge and sort all months ----
        $allMonths = array_unique(array_merge(array_keys($revenueByMonth), array_keys($expensesByMonth)));
        sort($allMonths);

        $tableData      = [];
        $chartCategories = [];
        $chartRevenue   = [];
        $chartExpenses  = [];
        $chartNetIncome = [];
        $totalRevenue   = 0.0;
        $totalExpenses  = 0.0;

        foreach ($allMonths as $ym) {
            $rev = $revenueByMonth[$ym]  ?? 0.0;
            $exp = $expensesByMonth[$ym] ?? 0.0;
            $net = $rev - $exp;

            $period = Carbon::parse($ym . '-01')->locale('en')->isoFormat('MMM YYYY');

            $tableData[]       = ['period' => $period, 'revenueAmount' => round($rev, 2), 'expensesAmount' => round($exp, 2), 'netIncome' => round($net, 2)];
            $chartCategories[] = $period;
            $chartRevenue[]    = round($rev, 2);
            $chartExpenses[]   = round($exp, 2);
            $chartNetIncome[]  = round($net, 2);

            $totalRevenue  += $rev;
            $totalExpenses += $exp;
        }

        $totalNet = $totalRevenue - $totalExpenses;

        return response()->json([
            'totalRevenue'  => ['total' => round($totalRevenue, 2)],
            'totalExpenses' => ['total' => round($totalExpenses, 2)],
            'netIncome'     => ['total' => round($totalNet, 2)],
            'chartsRevenueAndExpenses' => [
                'series' => [
                    ['name' => 'Revenue',  'data' => $chartRevenue],
                    ['name' => 'Expenses', 'data' => $chartExpenses],
                ],
                'categories' => $chartCategories,
            ],
            'chartsNetIncome' => [
                'series' => [['name' => 'Net Income', 'data' => $chartNetIncome]],
                'categories' => $chartCategories,
            ],
            'table' => ['data' => $tableData, 'totalPagination' => count($tableData)],
        ]);
    }

    public function indexDailyAudit(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchDailyAuditData($request, $perPage, $page);
        return response()->json(['table' => ['data' => $data, 'totalPagination' => $total]]);
    }

    public function exportDailyAudit(Request $request)
    {
        [$rows, ] = $this->fetchDailyAuditData($request, 0, 1);
        $data = ['table' => ['data' => $rows]];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_Daily_Audit.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        // Menulis header kolom pada baris 1 dan 2
        $sheet->setCellValue('A2', 'Day');
        $sheet->setCellValue('B2', 'Date');
        $sheet->setCellValue('C1', 'Sales Summary');
        $sheet->setCellValue('E1', 'Payment Summary');

        // Menggabungkan kolom Day dan Date
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('C1:D1');
        $sheet->mergeCells('E1:I1');

        // Menulis sub-header untuk Sales Summary dan Payment Summary
        $sheet->setCellValue('C2', 'Sales Value (Rp)');
        $sheet->setCellValue('D2', 'Discounts (Rp)');
        $sheet->setCellValue('E2', 'Cash (Rp)');
        $sheet->setCellValue('F2', 'Credit Card (Rp)');
        $sheet->setCellValue('G2', 'Bank Transfer (Rp)');
        $sheet->setCellValue('H2', 'Debit Card (Rp)');
        $sheet->setCellValue('I2', 'Total Amount (Rp)');

        // Menambahkan style pada header
        $sheet->getStyle('A1:I2')->getFont()->setBold(true);
        $sheet->getStyle('A1:I2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:I2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Menulis data dari $data
        $row = 3;  // Data mulai dari baris ke-3
        foreach ($data['table']['data'] as $item) {
            $sheet->setCellValue("A{$row}", $item['day']);
            $sheet->setCellValue("B{$row}", $item['date']);
            $sheet->setCellValue("C{$row}", $item['salesSummary']['salesValue']);
            $sheet->setCellValue("D{$row}", $item['salesSummary']['discounts']);
            $sheet->setCellValue("E{$row}", $item['paymentSummary']['cash']);
            $sheet->setCellValue("F{$row}", $item['paymentSummary']['creditCard']);
            $sheet->setCellValue("G{$row}", $item['paymentSummary']['bankTransfer']);
            $sheet->setCellValue("H{$row}", $item['paymentSummary']['debitCard']);
            $sheet->setCellValue("I{$row}", $item['paymentSummary']['totalAmount']);

            // Menambahkan border untuk setiap baris data
            $sheet->getStyle("A{$row}:I{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
        }

        // Menyesuaikan ukuran kolom agar otomatis sesuai dengan kontennya
        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Menulis dan menyimpan file Excel
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Sales Daily Audit.xlsx';
        $writer->save($newFilePath);

        // Mengirim file Excel untuk diunduh oleh pengguna
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Daily Audit.xlsx"',
        ]);
    }

    public function indexStaffServiceSales(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchStaffServiceSalesData($request, $perPage, $page);
        return response()->json(['totalPagination' => $total, 'data' => $data]);
    }

    public function exportStaffServiceSales(Request $request)
    {
        [$rows, ] = $this->fetchStaffServiceSalesData($request, 0, 1);
        $data = ['data' => $rows];

        // Collect all location names from real data
        $locationNames = [];
        foreach ($rows as $row) {
            foreach (array_keys($row['location']) as $locName) {
                if (!in_array($locName, $locationNames)) {
                    $locationNames[] = $locName;
                }
            }
        }
        sort($locationNames);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Staff_Service_Sales.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Staff');
        $sheet->setCellValue('B1', 'Service');
        $sheet->setCellValue('C1', 'Pricing');

        $colIndex = 4;
        foreach ($locationNames as $location) {

            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$col}1", $location);
            $colIndex++;
        }

        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue("{$col}1", 'Total Qty');
        $colIndex++;
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue("{$col}1", 'Total Duration (Hrs)');
        $colIndex++;
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue("{$col}1", 'Total Sold Value (Rp)');

        $sheet->getStyle('A1:' . $col . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $col . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:' . $col . '1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data['data'] as $item) {

            $sheet->setCellValue("A{$row}", $item['staff']);
            $sheet->setCellValue("B{$row}", $item['service']);
            $sheet->setCellValue("C{$row}", $item['pricing']);

            $colIndex = 4;
            foreach ($locationNames as $location) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue("{$col}{$row}", isset($item['location'][$location]) ? $item['location'][$location] : 0);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $colIndex++;
            }

            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$col}{$row}", $item['totalQty']);
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  // Set alignment center
            $colIndex++;

            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$col}{$row}", $item['totalDuration']);
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  // Set alignment center
            $colIndex++;

            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$col}{$row}", $item['totalSoldValue']);
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  // Set alignment center
            $colIndex++;

            $sheet->getStyle("A{$row}:" . $col . "{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
        }

        foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex)) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Staff Service Sales.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Staff Service Sales.xlsx"',
        ]);
    }

    /**
     * Fetch Daily Audit rows aggregated by date.
     * Returns: [$rows, $total]
     */
    private function fetchDailyAuditData(Request $request, int $perPage, int $page): array
    {
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $staffIds    = array_values(array_filter((array) $request->input('staffId',    []), fn($v) => $v !== '' && $v !== null));
        $paymentIds  = array_values(array_filter((array) $request->input('paymentId',  []), fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('invoiceCategoryId', []), fn($v) => $v !== '' && $v !== null));

        $dateFrom = $request->input('dateFrom') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->input('dateTo')   ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        // Load payment method type map: id => 'cash'|'creditCard'|'debitCard'|'bankTransfer'
        $methodType = [];
        $methodRows = DB::table('paymentmethod')->select('id', 'name')->get();
        foreach ($methodRows as $m) {
            $n = strtolower((string) $m->name);
            if (str_contains($n, 'cash')) {
                $methodType[$m->id] = 'cash';
            } elseif (str_contains($n, 'credit')) {
                $methodType[$m->id] = 'creditCard';
            } elseif (str_contains($n, 'debit')) {
                $methodType[$m->id] = 'debitCard';
            } else {
                $methodType[$m->id] = 'bankTransfer';
            }
        }

        // dayMap["YYYY-MM-DD"] => [cash, creditCard, debitCard, bankTransfer, discount]
        $dayMap = [];

        $includeClinic  = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel   = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon   = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);
        $includePetshop = empty($catIds) || in_array('4', $catIds) || in_array(4, $catIds);

        $initDay = fn() => ['cash' => 0.0, 'creditCard' => 0.0, 'debitCard' => 0.0, 'bankTransfer' => 0.0, 'discount' => 0.0];

        foreach (['clinic' => $includeClinic, 'hotel' => $includeHotel, 'salon' => $includeSalon] as $type => $include) {
            if (!$include) continue;
            $txTable = $type === 'clinic' ? 'transactionPetClinics' : "transaction_pet_{$type}s";
            $ptTable = "transaction_pet_{$type}_payment_totals";
            $piTable = "transaction_pet_{$type}_payments";

            // Payment amounts per date + method
            $ptQ = DB::table("{$ptTable} as pt")
                ->join("{$txTable} as t", 't.id', '=', 'pt.transactionId')
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->whereBetween(DB::raw('DATE(pt.created_at)'), [$dateFrom, $dateTo]);
            if (!empty($locationIds)) $ptQ->whereIn('t.locationId',        $locationIds);
            if (!empty($staffIds))    $ptQ->whereIn('t.doctorId',          $staffIds);
            if (!empty($paymentIds))  $ptQ->whereIn('pt.paymentMethodId',  $paymentIds);

            $ptRows = $ptQ->select([
                DB::raw('DATE(pt.created_at) as sale_date'),
                'pt.paymentMethodId',
                DB::raw('SUM(pt.amountPaid) as total'),
            ])->groupBy(DB::raw('DATE(pt.created_at)'), 'pt.paymentMethodId')->get();

            foreach ($ptRows as $r) {
                if (!isset($dayMap[$r->sale_date])) $dayMap[$r->sale_date] = $initDay();
                $t_ = $methodType[$r->paymentMethodId] ?? 'bankTransfer';
                $dayMap[$r->sale_date][$t_] += (float) $r->total;
            }

            // Discounts from payment items
            $discQ = DB::table("{$piTable} as p")
                ->join("{$txTable} as t", 't.id', '=', 'p.transactionId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->where('p.discountAmount', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), [$dateFrom, $dateTo]);
            if (!empty($locationIds)) $discQ->whereIn('t.locationId', $locationIds);
            if (!empty($staffIds))    $discQ->whereIn('t.doctorId',   $staffIds);

            $discRows = $discQ->select([
                DB::raw('DATE(t.created_at) as sale_date'),
                DB::raw('SUM(p.discountAmount) as disc'),
            ])->groupBy(DB::raw('DATE(t.created_at)'))->get();

            foreach ($discRows as $r) {
                if (!isset($dayMap[$r->sale_date])) $dayMap[$r->sale_date] = $initDay();
                $dayMap[$r->sale_date]['discount'] += (float) $r->disc;
            }
        }

        // Petshop payments
        if ($includePetshop) {
            $psQ = DB::table('transactionpetshop as t')
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->whereBetween(DB::raw('DATE(t.created_at)'), [$dateFrom, $dateTo]);
            if (!empty($locationIds)) $psQ->whereIn('t.locationId',  $locationIds);
            if (!empty($staffIds))    $psQ->whereIn('t.userId',      $staffIds);
            if (!empty($paymentIds))  $psQ->whereIn('t.paymentMethod', $paymentIds);

            $psRows = $psQ->select([
                DB::raw('DATE(t.created_at) as sale_date'),
                't.paymentMethod as paymentMethodId',
                DB::raw('SUM(t.totalPayment) as total'),
            ])->groupBy(DB::raw('DATE(t.created_at)'), 't.paymentMethod')->get();

            foreach ($psRows as $r) {
                if (!isset($dayMap[$r->sale_date])) $dayMap[$r->sale_date] = $initDay();
                $t_ = $methodType[$r->paymentMethodId] ?? 'bankTransfer';
                $dayMap[$r->sale_date][$t_] += (float) $r->total;
            }

            // Petshop discounts from transactionpetshopdetail
            $psDiscQ = DB::table('transactionpetshopdetail as d')
                ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->where('d.discount', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), [$dateFrom, $dateTo]);
            if (!empty($locationIds)) $psDiscQ->whereIn('t.locationId', $locationIds);

            $psDiscRows = $psDiscQ->select([
                DB::raw('DATE(t.created_at) as sale_date'),
                DB::raw('SUM(d.discount) as disc'),
            ])->groupBy(DB::raw('DATE(t.created_at)'))->get();

            foreach ($psDiscRows as $r) {
                if (!isset($dayMap[$r->sale_date])) $dayMap[$r->sale_date] = $initDay();
                $dayMap[$r->sale_date]['discount'] += (float) $r->disc;
            }
        }

        // Build result rows sorted by date
        ksort($dayMap);
        $rows = [];
        foreach ($dayMap as $date => $agg) {
            $salesValue  = $agg['cash'] + $agg['creditCard'] + $agg['debitCard'] + $agg['bankTransfer'];
            $totalExclCC = $salesValue - $agg['creditCard'];
            $dt = Carbon::parse($date);
            $rows[] = [
                'day'  => (int) $dt->format('j'),
                'date' => $dt->format('j/n/Y'),
                'salesSummary'   => ['salesValue' => round($salesValue, 2), 'discounts' => round($agg['discount'], 2)],
                'paymentSummary' => [
                    'cash'         => round($agg['cash'], 2),
                    'creditCard'   => round($agg['creditCard'], 2),
                    'bankTransfer' => round($agg['bankTransfer'], 2),
                    'debitCard'    => round($agg['debitCard'], 2),
                    'totalAmount'  => round($totalExclCC, 2),
                ],
            ];
        }

        $total = count($rows);
        if ($perPage > 0) {
            $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);
        }

        return [$rows, $total];
    }

    /**
     * Fetch Staff Service Sales rows aggregated by (doctorId, serviceId) with location pivot.
     * Returns: [$rows, $total]
     */
    private function fetchStaffServiceSalesData(Request $request, int $perPage, int $page): array
    {
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $staffIds    = array_values(array_filter((array) $request->input('staffId',   []), fn($v) => $v !== '' && $v !== null));
        $serviceIds  = array_values(array_filter((array) $request->input('serviceId', []), fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('categoryId',[]), fn($v) => $v !== '' && $v !== null));
        $dateFrom = $request->input('dateFrom');
        $dateTo   = $request->input('dateTo');

        // Location name map: id => locationName
        $locationMap = DB::table('location')
            ->where(fn($q) => $q->where('isDeleted', 0)->orWhereNull('isDeleted'))
            ->pluck('locationName', 'id')
            ->toArray();

        // agg map: "doctorId_serviceId" => [staff, service, locations => {name: qty}, soldValue]
        $agg = [];

        $includeClinic = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel  = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon  = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);

        foreach (['clinic' => $includeClinic, 'hotel' => $includeHotel, 'salon' => $includeSalon] as $type => $include) {
            if (!$include) continue;
            $txTable = $type === 'clinic' ? 'transactionPetClinics' : "transaction_pet_{$type}s";
            $piTable = "transaction_pet_{$type}_payments";

            $q = DB::table("{$piTable} as p")
                ->join("{$txTable} as t",  't.id',  '=', 'p.transactionId')
                ->join('services as s',    's.id',  '=', 'p.serviceId')
                ->join('users as u',       'u.id',  '=', 't.doctorId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->whereNotNull('p.serviceId')
                ->whereNotNull('t.doctorId');

            if ($dateFrom)             $q->where(DB::raw('DATE(t.created_at)'), '>=', $dateFrom);
            if ($dateTo)               $q->where(DB::raw('DATE(t.created_at)'), '<=', $dateTo);
            if (!empty($locationIds))  $q->whereIn('t.locationId', $locationIds);
            if (!empty($staffIds))     $q->whereIn('t.doctorId',   $staffIds);
            if (!empty($serviceIds))   $q->whereIn('p.serviceId',  $serviceIds);

            $rows = $q->select([
                't.doctorId',
                DB::raw('CONCAT(u.firstName, " ", COALESCE(u.lastName, "")) as staffName'),
                'p.serviceId',
                's.fullName as serviceName',
                't.locationId',
                DB::raw('SUM(COALESCE(p.quantity, 1)) as qty'),
                DB::raw('SUM(COALESCE(p.priceOverall, 0)) as soldValue'),
            ])->groupBy('t.doctorId', 'p.serviceId', 't.locationId', 'u.firstName', 'u.lastName', 's.fullName')
              ->get();

            foreach ($rows as $r) {
                $key = "{$r->doctorId}_{$r->serviceId}";
                if (!isset($agg[$key])) {
                    $agg[$key] = ['staff' => trim((string) $r->staffName), 'service' => $r->serviceName, 'locations' => [], 'soldValue' => 0.0];
                }
                $locName = $locationMap[$r->locationId] ?? 'Unknown';
                $agg[$key]['locations'][$locName] = ($agg[$key]['locations'][$locName] ?? 0) + (int) $r->qty;
                $agg[$key]['soldValue'] += (float) $r->soldValue;
            }
        }

        // Build result rows
        $result = [];
        foreach ($agg as $entry) {
            $totalQty = array_sum($entry['locations']);
            $result[] = [
                'staff'          => $entry['staff'],
                'service'        => $entry['service'],
                'pricing'        => 'Standard',
                'location'       => $entry['locations'],
                'totalQty'       => $totalQty,
                'totalDuration'  => 0,
                'totalSoldValue' => round($entry['soldValue'], 2),
            ];
        }

        $result = collect($result)
            ->sortBy([['staff', 'asc'], ['service', 'asc']])
            ->values()
            ->all();

        $total = count($result);
        if ($perPage > 0) {
            $result = array_slice($result, ($page - 1) * $perPage, $perPage);
        }

        return [$result, $total];
    }

    /**
     * Fetch paginated payment list rows (one row per payment record).
     * Sources: clinic/hotel/salon payment_totals + petshop transactions.
     * Returns: [$data, $total]
     */
    private function fetchPaymentListData(Request $request, int $perPage, int $page): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []),  fn($v) => $v !== '' && $v !== null));
        $statusIds   = array_values(array_filter((array) $request->input('statusId',   []),  fn($v) => $v !== '' && $v !== null));
        $staffIds    = array_values(array_filter((array) $request->input('staffId',    []),  fn($v) => $v !== '' && $v !== null));
        $methodIds   = array_values(array_filter((array) $request->input('methodId',   []),  fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('categoryId', []),  fn($v) => $v !== '' && $v !== null));
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'paidAt';
        $orderValue  = $request->input('orderValue')  ?: 'desc';

        $includeClinic  = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel   = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon   = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);
        $includePetshop = empty($catIds) || in_array('4', $catIds) || in_array(4, $catIds);

        $allRows = collect();

        // ---- Clinic / Hotel / Salon: one row per payment_totals record ----
        $sources = [];
        if ($includeClinic) $sources[] = ['tx' => 'transactionPetClinics',  'pt' => 'transaction_pet_clinic_payment_totals'];
        if ($includeHotel)  $sources[] = ['tx' => 'transaction_pet_hotels',  'pt' => 'transaction_pet_hotel_payment_totals'];
        if ($includeSalon)  $sources[] = ['tx' => 'transaction_pet_salons',  'pt' => 'transaction_pet_salon_payment_totals'];

        foreach ($sources as $src) {
            $rows = DB::table("{$src['pt']} as pt")
                ->join("{$src['tx']} as t",  't.id',  '=', 'pt.transactionId')
                ->join('location as l',       't.locationId', '=', 'l.id')
                ->join('paymentmethod as pm', 'pm.id', '=', 'pt.paymentMethodId')
                ->leftJoin('users as u',      'u.id',  '=', 'pt.userId')
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->when($dateFrom, fn($q) => $q->whereDate('pt.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('pt.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($staffIds),    fn($q) => $q->whereIn('t.doctorId', $staffIds))
                ->when(!empty($methodIds),   fn($q) => $q->whereIn('pt.paymentMethodId', $methodIds))
                ->when($search, fn($q) => $q->where('t.registrationNo', 'like', "%{$search}%"))
                ->select([
                    't.registrationNo as saleId',
                    'l.locationName as location',
                    'pm.name as paymentMethod',
                    'pt.created_at as paidAt',
                    DB::raw("TRIM(CONCAT(COALESCE(u.firstName,''), IF(u.lastName IS NOT NULL AND u.lastName != '', CONCAT(' ', u.lastName), ''))) as createdBy"),
                    'pt.created_at as createdAt',
                    'pt.amountPaid as totalAmount',
                ])
                ->get();

            $allRows = $allRows->concat($rows);
        }

        // ---- Petshop: one row per transaction ----
        if ($includePetshop && empty($staffIds)) {
            $rows = DB::table('transactionpetshop as t')
                ->join('location as l',       't.locationId', '=', 'l.id')
                ->leftJoin('paymentmethod as pm', 'pm.id', '=', 't.paymentMethod')
                ->leftJoin('users as u',          'u.id',  '=', 't.userId')
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($methodIds),   fn($q) => $q->whereIn('t.paymentMethod', $methodIds))
                ->when($search, fn($q) => $q->where('t.registrationNo', 'like', "%{$search}%"))
                ->select([
                    't.registrationNo as saleId',
                    'l.locationName as location',
                    'pm.name as paymentMethod',
                    't.created_at as paidAt',
                    DB::raw("TRIM(CONCAT(COALESCE(u.firstName,''), IF(u.lastName IS NOT NULL AND u.lastName != '', CONCAT(' ', u.lastName), ''))) as createdBy"),
                    't.created_at as createdAt',
                    't.totalPayment as totalAmount',
                ])
                ->get();

            $allRows = $allRows->concat($rows);
        }

        // Sort + paginate
        $allowedCols = ['saleId', 'location', 'paymentMethod', 'paidAt', 'createdBy', 'createdAt', 'totalAmount'];
        if (!in_array($orderColumn, $allowedCols)) $orderColumn = 'paidAt';
        $sorted = $orderValue === 'asc'
            ? $allRows->sortBy(fn($r)     => $r->$orderColumn ?? '')
            : $allRows->sortByDesc(fn($r) => $r->$orderColumn ?? '');

        $total = $sorted->count();
        $items = $perPage > 0
            ? $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            : $sorted->values();

        $result = $items->map(fn($r) => [
            'saleId'        => $r->saleId,
            'location'      => $r->location,
            'paymentMethod' => $r->paymentMethod ?? '-',
            'paidAt'        => $r->paidAt,
            'createdBy'     => $r->createdBy ?: '-',
            'createdAt'     => $r->createdAt,
            'totalAmount'   => (float) $r->totalAmount,
        ])->values()->toArray();

        return [$result, $total];
    }

    /**
     * Fetch paginated unpaid transaction rows from all sources.
     * "Unpaid" = clinic/hotel/salon transactions with at least one payment_total isPayed=0
     *            OR no payment_totals at all; petshop transactions where isPayed=0.
     * Returns: [$data, $total]
     */
    private function fetchUnpaidData(Request $request, int $perPage, int $page): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId',        []), fn($v) => $v !== '' && $v !== null));
        $statusIds   = array_values(array_filter((array) $request->input('statusId',          []), fn($v) => $v !== '' && $v !== null));
        $customerIds = array_values(array_filter((array) $request->input('customerId',        []), fn($v) => $v !== '' && $v !== null));
        $catIds      = array_values(array_filter((array) $request->input('invoiceCategoryId', []), fn($v) => $v !== '' && $v !== null));
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'saleDate';
        $orderValue  = $request->input('orderValue')  ?: 'desc';

        $includeClinic  = empty($catIds) || in_array('1', $catIds) || in_array(1, $catIds);
        $includeHotel   = empty($catIds) || in_array('2', $catIds) || in_array(2, $catIds);
        $includeSalon   = empty($catIds) || in_array('3', $catIds) || in_array(3, $catIds);
        $includePetshop = empty($catIds) || in_array('4', $catIds) || in_array(4, $catIds);

        $allTx = collect();

        // ---- Clinic / Hotel / Salon ----
        $sources = [];
        if ($includeClinic) $sources[] = ['tx' => 'transactionPetClinics', 'pt' => 'transaction_pet_clinic_payment_totals', 'pay' => 'transaction_pet_clinic_payments', 'type' => 'clinic', 'refCol' => 'bookingId'];
        if ($includeHotel)  $sources[] = ['tx' => 'transaction_pet_hotels', 'pt' => 'transaction_pet_hotel_payment_totals', 'pay' => 'transaction_pet_hotel_payments', 'type' => 'hotel',  'refCol' => 'bookingId'];
        if ($includeSalon)  $sources[] = ['tx' => 'transaction_pet_salons', 'pt' => 'transaction_pet_salon_payment_totals', 'pay' => 'transaction_pet_salon_payments', 'type' => 'salon',  'refCol' => 'bookingId'];

        foreach ($sources as $src) {
            $rows = DB::table("{$src['tx']} as t")
                ->join('location as l', 't.locationId', '=', 'l.id')
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->where(function ($q) use ($src) {
                    // Has at least one unpaid payment_total OR has no payment_totals at all
                    $q->whereExists(function ($sub) use ($src) {
                        $sub->from("{$src['pt']} as pt")
                            ->whereColumn('pt.transactionId', 't.id')
                            ->where(fn($q2) => $q2->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                            ->where('pt.isPayed', 0);
                    })->orWhereNotExists(function ($sub) use ($src) {
                        $sub->from("{$src['pt']} as pt")
                            ->whereColumn('pt.transactionId', 't.id')
                            ->where(fn($q2) => $q2->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'));
                    });
                })
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($statusIds),   fn($q) => $q->whereIn('t.status', $statusIds))
                ->when(!empty($customerIds), fn($q) => $q->whereIn('t.customerId', $customerIds))
                ->when($search, fn($q) => $q->where('t.registrationNo', 'like', "%{$search}%"))
                ->select([
                    't.id',
                    't.registrationNo as saleId',
                    DB::raw("IFNULL(t.{$src['refCol']}, '') as refNum"),
                    'l.locationName as location',
                    't.status',
                    't.customerId',
                    DB::raw('DATE(t.created_at) as saleDate'),
                    DB::raw("'{$src['type']}' as sourceType"),
                ])
                ->get();

            $allTx = $allTx->concat($rows);
        }

        // ---- Petshop: where isPayed=0 ----
        if ($includePetshop) {
            $rows = DB::table('transactionpetshop as t')
                ->join('location as l', 't.locationId', '=', 'l.id')
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->where('t.isPayed', 0)
                ->when($dateFrom, fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->when(!empty($customerIds), fn($q) => $q->whereIn('t.customerId', $customerIds))
                ->when($search, fn($q) => $q->where('t.registrationNo', 'like', "%{$search}%"))
                ->select([
                    't.id',
                    't.registrationNo as saleId',
                    DB::raw("IFNULL(t.no_nota, '') as refNum"),
                    'l.locationName as location',
                    DB::raw("'Active' as status"),
                    't.customerId',
                    DB::raw('DATE(t.created_at) as saleDate'),
                    DB::raw("'petshop' as sourceType"),
                    't.totalAmount as ps_totalAmount',
                    't.totalPayment as ps_paidAmount',
                ])
                ->get();

            $allTx = $allTx->concat($rows);
        }

        // Sort + paginate
        $allowedCols = ['saleId', 'location', 'saleDate', 'status'];
        if (!in_array($orderColumn, $allowedCols)) $orderColumn = 'saleDate';
        $sorted = $orderValue === 'asc'
            ? $allTx->sortBy(fn($r)     => $r->$orderColumn ?? '')
            : $allTx->sortByDesc(fn($r) => $r->$orderColumn ?? '');

        $total = $sorted->count();
        $items = $perPage > 0
            ? $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            : $sorted->values();

        if ($items->isEmpty()) return [[], $total];

        // Group IDs by source type
        $idsByType = ['clinic' => [], 'hotel' => [], 'salon' => [], 'petshop' => []];
        foreach ($items as $tx) {
            $idsByType[$tx->sourceType][] = (int) $tx->id;
        }

        // Batch-fetch totalAmount (gross) and paidAmount for clinic/hotel/salon
        $amountsMap = []; // sourceType_id => [totalAmount, paidAmount]

        foreach (['clinic', 'hotel', 'salon'] as $type) {
            if (empty($idsByType[$type])) continue;
            $payTable = "transaction_pet_{$type}_payments";
            $ptTable  = "transaction_pet_{$type}_payment_totals";

            // grossAmount = SUM(priceOverall) from payment items
            $grossRows = DB::table("{$payTable} as p")
                ->whereIn('p.transactionId', $idsByType[$type])
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->select(['p.transactionId', DB::raw('SUM(p.priceOverall) as gross')])
                ->groupBy('p.transactionId')
                ->get();
            foreach ($grossRows as $r) {
                $amountsMap["{$type}_{$r->transactionId}"]['totalAmount'] = (float) $r->gross;
            }

            // paidAmount = SUM(amountPaid) from payment_totals where isPayed=1
            $paidRows = DB::table("{$ptTable} as pt")
                ->whereIn('pt.transactionId', $idsByType[$type])
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->where('pt.isPayed', 1)
                ->select(['pt.transactionId', DB::raw('SUM(pt.amountPaid) as paid')])
                ->groupBy('pt.transactionId')
                ->get();
            foreach ($paidRows as $r) {
                $amountsMap["{$type}_{$r->transactionId}"]['paidAmount'] = (float) $r->paid;
            }
        }

        // Batch-fetch customer names + phone numbers
        $pageCustomerIds = $items->pluck('customerId')->filter()->unique()->values()->toArray();
        $customersMap = [];
        $phonesMap    = [];
        if (!empty($pageCustomerIds)) {
            $custRows = DB::table('customer as c')
                ->whereIn('c.id', $pageCustomerIds)
                ->select(['c.id', 'c.firstName', 'c.middleName', 'c.lastName'])
                ->get();
            foreach ($custRows as $c) {
                $name = trim(implode(' ', array_filter([$c->firstName, $c->middleName, $c->lastName])));
                $customersMap[$c->id] = $name;
            }

            $phoneRows = DB::table('customerTelephones as ct')
                ->whereIn('ct.customerId', array_map('strval', $pageCustomerIds))
                ->where(fn($q) => $q->where('ct.isDeleted', 0)->orWhereNull('ct.isDeleted'))
                ->select(['ct.customerId', 'ct.phoneNumber'])
                ->orderBy('ct.id')
                ->get();
            foreach ($phoneRows as $p) {
                $cId = (int) $p->customerId;
                if (!isset($phonesMap[$cId])) {
                    $phonesMap[$cId] = $p->phoneNumber;
                }
            }
        }

        // Assemble result
        $result = $items->map(function ($tx) use ($amountsMap, $customersMap, $phonesMap) {
            $key = $tx->sourceType . '_' . $tx->id;

            if ($tx->sourceType === 'petshop') {
                $totalAmount = (float) ($tx->ps_totalAmount ?? 0);
                $paidAmount  = (float) ($tx->ps_paidAmount  ?? 0);
            } else {
                $totalAmount = $amountsMap[$key]['totalAmount'] ?? 0.0;
                $paidAmount  = $amountsMap[$key]['paidAmount']  ?? 0.0;
            }

            $outstanding = max(0.0, $totalAmount - $paidAmount);

            return [
                'saleId'            => $tx->saleId,
                'location'          => $tx->location,
                'dueDate'           => $tx->saleDate,
                'overDue'           => $tx->status ?? 'Active',
                'customerName'      => $customersMap[(int) $tx->customerId] ?? '-',
                'phoneNo'           => $phonesMap[(int) $tx->customerId]    ?? '-',
                'totalAmount'       => round($totalAmount, 2),
                'paidAmount'        => round($paidAmount,  2),
                'outstandingAmount' => round($outstanding,  2),
                'refNum'            => $tx->refNum ?? '',
            ];
        })->values()->toArray();

        return [$result, $total];
    }
}
