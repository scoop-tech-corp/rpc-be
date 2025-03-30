<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExpensesController extends Controller
{
    public function indexList(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'expenseId' => '#001672',
                    'location' => 'RPC Karawaci',
                    'receiptDate' => '12 May 2022',
                    'submitter' => 'Dwi Indri Ani',
                    'recipient' => 'Dwi',
                    'supplier' => '',
                    'reference' => 'Untuk Petshop/Klinik',
                    'totalAmount' => 1002000.00,
                    'status' => 'Pending',
                ],
                [
                    'expenseId' => '#001671',
                    'location' => 'RPC Sawangan Lama',
                    'receiptDate' => '12 May 2022',
                    'submitter' => 'Marina Natasha Gultom',
                    'recipient' => 'Febi',
                    'supplier' => '',
                    'reference' => 'Barang Klinik',
                    'totalAmount' => 146100.00,
                    'status' => 'Pending',
                ],
                [
                    'expenseId' => '#001676',
                    'location' => 'RPC Buaran Klender',
                    'receiptDate' => '12 May 2022',
                    'submitter' => 'Nadiah Laila',
                    'recipient' => 'Mas Tarja',
                    'supplier' => '',
                    'reference' => 'Untuk Klinik',
                    'totalAmount' => 109000.00,
                    'status' => 'Pending',
                ],
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
                    'expenseId' => '#001672',
                    'location' => 'RPC Karawaci',
                    'receiptDate' => '12 May 2022',
                    'submitter' => 'Dwi Indri Ani',
                    'recipient' => 'Dwi',
                    'supplier' => '',
                    'reference' => 'Untuk Petshop/Klinik',
                    'totalAmount' => 1002000.00,
                    'status' => 'Pending',
                ],
                [
                    'expenseId' => '#001671',
                    'location' => 'RPC Sawangan Lama',
                    'receiptDate' => '12 May 2022',
                    'submitter' => 'Marina Natasha Gultom',
                    'recipient' => 'Febi',
                    'supplier' => '',
                    'reference' => 'Barang Klinik',
                    'totalAmount' => 146100.00,
                    'status' => 'Pending',
                ],
                [
                    'expenseId' => '#001676',
                    'location' => 'RPC Buaran Klender',
                    'receiptDate' => '12 May 2022',
                    'submitter' => 'Nadiah Laila',
                    'recipient' => 'Mas Tarja',
                    'supplier' => '',
                    'reference' => 'Untuk Klinik',
                    'totalAmount' => 109000.00,
                    'status' => 'Pending',
                ],
            ]
        ];

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
        foreach ($data['data'] as $item) {

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
        $newFilePath = public_path() . '/template_download/' . 'Export Expenses List.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Expenses List.xlsx"',
        ]);
    }

    public function indexSummary(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'category' => 'Kasbon',
                    'month1' => 0.00,
                    'month2' => 0.00,
                    'month3' => 0.00,
                    'month4' => 0.00,
                    'month5' => 0.00,
                    'month6' => 0.00,
                    'month7' => 0.00,
                    'month8' => 0.00,
                    'month9' =>  0.00,
                    'month10' => 0.00,
                    'month11' => 0.00,
                    'month12' => 0.00,
                    'month13' => 3292950.00,
                    'month14' => 8825250.00,
                    'month15' => 5149000.00,
                ],
                [
                    'category' => 'Klinik',
                    'month1' => 0.00,
                    'month2' => 0.00,
                    'month3' => 0.00,
                    'month4' => 0.00,
                    'month5' => 0.00,
                    'month6' => 0.00,
                    'month7' => 0.00,
                    'month8' => 0.00,
                    'month9' => 0.00,
                    'month10' => 0.00,
                    'month11' => 0.00,
                    'month12' => 1558100.00,
                    'month13' => 6386725.00,
                    'month14' => 19660603.00,
                    'month15' => 22154200.00,
                ],
                [
                    'category' => 'Petshop',
                    'month1' => 0.00,
                    'month2' => 0.00,
                    'month3' => 0.00,
                    'month4' => 0.00,
                    'month5' => 0.00,
                    'month6' => 0.00,
                    'month7' => 0.00,
                    'month8' => 0.00,
                    'month9' => 0.00,
                    'month10' => 0.00,
                    'month11' => 0.00,
                    'month12' => 0.00,
                    'month13' => 13294100.00,
                    'month14' => 23498000.00,
                    'month15' => 22834520.00,
                ]
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
                    'category' => 'Kasbon',
                    'month1' => 0.00,
                    'month2' => 0.00,
                    'month3' => 0.00,
                    'month4' => 0.00,
                    'month5' => 0.00,
                    'month6' => 0.00,
                    'month7' => 0.00,
                    'month8' => 0.00,
                    'month9' =>  0.00,
                    'month10' => 0.00,
                    'month11' => 0.00,
                    'month12' => 0.00,
                    'month13' => 3292950.00,
                    'month14' => 8825250.00,
                    'month15' => 5149000.00,
                ],
                [
                    'category' => 'Klinik',
                    'month1' => 0.00,
                    'month2' => 0.00,
                    'month3' => 0.00,
                    'month4' => 0.00,
                    'month5' => 0.00,
                    'month6' => 0.00,
                    'month7' => 0.00,
                    'month8' => 0.00,
                    'month9' => 0.00,
                    'month10' => 0.00,
                    'month11' => 0.00,
                    'month12' => 1558100.00,
                    'month13' => 6386725.00,
                    'month14' => 19660603.00,
                    'month15' => 22154200.00,
                ],
                [
                    'category' => 'Petshop',
                    'month1' => 0.00,
                    'month2' => 0.00,
                    'month3' => 0.00,
                    'month4' => 0.00,
                    'month5' => 0.00,
                    'month6' => 0.00,
                    'month7' => 0.00,
                    'month8' => 0.00,
                    'month9' => 0.00,
                    'month10' => 0.00,
                    'month11' => 0.00,
                    'month12' => 0.00,
                    'month13' => 13294100.00,
                    'month14' => 23498000.00,
                    'month15' => 22834520.00,
                ]
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Expenses_Summary.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'Category');
        $sheet->setCellValue('B1', 'Jan 24');
        $sheet->setCellValue('C1', 'Feb 24');
        $sheet->setCellValue('D1', 'Mar 24');
        $sheet->setCellValue('E1', 'Apr 24');
        $sheet->setCellValue('F1', 'May 24');
        $sheet->setCellValue('G1', 'Jun 24');
        $sheet->setCellValue('H1', 'Jul 24');
        $sheet->setCellValue('I1', 'Aug 24');
        $sheet->setCellValue('J1', 'Sep 24');
        $sheet->setCellValue('K1', 'Oct 24');
        $sheet->setCellValue('L1', 'Nov 24');
        $sheet->setCellValue('M1', 'Dec 24');
        $sheet->setCellValue('N1', 'Jan 25');
        $sheet->setCellValue('O1', 'Feb 25');
        $sheet->setCellValue('P1', 'Mar 25');

        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:P1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        $totals = array_fill(0, 15, 0);

        foreach ($data['data'] as $item) {
            $sheet->setCellValue("A{$row}", $item['category']);
            for ($col = 1; $col <= 15; $col++) {
                $monthKey = 'month' . $col;
                $value = isset($item[$monthKey]) ? $item[$monthKey] : 0.00;
                $sheet->setCellValueByColumnAndRow($col + 1, $row, $value);
                $totals[$col - 1] += $value;
            }

            $sheet->getStyle("A{$row}:P{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $sheet->getStyle("A{$row}:P{$row}")->getFont()->setBold(false);

            $row++;
        }

        $sheet->setCellValue("A{$row}", 'Total');
        for ($col = 1; $col <= 15; $col++) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $totals[$col - 1]);
        }

        $sheet->setCellValue("A{$row}", 'Total');
        for ($col = 1; $col <= 15; $col++) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $totals[$col - 1]);
        }
        
        $sheet->getStyle("A{$row}:P{$row}")->getFont()->setBold(true); 
        $sheet->getStyle("A{$row}:P{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle("A{$row}")->getFont()->setBold(true); 

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        foreach (range('A', 'P') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Expenses Summary.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Expenses Summary.xlsx"',
        ]);
    }
}
