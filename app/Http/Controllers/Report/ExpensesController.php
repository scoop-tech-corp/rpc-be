<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExpensesController extends Controller
{
    public function indexList(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);
        $orderColumn = $request->input('orderColumn') ?: 'e.transactionDate';
        $orderValue  = $request->input('orderValue')  ?: 'desc';

        [$data, $total] = $this->fetchExpensesList($request, $orderColumn, $orderValue, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportList(Request $request)
    {
        [$data] = $this->fetchExpensesList($request, 'e.transactionDate', 'desc');

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Expenses_List.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Expense');
        $sheet->setCellValue('B1', 'Location');
        $sheet->setCellValue('C1', 'Receipt Date');
        $sheet->setCellValue('D1', 'Submitter');
        $sheet->setCellValue('E1', 'Recipient');
        $sheet->setCellValue('F1', 'Supplier');
        $sheet->setCellValue('G1', 'Reference');
        $sheet->setCellValue('H1', 'Total (Rp)');
        $sheet->setCellValue('I1', 'Status');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:I1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['expenseId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['receiptDate']);
            $sheet->setCellValue("D{$row}", $item['submitter']);
            $sheet->setCellValue("E{$row}", $item['recipient']);
            $sheet->setCellValue("F{$row}", $item['supplier']);
            $sheet->setCellValue("G{$row}", $item['reference']);
            $sheet->setCellValue("H{$row}", $item['totalAmount']);
            $sheet->setCellValue("I{$row}", $item['status']);
            $sheet->getStyle("A{$row}:I{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $timestamp = now()->format('Ymd');
        $newFilePath = public_path() . '/template_download/' . 'Export Expenses List ' . $timestamp . '.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Expenses List ' . $timestamp . '.xlsx"',
        ]);
    }

    private function fetchExpensesList(Request $request, string $orderColumn, string $orderValue, int $itemPerPage = 0, int $page = 1): array
    {
        $dateFrom = $request->input('dateFrom');
        $dateTo   = $request->input('dateTo');
        $search   = $request->input('search');

        $locationIds  = array_values(array_filter((array) $request->input('locationId', []),   fn($v) => $v !== '' && $v !== null));
        $categoryIds  = array_values(array_filter((array) $request->input('categoryId', []),   fn($v) => $v !== '' && $v !== null));
        $submiterIds  = array_values(array_filter((array) $request->input('submiterId', []),   fn($v) => $v !== '' && $v !== null));
        $supplierIds  = array_values(array_filter((array) $request->input('supplierId', []),   fn($v) => $v !== '' && $v !== null));
        $recipientIds = array_values(array_filter((array) $request->input('recipientId', []),  fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['e.transactionDate', 'e.referenceNo', 'l.locationName', 'e.grandTotal', 'e.statusApproval'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'e.transactionDate';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'desc';

        $query = \Illuminate\Support\Facades\DB::table('expenses as e')
            ->join('location as l',             'l.id',  '=', 'e.locationId')
            ->join('users as u',                'u.id',  '=', 'e.userId')
            ->leftJoin('vendorFinances as vf',   'vf.id', '=', 'e.vendorId')
            ->leftJoin('categoryFinances as cf', 'cf.id', '=', 'e.categoryId')
            ->leftJoin('users as ua',            'ua.id', '=', 'e.userApprovalId')
            ->where(function ($q) {
                $q->where('e.isDeleted', 0)->orWhereNull('e.isDeleted');
            })
            ->select([
                'e.id',
                'e.referenceNo as expenseId',
                'l.locationName as location',
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(e.transactionDate, '%d %b %Y') as receiptDate"),
                \Illuminate\Support\Facades\DB::raw("TRIM(CONCAT(u.firstName, ' ',
                    COALESCE(NULLIF(u.middleName,''), ''), ' ',
                    COALESCE(NULLIF(u.lastName,''), ''))) as submitter"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(ua.firstName, '-') as recipient"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(vf.vendorName, '-') as supplier"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(e.description, '-') as reference"),
                \Illuminate\Support\Facades\DB::raw("TRIM(e.grandTotal)+0 as totalAmount"),
                'e.statusApproval as status',
            ]);

        if ($dateFrom)              $query->whereDate('e.transactionDate', '>=', $dateFrom);
        if ($dateTo)                $query->whereDate('e.transactionDate', '<=', $dateTo);
        if (!empty($locationIds))   $query->whereIn('e.locationId', $locationIds);
        if (!empty($categoryIds))   $query->whereIn('e.categoryId', $categoryIds);
        if (!empty($submiterIds))   $query->whereIn('e.userId', $submiterIds);
        if (!empty($supplierIds))   $query->whereIn('e.vendorId', $supplierIds);
        if (!empty($recipientIds))  $query->whereIn('e.userApprovalId', $recipientIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('e.referenceNo', 'like', "%{$search}%")
                  ->orWhere('e.description', 'like', "%{$search}%")
                  ->orWhere(\Illuminate\Support\Facades\DB::raw("TRIM(CONCAT(u.firstName, ' ',
                        COALESCE(NULLIF(u.middleName,''), ''), ' ',
                        COALESCE(NULLIF(u.lastName,''), '')))"), 'like', "%{$search}%")
                  ->orWhere('vf.vendorName', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();

        $query->orderBy($orderColumn, $orderValue);

        if ($itemPerPage > 0) {
            $query->limit($itemPerPage)->offset(($page - 1) * $itemPerPage);
        }

        $rows = $query->get()->map(fn($row) => [
            'id'          => $row->id,
            'expenseId'   => $row->expenseId,
            'location'    => $row->location,
            'receiptDate' => $row->receiptDate,
            'submitter'   => $row->submitter,
            'recipient'   => $row->recipient,
            'supplier'    => $row->supplier,
            'reference'   => $row->reference,
            'totalAmount' => (float) $row->totalAmount,
            'status'      => $row->status,
        ]);

        return [$rows, $total];
    }

    public function optionPayment()
    {
        $data = DB::table('paymentMethodFinances')
            ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
            ->orderBy('paymentMethod')
            ->get(['id', 'paymentMethod'])
            ->map(fn($r) => ['value' => $r->id, 'label' => $r->paymentMethod]);

        return response()->json($data);
    }

    public function optionStatus()
    {
        $statuses = ['Pending', 'Approved', 'Rejected'];
        $data = collect($statuses)->map(fn($s) => ['value' => $s, 'label' => $s]);

        return response()->json($data);
    }

    public function optionSubmiter()
    {
        $data = DB::table('users as u')
            ->join('expenses as e', 'e.userId', '=', 'u.id')
            ->where('u.isDeleted', 0)
            ->where(function ($q) { $q->where('e.isDeleted', 0)->orWhereNull('e.isDeleted'); })
            ->distinct()
            ->orderBy('u.firstName')
            ->get(['u.id', DB::raw("TRIM(CONCAT(u.firstName, ' ', COALESCE(NULLIF(u.middleName,''),''), ' ', COALESCE(NULLIF(u.lastName,''),''))) as name")])
            ->map(fn($r) => ['value' => $r->id, 'label' => trim($r->name)]);

        return response()->json($data);
    }

    public function optionRecipient()
    {
        $data = DB::table('users as u')
            ->join('expenses as e', 'e.userApprovalId', '=', 'u.id')
            ->where('u.isDeleted', 0)
            ->where(function ($q) { $q->where('e.isDeleted', 0)->orWhereNull('e.isDeleted'); })
            ->distinct()
            ->orderBy('u.firstName')
            ->get(['u.id', DB::raw("TRIM(CONCAT(u.firstName, ' ', COALESCE(NULLIF(u.middleName,''),''), ' ', COALESCE(NULLIF(u.lastName,''),''))) as name")])
            ->map(fn($r) => ['value' => $r->id, 'label' => trim($r->name)]);

        return response()->json($data);
    }

    public function optionCategory()
    {
        $data = DB::table('categoryFinances')
            ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
            ->orderBy('categoryName')
            ->get(['id', 'categoryName'])
            ->map(fn($r) => ['value' => $r->id, 'label' => $r->categoryName]);

        return response()->json($data);
    }

    public function optionSupplier()
    {
        $data = DB::table('vendorFinances')
            ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
            ->orderBy('vendorName')
            ->get(['id', 'vendorName'])
            ->map(fn($r) => ['value' => $r->id, 'label' => $r->vendorName]);

        return response()->json($data);
    }

    public function indexSummary(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);

        [$months, $data] = $this->fetchExpensesSummary($request);

        $total  = $data->count();
        $paged  = $itemPerPage > 0 ? $data->slice(($page - 1) * $itemPerPage, $itemPerPage)->values() : $data->values();

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $paged,
        ]);
    }

    public function exportSummary(Request $request)
    {
        [$months, $data] = $this->fetchExpensesSummary($request);

        $totalMonths = count($months);
        $lastColIndex = $totalMonths + 1; // +1 karena kolom A = Category

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Expenses_Summary.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        // Header row
        $sheet->setCellValue('A1', 'Category');
        foreach ($months as $idx => $m) {
            $sheet->setCellValueByColumnAndRow($idx + 2, 1, $m['label']);
        }

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);
        $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColLetter}1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColLetter}1")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Data rows
        $row    = 2;
        $totals = array_fill(1, $totalMonths, 0);

        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['category']);
            foreach ($months as $idx => $m) {
                $key   = 'month' . ($idx + 1);
                $value = $item[$key] ?? 0.00;
                $sheet->setCellValueByColumnAndRow($idx + 2, $row, $value);
                $totals[$idx + 1] += $value;
            }
            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        // Total row
        $sheet->setCellValue("A{$row}", 'Total');
        foreach ($months as $idx => $m) {
            $sheet->setCellValueByColumnAndRow($idx + 2, $row, $totals[$idx + 1]);
        }
        $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size semua kolom
        for ($c = 1; $c <= $lastColIndex; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $writer    = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $timestamp = now()->format('Ymd');
        $newFilePath = public_path() . '/template_download/' . 'Export Expenses Summary ' . $timestamp . '.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Expenses Summary ' . $timestamp . '.xlsx"',
        ]);
    }

    private function fetchExpensesSummary(Request $request): array
    {
        $locationIds = array_values(array_filter((array) $request->input('locationId', []),  fn($v) => $v !== '' && $v !== null));
        $categoryIds = array_values(array_filter((array) $request->input('categoryId', []),  fn($v) => $v !== '' && $v !== null));
        $supplierIds = array_values(array_filter((array) $request->input('supplierId', []),  fn($v) => $v !== '' && $v !== null));

        // 1. Bangun urutan bulan: 2 tahun terakhir dari bulan ini
        $startDate = Carbon::now()->subYears(2)->startOfMonth();
        $endDate   = Carbon::now()->startOfMonth();
        $months    = [];
        $current   = $startDate->copy();
        while ($current->lte($endDate)) {
            $months[] = [
                'year'  => $current->year,
                'month' => $current->month,
                'label' => $current->format('M y'),   // "Jan 24"
                'key'   => $current->year . '-' . $current->month,
            ];
            $current->addMonth();
        }

        // 2. Build monthKey lookup: "2024-1" => "month1" (1-based index)
        $monthLookup = [];
        foreach ($months as $idx => $m) {
            $monthLookup[$m['key']] = 'month' . ($idx + 1);
        }

        // 3. Ambil semua kategori aktif (sesuai filter)
        $categories = DB::table('categoryFinances')
            ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
            ->when(!empty($categoryIds), fn($q) => $q->whereIn('id', $categoryIds))
            ->orderBy('categoryName')
            ->get(['id', 'categoryName']);

        // 4. Query raw: SUM grandTotal per (categoryId, yr, mo)
        $rawData = DB::table('expenses as e')
            ->join('categoryFinances as cf', 'cf.id', '=', 'e.categoryId')
            ->where(function ($q) { $q->where('e.isDeleted', 0)->orWhereNull('e.isDeleted'); })
            ->whereDate('e.transactionDate', '>=', $startDate->toDateString())
            ->when(!empty($locationIds), fn($q) => $q->whereIn('e.locationId', $locationIds))
            ->when(!empty($categoryIds), fn($q) => $q->whereIn('e.categoryId', $categoryIds))
            ->when(!empty($supplierIds), fn($q) => $q->whereIn('e.vendorId', $supplierIds))
            ->select([
                'e.categoryId',
                DB::raw('YEAR(e.transactionDate) as yr'),
                DB::raw('MONTH(e.transactionDate) as mo'),
                DB::raw('SUM(e.grandTotal) as total'),
            ])
            ->groupBy('e.categoryId', 'yr', 'mo')
            ->get()
            ->groupBy('categoryId');

        // 5. Pivot: per kategori, isi nilai per monthKey
        $result = $categories->map(function ($cat) use ($months, $monthLookup, $rawData) {
            $row = ['categoryId' => $cat->id, 'category' => $cat->categoryName];
            foreach ($months as $idx => $m) {
                $row['month' . ($idx + 1)] = 0.00;
            }

            $catRaw = $rawData->get($cat->id, collect());
            foreach ($catRaw as $item) {
                $key = $item->yr . '-' . $item->mo;
                if (isset($monthLookup[$key])) {
                    $row[$monthLookup[$key]] = (float) $item->total;
                }
            }

            return $row;
        });

        return [$months, $result];
    }
}
