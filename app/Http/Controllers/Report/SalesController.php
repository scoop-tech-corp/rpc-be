<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $sheet->setCellValue('C1', 'Unit Price (Rp)');
        $sheet->setCellValue('H1', 'Total (Rp)');
        $sheet->setCellValue('I1', 'Payment');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($data['data'] as $item) {

            $saleDate = \Carbon\Carbon::createFromFormat('Y-m-d', $item['saleDate'])->locale('en')->isoFormat('D MMMM YYYY');

            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("C{$row}", $item['location']);
            $sheet->setCellValue("B{$row}", $saleDate);
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
        $sheet->setCellValue('C1', 'Total (Rp)');

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
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
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
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
                    'totalAmount' => 29000,
                ],
                [
                    'saleId' => 'INV-12345',
                    'location' => 'RPC Duren',
                    'paymentMethod' => 'Transfer',
                    'paidAt' => '2022-05-12',
                    'createdBy' => 'Agus',
                    'createdAt' => '2022-05-12',
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

            $paidAt = \Carbon\Carbon::createFromFormat('Y-m-d', $item['paidAt'])->locale('en')->isoFormat('D MMMM YYYY');
            $createdAt = \Carbon\Carbon::createFromFormat('Y-m-d', $item['createdAt'])->locale('en')->isoFormat('D MMMM YYYY');

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
                    'refNumber' => '1212',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1213',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1211',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1223',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
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
                    'refNumber' => '1212',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1213',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1211',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
                    ],
                    'payment' => 'Paid',
                ],
                [
                    'saleId' => 'INV-12345',
                    'refNumber' => '1223',
                    'location' => 'RPC Duren',
                    'saleDate' => '2022-05-12',
                    'status' => 'Active',
                    'items' => ['Proplan Sachet', 'Aboket'],
                    'totalAmount' => 0,
                    'paymentMethod' => [
                        'amount' => 47000,
                        'method' => 'Cash',
                        'date' => '2022-05-12'
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
        
            $saleDate = \Carbon\Carbon::createFromFormat('Y-m-d', $item['saleDate'])->locale('en')->isoFormat('D MMMM YYYY');
        
            $itemsString = implode(', ', $item['items']);
    
            $paymentMethodString = $item['paymentMethod']['amount'] . ' (' . $item['paymentMethod']['method'] . ') on ' . $item['paymentMethod']['date'];
    
            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['refNumber']);
            $sheet->setCellValue("C{$row}", $item['location']);
            $sheet->setCellValue("D{$row}", $saleDate); 
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
        $newFilePath = public_path() . '/template_download/' . 'Export_Sales_Details.xlsx';
        $writer->save($newFilePath);
    
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Sales_Details.xlsx"',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
                    'overDue' => '1212',
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
    
            $sheet->setCellValue("A{$row}", $item['saleId']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['dueDate']);
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
        $newFilePath = public_path() . '/template_download/' . 'Export_Sales_Unpaid.xlsx';
        $writer->save($newFilePath);
    
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Sales_Unpaid.xlsx"',
        ]);
    }
}
