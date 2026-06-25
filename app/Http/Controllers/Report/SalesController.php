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

    // ─────────────────────────────────────────────────────────────────────
    // Sales Value by Item Type
    // ─────────────────────────────────────────────────────────────────────

    public function indexSalesByItemType(Request $request)
    {
        $data = $this->fetchSalesByItemTypeData($request);
        return response()->json($data);
    }

    public function exportSalesByItemType(Request $request)
    {
        $data = $this->fetchSalesByItemTypeData($request);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales by Item Type');

        // ── Styles ───────────────────────────────────────────────────────
        $darkBlue   = \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE;
        $headerFill = [
            'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor'=> ['argb' => 'FF003366'],
        ];
        $altFill = [
            'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor'=> ['argb' => 'FFD9E1F2'],
        ];
        $totalFill = [
            'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor'=> ['argb' => 'FF1F4E79'],
        ];
        $thinBorder = [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color'       => ['argb' => 'FFBFBFBF'],
        ];

        // ── Title ────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Sales Value by Item Type');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF003366');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Date range subtitle ──────────────────────────────────────────
        $dateFrom = $request->input('dateFrom', '');
        $dateTo   = $request->input('dateTo', '');
        $subtitle = 'Periode: ' . ($dateFrom ?: '-') . ' s/d ' . ($dateTo ?: '-');
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF595959');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(16);
        $sheet->getRowDimension(3)->setRowHeight(8);

        // ── Header row ───────────────────────────────────────────────────
        $headers = ['No', 'Item Type', 'Total Transactions', 'Gross Revenue (Rp)', 'Share (%)', 'Avg per Transaction (Rp)'];
        foreach ($headers as $col => $hdr) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, 4);
            $cell->setValue($hdr);
            $style = $sheet->getStyleByColumnAndRow($col + 1, 4);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF003366');
            $style->getAlignment()
                  ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                  ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                  ->getColor()->setARGB('FFBFBFBF');
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Data rows ────────────────────────────────────────────────────
        $items = $data['rows'] ?? [];
        $excelRow = 5;
        foreach ($items as $idx => $item) {
            $isAlt = ($idx % 2 === 0);
            $bgArgb = $isAlt ? 'FFD9E1F2' : 'FFFFFFFF';

            $rowData = [
                $idx + 1,
                $item['itemType'],
                $item['totalTransactions'],
                $item['grossRevenue'],
                $item['sharePercent'],
                $item['avgPerTransaction'],
            ];

            foreach ($rowData as $col => $val) {
                $cellStyle = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
                $sheet->getCellByColumnAndRow($col + 1, $excelRow)->setValue($val);
                $cellStyle->getFont()->setSize(10);
                $cellStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                          ->getStartColor()->setARGB($bgArgb);
                $cellStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                          ->getColor()->setARGB('FFBFBFBF');

                $align = in_array($col, [0, 2]) ? \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER : \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
                if (in_array($col, [3, 4, 5])) {
                    $align = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
                }
                $cellStyle->getAlignment()->setHorizontal($align)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Number format for currency / percent columns
                if ($col === 3 || $col === 5) {
                    $cellStyle->getNumberFormat()->setFormatCode('#,##0');
                }
                if ($col === 4) {
                    $cellStyle->getNumberFormat()->setFormatCode('0.00"%"');
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
            $excelRow++;
        }

        // ── Total row ────────────────────────────────────────────────────
        $totals = $data['totals'] ?? [];
        $totalRowData = [
            '',
            'TOTAL',
            $totals['totalTransactions'] ?? 0,
            $totals['grossRevenue']       ?? 0,
            '100.00',
            $totals['avgPerTransaction']  ?? 0,
        ];
        foreach ($totalRowData as $col => $val) {
            $cellStyle = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $sheet->getCellByColumnAndRow($col + 1, $excelRow)->setValue($val);
            $cellStyle->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $cellStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FF1F4E79');
            $cellStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                      ->getColor()->setARGB('FFBFBFBF');
            $cellStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                      ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if ($col === 3 || $col === 5) {
                $cellStyle->getNumberFormat()->setFormatCode('#,##0');
            }
            if ($col === 4) {
                $cellStyle->getNumberFormat()->setFormatCode('0.00"%"');
            }
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);

        // ── Column widths ────────────────────────────────────────────────
        $widths = [5, 22, 20, 22, 14, 24];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth($w);
        }

        // ── Stream response ──────────────────────────────────────────────
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Sales_By_Item_Type.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Aggregate total transactions + gross revenue per item type (transaction source).
     * Sources: Pet Clinic, Pet Hotel, Pet Salon, Breeding (via payment tables),
     *          Pet Shop (via transactionpetshopdetail.total_final_price).
     * Filters:  dateFrom, dateTo, locationId[]
     */
    private function fetchSalesByItemTypeData(Request $request): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter(
            (array) $request->input('locationId', []),
            fn($v) => $v !== '' && $v !== null
        ));

        // ── Sources: clinic / hotel / salon / breeding ───────────────────
        $sources = [
            ['label' => 'Pet Clinic',  'tx' => 'transactionPetClinics',      'pay' => 'transaction_pet_clinic_payments'],
            ['label' => 'Pet Hotel',   'tx' => 'transaction_pet_hotels',      'pay' => 'transaction_pet_hotel_payments'],
            ['label' => 'Pet Salon',   'tx' => 'transaction_pet_salons',      'pay' => 'transaction_pet_salon_payments'],
            ['label' => 'Breeding',    'tx' => 'transaction_breedings',       'pay' => 'transactionBreedingPayments'],
        ];

        $rows = [];
        foreach ($sources as $src) {
            $agg = DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->when($dateFrom,              fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,                fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds),   fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->selectRaw('COUNT(DISTINCT p.transactionId) as txCount, COALESCE(SUM(p.priceOverall), 0) as grossRevenue')
                ->first();

            $rows[] = [
                'itemType'         => $src['label'],
                'totalTransactions'=> (int) ($agg->txCount      ?? 0),
                'grossRevenue'     => (float) ($agg->grossRevenue ?? 0),
            ];
        }

        // ── Pet Shop: uses transactionpetshopdetail ──────────────────────
        $shopAgg = DB::table('transactionpetshopdetail as d')
            ->join('transactionpetshop as t', 't.id', '=', 'd.transactionpetshopId')
            ->where(fn($q) => $q->where('d.isDeleted', 0)->orWhereNull('d.isDeleted'))
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
            ->selectRaw('COUNT(DISTINCT d.transactionpetshopId) as txCount, COALESCE(SUM(d.total_final_price), 0) as grossRevenue')
            ->first();

        $rows[] = [
            'itemType'         => 'Pet Shop',
            'totalTransactions'=> (int) ($shopAgg->txCount      ?? 0),
            'grossRevenue'     => (float) ($shopAgg->grossRevenue ?? 0),
        ];

        // ── Compute grand total + share ──────────────────────────────────
        $grandTotal     = array_sum(array_column($rows, 'grossRevenue'));
        $grandTxTotal   = array_sum(array_column($rows, 'totalTransactions'));

        $rows = array_map(function ($r) use ($grandTotal) {
            $share = $grandTotal > 0 ? round(($r['grossRevenue'] / $grandTotal) * 100, 2) : 0.00;
            $avg   = $r['totalTransactions'] > 0
                ? round($r['grossRevenue'] / $r['totalTransactions'], 2)
                : 0.00;
            return array_merge($r, [
                'sharePercent'       => $share,
                'avgPerTransaction'  => $avg,
            ]);
        }, $rows);

        $grandAvg = $grandTxTotal > 0 ? round($grandTotal / $grandTxTotal, 2) : 0.00;

        // ── Build chart data ─────────────────────────────────────────────
        $chart = [
            'categories' => array_column($rows, 'itemType'),
            'series'     => [
                [
                    'name' => 'Gross Revenue',
                    'data' => array_column($rows, 'grossRevenue'),
                ],
            ],
        ];

        return [
            'rows'   => $rows,
            'totals' => [
                'totalTransactions' => $grandTxTotal,
                'grossRevenue'      => round($grandTotal, 2),
                'avgPerTransaction' => $grandAvg,
            ],
            'chart'  => $chart,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Package Summary
    // ─────────────────────────────────────────────────────────────────────

    public function indexPackageSummary(Request $request)
    {
        $data = $this->fetchPackageSummaryData($request);
        return response()->json($data);
    }

    public function exportPackageSummary(Request $request)
    {
        $data = $this->fetchPackageSummaryData($request);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Package Summary');

        $dateFrom = $request->input('dateFrom', '');
        $dateTo   = $request->input('dateTo', '');

        // ── Title ─────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'Package Summary Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF003366');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Periode Redemption: ' . ($dateFrom ?: '-') . ' s/d ' . ($dateTo ?: '-'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF595959');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Header ────────────────────────────────────────────────────────
        $headers = [
            'No', 'Nama Paket', 'Periode Paket', 'Harga Paket (Rp)',
            'Kuota Max', 'Terealisasi', 'Usage Rate (%)',
            'Total Revenue (Rp)', 'Channel', 'Isi Paket'
        ];
        foreach ($headers as $col => $h) {
            $style = $sheet->getStyleByColumnAndRow($col + 1, 4);
            $sheet->getCellByColumnAndRow($col + 1, 4)->setValue($h);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Rows ──────────────────────────────────────────────────────────
        $rows = $data['rows'] ?? [];
        $excelRow = 5;
        foreach ($rows as $idx => $r) {
            $bg = $idx % 2 === 0 ? 'FFD9E1F2' : 'FFFFFFFF';
            $rowData = [
                $idx + 1,
                $r['packageName'],
                $r['period'],
                $r['packagePrice'],
                $r['quotaMax'],
                $r['redeemed'],
                $r['usageRate'],
                $r['totalRevenue'],
                implode(', ', $r['channels']),
                $r['bundleContents'],
            ];
            foreach ($rowData as $col => $val) {
                $cell = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
                $cell->setValue($val);
                $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
                $style->getFont()->setSize(10);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
                if (in_array($col, [3, 7])) {
                    $style->getNumberFormat()->setFormatCode('#,##0');
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                if ($col === 6) {
                    $style->getNumberFormat()->setFormatCode('0.00"%"');
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                if (in_array($col, [4, 5])) {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(20);
            $excelRow++;
        }

        // ── Total row ─────────────────────────────────────────────────────
        $totals = $data['totals'] ?? [];
        $totalRow = ['', 'TOTAL', '', $totals['totalPackageRevenue'] ?? 0, '', $totals['totalRedeemed'] ?? 0, '', $totals['totalRevenue'] ?? 0, '', ''];
        foreach ($totalRow as $col => $val) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
            $cell->setValue($val);
            $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if (in_array($col, [3, 7])) {
                $style->getNumberFormat()->setFormatCode('#,##0');
                $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);

        // ── Column widths ─────────────────────────────────────────────────
        foreach ([4, 28, 22, 20, 12, 14, 14, 22, 22, 40] as $col => $w) {
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth($w);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Package_Summary.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Aggregate package (bundle) usage across all transaction sources.
     * Filters: dateFrom, dateTo (on transaction created_at), locationId, status
     */
    private function fetchPackageSummaryData(Request $request): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter(
            (array) $request->input('locationId', []),
            fn($v) => $v !== '' && $v !== null
        ));
        $status = $request->input('status'); // 'active'|'inactive'|null=all

        // ── Fetch all bundles ─────────────────────────────────────────────
        $bundleQuery = DB::table('promotionBundles as pb')
            ->join('promotionMasters as pm', 'pm.id', '=', 'pb.promoMasterId')
            ->where(fn($q) => $q->where('pb.isDeleted', 0)->orWhereNull('pb.isDeleted'))
            ->where(fn($q) => $q->where('pm.isDeleted', 0)->orWhereNull('pm.isDeleted'))
            ->where('pm.type', 3); // type 3 = Bundle

        if ($status === 'active')   $bundleQuery->where('pm.status', 1);
        if ($status === 'inactive') $bundleQuery->where('pm.status', 0);

        $bundles = $bundleQuery->select(
            'pb.id as bundleId',
            'pm.name as packageName',
            'pm.startDate',
            'pm.endDate',
            'pm.status',
            'pb.price as packagePrice',
            'pb.totalMaxUsage',
            'pb.maxUsagePerCustomer'
        )->get();

        if ($bundles->isEmpty()) {
            return ['rows' => [], 'totals' => [], 'chart' => ['categories' => [], 'series' => []]];
        }

        // ── Fetch bundle contents (products + services) ───────────────────
        $allBundleIds = $bundles->pluck('bundleId')->toArray();

        $bundleProducts = DB::table('promotion_bundle_detail_products as bpd')
            ->join('products as p', 'p.id', '=', 'bpd.productId')
            ->whereIn('bpd.promoBundleId', $allBundleIds)
            ->where(fn($q) => $q->where('bpd.isDeleted', 0)->orWhereNull('bpd.isDeleted'))
            ->select('bpd.promoBundleId', 'p.fullName as name', 'bpd.quantity')
            ->get()->groupBy('promoBundleId');

        $bundleServices = DB::table('promotion_bundle_detail_services as bsd')
            ->join('services as s', 's.id', '=', 'bsd.serviceId')
            ->whereIn('bsd.promoBundleId', $allBundleIds)
            ->where(fn($q) => $q->where('bsd.isDeleted', 0)->orWhereNull('bsd.isDeleted'))
            ->select('bsd.promoBundleId', 's.fullName as name', 'bsd.quantity')
            ->get()->groupBy('promoBundleId');

        // Also check old promotionBundleDetails table (legacy)
        $bundleLegacy = DB::table('promotionBundleDetails as bd')
            ->whereIn('bd.promoBundleId', $allBundleIds)
            ->where(fn($q) => $q->where('bd.isDeleted', 0)->orWhereNull('bd.isDeleted'))
            ->select('bd.promoBundleId', 'bd.productOrService', 'bd.productId', 'bd.serviceId', 'bd.quantity')
            ->get()->groupBy('promoBundleId');

        // ── Redemption count per bundle per source ────────────────────────
        $sources = [
            'Clinic' => [
                'tx'  => 'transactionPetClinics',
                'pay' => 'transaction_pet_clinic_payments',
                'loc' => 'locationId',
            ],
            'Hotel' => [
                'tx'  => 'transaction_pet_hotels',
                'pay' => 'transaction_pet_hotel_payments',
                'loc' => 'locationId',
            ],
            'Salon' => [
                'tx'  => 'transaction_pet_salons',
                'pay' => 'transaction_pet_salon_payments',
                'loc' => 'locationId',
            ],
            'Breeding' => [
                'tx'  => 'transaction_breedings',
                'pay' => 'transactionBreedingPayments',
                'loc' => 'locationId',
            ],
        ];

        // redemptionMap[bundleId][channel] = { redeemed, revenue }
        $redemptionMap = [];

        foreach ($sources as $label => $src) {
            $agg = DB::table("{$src['pay']} as p")
                ->join("{$src['tx']} as t", 't.id', '=', 'p.transactionId')
                ->where('p.isBundle', 1)
                ->whereNotNull('p.promoId')
                ->whereIn('p.promoId', $allBundleIds)
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn("t.{$src['loc']}", $locationIds))
                ->selectRaw('p.promoId as bundleId, COUNT(DISTINCT p.transactionId) as redeemed, COALESCE(SUM(p.priceOverall),0) as revenue')
                ->groupBy('p.promoId')
                ->get();

            foreach ($agg as $row) {
                if (!isset($redemptionMap[$row->bundleId])) {
                    $redemptionMap[$row->bundleId] = [];
                }
                $redemptionMap[$row->bundleId][$label] = [
                    'redeemed' => (int) $row->redeemed,
                    'revenue'  => (float) $row->revenue,
                ];
            }
        }

        // ── Build rows ────────────────────────────────────────────────────
        $rows = [];
        foreach ($bundles as $bundle) {
            $bid = $bundle->bundleId;

            // Contents string
            $contents = [];
            if (isset($bundleServices[$bid])) {
                foreach ($bundleServices[$bid] as $s) {
                    $contents[] = $s->name . ' (x' . $s->quantity . ')';
                }
            }
            if (isset($bundleProducts[$bid])) {
                foreach ($bundleProducts[$bid] as $p) {
                    $contents[] = $p->name . ' (x' . $p->quantity . ')';
                }
            }
            if (empty($contents) && isset($bundleLegacy[$bid])) {
                foreach ($bundleLegacy[$bid] as $l) {
                    $contents[] = 'Item (x' . $l->quantity . ')';
                }
            }

            // Redemption aggregation
            $channelData = $redemptionMap[$bid] ?? [];
            $totalRedeemed = array_sum(array_column($channelData, 'redeemed'));
            $totalRevenue  = array_sum(array_column($channelData, 'revenue'));
            $channels = array_keys($channelData);

            $quotaMax   = (int) $bundle->totalMaxUsage;
            $usageRate  = $quotaMax > 0 ? round(($totalRedeemed / $quotaMax) * 100, 2) : 0.00;
            $remaining  = max(0, $quotaMax - $totalRedeemed);

            $rows[] = [
                'bundleId'       => $bid,
                'packageName'    => $bundle->packageName,
                'period'         => date('d/m/Y', strtotime($bundle->startDate)) . ' – ' . date('d/m/Y', strtotime($bundle->endDate)),
                'startDate'      => $bundle->startDate,
                'endDate'        => $bundle->endDate,
                'status'         => (int) $bundle->status,
                'packagePrice'   => (float) $bundle->packagePrice,
                'quotaMax'       => $quotaMax,
                'maxPerCustomer' => (int) $bundle->maxUsagePerCustomer,
                'redeemed'       => $totalRedeemed,
                'remaining'      => $remaining,
                'usageRate'      => $usageRate,
                'totalRevenue'   => round($totalRevenue, 2),
                'channels'       => $channels,
                'channelDetail'  => $channelData,
                'bundleContents' => implode('; ', $contents),
            ];
        }

        // Sort by totalRevenue desc
        usort($rows, fn($a, $b) => $b['totalRevenue'] <=> $a['totalRevenue']);

        // ── Totals ────────────────────────────────────────────────────────
        $grandRedeemed        = array_sum(array_column($rows, 'redeemed'));
        $grandRevenue         = array_sum(array_column($rows, 'totalRevenue'));
        $grandPackageRevenue  = array_sum(array_column($rows, 'packagePrice'));
        $activeCount          = count(array_filter($rows, fn($r) => $r['status'] === 1));

        // ── Chart data ────────────────────────────────────────────────────
        $chartRows = array_slice($rows, 0, 10); // top 10
        $chart = [
            'categories' => array_column($chartRows, 'packageName'),
            'seriesRevenue'  => [['name' => 'Total Revenue', 'data' => array_column($chartRows, 'totalRevenue')]],
            'seriesRedeemed' => [['name' => 'Redeemed', 'data' => array_column($chartRows, 'redeemed')]],
        ];

        return [
            'rows'   => $rows,
            'totals' => [
                'totalPackages'       => count($rows),
                'activePackages'      => $activeCount,
                'totalRedeemed'       => $grandRedeemed,
                'totalRevenue'        => round($grandRevenue, 2),
                'totalPackageRevenue' => round($grandPackageRevenue, 2),
            ],
            'chart'  => $chart,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Customer Spend
    // ─────────────────────────────────────────────────────────────────────

    public function indexCustomerSpend(Request $request)
    {
        $data = $this->fetchCustomerSpendData($request);
        return response()->json($data);
    }

    public function exportCustomerSpend(Request $request)
    {
        $request->merge(['rowPerPage' => 0]); // no pagination for export
        $data     = $this->fetchCustomerSpendData($request);
        $rows     = $data['rows'] ?? [];
        $dateFrom = $request->input('dateFrom', '');
        $dateTo   = $request->input('dateTo', '');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Spend');

        // ── Title ─────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'Customer Spend Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF003366');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'Periode: ' . ($dateFrom ?: '-') . ' s/d ' . ($dateTo ?: '-'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF595959');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Header ────────────────────────────────────────────────────────
        $headers = [
            'No', 'Nama Customer', 'Grup', 'Total Transaksi',
            'Clinic (Rp)', 'Hotel (Rp)', 'Salon (Rp)', 'Breeding (Rp)', 'Pet Shop (Rp)',
            'Total Spend (Rp)', 'Transaksi Terakhir', 'Join Date'
        ];
        foreach ($headers as $col => $h) {
            $cell  = $sheet->getCellByColumnAndRow($col + 1, 4);
            $style = $sheet->getStyleByColumnAndRow($col + 1, 4);
            $cell->setValue($h);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');
            $style->getAlignment()
                  ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                  ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()
                  ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                  ->getColor()->setARGB('FFBFBFBF');
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Data rows ─────────────────────────────────────────────────────
        $currencyFmt = '#,##0';
        $excelRow = 5;
        foreach ($rows as $idx => $r) {
            $bg = $idx % 2 === 0 ? 'FFD9E1F2' : 'FFFFFFFF';
            $rowData = [
                $idx + 1,
                $r['customerName'],
                $r['customerGroup'] ?? '-',
                $r['totalTransactions'],
                $r['clinicSpend'],
                $r['hotelSpend'],
                $r['salonSpend'],
                $r['breedingSpend'],
                $r['petshopSpend'],
                $r['totalSpend'],
                $r['lastTransaction'],
                $r['joinDate'],
            ];
            foreach ($rowData as $col => $val) {
                $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
                $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
                $cell->setValue($val);
                $style->getFont()->setSize(10);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                $style->getBorders()->getAllBorders()
                      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                      ->getColor()->setARGB('FFBFBFBF');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                if (in_array($col, [4, 5, 6, 7, 8, 9])) {
                    $style->getNumberFormat()->setFormatCode($currencyFmt);
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                } elseif ($col === 3) {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
            $excelRow++;
        }

        // ── Total row ─────────────────────────────────────────────────────
        $totals   = $data['totals'] ?? [];
        $totalRow = [
            '', 'TOTAL', '', $totals['totalTransactions'] ?? 0,
            $totals['clinicSpend']   ?? 0,
            $totals['hotelSpend']    ?? 0,
            $totals['salonSpend']    ?? 0,
            $totals['breedingSpend'] ?? 0,
            $totals['petshopSpend']  ?? 0,
            $totals['totalSpend']    ?? 0,
            '', '',
        ];
        foreach ($totalRow as $col => $val) {
            $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
            $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $cell->setValue($val);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');
            $style->getBorders()->getAllBorders()
                  ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                  ->getColor()->setARGB('FFBFBFBF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                  ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if (in_array($col, [4, 5, 6, 7, 8, 9])) {
                $style->getNumberFormat()->setFormatCode($currencyFmt);
                $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);

        // ── Column widths ─────────────────────────────────────────────────
        foreach ([4, 26, 18, 16, 18, 18, 18, 18, 18, 20, 20, 14] as $col => $w) {
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth($w);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Customer_Spend.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Aggregate total spending per customer across all 5 transaction sources.
     * Filters: dateFrom, dateTo, locationId[], customerGroupId[], minSpend
     */
    private function fetchCustomerSpendData(Request $request): array
    {
        $dateFrom       = $request->input('dateFrom');
        $dateTo         = $request->input('dateTo');
        $locationIds    = array_values(array_filter((array) $request->input('locationId', []),    fn($v) => $v !== '' && $v !== null));
        $customerGroups = array_values(array_filter((array) $request->input('customerGroup', []), fn($v) => $v !== '' && $v !== null));
        $minSpend       = (float) ($request->input('minSpend', 0));
        $goToPage       = max(1, (int) $request->input('goToPage', 1));
        $rowPerPage     = (int) $request->input('rowPerPage', 10);
        $orderColumn    = $request->input('orderColumn', 'totalSpend');
        $orderValue     = strtolower($request->input('orderValue', 'desc')) === 'asc' ? 'asc' : 'desc';

        // ── Source config ─────────────────────────────────────────────────
        $serviceSources = [
            'clinic'   => ['tx' => 'transactionPetClinics',  'tot' => 'transaction_pet_clinic_payment_totals'],
            'hotel'    => ['tx' => 'transaction_pet_hotels',  'tot' => 'transaction_pet_hotel_payment_totals'],
            'salon'    => ['tx' => 'transaction_pet_salons',  'tot' => 'transaction_pet_salon_payment_totals'],
            'breeding' => ['tx' => 'transaction_breedings',   'tot' => 'transaction_breeding_payment_totals'],
        ];

        // ── Aggregate per customer per service source ─────────────────────
        $spendMap = [];

        foreach ($serviceSources as $channel => $src) {
            $agg = DB::table("{$src['tx']} as t")
                ->join("{$src['tot']} as pt", 'pt.transactionId', '=', 't.id')
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->selectRaw('t.customerId, COUNT(DISTINCT t.id) as txCount, COALESCE(SUM(pt.amountPaid),0) as spend, MAX(t.created_at) as lastDate')
                ->groupBy('t.customerId')
                ->get();

            foreach ($agg as $row) {
                $cid = $row->customerId;
                if (!isset($spendMap[$cid])) $spendMap[$cid] = [];
                $spendMap[$cid][$channel] = [
                    'spend'    => (float) $row->spend,
                    'txCount'  => (int)   $row->txCount,
                    'lastDate' => $row->lastDate,
                ];
            }
        }

        // ── Pet Shop (uses totalPayment on header table) ──────────────────
        $shopAgg = DB::table('transactionpetshop as t')
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
            ->selectRaw('t.customerId, COUNT(t.id) as txCount, COALESCE(SUM(t.totalPayment),0) as spend, MAX(t.created_at) as lastDate')
            ->groupBy('t.customerId')
            ->get();

        foreach ($shopAgg as $row) {
            $cid = $row->customerId;
            if (!isset($spendMap[$cid])) $spendMap[$cid] = [];
            $spendMap[$cid]['petshop'] = [
                'spend'    => (float) $row->spend,
                'txCount'  => (int)   $row->txCount,
                'lastDate' => $row->lastDate,
            ];
        }

        if (empty($spendMap)) {
            return ['rows' => [], 'totals' => [], 'totalPagination' => 0, 'chart' => [], 'channelTotals' => []];
        }

        // ── Fetch customer info in bulk ────────────────────────────────────
        $customerIds = array_keys($spendMap);

        $customerQuery = DB::table('customer as c')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->whereIn('c.id', $customerIds)
            ->where(fn($q) => $q->where('c.isDeleted', 0)->orWhereNull('c.isDeleted'))
            ->select(
                'c.id',
                DB::raw("TRIM(CONCAT(COALESCE(c.firstName,''), IF(c.lastName IS NOT NULL AND c.lastName != '', CONCAT(' ', c.lastName), ''))) as fullName"),
                'c.customerGroupId',
                'cg.customerGroup as groupName',
                'c.joinDate'
            );

        if (!empty($customerGroups)) {
            $customerQuery->whereIn('c.customerGroupId', $customerGroups);
        }

        $customers = $customerQuery->get()->keyBy('id');

        // ── Build result rows ─────────────────────────────────────────────
        $rows = [];
        foreach ($spendMap as $cid => $channels) {
            if (!isset($customers[$cid])) continue;

            $cust     = $customers[$cid];
            $clinic   = $channels['clinic']   ?? ['spend' => 0, 'txCount' => 0, 'lastDate' => null];
            $hotel    = $channels['hotel']    ?? ['spend' => 0, 'txCount' => 0, 'lastDate' => null];
            $salon    = $channels['salon']    ?? ['spend' => 0, 'txCount' => 0, 'lastDate' => null];
            $breeding = $channels['breeding'] ?? ['spend' => 0, 'txCount' => 0, 'lastDate' => null];
            $petshop  = $channels['petshop']  ?? ['spend' => 0, 'txCount' => 0, 'lastDate' => null];

            $totalSpend = $clinic['spend'] + $hotel['spend'] + $salon['spend'] + $breeding['spend'] + $petshop['spend'];
            if ($minSpend > 0 && $totalSpend < $minSpend) continue;

            $totalTx = $clinic['txCount'] + $hotel['txCount'] + $salon['txCount'] + $breeding['txCount'] + $petshop['txCount'];

            $dates    = array_filter([$clinic['lastDate'], $hotel['lastDate'], $salon['lastDate'], $breeding['lastDate'], $petshop['lastDate']]);
            $lastDate = $dates ? max($dates) : null;

            $rows[] = [
                'customerId'        => $cid,
                'customerName'      => $cust->fullName ?: '-',
                'customerGroup'     => $cust->groupName ?? '-',
                'joinDate'          => $cust->joinDate ? date('d/m/Y', strtotime($cust->joinDate)) : '-',
                'totalTransactions' => $totalTx,
                'clinicSpend'       => round($clinic['spend'],   2),
                'hotelSpend'        => round($hotel['spend'],    2),
                'salonSpend'        => round($salon['spend'],    2),
                'breedingSpend'     => round($breeding['spend'], 2),
                'petshopSpend'      => round($petshop['spend'],  2),
                'totalSpend'        => round($totalSpend,        2),
                'lastTransaction'   => $lastDate ? date('d/m/Y', strtotime($lastDate)) : '-',
            ];
        }

        // ── Sort ──────────────────────────────────────────────────────────
        $allowed = ['totalSpend', 'totalTransactions', 'clinicSpend', 'hotelSpend', 'salonSpend', 'breedingSpend', 'petshopSpend', 'customerName', 'lastTransaction'];
        if (!in_array($orderColumn, $allowed)) $orderColumn = 'totalSpend';

        usort($rows, function ($a, $b) use ($orderColumn, $orderValue) {
            $va = $a[$orderColumn] ?? 0;
            $vb = $b[$orderColumn] ?? 0;
            return $orderValue === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
        });

        // ── Totals (before pagination) ────────────────────────────────────
        $totalPagination = count($rows);
        $grandSpend      = array_sum(array_column($rows, 'totalSpend'));
        $grandTx         = array_sum(array_column($rows, 'totalTransactions'));
        $totals = [
            'totalCustomers'    => $totalPagination,
            'totalTransactions' => $grandTx,
            'totalSpend'        => round($grandSpend, 2),
            'avgSpend'          => $totalPagination > 0 ? round($grandSpend / $totalPagination, 2) : 0,
            'topSpender'        => $rows[0] ?? null,
            'clinicSpend'       => round(array_sum(array_column($rows, 'clinicSpend')),   2),
            'hotelSpend'        => round(array_sum(array_column($rows, 'hotelSpend')),    2),
            'salonSpend'        => round(array_sum(array_column($rows, 'salonSpend')),    2),
            'breedingSpend'     => round(array_sum(array_column($rows, 'breedingSpend')), 2),
            'petshopSpend'      => round(array_sum(array_column($rows, 'petshopSpend')),  2),
        ];

        // ── Chart data ────────────────────────────────────────────────────
        $top10 = array_slice($rows, 0, 10);
        $chart = [
            'categories'  => array_column($top10, 'customerName'),
            'seriesSpend' => [['name' => 'Total Spend', 'data' => array_column($top10, 'totalSpend')]],
            'seriesTx'    => [['name' => 'Total Transactions', 'data' => array_column($top10, 'totalTransactions')]],
        ];

        $channelTotals = [
            ['label' => 'Clinic',   'value' => $totals['clinicSpend']],
            ['label' => 'Hotel',    'value' => $totals['hotelSpend']],
            ['label' => 'Salon',    'value' => $totals['salonSpend']],
            ['label' => 'Breeding', 'value' => $totals['breedingSpend']],
            ['label' => 'Pet Shop', 'value' => $totals['petshopSpend']],
        ];

        // ── Paginate ──────────────────────────────────────────────────────
        if ($rowPerPage > 0) {
            $offset = ($goToPage - 1) * $rowPerPage;
            $rows   = array_slice($rows, $offset, $rowPerPage);
        }

        return [
            'rows'            => $rows,
            'totals'          => $totals,
            'totalPagination' => $totalPagination,
            'chart'           => $chart,
            'channelTotals'   => $channelTotals,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Daily Reconciliation
    // ─────────────────────────────────────────────────────────────────────

    public function indexDailyReconciliation(Request $request)
    {
        $data = $this->fetchDailyReconciliationData($request);
        return response()->json($data);
    }

    public function exportDailyReconciliation(Request $request)
    {
        $data     = $this->fetchDailyReconciliationData($request);
        $dateFrom = $request->input('dateFrom', '');
        $dateTo   = $request->input('dateTo', '');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daily Reconciliation');

        $navyARGB = 'FF1F4E79';
        $whiteARGB = 'FFFFFFFF';
        $currFmt   = '#,##0';

        // ── Title ─────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Daily Reconciliation Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF003366');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Periode: ' . ($dateFrom ?: '-') . ' s/d ' . ($dateTo ?: '-'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF595959');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Section 1: Daily detail ───────────────────────────────────────
        $sheet->setCellValue('A4', 'REKAP HARIAN PER CHANNEL');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB($navyARGB);
        $sheet->getRowDimension(4)->setRowHeight(18);

        $headers = ['No', 'Tanggal', 'Channel', 'Jml Transaksi', 'Gross Revenue (Rp)', 'Total Paid (Rp)', 'Outstanding (Rp)', '% Lunas'];
        foreach ($headers as $col => $h) {
            $style = $sheet->getStyleByColumnAndRow($col + 1, 5);
            $sheet->getCellByColumnAndRow($col + 1, 5)->setValue($h);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB($whiteARGB);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($navyARGB);
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        }
        $sheet->getRowDimension(5)->setRowHeight(22);

        $excelRow = 6;
        $rowIdx   = 0;
        $dailyRows = $data['dailyRows'] ?? [];
        $currentDate = null;
        $dateGroup = [];

        foreach ($dailyRows as $r) {
            $bg = $rowIdx % 2 === 0 ? 'FFD9E1F2' : 'FFFFFFFF';
            $isDateSubtotal = ($r['isSubtotal'] ?? false);

            if ($isDateSubtotal) {
                $bg = 'FFE2EFDA'; // green tint for subtotals
            }

            $rowData = [
                $isDateSubtotal ? '' : ($rowIdx + 1),
                $r['date'],
                $r['channel'],
                $r['txCount'],
                $r['grossRevenue'],
                $r['totalPaid'],
                $r['outstanding'],
                number_format($r['percentPaid'], 1) . '%',
            ];

            foreach ($rowData as $col => $val) {
                $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
                $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
                $cell->setValue($val);
                $style->getFont()->setSize(10)->setBold($isDateSubtotal);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                if (in_array($col, [4, 5, 6])) {
                    $style->getNumberFormat()->setFormatCode($currFmt);
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($col, [3])) {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
            $excelRow++;
            if (!$isDateSubtotal) $rowIdx++;
        }

        // Grand total
        $totals = $data['totals'] ?? [];
        $grandRow = ['', 'GRAND TOTAL', '', $totals['totalTransactions'] ?? 0, $totals['totalGross'] ?? 0, $totals['totalPaid'] ?? 0, $totals['totalOutstanding'] ?? 0, number_format($totals['percentPaid'] ?? 0, 1) . '%'];
        foreach ($grandRow as $col => $val) {
            $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
            $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $cell->setValue($val);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB($whiteARGB);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($navyARGB);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if (in_array($col, [4, 5, 6])) {
                $style->getNumberFormat()->setFormatCode($currFmt);
                $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);
        $excelRow += 2;

        // ── Section 2: Channel summary ────────────────────────────────────
        $sheet->setCellValue('A' . $excelRow, 'REKAP PER CHANNEL');
        $sheet->getStyle('A' . $excelRow)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB($navyARGB);
        $sheet->getRowDimension($excelRow)->setRowHeight(18);
        $excelRow++;

        $sumHeaders = ['No', 'Channel', 'Jml Transaksi', 'Gross Revenue (Rp)', 'Total Paid (Rp)', 'Outstanding (Rp)', '% Lunas'];
        foreach ($sumHeaders as $col => $h) {
            $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $sheet->getCellByColumnAndRow($col + 1, $excelRow)->setValue($h);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB($whiteARGB);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($navyARGB);
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);
        $excelRow++;

        foreach (($data['channelSummary'] ?? []) as $idx => $r) {
            $bg = $idx % 2 === 0 ? 'FFD9E1F2' : 'FFFFFFFF';
            $sumRow = [$idx + 1, $r['channel'], $r['txCount'], $r['grossRevenue'], $r['totalPaid'], $r['outstanding'], number_format($r['percentPaid'], 1) . '%'];
            foreach ($sumRow as $col => $val) {
                $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
                $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
                $cell->setValue($val);
                $style->getFont()->setSize(10);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                if (in_array($col, [3, 4, 5])) {
                    $style->getNumberFormat()->setFormatCode($currFmt);
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                } elseif ($col === 2) {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
            $excelRow++;
        }

        // Channel total row
        $chTotalRow = ['', 'TOTAL', $totals['totalTransactions'] ?? 0, $totals['totalGross'] ?? 0, $totals['totalPaid'] ?? 0, $totals['totalOutstanding'] ?? 0, number_format($totals['percentPaid'] ?? 0, 1) . '%'];
        foreach ($chTotalRow as $col => $val) {
            $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
            $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $cell->setValue($val);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB($whiteARGB);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($navyARGB);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if (in_array($col, [3, 4, 5])) {
                $style->getNumberFormat()->setFormatCode($currFmt);
                $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);

        // ── Column widths ─────────────────────────────────────────────────
        foreach ([5, 14, 18, 16, 22, 20, 20, 12] as $col => $w) {
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth($w);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Daily_Reconciliation.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Aggregate gross revenue vs amount paid per day per channel.
     * Sources: 4 service channels (priceOverall vs amountPaid) + petshop (totalPayment vs isPayed).
     */
    private function fetchDailyReconciliationData(Request $request): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));

        // ── Source config for service channels ────────────────────────────
        $sources = [
            'Pet Clinic'  => ['tx' => 'transactionPetClinics',  'pay' => 'transaction_pet_clinic_payments',  'tot' => 'transaction_pet_clinic_payment_totals',  'fk' => 'transactionId'],
            'Pet Hotel'   => ['tx' => 'transaction_pet_hotels',  'pay' => 'transaction_pet_hotel_payments',   'tot' => 'transaction_pet_hotel_payment_totals',   'fk' => 'transactionId'],
            'Pet Salon'   => ['tx' => 'transaction_pet_salons',  'pay' => 'transaction_pet_salon_payments',   'tot' => 'transaction_pet_salon_payment_totals',   'fk' => 'transactionId'],
            'Breeding'    => ['tx' => 'transaction_breedings',   'pay' => 'transactionBreedingPayments',      'tot' => 'transaction_breeding_payment_totals',    'fk' => 'transactionId'],
        ];

        // rawMap[date][channel] = { txCount, gross, paid }
        $rawMap = [];

        foreach ($sources as $channel => $src) {
            // Gross revenue = SUM(priceOverall) from payment line items, joined via tx for date/location
            $grossRows = DB::table("{$src['tx']} as t")
                ->join("{$src['pay']} as p", 'p.' . $src['fk'], '=', 't.id')
                ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
                ->where(fn($q) => $q->where('p.isDeleted', 0)->orWhereNull('p.isDeleted'))
                ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->selectRaw("DATE(t.created_at) as txDate, COUNT(DISTINCT t.id) as txCount, COALESCE(SUM(p.priceOverall),0) as gross")
                ->groupBy(DB::raw('DATE(t.created_at)'))
                ->get();

            foreach ($grossRows as $r) {
                $date = $r->txDate;
                if (!isset($rawMap[$date][$channel])) $rawMap[$date][$channel] = ['txCount' => 0, 'gross' => 0, 'paid' => 0];
                $rawMap[$date][$channel]['txCount'] = (int) $r->txCount;
                $rawMap[$date][$channel]['gross']   = (float) $r->gross;
            }

            // Paid = SUM(amountPaid) from payment_totals
            $paidRows = DB::table("{$src['tx']} as t")
                ->join("{$src['tot']} as pt", 'pt.transactionId', '=', 't.id')
                ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'))
                ->where(fn($q) => $q->where('pt.isDeleted', 0)->orWhereNull('pt.isDeleted'))
                ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
                ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
                ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
                ->selectRaw("DATE(t.created_at) as txDate, COALESCE(SUM(pt.amountPaid),0) as paid")
                ->groupBy(DB::raw('DATE(t.created_at)'))
                ->get();

            foreach ($paidRows as $r) {
                $date = $r->txDate;
                if (!isset($rawMap[$date][$channel])) $rawMap[$date][$channel] = ['txCount' => 0, 'gross' => 0, 'paid' => 0];
                $rawMap[$date][$channel]['paid'] = (float) $r->paid;
            }
        }

        // ── Pet Shop ──────────────────────────────────────────────────────
        $shopRows = DB::table('transactionpetshop as t')
            ->where(fn($q) => $q->where('t.isDeleted', 0)->orWhereNull('t.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('t.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('t.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('t.locationId', $locationIds))
            ->selectRaw("DATE(t.created_at) as txDate, COUNT(t.id) as txCount, COALESCE(SUM(t.totalPayment),0) as gross, COALESCE(SUM(CASE WHEN t.isPayed=1 THEN t.totalPayment ELSE 0 END),0) as paid")
            ->groupBy(DB::raw('DATE(t.created_at)'))
            ->get();

        foreach ($shopRows as $r) {
            $date = $r->txDate;
            if (!isset($rawMap[$date]['Pet Shop'])) $rawMap[$date]['Pet Shop'] = ['txCount' => 0, 'gross' => 0, 'paid' => 0];
            $rawMap[$date]['Pet Shop']['txCount'] = (int)   $r->txCount;
            $rawMap[$date]['Pet Shop']['gross']   = (float) $r->gross;
            $rawMap[$date]['Pet Shop']['paid']    = (float) $r->paid;
        }

        if (empty($rawMap)) {
            return ['dailyRows' => [], 'channelSummary' => [], 'dailySummary' => [], 'totals' => [], 'chart' => []];
        }

        // ── Build flat daily rows (with subtotals) ─────────────────────────
        ksort($rawMap); // sort by date ascending
        $allChannels = ['Pet Clinic', 'Pet Hotel', 'Pet Salon', 'Breeding', 'Pet Shop'];
        $dailyRows   = [];
        $dailySummary = []; // for chart: per date totals

        foreach ($rawMap as $date => $channels) {
            $dateTx    = 0; $dateGross = 0; $datePaid = 0;
            foreach ($allChannels as $ch) {
                $d = $channels[$ch] ?? null;
                if (!$d || ($d['txCount'] === 0 && $d['gross'] == 0)) continue;

                $outstanding = $d['gross'] - $d['paid'];
                $pct         = $d['gross'] > 0 ? round($d['paid'] / $d['gross'] * 100, 1) : 0;

                $dailyRows[] = [
                    'date'        => date('d/m/Y', strtotime($date)),
                    'channel'     => $ch,
                    'txCount'     => $d['txCount'],
                    'grossRevenue'=> round($d['gross'], 2),
                    'totalPaid'   => round($d['paid'],  2),
                    'outstanding' => round($outstanding, 2),
                    'percentPaid' => $pct,
                    'isSubtotal'  => false,
                ];
                $dateTx    += $d['txCount'];
                $dateGross += $d['gross'];
                $datePaid  += $d['paid'];
            }

            if ($dateTx > 0) {
                $dateOutstanding = $dateGross - $datePaid;
                $datePct         = $dateGross > 0 ? round($datePaid / $dateGross * 100, 1) : 0;
                // Subtotal row for date
                $dailyRows[] = [
                    'date'        => 'Subtotal ' . date('d/m/Y', strtotime($date)),
                    'channel'     => '',
                    'txCount'     => $dateTx,
                    'grossRevenue'=> round($dateGross, 2),
                    'totalPaid'   => round($datePaid,  2),
                    'outstanding' => round($dateOutstanding, 2),
                    'percentPaid' => $datePct,
                    'isSubtotal'  => true,
                ];
                $dailySummary[] = [
                    'date'        => date('d/m/Y', strtotime($date)),
                    'dateRaw'     => $date,
                    'gross'       => round($dateGross, 2),
                    'paid'        => round($datePaid,  2),
                    'outstanding' => round($dateOutstanding, 2),
                ];
            }
        }

        // ── Channel summary ────────────────────────────────────────────────
        $channelMap = [];
        foreach ($rawMap as $date => $channels) {
            foreach ($channels as $ch => $d) {
                if (!isset($channelMap[$ch])) $channelMap[$ch] = ['txCount' => 0, 'gross' => 0, 'paid' => 0];
                $channelMap[$ch]['txCount'] += $d['txCount'];
                $channelMap[$ch]['gross']   += $d['gross'];
                $channelMap[$ch]['paid']    += $d['paid'];
            }
        }

        $channelSummary = [];
        foreach ($allChannels as $ch) {
            $d = $channelMap[$ch] ?? null;
            if (!$d || $d['txCount'] === 0) continue;
            $outstanding = $d['gross'] - $d['paid'];
            $channelSummary[] = [
                'channel'      => $ch,
                'txCount'      => $d['txCount'],
                'grossRevenue' => round($d['gross'], 2),
                'totalPaid'    => round($d['paid'],  2),
                'outstanding'  => round($outstanding, 2),
                'percentPaid'  => $d['gross'] > 0 ? round($d['paid'] / $d['gross'] * 100, 1) : 0,
            ];
        }

        // ── Grand totals ──────────────────────────────────────────────────
        $grandTx      = array_sum(array_column($channelSummary, 'txCount'));
        $grandGross   = array_sum(array_column($channelSummary, 'grossRevenue'));
        $grandPaid    = array_sum(array_column($channelSummary, 'totalPaid'));
        $grandOut     = $grandGross - $grandPaid;
        $grandPct     = $grandGross > 0 ? round($grandPaid / $grandGross * 100, 1) : 0;

        $totals = [
            'totalTransactions' => $grandTx,
            'totalGross'        => round($grandGross, 2),
            'totalPaid'         => round($grandPaid,  2),
            'totalOutstanding'  => round($grandOut,   2),
            'percentPaid'       => $grandPct,
        ];

        // ── Chart data ────────────────────────────────────────────────────
        $chart = [
            'categories'     => array_column($dailySummary, 'date'),
            'seriesPaid'     => [['name' => 'Total Paid',        'data' => array_column($dailySummary, 'paid')]],
            'seriesOutstand' => [['name' => 'Outstanding',       'data' => array_column($dailySummary, 'outstanding')]],
        ];

        return [
            'dailyRows'      => $dailyRows,
            'channelSummary' => $channelSummary,
            'dailySummary'   => $dailySummary,
            'totals'         => $totals,
            'chart'          => $chart,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Refunds
    // ─────────────────────────────────────────────────────────────────────

    public function indexRefunds(Request $request)
    {
        $data = $this->fetchRefundsData($request);
        return response()->json($data);
    }

    public function exportRefunds(Request $request)
    {
        $request->merge(['rowPerPage' => 0]);
        $data     = $this->fetchRefundsData($request);
        $rows     = $data['rows'] ?? [];
        $dateFrom = $request->input('dateFrom', '');
        $dateTo   = $request->input('dateTo', '');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Refunds');

        $navyARGB  = 'FF1F4E79';
        $whiteARGB = 'FFFFFFFF';
        $currFmt   = '#,##0';

        // ── Title ─────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'Refunds Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF003366');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'Periode: ' . ($dateFrom ?: '-') . ' s/d ' . ($dateTo ?: '-'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF595959');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Header ────────────────────────────────────────────────────────
        $headers = ['No', 'No. Refund', 'Tanggal', 'Customer', 'Channel', 'No. Invoice', 'Metode Pembayaran', 'Jumlah Refund (Rp)', 'Alasan', 'Catatan', 'Status', 'Disetujui Oleh'];
        foreach ($headers as $col => $h) {
            $style = $sheet->getStyleByColumnAndRow($col + 1, 4);
            $sheet->getCellByColumnAndRow($col + 1, 4)->setValue($h);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB($whiteARGB);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($navyARGB);
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Data rows ─────────────────────────────────────────────────────
        $statusMap = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];
        $excelRow  = 5;
        foreach ($rows as $idx => $r) {
            $bg = $idx % 2 === 0 ? 'FFD9E1F2' : 'FFFFFFFF';
            $rowData = [
                $idx + 1,
                $r['refundNumber'],
                $r['createdAt'],
                $r['customerName'],
                $r['serviceType'],
                $r['invoiceNumber'],
                $r['paymentMethod'],
                $r['amount'],
                $r['reason'],
                $r['notes'],
                $statusMap[$r['status']] ?? '-',
                $r['approvedBy'],
            ];
            foreach ($rowData as $col => $val) {
                $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
                $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
                $cell->setValue($val);
                $style->getFont()->setSize(10);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                if ($col === 7) {
                    $style->getNumberFormat()->setFormatCode($currFmt);
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($col, [0, 2, 10])) {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
            $excelRow++;
        }

        // ── Total row ─────────────────────────────────────────────────────
        $totals = $data['totals'] ?? [];
        $totalRow = ['', 'TOTAL', '', '', '', '', '', $totals['totalAmount'] ?? 0, '', '', '', ''];
        foreach ($totalRow as $col => $val) {
            $cell  = $sheet->getCellByColumnAndRow($col + 1, $excelRow);
            $style = $sheet->getStyleByColumnAndRow($col + 1, $excelRow);
            $cell->setValue($val);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setARGB($whiteARGB);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($navyARGB);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFBFBFBF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if ($col === 7) {
                $style->getNumberFormat()->setFormatCode($currFmt);
                $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getRowDimension($excelRow)->setRowHeight(20);

        // ── Column widths ─────────────────────────────────────────────────
        foreach ([5, 18, 14, 22, 14, 18, 20, 20, 30, 24, 12, 20] as $col => $w) {
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth($w);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Refunds.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function fetchRefundsData(Request $request): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = array_values(array_filter((array) $request->input('locationId', []),   fn($v) => $v !== '' && $v !== null));
        $serviceType = (string) ($request->input('serviceType') ?? '');
        $status      = $request->input('status', '');
        $goToPage    = max(1, (int) $request->input('goToPage', 1));
        $rowPerPage  = (int) $request->input('rowPerPage', 10);
        $orderColumn = $request->input('orderColumn', 'fr.created_at');
        $orderValue  = strtolower($request->input('orderValue', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedOrder = ['fr.created_at', 'fr.amount', 'fr.refundNumber', 'fr.serviceType', 'fr.status', 'customerName'];
        if (!in_array($orderColumn, $allowedOrder)) $orderColumn = 'fr.created_at';

        $query = DB::table('finance_refunds as fr')
            ->join('customer as c', 'c.id', '=', 'fr.customerId')
            ->join('location as l',  'l.id', '=', 'fr.locationId')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'fr.paymentMethodId')
            ->leftJoin('users as ua', 'ua.id', '=', 'fr.approvedBy')
            ->where(fn($q) => $q->where('fr.isDeleted', 0)->orWhereNull('fr.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('fr.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('fr.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('fr.locationId', $locationIds))
            ->when($serviceType !== '',  fn($q) => $q->where('fr.serviceType', $serviceType))
            ->when($status !== '',       fn($q) => $q->where('fr.status', (int) $status))
            ->select(
                'fr.id',
                'fr.refundNumber',
                'fr.serviceType',
                'fr.invoiceNumber',
                'fr.amount',
                'fr.reason',
                'fr.notes',
                'fr.status',
                'fr.created_at',
                DB::raw("TRIM(CONCAT(COALESCE(c.firstName,''), IF(c.lastName IS NOT NULL AND c.lastName != '', CONCAT(' ', c.lastName), ''))) AS customerName"),
                'l.locationName',
                DB::raw("COALESCE(pm.paymentMethod, '-') AS paymentMethod"),
                DB::raw("COALESCE(TRIM(CONCAT(COALESCE(ua.firstName,''), IF(ua.lastName IS NOT NULL AND ua.lastName != '', CONCAT(' ', ua.lastName), ''))), '-') AS approvedByName")
            );

        $total = $query->count();

        // ── Totals — fresh query to avoid mixing aggregate with non-aggregate selects ──
        $aggregates = DB::table('finance_refunds as fr')
            ->where(fn($q) => $q->where('fr.isDeleted', 0)->orWhereNull('fr.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('fr.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('fr.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('fr.locationId', $locationIds))
            ->when($serviceType !== '',  fn($q) => $q->where('fr.serviceType', $serviceType))
            ->when($status !== '',       fn($q) => $q->where('fr.status', (int) $status))
            ->selectRaw(
                'COUNT(*) as totalRefunds,
                 COALESCE(SUM(amount), 0) as totalAmount,
                 SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as totalPending,
                 SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as totalApproved,
                 SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as totalRejected,
                 COALESCE(SUM(CASE WHEN status = 1 THEN amount ELSE 0 END), 0) as approvedAmount,
                 COALESCE(SUM(CASE WHEN status = 0 THEN amount ELSE 0 END), 0) as pendingAmount'
            )->first();

        $totals = [
            'totalRefunds'   => (int)   ($aggregates->totalRefunds   ?? 0),
            'totalAmount'    => (float) ($aggregates->totalAmount     ?? 0),
            'totalPending'   => (int)   ($aggregates->totalPending    ?? 0),
            'totalApproved'  => (int)   ($aggregates->totalApproved   ?? 0),
            'totalRejected'  => (int)   ($aggregates->totalRejected   ?? 0),
            'approvedAmount' => (float) ($aggregates->approvedAmount  ?? 0),
            'pendingAmount'  => (float) ($aggregates->pendingAmount   ?? 0),
        ];

        // ── Chart: daily stacked by status ────────────────────────────────
        $chartData = DB::table('finance_refunds as fr')
            ->where(fn($q) => $q->where('fr.isDeleted', 0)->orWhereNull('fr.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('fr.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('fr.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('fr.locationId', $locationIds))
            ->when($serviceType !== '',  fn($q) => $q->where('fr.serviceType', $serviceType))
            ->selectRaw("DATE(fr.created_at) as txDate,
                COALESCE(SUM(CASE WHEN fr.status = 1 THEN fr.amount ELSE 0 END), 0) as approved,
                COALESCE(SUM(CASE WHEN fr.status = 0 THEN fr.amount ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN fr.status = 2 THEN fr.amount ELSE 0 END), 0) as rejected")
            ->groupBy(DB::raw('DATE(fr.created_at)'))
            ->orderBy('txDate')
            ->get();

        $chart = [
            'categories'     => $chartData->pluck('txDate')->map(fn($d) => date('d/m/Y', strtotime($d)))->values()->toArray(),
            'seriesApproved' => [['name' => 'Approved', 'data' => $chartData->pluck('approved')->map(fn($v) => (float) $v)->values()->toArray()]],
            'seriesPending'  => [['name' => 'Pending',  'data' => $chartData->pluck('pending')->map(fn($v) => (float) $v)->values()->toArray()]],
            'seriesRejected' => [['name' => 'Rejected', 'data' => $chartData->pluck('rejected')->map(fn($v) => (float) $v)->values()->toArray()]],
        ];

        // ── Channel summary for donut ─────────────────────────────────────
        $channelData = DB::table('finance_refunds as fr')
            ->where(fn($q) => $q->where('fr.isDeleted', 0)->orWhereNull('fr.isDeleted'))
            ->when($dateFrom,            fn($q) => $q->whereDate('fr.created_at', '>=', $dateFrom))
            ->when($dateTo,              fn($q) => $q->whereDate('fr.created_at', '<=', $dateTo))
            ->when(!empty($locationIds), fn($q) => $q->whereIn('fr.locationId', $locationIds))
            ->selectRaw('fr.serviceType, COUNT(*) as cnt, COALESCE(SUM(fr.amount),0) as totalAmount')
            ->groupBy('fr.serviceType')
            ->get();

        $channelSummary = $channelData->map(fn($r) => [
            'label'  => $r->serviceType,
            'count'  => (int)   $r->cnt,
            'amount' => (float) $r->totalAmount,
        ])->values()->toArray();

        // ── Paginate rows ─────────────────────────────────────────────────
        $rawRows = $query->orderBy($orderColumn, $orderValue)
            ->when($rowPerPage > 0, fn($q) => $q->offset(($goToPage - 1) * $rowPerPage)->limit($rowPerPage))
            ->get();

        $statusMap = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];
        $rows = $rawRows->map(fn($r) => [
            'id'            => $r->id,
            'refundNumber'  => $r->refundNumber,
            'createdAt'     => date('d/m/Y', strtotime($r->created_at)),
            'customerName'  => $r->customerName,
            'locationName'  => $r->locationName,
            'serviceType'   => $r->serviceType,
            'invoiceNumber' => $r->invoiceNumber,
            'paymentMethod' => $r->paymentMethod,
            'amount'        => (float) $r->amount,
            'reason'        => $r->reason ?? '-',
            'notes'         => $r->notes  ?? '-',
            'status'        => (int) $r->status,
            'statusLabel'   => $statusMap[(int) $r->status] ?? '-',
            'approvedBy'    => $r->approvedByName,
        ])->values()->toArray();

        return [
            'rows'           => $rows,
            'totals'         => $totals,
            'totalPagination'=> $total,
            'chart'          => $chart,
            'channelSummary' => $channelSummary,
        ];
    }
}
