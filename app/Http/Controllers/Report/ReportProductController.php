<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportProductController extends Controller
{
    public function indexStockCount(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);

        [$data, $total] = $this->fetchStockCount($request, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportStockCount(Request $request)
    {
        [$data] = $this->fetchStockCount($request);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_Stock_Count.xlsx');
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Category');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Supplier Name');
        $sheet->setCellValue('E1', 'Location');
        $sheet->setCellValue('F1', 'In Stock');

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $quantities = $item['quantities'] ?? [];
            if (empty($quantities)) {
                $sheet->setCellValue("A{$row}", $item['fullName']);
                $sheet->setCellValue("B{$row}", $item['category']);
                $sheet->setCellValue("C{$row}", $item['sku']);
                $sheet->setCellValue("D{$row}", $item['supplierName']);
                $sheet->setCellValue("E{$row}", '-');
                $sheet->setCellValue("F{$row}", 0);
                $row++;
            } else {
                foreach ($quantities as $qty) {
                    $sheet->setCellValue("A{$row}", $item['fullName']);
                    $sheet->setCellValue("B{$row}", $item['category']);
                    $sheet->setCellValue("C{$row}", $item['sku']);
                    $sheet->setCellValue("D{$row}", $item['supplierName']);
                    $sheet->setCellValue("E{$row}", $qty['location']);
                    $sheet->setCellValue("F{$row}", $qty['qty']);
                    $row++;
                }
            }
        }

        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $newFilePath = public_path() . '/template_download/' . 'Export Report Product Stock Count.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Product Stock Count.xlsx"',
        ]);
    }

    private function fetchStockCount(Request $request, int $itemPerPage = 0, int $page = 1): array
    {
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'fullName';
        $orderValue  = $request->input('orderValue')  ?: 'asc';

        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $brandIds    = array_values(array_filter((array) $request->input('brandId',    []), fn($v) => $v !== '' && $v !== null));
        $supplierIds = array_values(array_filter((array) $request->input('supplierId', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['fullName', 'category', 'sku', 'supplierName', 'brandName'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'fullName';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'asc';

        // Ambil semua grup produk unik (GROUP BY deduplikasi duplikat nama+brand+supplier)
        [$groups, $idToGroup] = $this->getUniqueProductGroups($locationIds, $brandIds, $supplierIds, $search);

        // Filter: hanya grup yang punya setidaknya 1 lokasi stock
        $filtered = $groups->filter(fn($g) => $g['hasStock']);

        $total = $filtered->count();

        // Urutkan dan paginate di PHP
        $sorted = $orderValue === 'desc'
            ? $filtered->sortByDesc(fn($g) => strtolower($g[$orderColumn] ?? ''))
            : $filtered->sortBy(fn($g) => strtolower($g[$orderColumn] ?? ''));

        $page_groups = $itemPerPage > 0
            ? $sorted->slice(($page - 1) * $itemPerPage, $itemPerPage)
            : $sorted;

        // Ambil stocks untuk semua ID di halaman ini
        $pageAllIds = $page_groups->flatMap(fn($g) => $g['allIds'])->values()->toArray();

        $stockRows = $this->getStocksForIds($pageAllIds, $locationIds);

        // Agregasi per grup
        $result = $page_groups->map(function ($g) use ($stockRows, $idToGroup) {
            $locStocks = $this->aggregateStocks($g['allIds'], $stockRows, $idToGroup);
            return [
                'id'           => $g['id'],
                'fullName'     => $g['fullName'],
                'category'     => $g['category'],
                'sku'          => $g['sku'],
                'supplierName' => $g['supplierName'],
                'brandName'    => $g['brandName'],
                'quantities'   => array_values($locStocks),
            ];
        })->values();

        return [$result, $total];
    }

    public function indexLowStock(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);

        [$data, $total] = $this->fetchLowStock($request, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportLowStock(Request $request)
    {
        [$data] = $this->fetchLowStock($request);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_Low_Stock.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Category');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Supplier Name');
        $sheet->setCellValue('E1', 'Location');
        $sheet->setCellValue('F1', 'In Stock');
        $sheet->setCellValue('G1', 'Low Stock Threshold');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $quantities = $item['quantities'] ?? [];
            if (empty($quantities)) {
                $sheet->setCellValue("A{$row}", $item['fullName']);
                $sheet->setCellValue("B{$row}", $item['category']);
                $sheet->setCellValue("C{$row}", $item['sku']);
                $sheet->setCellValue("D{$row}", $item['supplierName']);
                $sheet->setCellValue("E{$row}", '-');
                $sheet->setCellValue("F{$row}", 0);
                $sheet->setCellValue("G{$row}", 0);
                $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;
            } else {
                foreach ($quantities as $qty) {
                    $sheet->setCellValue("A{$row}", $item['fullName']);
                    $sheet->setCellValue("B{$row}", $item['category']);
                    $sheet->setCellValue("C{$row}", $item['sku']);
                    $sheet->setCellValue("D{$row}", $item['supplierName']);
                    $sheet->setCellValue("E{$row}", $qty['location']);
                    $sheet->setCellValue("F{$row}", $qty['qty']);
                    $sheet->setCellValue("G{$row}", $qty['lowStock']);
                    $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $row++;
                }
            }
        }

        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Product Low Stock.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Product Low Stock.xlsx"',
        ]);
    }

    private function fetchLowStock(Request $request, int $itemPerPage = 0, int $page = 1): array
    {
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'fullName';
        $orderValue  = $request->input('orderValue')  ?: 'asc';

        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $brandIds    = array_values(array_filter((array) $request->input('brandId',    []), fn($v) => $v !== '' && $v !== null));
        $supplierIds = array_values(array_filter((array) $request->input('supplierId', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['fullName', 'category', 'sku', 'supplierName', 'brandName'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'fullName';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'asc';

        [$groups, $idToGroup] = $this->getUniqueProductGroups($locationIds, $brandIds, $supplierIds, $search);

        // Filter: hanya grup yang punya setidaknya 1 lokasi dengan inStock <= lowStock
        // Cek dari semua ID dalam grup (termasuk duplikat)
        $lowStockIds = DB::table('productLocations as pl')
            ->whereColumn('pl.inStock', '<=', 'pl.lowStock')
            ->where(function ($q) { $q->where('pl.isDeleted', 0)->orWhereNull('pl.isDeleted'); })
            ->when(!empty($locationIds), fn($q) => $q->whereIn('pl.locationId', $locationIds))
            ->pluck('pl.productId')
            ->flip(); // flip untuk O(1) lookup

        $filtered = $groups->filter(function ($g) use ($lowStockIds) {
            foreach ($g['allIds'] as $id) {
                if ($lowStockIds->has($id)) return true;
            }
            return false;
        });

        $total = $filtered->count();

        $sorted = $orderValue === 'desc'
            ? $filtered->sortByDesc(fn($g) => strtolower($g[$orderColumn] ?? ''))
            : $filtered->sortBy(fn($g) => strtolower($g[$orderColumn] ?? ''));

        $page_groups = $itemPerPage > 0
            ? $sorted->slice(($page - 1) * $itemPerPage, $itemPerPage)
            : $sorted;

        $pageAllIds = $page_groups->flatMap(fn($g) => $g['allIds'])->values()->toArray();

        // Ambil stocks + lowStock threshold
        $stockRows = DB::table('productLocations as pl')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->where(function ($q) { $q->where('pl.isDeleted', 0)->orWhereNull('pl.isDeleted'); })
            ->whereIn('pl.productId', $pageAllIds)
            ->when(!empty($locationIds), fn($q) => $q->whereIn('pl.locationId', $locationIds))
            ->select(['pl.productId', 'l.locationName', 'pl.inStock', 'pl.lowStock'])
            ->get();

        $result = $page_groups->map(function ($g) use ($stockRows, $idToGroup) {
            // Agregasi stok + ambil lowStock threshold (min per lokasi)
            $locData = [];
            foreach ($stockRows->whereIn('productId', $g['allIds']) as $s) {
                $loc = $s->locationName;
                $locData[$loc]['qty']      = ($locData[$loc]['qty'] ?? 0) + (int) $s->inStock;
                $locData[$loc]['lowStock'] = min($locData[$loc]['lowStock'] ?? PHP_INT_MAX, (int) $s->lowStock);
            }
            return [
                'id'           => $g['id'],
                'fullName'     => $g['fullName'],
                'category'     => $g['category'],
                'sku'          => $g['sku'],
                'supplierName' => $g['supplierName'],
                'brandName'    => $g['brandName'],
                'quantities'   => array_values(array_map(
                    fn($loc, $d) => ['location' => $loc, 'qty' => $d['qty'], 'lowStock' => $d['lowStock']],
                    array_keys($locData), array_values($locData)
                )),
            ];
        })->values();

        return [$result, $total];
    }

    public function indexCost(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);

        [$data, $total, $summary] = $this->fetchCost($request, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
            'summary'         => $summary,
        ]);
    }

    public function exportCost(Request $request)
    {
        [$data] = $this->fetchCost($request);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_Cost.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Brand');
        $sheet->setCellValue('C1', 'Supplier');
        $sheet->setCellValue('D1', 'Price (Rp)');
        $sheet->setCellValue('E1', 'Cost (Rp)');
        $sheet->setCellValue('F1', 'Location');
        $sheet->setCellValue('G1', 'In Stock');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $product) {
            $quantities = $product['quantities'];
            if (empty($quantities)) {
                // Produk tanpa lokasi stock — tulis 1 baris saja
                $sheet->setCellValue("A{$row}", $product['product']['name']);
                $sheet->setCellValue("B{$row}", $product['brandName']);
                $sheet->setCellValue("C{$row}", $product['supplierName']);
                $sheet->setCellValue("D{$row}", $product['averagePrice']);
                $sheet->setCellValue("E{$row}", $product['averageCost']);
                $sheet->setCellValue("F{$row}", '-');
                $sheet->setCellValue("G{$row}", 0);
                $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;
            } else {
                foreach ($quantities as $quantity) {
                    $sheet->setCellValue("A{$row}", $product['product']['name']);
                    $sheet->setCellValue("B{$row}", $product['brandName']);
                    $sheet->setCellValue("C{$row}", $product['supplierName']);
                    $sheet->setCellValue("D{$row}", $product['averagePrice']);
                    $sheet->setCellValue("E{$row}", $product['averageCost']);
                    $sheet->setCellValue("F{$row}", $quantity['location']);
                    $sheet->setCellValue("G{$row}", $quantity['qty']);
                    $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $row++;
                }
            }
        }

        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Product Cost.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Product Cost.xlsx"',
        ]);
    }

    private function fetchCost(Request $request, int $itemPerPage = 0, int $page = 1): array
    {
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'ps.fullName';
        $orderValue  = $request->input('orderValue')  ?: 'asc';
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');

        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $productIds  = array_values(array_filter((array) $request->input('productId',  []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['ps.fullName', 'pb.brandName', 'psup.supplierName', 'ps.price', 'ps.costPrice'];
        $allowedColumns = ['fullName', 'brandName', 'supplierName', 'price', 'costPrice'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'fullName';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'asc';

        // Ambil semua grup unik (deduplikasi produk duplikat di DB)
        [$groups, $idToGroup] = $this->getUniqueProductGroups($locationIds, [], [], $search);

        // Filter tambahan: dateFrom, dateTo, productIds
        if ($dateFrom || $dateTo || !empty($productIds)) {
            $extraIds = DB::table('products as ps')
                ->where('ps.isDeleted', 0)
                ->when($dateFrom, fn($q) => $q->whereDate('ps.created_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('ps.created_at', '<=', $dateTo))
                ->when(!empty($productIds), fn($q) => $q->whereIn('ps.id', $productIds))
                ->pluck('id')
                ->flip();

            $groups = $groups->filter(function ($g) use ($extraIds) {
                foreach ($g['allIds'] as $id) {
                    if ($extraIds->has($id)) return true;
                }
                return false;
            });
        }

        // Hanya produk yang punya stock
        $filtered = $groups->filter(fn($g) => $g['hasStock']);
        $total    = $filtered->count();

        // Summary stats (dari semua grup yang lolos)
        $allMatchIds = $filtered->flatMap(fn($g) => $g['allIds'])->values()->toArray();
        $totalQty    = DB::table('productLocations as pl')
            ->whereIn('pl.productId', $allMatchIds)
            ->where(function ($q) { $q->where('pl.isDeleted', 0)->orWhereNull('pl.isDeleted'); })
            ->sum('pl.inStock');
        $totalCost   = $filtered->sum('costPrice');

        $summary = [
            'totalProducts' => $total,
            'totalQuantity' => (int) $totalQty,
            'totalCost'     => (float) $totalCost,
        ];

        // Sort & paginate di PHP
        $sorted = $orderValue === 'desc'
            ? $filtered->sortByDesc(fn($g) => strtolower((string)($g[$orderColumn] ?? '')))
            : $filtered->sortBy(fn($g) => strtolower((string)($g[$orderColumn] ?? '')));

        $page_groups = $itemPerPage > 0
            ? $sorted->slice(($page - 1) * $itemPerPage, $itemPerPage)
            : $sorted;

        $pageAllIds = $page_groups->flatMap(fn($g) => $g['allIds'])->values()->toArray();

        $stockRows = $this->getStocksForIds($pageAllIds, $locationIds);

        $result = $page_groups->map(function ($g) use ($stockRows, $idToGroup) {
            $locStocks = $this->aggregateStocks($g['allIds'], $stockRows, $idToGroup);
            return [
                'product'      => ['id' => $g['id'], 'name' => $g['fullName']],
                'brandName'    => $g['brandName'],
                'supplierName' => $g['supplierName'],
                'averagePrice' => (float) $g['price'],
                'averageCost'  => (float) $g['costPrice'],
                'quantities'   => array_values($locStocks),
            ];
        })->values();

        return [$result, $total, $summary];
    }

    public function indexNoStock(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 5);
        $page        = (int) ($request->input('goToPage')   ?: 1);

        [$data, $total] = $this->fetchNoStock($request, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
        ]);
    }

    public function exportNoStock(Request $request)
    {
        [$data] = $this->fetchNoStock($request);

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_No_Stock.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Category');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Supplier Name');
        $sheet->setCellValue('E1', 'Location');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:E1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data as $item) {
            $quantities = $item['quantities'] ?? [];
            if (empty($quantities)) {
                $sheet->setCellValue("A{$row}", $item['fullName']);
                $sheet->setCellValue("B{$row}", $item['category']);
                $sheet->setCellValue("C{$row}", $item['sku']);
                $sheet->setCellValue("D{$row}", $item['supplierName']);
                $sheet->setCellValue("E{$row}", '-');
                $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;
            } else {
                foreach ($quantities as $qty) {
                    $sheet->setCellValue("A{$row}", $item['fullName']);
                    $sheet->setCellValue("B{$row}", $item['category']);
                    $sheet->setCellValue("C{$row}", $item['sku']);
                    $sheet->setCellValue("D{$row}", $item['supplierName']);
                    $sheet->setCellValue("E{$row}", $qty['location']);
                    $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $row++;
                }
            }
        }

        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Product No Stock.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Product No Stock.xlsx"',
        ]);
    }

    private function fetchNoStock(Request $request, int $itemPerPage = 0, int $page = 1): array
    {
        $search      = $request->input('search');
        $orderColumn = $request->input('orderColumn') ?: 'fullName';
        $orderValue  = $request->input('orderValue')  ?: 'asc';

        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));
        $brandIds    = array_values(array_filter((array) $request->input('brandId',    []), fn($v) => $v !== '' && $v !== null));
        $supplierIds = array_values(array_filter((array) $request->input('supplierId', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['fullName', 'category', 'sku', 'supplierName', 'brandName'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'fullName';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'asc';

        [$groups, $idToGroup] = $this->getUniqueProductGroups($locationIds, $brandIds, $supplierIds, $search);

        // Cari semua product ID yang punya inStock > 0 (di lokasi yg dipilih)
        $withStockIds = DB::table('productLocations as pl')
            ->where('pl.inStock', '>', 0)
            ->where(function ($q) { $q->where('pl.isDeleted', 0)->orWhereNull('pl.isDeleted'); })
            ->when(!empty($locationIds), fn($q) => $q->whereIn('pl.locationId', $locationIds))
            ->pluck('pl.productId')
            ->flip(); // O(1) lookup

        // Filter: grup yang TIDAK punya satupun ID dengan inStock > 0 (benar-benar no stock)
        // Dan grup harus punya setidaknya 1 lokasi stock record
        $filtered = $groups->filter(function ($g) use ($withStockIds) {
            if (!$g['hasStock']) return false; // tidak ada catatan stock sama sekali
            foreach ($g['allIds'] as $id) {
                if ($withStockIds->has($id)) return false; // ada yang punya stock
            }
            return true;
        });

        $total = $filtered->count();

        $sorted = $orderValue === 'desc'
            ? $filtered->sortByDesc(fn($g) => strtolower($g[$orderColumn] ?? ''))
            : $filtered->sortBy(fn($g) => strtolower($g[$orderColumn] ?? ''));

        $page_groups = $itemPerPage > 0
            ? $sorted->slice(($page - 1) * $itemPerPage, $itemPerPage)
            : $sorted;

        $pageAllIds = $page_groups->flatMap(fn($g) => $g['allIds'])->values()->toArray();

        $stockRows = $this->getStocksForIds($pageAllIds, $locationIds);

        $result = $page_groups->map(function ($g) use ($stockRows, $idToGroup) {
            $locStocks = $this->aggregateStocks($g['allIds'], $stockRows, $idToGroup);
            return [
                'id'           => $g['id'],
                'fullName'     => $g['fullName'],
                'category'     => $g['category'],
                'sku'          => $g['sku'],
                'supplierName' => $g['supplierName'],
                'brandName'    => $g['brandName'],
                'quantities'   => array_values($locStocks),
            ];
        })->values();

        return [$result, $total];
    }

    /**
     * Ambil semua produk unik terkelompok berdasarkan (fullName, brandId, supplierId).
     * Mengembalikan [$groups, $idToGroup]:
     *   - $groups: Collection array per grup (id=representative, allIds, fullName, sku, category, price, costPrice, brandName, supplierName, hasStock)
     *   - $idToGroup: array productId → representative id (untuk aggregasi stok)
     */
    private function getUniqueProductGroups(array $locationIds, array $brandIds, array $supplierIds, ?string $search): array
    {
        // Ambil semua produk dengan brand & supplier
        $query = DB::table('products as ps')
            ->leftJoin('productBrands as pb',      'ps.productBrandId',    '=', 'pb.id')
            ->leftJoin('productSuppliers as psup', 'ps.productSupplierId', '=', 'psup.id')
            ->where('ps.isDeleted', 0)
            ->select([
                'ps.id', 'ps.fullName', 'ps.sku', 'ps.category',
                'ps.price', 'ps.costPrice',
                DB::raw("IFNULL(pb.brandName, '') as brandName"),
                DB::raw("IFNULL(psup.supplierName, '') as supplierName"),
                'ps.productBrandId', 'ps.productSupplierId',
            ]);

        if (!empty($brandIds))    $query->whereIn('ps.productBrandId', $brandIds);
        if (!empty($supplierIds)) $query->whereIn('ps.productSupplierId', $supplierIds);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ps.fullName', 'like', "%{$search}%")
                  ->orWhere('ps.sku', 'like', "%{$search}%");
            });
        }

        $allProducts = $query->get();

        // Cari product ID yang punya stock (dengan filter lokasi jika ada)
        $hasStockIds = DB::table('productLocations as pl')
            ->where(function ($q) { $q->where('pl.isDeleted', 0)->orWhereNull('pl.isDeleted'); })
            ->when(!empty($locationIds), fn($q) => $q->whereIn('pl.locationId', $locationIds))
            ->pluck('pl.productId')
            ->flip(); // Collection untuk O(1) lookup

        // Kelompokkan produk berdasarkan (fullName, brandId, supplierId)
        $grouped = $allProducts->groupBy(fn($p) => $p->fullName . '||' . ($p->productBrandId ?? '') . '||' . ($p->productSupplierId ?? ''));

        // Bangun struktur grup
        $groups    = collect();
        $idToGroup = [];

        foreach ($grouped as $key => $items) {
            $first  = $items->first();
            $allIds = $items->pluck('id')->map(fn($id) => (int)$id)->toArray();

            // Representative id = MIN id
            $repId = min($allIds);

            foreach ($allIds as $id) {
                $idToGroup[$id] = $repId;
            }

            // hasStock: apakah ada setidaknya 1 ID dalam grup yang punya stock di lokasi terpilih
            $hasStock = collect($allIds)->contains(fn($id) => $hasStockIds->has($id));

            $groups->push([
                'id'           => $repId,
                'allIds'       => $allIds,
                'fullName'     => $first->fullName,
                'sku'          => $first->sku,
                'category'     => $first->category,
                'price'        => $first->price,
                'costPrice'    => $first->costPrice,
                'brandName'    => $first->brandName,
                'supplierName' => $first->supplierName,
                'hasStock'     => $hasStock,
            ]);
        }

        return [$groups, $idToGroup];
    }

    /**
     * Ambil rows stok per lokasi untuk daftar product ID.
     */
    private function getStocksForIds(array $productIds, array $locationIds): \Illuminate\Support\Collection
    {
        if (empty($productIds)) return collect();

        return DB::table('productLocations as pl')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->where(function ($q) { $q->where('pl.isDeleted', 0)->orWhereNull('pl.isDeleted'); })
            ->whereIn('pl.productId', $productIds)
            ->when(!empty($locationIds), fn($q) => $q->whereIn('pl.locationId', $locationIds))
            ->select(['pl.productId', 'l.locationName', 'pl.inStock'])
            ->get();
    }

    /**
     * Agregasi stok per lokasi untuk sekelompok product ID.
     * Mengembalikan array ['location' => ..., 'qty' => ...] per lokasi.
     */
    private function aggregateStocks(array $groupIds, \Illuminate\Support\Collection $stockRows, array $idToGroup): array
    {
        $locStocks = [];
        foreach ($stockRows as $s) {
            if (!in_array($s->productId, $groupIds)) continue;
            $loc = $s->locationName;
            $locStocks[$loc] = ($locStocks[$loc] ?? 0) + (int) $s->inStock;
        }
        return array_map(fn($loc, $qty) => ['location' => $loc, 'qty' => $qty], array_keys($locStocks), array_values($locStocks));
    }

    public function detail(Request $request)
    {
        $data = [
            'data' => [
                [
                    'customerName' => "1",
                    'supplierName' => 'Supplier 1',
                    'sku' => '123456',
                    'brandName' => 'Whiskas 500 g',
                    'categoryName' => "RPC Condet",
                ],
                [
                    'status' => "1",
                    'supplierName' => 'sell',
                    'sku' => '123456',
                    'brandName' => 'PT. Whiskas Indonesia',
                    'categoryName' => "RPC Condet",
                ],
                [
                    'status' => "1",
                    'supplierName' => 'sell',
                    'sku' => '123456',
                    'brandName' => 'PT. Whiskas Indonesia',
                    'categoryName' => "RPC Condet",
                ],
                [
                    'status' => "1",
                    'supplierName' => 'sell',
                    'sku' => '123456',
                    'brandName' => 'PT. Whiskas Indonesia',
                    'categoryName' => "RPC Condet",
                ]
            ]
        ];
        return response()->json($data);
    }

    public function indexReminders(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Belia',
                    'subAccount' => 'Pino',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62812299338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '30 May 2022 (17 days from now)',
                ],
            ]
        ];

        return response()->json($data);
    }
     
    public function exportReminders(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Belia',
                    'subAccount' => 'Pino',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62812299338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '31 May 2022 (18 days from now)',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '30 May 2022 (17 days from now)',
                ],
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_Reminders.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Customer');
        $sheet->setCellValue('B1', 'Sub Account');
        $sheet->setCellValue('C1', 'Product');
        $sheet->setCellValue('D1', 'Phone');
        $sheet->setCellValue('E1', 'Due Date');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($data['data'] as $item) {
            
            // $dueDate = \Carbon\Carbon::createFromFormat('Y-m-d', $item['dueDate'])->locale('en')->isoFormat('D MMMM YYYY');

            $sheet->setCellValue("A{$row}", $item['customerName']);
            $sheet->setCellValue("B{$row}", $item['subAccount']);
            $sheet->setCellValue("C{$row}", $item['productName']);
            $sheet->setCellValue("D{$row}", $item['phoneNumber']);
            $sheet->setCellValue("E{$row}", $item['dueDate']);

            $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
        }


        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Product Reminders.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Product Reminders.xlsx"',
        ]);
    }

    // ─────────────────────────────────────────────
    //  PRODUCT BATCHES
    // ─────────────────────────────────────────────

    public function indexBatches(Request $request)
    {
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 10);
        $page        = (int) ($request->input('goToPage')   ?: 1);
        $orderColumn = $request->input('orderColumn') ?: 'pb.created_at';
        $orderValue  = strtolower($request->input('orderValue') ?: 'desc');

        [$data, $total, $summary] = $this->fetchBatches($request, $orderColumn, $orderValue, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => $itemPerPage > 0 ? (int) ceil($total / $itemPerPage) : 1,
            'data'            => $data->values(),
            'summary'         => $summary,
        ]);
    }

    public function exportBatches(Request $request)
    {
        [$data] = $this->fetchBatches($request, 'pb.created_at', 'desc');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Product Batches');

        // Header
        $headers = ['Batch Number', 'Product', 'SKU', 'Qty', 'Harga/Item', 'Total Nilai', 'Supplier', 'Expired Date', 'Status', 'No. Restock', 'Tanggal Masuk'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAD3');

        // Data rows
        $row = 2;
        $today = Carbon::today();
        foreach ($data as $item) {
            $exp = $item['expiredDateRaw'] ? Carbon::parse($item['expiredDateRaw']) : null;
            if (!$exp)                          $status = '-';
            elseif ($exp->lt($today))           $status = 'Expired';
            elseif ($exp->diffInDays($today) <= 30) $status = 'Expiring Soon';
            else                                $status = 'Active';

            $sheet->setCellValue("A{$row}", $item['batchNumber']);
            $sheet->setCellValue("B{$row}", $item['productName']);
            $sheet->setCellValue("C{$row}", $item['sku']);
            $sheet->setCellValue("D{$row}", $item['quantity']);
            $sheet->setCellValue("E{$row}", $item['costPerItem']);
            $sheet->setCellValue("F{$row}", $item['totalValue']);
            $sheet->setCellValue("G{$row}", $item['supplierName']);
            $sheet->setCellValue("H{$row}", $item['expiredDate']);
            $sheet->setCellValue("I{$row}", $status);
            $sheet->setCellValue("J{$row}", $item['restockNumber']);
            $sheet->setCellValue("K{$row}", $item['createdAt']);

            // Colour expired rows
            if ($status === 'Expired') {
                $sheet->getStyle("A{$row}:K{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFCE4EC');
            } elseif ($status === 'Expiring Soon') {
                $sheet->getStyle("A{$row}:K{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF8E1');
            }

            $row++;
        }

        $writer    = new Xlsx($spreadsheet);
        $timestamp = now()->format('Ymd');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"Export Product Batches {$timestamp}.xlsx\"",
        ]);
    }

    /**
     * Shared query untuk indexBatches() dan exportBatches().
     * Return: [$collection, $totalCount, $summary]
     */
    private function fetchBatches(Request $request, string $orderColumn, string $orderValue, int $itemPerPage = 0, int $page = 1): array
    {
        $dateFrom     = $request->input('dateFrom');
        $dateTo       = $request->input('dateTo');
        $search       = $request->input('search');
        $expiryStatus = $request->input('expiryStatus'); // active | expiring | expired
        $locationIds  = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));

        $allowedColumns = ['pb.created_at', 'pb.batchNumber', 'p.fullName', 'pb.expiredDate', 'prd.received'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'pb.created_at';
        if (!in_array($orderValue, ['asc', 'desc']))  $orderValue  = 'desc';

        $today = Carbon::today()->toDateString();

        $query = DB::table('productBatches as pb')
            ->join('products as p', 'p.id', '=', 'pb.productId')
            ->leftJoin('productRestocks as pr', 'pr.id', '=', 'pb.productRestockId')
            ->leftJoin('productRestockDetails as prd', 'prd.id', '=', 'pb.productRestockDetailId')
            ->leftJoin('productSuppliers as sup', 'sup.id', '=', 'prd.supplierId')
            ->leftJoin('location as loc', 'loc.id', '=', 'pr.locationId')
            ->where('pb.isDeleted', 0)
            ->where('pb.productRestockId', '!=', 0)
            ->select([
                'pb.batchNumber',
                'p.fullName as productName',
                'pb.sku',
                DB::raw('COALESCE(prd.received, 0) as quantity'),
                DB::raw('COALESCE(prd.costPerItem, 0) as costPerItem'),
                DB::raw('COALESCE(prd.received, 0) * COALESCE(prd.costPerItem, 0) as totalValue'),
                DB::raw("COALESCE(sup.supplierName, '-') as supplierName"),
                DB::raw("NULLIF(pb.expiredDate, '0000-00-00') as expiredDate"),
                'pr.numberId as restockNumber',
                DB::raw("DATE_FORMAT(pb.created_at, '%d/%m/%Y') as createdAt"),
            ]);

        if ($dateFrom)            $query->whereDate('pb.created_at', '>=', $dateFrom);
        if ($dateTo)              $query->whereDate('pb.created_at', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('loc.id', $locationIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pb.batchNumber', 'like', "%{$search}%")
                  ->orWhere('p.fullName',   'like', "%{$search}%")
                  ->orWhere('pr.numberId',  'like', "%{$search}%");
            });
        }

        if ($expiryStatus === 'active') {
            $query->where(function ($q) use ($today) {
                $q->whereNull('pb.expiredDate')
                  ->orWhere('pb.expiredDate', '>', Carbon::today()->addDays(30)->toDateString());
            });
        } elseif ($expiryStatus === 'expiring') {
            $query->whereBetween('pb.expiredDate', [$today, Carbon::today()->addDays(30)->toDateString()]);
        } elseif ($expiryStatus === 'expired') {
            $query->where('pb.expiredDate', '<', $today);
        }

        // Summary counts (before pagination)
        $allRows   = (clone $query)->get(['pb.expiredDate']);
        $total     = $allRows->count();
        $expired   = $allRows->filter(fn($r) => $r->expiredDate && $r->expiredDate < $today)->count();
        $expiring  = $allRows->filter(fn($r) => $r->expiredDate && $r->expiredDate >= $today && $r->expiredDate <= Carbon::today()->addDays(30)->toDateString())->count();
        $active    = $total - $expired - $expiring;

        $summary = [
            'total'        => $total,
            'active'       => $active,
            'expiringSoon' => $expiring,
            'expired'      => $expired,
        ];

        $query->orderBy($orderColumn, $orderValue);

        if ($itemPerPage > 0) {
            $query->limit($itemPerPage)->offset(($page - 1) * $itemPerPage);
        }

        $rows = $query->get()->map(fn($r) => [
            'batchNumber'    => $r->batchNumber,
            'productName'    => $r->productName,
            'sku'            => $r->sku,
            'quantity'       => (int) $r->quantity,
            'costPerItem'    => (float) $r->costPerItem,
            'totalValue'     => (float) $r->totalValue,
            'supplierName'   => $r->supplierName,
            'expiredDate'    => $r->expiredDate ? Carbon::parse($r->expiredDate)->format('d/m/Y') : '-',
            'expiredDateRaw' => $r->expiredDate,
            'restockNumber'  => $r->restockNumber ?? '-',
            'createdAt'      => $r->createdAt,
        ]);

        return [$rows, $total, $summary];
    }

    // ── EXPIRY ────────────────────────────────────────────────────────────────

    public function indexExpiry(Request $request)
    {
        $orderColumn = $request->input('orderColumn') ?: 'pb.expiredDate';
        $orderValue  = $request->input('orderValue')  ?: 'asc';
        $itemPerPage = (int) ($request->input('rowPerPage') ?: 10);
        $page        = (int) ($request->input('goToPage')   ?: 1);

        [$rows, $total, $summary] = $this->fetchExpiry($request, $orderColumn, $orderValue, $itemPerPage, $page);

        return response()->json([
            'totalPagination' => ceil($total / max($itemPerPage, 1)),
            'count'           => $total,
            'summary'         => $summary,
            'data'            => $rows,
        ], 200);
    }

    public function exportExpiry(Request $request)
    {
        [$data] = $this->fetchExpiry($request, 'pb.expiredDate', 'asc');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Product Expiry');

        $headers = ['Batch Number', 'Product', 'SKU', 'Qty', 'Expired Date', 'Sisa Hari', 'Location', 'Status'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAD3');

        $row   = 2;
        $today = Carbon::today();
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['batchNumber']);
            $sheet->setCellValue("B{$row}", $item['productName']);
            $sheet->setCellValue("C{$row}", $item['sku']);
            $sheet->setCellValue("D{$row}", $item['quantity']);
            $sheet->setCellValue("E{$row}", $item['expiredDate']);
            $sheet->setCellValue("F{$row}", $item['daysLeft']);
            $sheet->setCellValue("G{$row}", $item['locationName']);
            $sheet->setCellValue("H{$row}", $item['status']);

            $color = match ($item['status']) {
                'Expired'    => 'FFFCE4EC',
                'Kritis'     => 'FFFFE0B2',
                'Peringatan' => 'FFFFF8E1',
                'Aman'       => 'FFF1F8E9',
                default      => null,
            };
            if ($color) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);
            }
            $row++;
        }

        $writer    = new Xlsx($spreadsheet);
        $timestamp = now()->format('Ymd');

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"Export Product Expiry {$timestamp}.xlsx\"",
        ]);
    }

    private function fetchExpiry(Request $request, string $orderColumn, string $orderValue, int $itemPerPage = 0, int $page = 1): array
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $search      = $request->input('search');
        $status      = $request->input('status'); // expired | critical | warning | safe | upcoming90
        $locationIds = array_values(array_filter((array) $request->input('locationId', []), fn($v) => $v !== '' && $v !== null));

        $allowed = ['pb.expiredDate', 'pb.batchNumber', 'p.fullName', 'prd.received', 'loc.locationName'];
        if (!in_array($orderColumn, $allowed)) $orderColumn = 'pb.expiredDate';
        if (!in_array($orderValue, ['asc', 'desc'])) $orderValue = 'asc';

        $today  = Carbon::today()->toDateString();
        $in7    = Carbon::today()->addDays(7)->toDateString();
        $in30   = Carbon::today()->addDays(30)->toDateString();
        $in90   = Carbon::today()->addDays(90)->toDateString();

        $query = DB::table('productBatches as pb')
            ->join('products as p', 'p.id', '=', 'pb.productId')
            ->leftJoin('productRestocks as pr', 'pr.id', '=', 'pb.productRestockId')
            ->leftJoin('productRestockDetails as prd', 'prd.id', '=', 'pb.productRestockDetailId')
            ->leftJoin('location as loc', 'loc.id', '=', 'pr.locationId')
            ->where('pb.isDeleted', 0)
            ->where('pb.productRestockId', '!=', 0)
            ->whereNotNull(DB::raw("NULLIF(pb.expiredDate, '0000-00-00')"))
            ->select([
                'pb.batchNumber',
                'p.fullName as productName',
                'pb.sku',
                DB::raw('COALESCE(prd.received, 0) as quantity'),
                DB::raw("NULLIF(pb.expiredDate, '0000-00-00') as expiredDate"),
                DB::raw("COALESCE(loc.locationName, '-') as locationName"),
            ]);

        if ($dateFrom)            $query->whereDate('pb.expiredDate', '>=', $dateFrom);
        if ($dateTo)              $query->whereDate('pb.expiredDate', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('loc.id', $locationIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pb.batchNumber', 'like', "%{$search}%")
                  ->orWhere('p.fullName',   'like', "%{$search}%");
            });
        }

        if ($status === 'expired')    $query->where('pb.expiredDate', '<', $today);
        elseif ($status === 'critical')   $query->whereBetween('pb.expiredDate', [$today, $in7]);
        elseif ($status === 'warning')    $query->whereBetween('pb.expiredDate', [$today, $in30]);
        elseif ($status === 'upcoming90') $query->whereBetween('pb.expiredDate', [$today, $in90]);
        elseif ($status === 'safe')       $query->where('pb.expiredDate', '>', $in30);

        // Summary (sebelum pagination)
        $allRows  = (clone $query)->get(['pb.expiredDate']);
        $total    = $allRows->count();
        $expired  = $allRows->filter(fn($r) => $r->expiredDate < $today)->count();
        $critical = $allRows->filter(fn($r) => $r->expiredDate >= $today && $r->expiredDate <= $in7)->count();
        $warning  = $allRows->filter(fn($r) => $r->expiredDate > $in7  && $r->expiredDate <= $in30)->count();
        $safe     = $allRows->filter(fn($r) => $r->expiredDate > $in30)->count();

        $summary = compact('total', 'expired', 'critical', 'warning', 'safe');

        $query->orderBy($orderColumn, $orderValue);
        if ($itemPerPage > 0) {
            $query->limit($itemPerPage)->offset(($page - 1) * $itemPerPage);
        }

        $rows = $query->get()->map(function ($r) use ($today) {
            $exp      = Carbon::parse($r->expiredDate);
            $daysLeft = (int) Carbon::today()->diffInDays($exp, false);

            if ($daysLeft < 0)      $status = 'Expired';
            elseif ($daysLeft <= 7) $status = 'Kritis';
            elseif ($daysLeft <= 30) $status = 'Peringatan';
            else                    $status = 'Aman';

            return [
                'batchNumber' => $r->batchNumber,
                'productName' => $r->productName,
                'sku'         => $r->sku,
                'quantity'    => (int) $r->quantity,
                'expiredDate' => $exp->format('d/m/Y'),
                'daysLeft'    => $daysLeft,
                'locationName'=> $r->locationName,
                'status'      => $status,
            ];
        });

        return [$rows, $total, $summary];
    }
}
