<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        $newFilePath = public_path() . '/template_download/' . 'Export Report Product Stock Count.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Product Stock Count.xlsx"',
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

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_Low_Stock.xlsx');
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

    public function indexCost(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'productName' => 'Zoletil Inj (1 ml)',
                    'brandName' => 'KLN',
                    'supplierName' => 'PT Emvi Indonesia',
                    'averagePrice' => 0,
                    'averageCost' => 0,
                    'quantities' => [
                        [
                            'location' => 'RPC Buaran Klender',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Condet',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Hankam Pondok Gede',
                            'qty' => 20
                        ],
                        [
                            'location' => 'RPC Karawang Tengah',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Karawaci',
                            'qty' => 10
                        ],
                        [
                            'location' => 'RPC Lippo Cikarang',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Pulogebang',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Rawamangu',
                            'qty' => 0
                        ]
                    ]
                ],
                [
                    'productName' => 'Zentonil Advance (SAme) Kapsul (1 Kapsul)',
                    'brandName' => 'KLN',
                    'supplierName' => 'Online',
                    'averagePrice' => 0,
                    'averageCost' => 0,
                    'quantities' => [
                        [
                            'location' => 'RPC Buaran Klender',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Condet',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Hankam Pondok Gede',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Karawang Tengah',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Karawaci',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Lippo Cikarang',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Pulogebang',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Rawamangu',
                            'qty' => 0
                        ]
                    ]
                ],
                [
                    'productName' => 'Yummy Raw Food Turkey 500gr',
                    'brandName' => 'PTS',
                    'supplierName' => 'Online',
                    'averagePrice' => 80500,
                    'averageCost' => 800,
                    'quantities' => [
                        [
                            'location' => 'RPC Buaran Klender',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Condet',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Hankam Pondok Gede',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Karawang Tengah',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Karawaci',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Lippo Cikarang',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Pulogebang',
                            'qty' => 0
                        ],
                        [
                            'location' => 'RPC Rawamangu',
                            'qty' => 0
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($data);
    }

    public function exportCost(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'productName' => 'Zoletil Inj (1 ml)',
                    'brandName' => 'KLN',
                    'supplierName' => 'PT Emvi Indonesia',
                    'averagePrice' => 0,
                    'averageCost' => 0,
                    'quantities' => [
                        ['location' => 'RPC Buaran Klender', 'qty' => 0],
                        ['location' => 'RPC Condet', 'qty' => 0],
                        ['location' => 'RPC Hankam Pondok Gede', 'qty' => 20],
                        ['location' => 'RPC Karawang Tengah', 'qty' => 0],
                        ['location' => 'RPC Karawaci', 'qty' => 10],
                        ['location' => 'RPC Lippo Cikarang', 'qty' => 0],
                        ['location' => 'RPC Pulogebang', 'qty' => 0],
                        ['location' => 'RPC Rawamangu', 'qty' => 0]
                    ]
                ],
                [
                    'productName' => 'Zentonil Advance (SAme) Kapsul (1 Kapsul)',
                    'brandName' => 'KLN',
                    'supplierName' => 'Online',
                    'averagePrice' => 0,
                    'averageCost' => 0,
                    'quantities' => [
                        ['location' => 'RPC Buaran Klender', 'qty' => 0],
                        ['location' => 'RPC Condet', 'qty' => 0],
                        ['location' => 'RPC Hankam Pondok Gede', 'qty' => 0],
                        ['location' => 'RPC Karawang Tengah', 'qty' => 0],
                        ['location' => 'RPC Karawaci', 'qty' => 0],
                        ['location' => 'RPC Lippo Cikarang', 'qty' => 0],
                        ['location' => 'RPC Pulogebang', 'qty' => 0],
                        ['location' => 'RPC Rawamangu', 'qty' => 0]
                    ]
                ],
                [
                    'productName' => 'Yummy Raw Food Turkey 500gr',
                    'brandName' => 'PTS',
                    'supplierName' => 'Online',
                    'averagePrice' => 80500,
                    'averageCost' => 800,
                    'quantities' => [
                        ['location' => 'RPC Buaran Klender', 'qty' => 0],
                        ['location' => 'RPC Condet', 'qty' => 0],
                        ['location' => 'RPC Hankam Pondok Gede', 'qty' => 0],
                        ['location' => 'RPC Karawang Tengah', 'qty' => 0],
                        ['location' => 'RPC Karawaci', 'qty' => 0],
                        ['location' => 'RPC Lippo Cikarang', 'qty' => 0],
                        ['location' => 'RPC Pulogebang', 'qty' => 0],
                        ['location' => 'RPC Rawamangu', 'qty' => 0]
                    ]
                ]
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_Cost.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data['data'] as $product) {
            foreach ($product['quantities'] as $quantity) {
                $sheet->setCellValue("A{$row}", $product['productName']);
                $sheet->setCellValue("B{$row}", $product['brandName']);
                $sheet->setCellValue("C{$row}", $product['supplierName']);
                $sheet->setCellValue("D{$row}", $quantity['location']);
                $sheet->setCellValue("E{$row}", $quantity['qty']);
                $row++;
            }
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Product Cost.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Product Cost.xlsx"',
        ]);
    }
}
