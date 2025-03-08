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
        $itemPerPage = $request->rowPerPage;
        $page = $request->goToPage;

        $query = DB::table('products as ps')
            ->join('productLocations as pl', 'ps.id', '=', 'pl.productId')
            ->join('location as l', 'pl.locationId', '=', 'l.id')
            ->leftJoin('productSuppliers as psup', 'ps.productSupplierId', '=', 'psup.id')
            ->select(
                'ps.fullName',
                'ps.category',
                'ps.sku',
                DB::raw("IFNULL(psup.supplierName, '') as supplierName"),
                'l.locationName',
                'pl.inStock',
                'ps.created_at'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($request->dateFrom && $request->dateTo) {
            $query = $query->whereBetween(DB::raw('DATE(ps.created_at)'), [$request->dateFrom, $request->dateTo]);
        }

        if ($request->has('locationId') && !empty($request->locationId)) {
            $query = $query->whereIn('pl.locationId', (array) $request->locationId);
        }

        if ($request->orderValue) {
            if ($request->orderColumn == 'name') {
                $query = $query->orderBy('ps.fullName', $request->orderValue);
            } elseif ($request->orderValue == 'date' || $request->orderValue == 'time') {
                $query = $query->orderBy('ps.created_at', $request->orderValue);
            } else {
                $query = $query->orderBy($request->orderColumn, $request->orderValue);
            }
        }

        $count_data = $query->count();
        $offset = ($page - 1) * $itemPerPage;

        $count_result = $count_data - $offset;

        if ($count_result <= 0) {
            $data = $query->limit($itemPerPage)->get();
        } else {
            $data = $query->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPagination = ceil($count_data / $itemPerPage);

        $responseData = [
            'totalPagination' => $totalPagination,
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

        $itemPerPage = $request->rowPerPage;
        $page = $request->goToPage;


        $query = DB::table('products as ps')
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
            ->where('ps.isDeleted', '=', 0);


        if ($request->has('locationId') && !empty($request->locationId)) {
            $query = $query->whereIn('pl.locationId', (array) $request->locationId);
        }


        if ($request->dateFrom && $request->dateTo) {
            $query = $query->whereBetween(DB::raw('DATE(ps.created_at)'), [$request->dateFrom, $request->dateTo]);
        }

        if ($request->orderValue) {
            if ($request->orderColumn == 'name') {
                $query = $query->orderBy('ps.fullName', $request->orderValue);
            } elseif ($request->orderValue == 'date' || $request->orderValue == 'time') {
                $query = $query->orderBy('ps.created_at', $request->orderValue);
            } else {
                $query = $query->orderBy($request->orderColumn, $request->orderValue);
            }
        }

        $count_data = $query->count();

        $offset = ($page - 1) * $itemPerPage;

        $data = $query->offset($offset)->limit($itemPerPage)->get();

        $totalPagination = ceil($count_data / $itemPerPage);

        $responseData = [
            'totalPagination' => $totalPagination,
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
                    'product' => [
                        'id' => 123,
                        'name' => 'Zoletil Inj (1 ml)',
                    ],
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
                    'product' => [
                        'id' => 124,
                        'name' => 'Zentonil Advance (SAme) Kapsul (1 Kapsul)',
                    ],
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
                    'product' => [
                        'id' => 125,
                        'name' => 'Yummy Raw Food Turkey 500gr',
                    ],
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
        $newFilePath = public_path() . '/template_download/' . 'Export Report Product Cost.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Product Cost.xlsx"',
        ]);
    }

    public function indexNoStock(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'fullName' => 'Whiskas WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Whiskas Indonesia',
                    'locationName' => "RPC Condet",
                    'noStock' => true,
                ],
                [
                    'fullName' => 'Royal Canin WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Royal Canin Indonesia',
                    'locationName' => "RPC Hankam",
                    'noStock' => true,
                ],
                [
                    'fullName' => 'Crystal WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Crystal Indonesia',
                    'locationName' => "RPC Pulogebang",
                    'noStock' => true,
                ],
                [
                    'fullName' => 'Me-Oh WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Me-Oh Indonesia',
                    'locationName' => "RPC Cikarang",
                    'noStock' => true,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportNoStock(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'fullName' => 'Whiskas WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Whiskas Indonesia',
                    'locationName' => "RPC Condet",
                    'noStock' => true,
                ],
                [
                    'fullName' => 'Royal Canin WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Royal Canin Indonesia',
                    'locationName' => "RPC Hankam",
                    'noStock' => true,
                ],
                [
                    'fullName' => 'Crystal WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Crystal Indonesia',
                    'locationName' => "RPC Pulogebang",
                    'noStock' => true,
                ],
                [
                    'fullName' => 'Me-Oh WetFood',
                    'category' => 'sell',
                    'sku' => '123456',
                    'supplierName' => 'PT. Me-Oh Indonesia',
                    'locationName' => "RPC Cikarang",
                    'noStock' => true,
                ],
            ]
        ];


        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Product_No_Stock.xlsx');
        $sheet = $spreadsheet->getSheet(0);


        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Category');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Supplier Name');
        $sheet->setCellValue('E1', 'Location');
        $sheet->setCellValue('F1', 'No Stock');

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($data['data'] as $item) {

            $sheet->setCellValue("A{$row}", $item['fullName']);
            $sheet->setCellValue("B{$row}", $item['category']);
            $sheet->setCellValue("C{$row}", $item['sku']);
            $sheet->setCellValue("D{$row}", $item['supplierName']);
            $sheet->setCellValue("E{$row}", $item['locationName']);
            $sheet->setCellValue("F{$row}", $item['noStock'] ? 'Yes' : 'No');

            $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
        }

        foreach (range('A', 'F') as $columnID) {
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
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Belia',
                    'subAccount' => 'Pino',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62812299338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
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
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Belia',
                    'subAccount' => 'Pino',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62812299338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
                ],
                [
                    'customerName' => 'Fariez Tachsin',
                    'subAccount' => 'Kimi',
                    'productName' => 'Vaksin Felocell 4 / F4 (1 pcs)',
                    'phoneNumber' => '62811999338',
                    'dueDate' => '2022-05-31',
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
            
            $dueDate = \Carbon\Carbon::createFromFormat('Y-m-d', $item['dueDate'])->locale('en')->isoFormat('D MMMM YYYY');

            $sheet->setCellValue("A{$row}", $item['customerName']);
            $sheet->setCellValue("B{$row}", $item['subAccount']);
            $sheet->setCellValue("C{$row}", $item['productName']);
            $sheet->setCellValue("D{$row}", $item['phoneNumber']);
            $sheet->setCellValue("E{$row}", $dueDate);

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
}
