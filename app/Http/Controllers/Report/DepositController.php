<?php

namespace App\Http\Controllers\Report;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DepositController extends Controller
{
    public function indexList(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchDepositListData($request, $perPage, $page);
        return response()->json(['totalPagination' => $total, 'data' => $data]);
    }

    public function exportList(Request $request)
    {
        [$rows, ] = $this->fetchDepositListData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Deposit_List.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Reference No');
        $sheet->setCellValue('B1', 'Customer');
        $sheet->setCellValue('C1', 'Date');
        $sheet->setCellValue('D1', 'Location');
        $sheet->setCellValue('E1', 'Method');
        $sheet->setCellValue('F1', 'Received');
        $sheet->setCellValue('G1', 'Used As Payment');
        $sheet->setCellValue('H1', 'Returned');
        $sheet->setCellValue('I1', 'Remaining');
        $sheet->setCellValue('J1', 'Invoice');

        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:J1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($rows as $item) {
            $sheet->setCellValue("A{$row}", $item['referenceNo']);
            $sheet->setCellValue("B{$row}", $item['customerName']);
            $sheet->setCellValue("C{$row}", $item['date']);
            $sheet->setCellValue("D{$row}", $item['locationName']);
            $sheet->setCellValue("E{$row}", $item['paymentMethod']);
            $sheet->setCellValue("F{$row}", $item['receivedAmount']);
            $sheet->setCellValue("G{$row}", $item['usedAmount']);
            $sheet->setCellValue("H{$row}", $item['returnedAmount']);
            $sheet->setCellValue("I{$row}", $item['remainingAmount']);
            $sheet->setCellValue("J{$row}", $item['invoiceNo']);
            $sheet->getStyle("A{$row}:J{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Deposit List.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Deposit List.xlsx"',
        ]);
    }

    public function indexSummary(Request $request)
    {
        $page    = max(1, (int) $request->input('goToPage', 1));
        $perPage = max(1, (int) $request->input('rowPerPage', 10));
        [$data, $total] = $this->fetchDepositSummaryData($request, $perPage, $page);
        return response()->json(['totalPagination' => $total, 'data' => $data]);
    }

    public function exportSummary(Request $request)
    {
        [$rows, ] = $this->fetchDepositSummaryData($request, 0, 1);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Deposit_Summary.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Location');
        $sheet->setCellValue('B1', 'Return (Rp)');
        $sheet->setCellValue('C1', 'Used (Rp)');
        $sheet->setCellValue('D1', 'Remaining (Rp)');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:D1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($rows as $item) {
            $sheet->setCellValue("A{$row}", $item['locationName']);
            $sheet->setCellValue("B{$row}", $item['returnedAmount']);
            $sheet->setCellValue("C{$row}", $item['usedAmount']);
            $sheet->setCellValue("D{$row}", $item['remainingAmount']);
            $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'D') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Deposit Summary.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Deposit Summary.xlsx"',
        ]);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Fetch all deposit (prepayment) rows with computed amounts.
     * Sources: clinic prepayments + hotel prepayments + breeding prepayments.
     * Returns: [$rows, $total]
     *
     * Fields per row:
     *   referenceNo, customerName, date, locationName, paymentMethod,
     *   receivedAmount, usedAmount, returnedAmount, remainingAmount, invoiceNo
     */
    private function fetchDepositListData(Request $request, int $perPage, int $page): array
    {
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $methodIds   = array_values(array_filter((array) $request->input('methodId',   []), fn($v) => $v !== '' && $v !== null));
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $search      = $request->input('search');

        $rows = collect();

        // ---- 1. Clinic prepayments ----
        $clinicQ = DB::table('transactionPetClinicPrepayments as pp')
            ->join('transactionPetClinics as t', 't.id', '=', 'pp.transactionId')
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('paymentmethod as pm', 'pm.id', '=', 'pp.paymentMethodId')
            ->where(fn($q) => $q->where('pp.isDeleted', 0)->orWhereNull('pp.isDeleted'))
            ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'));

        if ($dateFrom)            $clinicQ->where(DB::raw('DATE(pp.created_at)'), '>=', $dateFrom);
        if ($dateTo)              $clinicQ->where(DB::raw('DATE(pp.created_at)'), '<=', $dateTo);
        if (!empty($locationIds)) $clinicQ->whereIn('t.locationId',      $locationIds);
        if (!empty($methodIds))   $clinicQ->whereIn('pp.paymentMethodId', $methodIds);
        if ($search)              $clinicQ->where(DB::raw("CONCAT(c.firstName,' ',COALESCE(c.middleName,''),' ',COALESCE(c.lastName,''))"), 'like', "%{$search}%");

        $clinicRows = $clinicQ->select([
            DB::raw("CONCAT('clinic_', pp.id) as _uid"),
            DB::raw("CONCAT('#', LPAD(pp.id, 6, '0')) as referenceNo"),
            DB::raw("TRIM(CONCAT(c.firstName,' ',COALESCE(c.middleName,''),' ',COALESCE(c.lastName,''))) as customerName"),
            DB::raw('DATE(pp.created_at) as date'),
            'l.locationName',
            'pm.name as paymentMethod',
            'pp.amount as receivedAmount',
            'pp.transactionId',
            DB::raw("'clinic' as _source"),
            DB::raw('COALESCE(t.registrationNo, "") as invoiceNo'),
        ])->get();

        $rows = $rows->merge($clinicRows);

        // ---- 2. Hotel prepayments ----
        $hotelQ = DB::table('transaction_pet_hotel_prepayments as pp')
            ->join('transaction_pet_hotels as t', 't.id', '=', 'pp.transactionId')
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('paymentmethod as pm', 'pm.id', '=', 'pp.paymentMethodId');
        // Hotel prepayments has no isDeleted column
        if ($dateFrom)            $hotelQ->where(DB::raw('DATE(pp.created_at)'), '>=', $dateFrom);
        if ($dateTo)              $hotelQ->where(DB::raw('DATE(pp.created_at)'), '<=', $dateTo);
        if (!empty($locationIds)) $hotelQ->whereIn('t.locationId',      $locationIds);
        if (!empty($methodIds))   $hotelQ->whereIn('pp.paymentMethodId', $methodIds);
        if ($search)              $hotelQ->where(DB::raw("CONCAT(c.firstName,' ',COALESCE(c.middleName,''),' ',COALESCE(c.lastName,''))"), 'like', "%{$search}%");
        if (!empty($locationIds)) $hotelQ->whereIn('t.locationId', $locationIds);

        $hotelRows = $hotelQ->select([
            DB::raw("CONCAT('hotel_', pp.id) as _uid"),
            DB::raw("COALESCE(pp.nota_number, CONCAT('#', LPAD(pp.id, 6, '0'))) as referenceNo"),
            DB::raw("TRIM(CONCAT(c.firstName,' ',COALESCE(c.middleName,''),' ',COALESCE(c.lastName,''))) as customerName"),
            DB::raw('DATE(pp.created_at) as date'),
            'l.locationName',
            'pm.name as paymentMethod',
            'pp.amount as receivedAmount',
            'pp.transactionId',
            DB::raw("'hotel' as _source"),
            DB::raw('COALESCE(t.registrationNo, "") as invoiceNo'),
        ])->get();

        $rows = $rows->merge($hotelRows);

        // ---- 3. Breeding prepayments ----
        $breedQ = DB::table('transaction_breeding_prepayments as pp')
            ->join('transaction_breedings as t', 't.id', '=', 'pp.transactionId')
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('paymentmethod as pm', 'pm.id', '=', 'pp.paymentMethodId')
            ->where(fn($q) => $q->where('pp.isDeleted', 0)->orWhereNull('pp.isDeleted'))
            ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'));

        if ($dateFrom)            $breedQ->where(DB::raw('DATE(pp.created_at)'), '>=', $dateFrom);
        if ($dateTo)              $breedQ->where(DB::raw('DATE(pp.created_at)'), '<=', $dateTo);
        if (!empty($locationIds)) $breedQ->whereIn('t.locationId',      $locationIds);
        if (!empty($methodIds))   $breedQ->whereIn('pp.paymentMethodId', $methodIds);
        if ($search)              $breedQ->where(DB::raw("CONCAT(c.firstName,' ',COALESCE(c.middleName,''),' ',COALESCE(c.lastName,''))"), 'like', "%{$search}%");

        $breedRows = $breedQ->select([
            DB::raw("CONCAT('breed_', pp.id) as _uid"),
            DB::raw("COALESCE(pp.nota_number, CONCAT('#', LPAD(pp.id, 6, '0'))) as referenceNo"),
            DB::raw("TRIM(CONCAT(c.firstName,' ',COALESCE(c.middleName,''),' ',COALESCE(c.lastName,''))) as customerName"),
            DB::raw('DATE(pp.created_at) as date'),
            'l.locationName',
            'pm.name as paymentMethod',
            'pp.amount as receivedAmount',
            'pp.transactionId',
            DB::raw("'breed' as _source"),
            DB::raw('COALESCE(t.registrationNo, "") as invoiceNo'),
        ])->get();

        $rows = $rows->merge($breedRows);

        // ---- Batch-fetch refunds (returnedAmount) keyed by transactionId ----
        $allTxIds = $rows->pluck('transactionId')->unique()->filter()->values()->toArray();
        $refundMap = []; // transactionId => float
        if (!empty($allTxIds)) {
            $refundRows = DB::table('finance_refunds')
                ->whereIn('transactionId', $allTxIds)
                ->where(fn($q) => $q->where('isDeleted', 0)->orWhereNull('isDeleted'))
                ->select('transactionId', DB::raw('SUM(amount) as total'))
                ->groupBy('transactionId')
                ->get();
            foreach ($refundRows as $r) {
                $refundMap[$r->transactionId] = (float) $r->total;
            }
        }

        // ---- Build result rows ----
        $result = [];
        foreach ($rows as $r) {
            $received  = (float) $r->receivedAmount;
            $returned  = $refundMap[$r->transactionId] ?? 0.0;
            $used      = 0.0; // No direct DB column; deposit stays as remaining until refunded
            $remaining = $received - $used - $returned;

            $result[] = [
                'referenceNo'     => $r->referenceNo,
                'customerName'    => $r->customerName,
                'date'            => $r->date,
                'locationName'    => $r->locationName,
                'paymentMethod'   => $r->paymentMethod,
                'receivedAmount'  => round($received, 2),
                'usedAmount'      => round($used, 2),
                'returnedAmount'  => round($returned, 2),
                'remainingAmount' => round($remaining, 2),
                'invoiceNo'       => $r->invoiceNo,
            ];
        }

        // Sort by date DESC
        usort($result, fn($a, $b) => strcmp($b['date'], $a['date']));

        $total = count($result);
        if ($perPage > 0) {
            $result = array_slice($result, ($page - 1) * $perPage, $perPage);
        }

        return [$result, $total];
    }

    /**
     * Fetch deposit summary rows grouped by location.
     * Returns: [$rows, $total]
     *
     * Fields per row: locationName, returnedAmount, usedAmount, remainingAmount
     */
    private function fetchDepositSummaryData(Request $request, int $perPage, int $page): array
    {
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $methodIds   = array_values(array_filter((array) $request->input('methodId',   []), fn($v) => $v !== '' && $v !== null));
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');

        // Aggregate per locationId using the same sources
        // locMap: locationId => [locationName, received, refunded]
        $locMap = [];

        $sources = [
            [
                'pp'     => 'transactionPetClinicPrepayments',
                'tx'     => 'transactionPetClinics',
                'hasDeleted' => true,
            ],
            [
                'pp'     => 'transaction_pet_hotel_prepayments',
                'tx'     => 'transaction_pet_hotels',
                'hasDeleted' => false,
            ],
            [
                'pp'     => 'transaction_breeding_prepayments',
                'tx'     => 'transaction_breedings',
                'hasDeleted' => true,
            ],
        ];

        foreach ($sources as $src) {
            $q = DB::table("{$src['pp']} as pp")
                ->join("{$src['tx']} as t", 't.id', '=', 'pp.transactionId')
                ->join('location as l', 'l.id', '=', 't.locationId');

            if ($src['hasDeleted']) {
                $q->where(fn($q) => $q->where('pp.isDeleted', 0)->orWhereNull('pp.isDeleted'))
                  ->where(fn($q) => $q->where('t.isDeleted',  0)->orWhereNull('t.isDeleted'));
            }

            if ($dateFrom)            $q->where(DB::raw('DATE(pp.created_at)'), '>=', $dateFrom);
            if ($dateTo)              $q->where(DB::raw('DATE(pp.created_at)'), '<=', $dateTo);
            if (!empty($locationIds)) $q->whereIn('t.locationId',      $locationIds);
            if (!empty($methodIds))   $q->whereIn('pp.paymentMethodId', $methodIds);

            $rows = $q->select([
                't.locationId',
                'l.locationName',
                DB::raw('SUM(pp.amount) as totalReceived'),
                DB::raw('GROUP_CONCAT(pp.transactionId) as txIds'),
            ])->groupBy('t.locationId', 'l.locationName')->get();

            foreach ($rows as $r) {
                $locId = $r->locationId;
                if (!isset($locMap[$locId])) {
                    $locMap[$locId] = ['locationName' => $r->locationName, 'received' => 0.0, 'txIds' => []];
                }
                $locMap[$locId]['received'] += (float) $r->totalReceived;
                // Collect all transactionIds for batch refund lookup
                if ($r->txIds) {
                    foreach (explode(',', $r->txIds) as $txId) {
                        if ($txId && !in_array((int)$txId, $locMap[$locId]['txIds'])) {
                            $locMap[$locId]['txIds'][] = (int) $txId;
                        }
                    }
                }
            }
        }

        // Batch-fetch refunds for all transactions
        $allTxIds = array_unique(array_merge(...array_column(array_values($locMap), 'txIds')));
        $refundByTx = [];
        if (!empty($allTxIds)) {
            $refundRows = DB::table('finance_refunds')
                ->whereIn('transactionId', $allTxIds)
                ->where(fn($q) => $q->where('isDeleted', 0)->orWhereNull('isDeleted'))
                ->select('transactionId', DB::raw('SUM(amount) as total'))
                ->groupBy('transactionId')
                ->get();
            foreach ($refundRows as $r) {
                $refundByTx[$r->transactionId] = (float) $r->total;
            }
        }

        // Build result rows
        $result = [];
        foreach ($locMap as $locId => $agg) {
            $returned = 0.0;
            foreach ($agg['txIds'] as $txId) {
                $returned += $refundByTx[$txId] ?? 0.0;
            }
            $used      = 0.0;
            $remaining = $agg['received'] - $used - $returned;

            $result[] = [
                'locationName'    => $agg['locationName'],
                'returnedAmount'  => round($returned, 2),
                'usedAmount'      => round($used, 2),
                'remainingAmount' => round($remaining, 2),
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['locationName'], $b['locationName']));

        $total = count($result);
        if ($perPage > 0) {
            $result = array_slice($result, ($page - 1) * $perPage, $perPage);
        }

        return [$result, $total];
    }
}
