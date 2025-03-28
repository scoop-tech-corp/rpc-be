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

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'saleDate' => '2025-02-20',
                    'status' => 'Active',
                    'items' => 'Proplan Sachet',
                    'quantity' => 1,
                    'price' => 29000,
                    'totalAmount' => 29000,
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'saleDate' => '2025-02-20',
                    'status' => 'Active',
                    'items' => 'Bolt',
                    'quantity' => 1,
                    'price' => 29000,
                    'totalAmount' => 29000,
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'saleDate' => '2025-02-20',
                    'status' => 'Active',
                    'items' => 'Proplan Sachet',
                    'quantity' => 1,
                    'price' => 29000,
                    'totalAmount' => 29000,
                    'payment' => 'Paid',
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportItems(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'saleDate' => '2025-02-20',
                    'status' => 'Active',
                    'items' => 'Proplan Sachet',
                    'quantity' => 1,
                    'price' => 29000,
                    'totalAmount' => 29000,
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'saleDate' => '2025-02-20',
                    'status' => 'Active',
                    'items' => 'Bolt',
                    'quantity' => 1,
                    'price' => 29000,
                    'totalAmount' => 29000,
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'saleDate' => '2025-02-20',
                    'status' => 'Active',
                    'items' => 'Proplan Sachet',
                    'quantity' => 1,
                    'price' => 29000,
                    'totalAmount' => 29000,
                    'payment' => 'Paid',
                ],
            ]
        ];

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
        foreach ($data['data'] as $item) {

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

    public function indexSummary(Request $request)
    {

        $last10Days = collect(range(0, 9))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('j M');
        });

        $data = [
            'charts' => [
                'series' => [
                    [
                        'name' => 'RPC Hankam',
                        'data' => [10, 20, 10, 50, 30, 40, 10],
                    ],
                    [
                        'name' => 'RPC Codet',
                        'data' => [10, 20, 10, 47, 30, 39, 10],
                    ]
                ],
                'categories' => $last10Days,
            ],
            'table' => [
                'data' => [
                    [
                        'location' => 'RPC Condet',
                        'grossAmount' => 185501500,
                        'discounts' => 185501500,
                        'netAmount' => 0,
                        'taxesAmount' => 0,
                        'chargesAmount' => 0,
                        'totalAmount' => 0,
                    ],
                    [
                        'location' => 'RPC Hankam',
                        'grossAmount' => 185501500,
                        'discounts' => 0,
                        'netAmount' => 0,
                        'taxesAmount' => 0,
                        'chargesAmount' => 0,
                        'totalAmount' => 0,
                    ],
                ],
                'totalData' => [
                    'grossAmount' => 185501500,
                    'discounts' => 0,
                    'netAmount' => 0,
                    'taxesAmount' => 0,
                    'chargesAmount' => 0,
                    'totalAmount' => 0,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportSummary(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'location' => 'RPC Condet',
                    'grossAmount' => 185501500,
                    'discounts' => 185501500,
                    'netAmount' => 0,
                    'taxesAmount' => 0,
                    'chargesAmount' => 0,
                    'totalAmount' => 0,
                ],
                [
                    'location' => 'RPC Hankam',
                    'grossAmount' => 185501500,
                    'discounts' => 0,
                    'netAmount' => 0,
                    'taxesAmount' => 0,
                    'chargesAmount' => 0,
                    'totalAmount' => 0,
                ],
            ]
        ];

        $totalData = [
            'grossAmount' => 185501500,
            'discounts' => 0,
            'netAmount' => 0,
            'taxesAmount' => 0,
            'chargesAmount' => 0,
            'totalAmount' => 0,
        ];

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
        foreach ($data['data'] as $item) {

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

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("F{$row}")->getFont()->setBold(true);
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['grossAmount']);
        $sheet->setCellValue("C{$row}", $totalData['discounts']);
        $sheet->setCellValue("D{$row}", $totalData['netAmount']);
        $sheet->setCellValue("E{$row}", $totalData['taxesAmount']);
        $sheet->setCellValue("F{$row}", $totalData['chargesAmount']);
        $sheet->setCellValue("G{$row}", $totalData['totalAmount']);

        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


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

    public function indexSalesByService(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 13,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 15,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 16,
                    'totalAmount' => 171290009
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportSalesByService(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 13,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 15,
                    'totalAmount' => 171290009
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'quantity' => 16,
                    'totalAmount' => 171290009
                ],
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_By_Service.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Service');
        $sheet->setCellValue('B1', 'Quantity');
        $sheet->setCellValue('C1', 'Total (Rp)');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:C1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data['data'] as $item) {

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
        $newFilePath = public_path() . '/template_download/' . 'Export Sales By Service.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales By Service.xlsx"',
        ]);
    }

    public function indexSalesByProduct(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',

                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 13,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 15,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 16,
                    'totalAmount' => 171290009
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportSalesByProduct(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',

                    'quantity' => 12,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 13,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 15,
                    'totalAmount' => 171290009
                ],
                [
                    'productName' => 'Biodin Inj (1 ml)',
                    'quantity' => 16,
                    'totalAmount' => 171290009
                ],
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Sales_By_Product.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Product');
        $sheet->setCellValue('B1', 'Quantity');
        $sheet->setCellValue('C1', 'Total (Rp)');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:C1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data['data'] as $item) {

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
        $newFilePath = public_path() . '/template_download/' . 'Export Sales By Product.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales By Product.xlsx"',
        ]);
    }

    public function indexPaymentList(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '12 May 2022 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '12 May 2022 11:55 PM',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '12 May 2022 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '12 May 2022 11:55 PM',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '12 May 2022 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '12 May 2022 11:55 PM',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '12 May 2022 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '12 May 2022 11:55 PM',
                    'totalAmount' => 29000,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportPaymentList(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12 11:55 PM',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12 11:55 PM',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12 11:55 PM',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12 11:55 PM',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12 11:55 PM',
                    'totalAmount' => 29000,
                ],
            ]
        ];

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
        foreach ($data['data'] as $item) {

            $paidAt = \Carbon\Carbon::createFromFormat('Y-m-d h:i A', $item['paidAt'])
                ->locale('en')
                ->isoFormat('D MMMM YYYY h:mm A');

            $createdAt = \Carbon\Carbon::createFromFormat('Y-m-d h:i A', $item['createdAt'])
                ->locale('en')
                ->isoFormat('D MMMM YYYY h:mm A');


            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['paymentMethod']);
            $sheet->setCellValue("D{$row}", $paidAt);
            $sheet->setCellValue("E{$row}", $item['createdBy']);;
            $sheet->setCellValue("F{$row}", $createdAt);
            $sheet->setCellValue("G{$row}", $item['totalAmount']);

            $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
        }


        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Sales Payment List.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Payment List.xlsx"',
        ]);
    }

    public function indexDetails(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1213',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Cash',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1212',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Cash',
                            'date' => '12/5/2022'
                        ],
                        [
                            'amount' => 20000,
                            'method' => 'Debit',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1211',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Bank Transfer',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1223',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Bank Transfer',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportDetails(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1213',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Cash',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1212',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Cash',
                            'date' => '12/5/2022'
                        ],
                        [
                            'amount' => 20000,
                            'method' => 'Debit',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1211',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Bank Transfer',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1223',
                    'location' => 'RPC Duren',
                    'saleDate' => '12 May 2024',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        [
                            'amount' => 47000,
                            'method' => 'Bank Transfer',
                            'date' => '12/5/2022'
                        ]
                    ],
                    'payment' => 'Paid',
                ],
            ]
        ];

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
        $sheet->setCellValue('I1', 'Payment (Rp)');


        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:I1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data['data'] as $item) {

            $itemsString = implode(', ', $item['items']);

            $paymentMethodStrings = [];
            foreach ($item['paymentMethod'] as $payment) {
                $paymentMethodStrings[] = $payment['method'] . ' (' . number_format($payment['amount'], 0, ',', '.') . ' Rp) - ' . $payment['date'];
            }
            $paymentMethodString = implode(', ', $paymentMethodStrings);

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

    public function indexUnpaid(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ]
            ]
        ];

        return response()->json($data);
    }

    public function exportUnpaid(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'dueDate' => '2024-05-12',
                    'overDue' => 'Active',
                    'customerName' => 'Miao',
                    'phoneNo' => '081298557575',
                    'totalAmount' => 29000,
                    'paidAmount' => 29000,
                    'outstandingAmount' => 29000,
                    'refNum' => 'test',
                ]
            ]
        ];

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
        foreach ($data['data'] as $item) {

            $dueDate = \Carbon\Carbon::createFromFormat('Y-m-d', $item['dueDate'])->locale('en')->isoFormat('D MMMM YYYY');

            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $dueDate);
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
        $newFilePath = public_path() . '/template_download/' . 'Export Sales Unpaid.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Sales Unpaid.xlsx"',
        ]);
    }

    public function indexDiscountSummary(Request $request)
    {

        $data = [

            "charts" => [
                "series" => [
                    [
                        "name" => "Previous",
                        "data" => [2000000, 4000000, 6000000, 8000000, 10000000, 12000000, 4000000, 6000000, 8000000, 10000000, 12000000, 2000000, 6000000]
                    ],
                    [
                        "name" => "Current",
                        "data" => [3000000, 5000000, 7000000, 9000000, 11000000, 2000000, 5000000, 7000000, 9000000, 11000000, 3000000, 5000000, 9000000]
                    ]
                ],
                "categories" => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
            ],

            'totalDiscount' => [
                'percentage' => 14.01,
                'total' => 76872119.50,
                'isLoss' => 0
            ],

            'itemsDicounted' => [
                'percentage' => 0,
                'total' => 0,
                'isLoss' => null
            ],

            'salesDiscounted' => [
                'percentage' => 2,
                'total' => 17,
                'isLoss' => 1
            ],

            'chartsDiscountValueByStaff' => [
                'labels' => [
                    'Not Set',
                    'Drh. Olivionita Julina Paxy',
                    'Drh. Laili Nadhilah Pradyane',
                    'Drh. Shinta Ayu Phinnaka Purnama Dewi',
                    'Drh. Jihaan Haajidah',
                    'Drh. Dita Ardiah Napitupulu',
                    'Other'
                ],
                'series' => [
                    25,
                    5,
                    7,
                    5,
                    5,
                    5,
                    48
                ]
            ]
        ];

        return response()->json($data);
    }

    public function indexPaymentSummary(Request $request)
    {

        $data = [

            'totalPayments' => [
                'percentage' => 15.51,
                'total' => 1459162649.50,
                'isLoss' => 0
            ],

            'totalRefunds' => [
                'percentage' => 34.50,
                'total' => 1373500.00,
                'isLoss' => 0
            ],

            'netPayments' => [
                'percentage' => 15.59,
                'total' => 1457789149.00,
                'isLoss' => 0
            ],

            'chartsDiscountValueByStaff' => [
                'labels' => [
                    'Debit Card',
                    'Cash',
                    'Bank Transfer',
                    'Credit Card',
                    'Customer Credit',
                    'Customer Package'
                ],
                'series' => [
                    50,
                    15,
                    15,
                    20,
                    0,
                    0
                ]
            ],

            'table' => [
                'data' => [
                    [
                        'method' => 'Debit Card',
                        'totalAmount' => 723603483.30,
                        'refundAmount' => 70000.00,
                        'netAmount' => 723533483.30,
                    ],
                    [
                        'method' => 'Cash',
                        'totalAmount' => 434766400.70,
                        'refundAmount' => 1303500.00,
                        'netAmount' => 433462900.70,
                    ],
                    [
                        'method' => 'Bank Transfer',
                        'totalAmount' => 272410986.00,
                        'refundAmount' => 0,
                        'netAmount' => 272410986.00,
                    ],
                    [
                        'method' => 'Credit Card',
                        'totalAmount' => 28381779.00,
                        'refundAmount' => 0,
                        'netAmount' => 28381779.00,
                    ],
                    [
                        'method' => 'Customer Credit',
                        'totalAmount' => 0,
                        'refundAmount' => 0,
                        'netAmount' => 0,
                    ],
                    [
                        'method' => 'Customer Package',
                        'totalAmount' => 0,
                        'refundAmount' => 0,
                        'netAmount' => 0,
                    ],
                ],
            ]
        ];

        return response()->json($data);
    }

    public function indexNetIncome(Request $request)
    {

        $data = [

            'totalRevenue' => [
                'total' => 12954384408.30,
            ],

            'totalExpenses' => [
                'total' => 260888263.00,
            ],

            'netIncome' => [
                'total' => 12693496145.30,
            ],

            "chartsRevenueAndExpenses" => [
                "series" => [
                    [
                        "name" => "Revenue",
                        "data" => [2300000000, 2700000000, 3300000000, 3200000000, 10000000, 1500000000]
                    ],
                    [
                        "name" => "Expenses",
                        "data" => [0, 100000000, 200000000, 200000000, 200000000, 0]
                    ]
                ],
                "categories" => ["Jan", "Feb", "Mar", "Apr", "May"]
            ],

            "chartsNetIncome" => [
                "series" => [
                    [
                        "name" => "Net Income",
                        "data" => [2300000000, 2700000000, 3300000000, 3200000000, 10000000, 1500000000]
                    ]
                ],
                "categories" => ["Jan", "Feb", "Mar", "Apr", "May"]
            ],

            'table' => [
                'data' => [
                    [
                        'period' => 'Jan',
                        'revenueAmount' => 2278977407.95,
                        'expensesAmount' => 70000.00,
                        'netIncome' => 2278977407.95,
                    ],
                    [
                        'period' => 'Feb',
                        'revenueAmount' => 2737031819.70,
                        'expensesAmount' => 70000.00,
                        'netIncome' => 2278977407.95,
                    ],
                    [
                        'period' => 'Mar',
                        'revenueAmount' => 3271008567.00,
                        'expensesAmount' => 70000.00,
                        'netIncome' => 2278977407.95,
                    ],
                    [
                        'period' => 'Apr',
                        'revenueAmount' => 3148391525.15,
                        'expensesAmount' => 70000.00,
                        'netIncome' => 2278977407.95,
                    ],
                    [
                        'period' => 'May',
                        'revenueAmount' => 1498196588.50,
                        'expensesAmount' => 70000.00,
                        'netIncome' => 2278977407.95,
                    ]
                ],
            ]
        ];

        return response()->json($data);
    }

    public function indexDailyAudit(Request $request)
    {

        $data = [

            'table' => [
                'data' => [
                    [
                        'day' => 1,
                        'date' => '1/5/2022',
                        'salesSummary' => [
                            'salesValue' => 4343239.50,
                            'discounts' => 189260.50
                        ],
                        'paymentSummary' => [
                            'cash' => 2404500.00,
                            'creditCard' => 0.00,
                            'bankTransfer' => 1904750.00,
                            'debitCard' => 2752989.50,
                            'totalAmount' => 7062239.50
                        ],
                    ],
                    [
                        'day' => 2,
                        'date' => '2/5/2022',
                        'salesSummary' => [
                            'salesValue' => 4343239.50,
                            'discounts' => 189260.50
                        ],
                        'paymentSummary' => [
                            'cash' => 2404500.00,
                            'creditCard' => 0.00,
                            'bankTransfer' => 1904750.00,
                            'debitCard' => 2752989.50,
                            'totalAmount' => 7062239.50
                        ],
                    ],
                    [
                        'day' => 3,
                        'date' => '3/5/2022',
                        'salesSummary' => [
                            'salesValue' => 4343239.50,
                            'discounts' => 189260.50
                        ],
                        'paymentSummary' => [
                            'cash' => 2404500.00,
                            'creditCard' => 0.00,
                            'bankTransfer' => 1904750.00,
                            'debitCard' => 2752989.50,
                            'totalAmount' => 7062239.50
                        ],
                    ],
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportDailyAudit(Request $request)
    {
        $data = [
            'table' => [
                'data' => [
                    [
                        'day' => 1,
                        'date' => '1/5/2022',
                        'salesSummary' => [
                            'salesValue' => 4343239.50,
                            'discounts' => 189260.50
                        ],
                        'paymentSummary' => [
                            'cash' => 2404500.00,
                            'creditCard' => 0.00,
                            'bankTransfer' => 1904750.00,
                            'debitCard' => 2752989.50,
                            'totalAmount' => 7062239.50
                        ],
                    ],
                    [
                        'day' => 2,
                        'date' => '2/5/2022',
                        'salesSummary' => [
                            'salesValue' => 4343239.50,
                            'discounts' => 189260.50
                        ],
                        'paymentSummary' => [
                            'cash' => 2404500.00,
                            'creditCard' => 0.00,
                            'bankTransfer' => 1904750.00,
                            'debitCard' => 2752989.50,
                            'totalAmount' => 7062239.50
                        ],
                    ],
                    [
                        'day' => 3,
                        'date' => '3/5/2022',
                        'salesSummary' => [
                            'salesValue' => 4343239.50,
                            'discounts' => 189260.50
                        ],
                        'paymentSummary' => [
                            'cash' => 2404500.00,
                            'creditCard' => 0.00,
                            'bankTransfer' => 1904750.00,
                            'debitCard' => 2752989.50,
                            'totalAmount' => 7062239.50
                        ],
                    ],
                ],
            ]
        ];

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

        $locations = DB::table('location')->select('id', 'locationName')->get();

        $locationQty = [];

        foreach ($locations as $location) {

            mt_srand($location->id);

            $qty = mt_rand(1, 10);

            $locationQty[$location->locationName] = $qty;
        }

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'staff' => 'Drh Cahyo Bagaskoro',
                    'service' => 'USG',
                    'pricing' => 'Standard',
                    'location' => $locationQty, 
                    'totalQty' => array_sum($locationQty),
                    'totalDuration' => 2,
                    'totalSoldValue' => 150000,
                ],
                [
                    'staff' => 'Drh Cahyo Bagaskoro',
                    'service' => 'Healing Luka',
                    'pricing' => 'Standard',
                    'location' => $locationQty,
                    'totalQty' => array_sum($locationQty),
                    'totalDuration' => 4,
                    'totalSoldValue' => 10000,
                ],
                [
                    'staff' => 'Drh Cahyo Bagaskoro',
                    'service' => 'Jasa Dokter Hewan',
                    'pricing' => 'Cat Large',
                    'location' => $locationQty,
                    'totalQty' => array_sum($locationQty), 
                    'totalDuration' => 0.25,
                    'totalSoldValue' => 100000,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportStaffServiceSales(Request $request)
    {

        $locations = DB::table('location')->select('id', 'locationName')->get();
        $locationNames = $locations->pluck('locationName')->toArray();


        $locationQty = [];
        foreach ($locations as $location) {
            mt_srand($location->id);
            $qty = mt_rand(1, 10);
            $locationQty[$location->locationName] = $qty;
        }


        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'staff' => 'Drh Cahyo Bagaskoro',
                    'service' => 'USG',
                    'pricing' => 'Standard',
                    'location' => $locationQty, 
                    'totalQty' => array_sum($locationQty),
                    'totalDuration' => 2,
                    'totalSoldValue' => 150000,
                ],
                [
                    'staff' => 'Drh Cahyo Bagaskoro',
                    'service' => 'Healing Luka',
                    'pricing' => 'Standard',
                    'location' => $locationQty,
                    'totalQty' => array_sum($locationQty),
                    'totalDuration' => 4,
                    'totalSoldValue' => 10000,
                ],
                [
                    'staff' => 'Drh Cahyo Bagaskoro',
                    'service' => 'Jasa Dokter Hewan',
                    'pricing' => 'Cat Large',
                    'location' => $locationQty,
                    'totalQty' => array_sum($locationQty), 
                    'totalDuration' => 0.25,
                    'totalSoldValue' => 100000,
                ],
            ]
        ];


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
}
