<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductController extends Controller
{
    public function indexStockCount(Request $request)
    {
        $data = DB::table('products as ps')
            ->join('productLocations as pl', 'ps.id', '=', 'pl.productId')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', '=', 'psup.id')
            ->select(
                'ps.fullName',
                'ps.category',
                'ps.sku',
                DB::raw("IFNULL(psup.supplierName, '') as supplierName"),
                'l.locationName',
                'pl.inStock',
            )
            ->where('ps.isDeleted', '=', 0)
            ->get();

        $responseData = [
            'totalPagination' => ceil($data->count() / 10),
            'data' => $data
        ];

        return response()->json($responseData);
    }


    public function exportStockCount(Request $request)
    {
        $data = DB::table('products as ps')
            ->join('productLocations as pl', 'ps.id', '=', 'pl.productId')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', '=', 'psup.id')
            ->select(
                'ps.fullName',
                'ps.category',
                'ps.sku',
                DB::raw("IFNULL(psup.supplierName, '') as supplierName"),
                'l.locationName',
                'pl.inStock',
            )
            ->where('ps.isDeleted', '=', 0)
            ->get();

        $spreadsheet = new Spreadsheet();
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
            $sheet->setCellValue("A{$row}", $item->fullName);
            $sheet->setCellValue("B{$row}", $item->category);
            $sheet->setCellValue("C{$row}", $item->sku);
            $sheet->setCellValue("D{$row}", $item->supplierName);
            $sheet->setCellValue("E{$row}", $item->locationName);
            $sheet->setCellValue("F{$row}", $item->inStock);

            $row++;
        }

        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $newFilePath = public_path() . '/template_download/' . 'Export Report Stock Count Report.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Stock Count.xlsx"',
        ]);
    }

    public function indexLowStock(Request $request)
    {
        $data = DB::table('products as ps')
            ->join('productLocations as pl', 'ps.id', '=', 'pl.productId')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', '=', 'psup.id')
            ->select(
                'ps.fullName',
                'ps.category',
                'ps.sku',
                DB::raw("IFNULL(psup.supplierName, '') as supplierName"),
                'l.locationName',
                'pl.lowStock',
            )
            ->where('ps.isDeleted', '=', 0)
            ->get();

        $responseData = [
            'totalPagination' => ceil($data->count() / 10),
            'data' => $data
        ];

        return response()->json($responseData);
    }

    public function exportLowStock(Request $request)
    {
        $data = DB::table('products as ps')
            ->join('productLocations as pl', 'ps.id', '=', 'pl.productId')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', '=', 'psup.id')
            ->select(
                'ps.fullName',
                'ps.category',
                'ps.sku',
                DB::raw("IFNULL(psup.supplierName, '') as supplierName"),
                'l.locationName',
                'pl.lowStock',
            )
            ->where('ps.isDeleted', '=', 0)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Category');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Supplier Name');
        $sheet->setCellValue('E1', 'Location');
        $sheet->setCellValue('F1', 'Low Stock');

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item->fullName);
            $sheet->setCellValue("B{$row}", $item->category);
            $sheet->setCellValue("C{$row}", $item->sku);
            $sheet->setCellValue("D{$row}", $item->supplierName);
            $sheet->setCellValue("E{$row}", $item->locationName);
            $sheet->setCellValue("F{$row}", $item->lowStock);

            $row++;
        }

        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $newFilePath = public_path() . '/template_download/' . 'Export Report Low Stock.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Low Stock.xlsx"',
        ]);
    }
}
