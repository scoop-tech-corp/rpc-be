<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DepositController extends Controller
{
    public function indexList(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Kuki',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ],
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Bubu',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ],
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Lilo',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ],
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Ucil',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ]
            ]
        ];

        return response()->json($data);
    }
    public function exportList(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Kuki',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ],
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Bubu',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ],
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Lilo',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ],
                [
                    'referenceNo' => '006076',
                    'customerName' => 'Ucil',
                    'date' => "2025-02-20",
                    'locationName' => "RPC Duren",
                    'paymentMethod' => "debit",
                    'receivedAmount' => 80000,
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                    'invoiceNo' => "INV-58032",
                ]
            ]
        ];


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
        foreach ($data['data'] as $item) {
    
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
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Deposit List.xlsx"',
        ]);
    }
    public function indexSummary(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'locationName' => "RPC Buaran",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],
                [
                    'locationName' => "RPC Condet",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],
                [
                    'locationName' => "RPC Hankam",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],
                [
                    'locationName' => "RPC Pondok Gede",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
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
                    'locationName' => "RPC Buaran",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],
                [
                    'locationName' => "RPC Condet",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],
                [
                    'locationName' => "RPC Hankam",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],
                [
                    'locationName' => "RPC Pondok Gede",
                    'usedAmount' => 0,
                    'returnedAmount' => 80000,
                    'remainingAmount' => 80000,
                ],

            ]
        ];


        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Deposit_Summary.xlsx');
        $sheet = $spreadsheet->getSheet(0);


        $sheet->setCellValue('A1', 'Location');
        $sheet->setCellValue('B1', 'Return');
        $sheet->setCellValue('C1', 'Used');
        $sheet->setCellValue('D1', 'Remaining');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A1:D1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        foreach ($data['data'] as $item) {
            
            $sheet->setCellValue("A{$row}", $item['locationName']);
            $sheet->setCellValue("B{$row}", $item['usedAmount']);
            $sheet->setCellValue("C{$row}", $item['returnedAmount']);
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
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Deposit Summary.xlsx"',
        ]);
    }
}
