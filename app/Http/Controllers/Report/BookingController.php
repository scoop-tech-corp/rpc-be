<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BookingController extends Controller
{
    public function indexDiagnosesSpeciesGender(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'no' => '1',
                    'diagnosis' => '(Suspect) Limpoma',
                    'anjing' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'ayam' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'burung' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'gecko' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'hamster' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'iguana' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'kelinci' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'marmut' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'monyet' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'musang' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'naga' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'other' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'otter' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'sugarGlider' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                ],
                [
                    'no' => '2',
                    'diagnosis' => '(Suspect) Salmonellosis',
                    'anjing' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'ayam' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'burung' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'gecko' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'hamster' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'iguana' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'kelinci' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'marmut' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'monyet' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'musang' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'naga' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'other' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'otter' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'sugarGlider' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                ],
                [
                    'no' => '3',
                    'diagnosis' => 'Abnormalitas Gigi',
                    'anjing' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'ayam' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'burung' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'gecko' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'hamster' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'iguana' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'kelinci' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'marmut' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'monyet' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'musang' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'naga' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'other' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'otter' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                    'sugarGlider' => [
                        'betina' => 0,
                        'jantan' => 0
                    ],
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportDiagnosesSpeciesGender(Request $request)
    {
        $data = [
            'totalPagination' => 1,
            'table' => [
                'data' => [
                    [
                        'no' => '1',
                        'diagnosis' => '(Suspect) Limpoma',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarglider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'total' => '0'
                    ],
                    [
                        'no' => '2',
                        'diagnosis' => '(Suspect) Salmonellosis',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarglider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'total' => '0'
                    ],
                    [
                        'no' => '3',
                        'diagnosis' => 'Abnormalitas Gigi',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarglider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'total' => '0'
                    ],
                ],
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Booking_Diagnoses_Species_Gender.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A2', 'No');
        $sheet->setCellValue('B2', 'Diagnosis');
        $sheet->setCellValue('D1', 'Anjing');
        $sheet->setCellValue('F1', 'Ayam');
        $sheet->setCellValue('H1', 'Burung');
        $sheet->setCellValue('J1', 'Gecko');
        $sheet->setCellValue('L1', 'Hamster');
        $sheet->setCellValue('N1', 'Iguana');
        $sheet->setCellValue('O1', 'Kelinci');
        $sheet->setCellValue('Q1', 'Marmut');
        $sheet->setCellValue('S1', 'Monyet');
        $sheet->setCellValue('U1', 'Musang');
        $sheet->setCellValue('W1', 'Naga');
        $sheet->setCellValue('Y1', 'Other');
        $sheet->setCellValue('AA1', 'Otter/Berang-Berang');
        $sheet->setCellValue('AC1', 'Sugarglider');

        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('C1:D1');
        $sheet->mergeCells('E1:F1');
        $sheet->mergeCells('G1:H1');
        $sheet->mergeCells('I1:J1');
        $sheet->mergeCells('K1:L1');
        $sheet->mergeCells('M1:N1');
        $sheet->mergeCells('P1:Q1');
        $sheet->mergeCells('R1:S1');
        $sheet->mergeCells('T1:U1');
        $sheet->mergeCells('V1:W1');
        $sheet->mergeCells('X1:Y1');
        $sheet->mergeCells('Z1:AA1');
        $sheet->mergeCells('AB1:AC1');

        $sheet->setCellValue('C2', 'Betina');
        $sheet->setCellValue('D2', 'Jantan');
        $sheet->setCellValue('E2', 'Betina');
        $sheet->setCellValue('F2', 'Jantan');
        $sheet->setCellValue('G2', 'Betina');
        $sheet->setCellValue('H2', 'Jantan');
        $sheet->setCellValue('I2', 'Betina');
        $sheet->setCellValue('J2', 'Jantan');
        $sheet->setCellValue('K2', 'Betina');
        $sheet->setCellValue('L2', 'Jantan');
        $sheet->setCellValue('M2', 'Betina');
        $sheet->setCellValue('N2', 'Jantan');
        $sheet->setCellValue('O2', 'Betina');
        $sheet->setCellValue('P2', 'Betina');
        $sheet->setCellValue('Q2', 'Jantan');
        $sheet->setCellValue('R2', 'Betina');
        $sheet->setCellValue('S2', 'Jantan');
        $sheet->setCellValue('T2', 'Betina');
        $sheet->setCellValue('U2', 'Jantan');
        $sheet->setCellValue('V2', 'Betina');
        $sheet->setCellValue('W2', 'Jantan');
        $sheet->setCellValue('X2', 'Betina');
        $sheet->setCellValue('Y2', 'Jantan');
        $sheet->setCellValue('Z2', 'Betina');
        $sheet->setCellValue('AA2', 'Jantan');
        $sheet->setCellValue('AB2', 'Betina');
        $sheet->setCellValue('AC2', 'Jantan');


        
        $sheet->getStyle('A1:AD2')->getFont()->setBold(true);
        $sheet->getStyle('A1:AD2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:AD2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        
        $row = 3;  
        foreach ($data['table']['data'] as $item) {
            $sheet->setCellValue("A{$row}", $item['no']);
            $sheet->setCellValue("B{$row}", $item['diagnosis']);
            $sheet->setCellValue("C{$row}", $item['anjing']['betina']);
            $sheet->setCellValue("D{$row}", $item['anjing']['jantan']);
            $sheet->setCellValue("E{$row}", $item['ayam']['betina']);
            $sheet->setCellValue("F{$row}", $item['ayam']['jantan']);
            $sheet->setCellValue("G{$row}", $item['burung']['betina']);
            $sheet->setCellValue("H{$row}", $item['burung']['jantan']);
            $sheet->setCellValue("I{$row}", $item['gecko']['betina']);
            $sheet->setCellValue("J{$row}", $item['gecko']['jantan']);
            $sheet->setCellValue("K{$row}", $item['hamster']['betina']);
            $sheet->setCellValue("L{$row}", $item['hamster']['jantan']);
            $sheet->setCellValue("M{$row}", $item['iguana']['betina']);
            $sheet->setCellValue("N{$row}", $item['iguana']['jantan']);
            $sheet->setCellValue("O{$row}", $item['kelinci']['betina']);
            $sheet->setCellValue("P{$row}", $item['marmut']['betina']);
            $sheet->setCellValue("Q{$row}", $item['marmut']['jantan']);
            $sheet->setCellValue("R{$row}", $item['monyet']['betina']);
            $sheet->setCellValue("S{$row}", $item['monyet']['jantan']);
            $sheet->setCellValue("T{$row}", $item['musang']['betina']);
            $sheet->setCellValue("U{$row}", $item['musang']['jantan']);
            $sheet->setCellValue("V{$row}", $item['naga']['betina']);
            $sheet->setCellValue("W{$row}", $item['naga']['jantan']);
            $sheet->setCellValue("X{$row}", $item['other']['betina']);
            $sheet->setCellValue("Y{$row}", $item['other']['jantan']);
            $sheet->setCellValue("Z{$row}", $item['otter']['betina']);
            $sheet->setCellValue("AA{$row}", $item['otter']['jantan']);
            $sheet->setCellValue("AB{$row}", $item['sugarglider']['betina']);
            $sheet->setCellValue("AC{$row}", $item['sugarglider']['jantan']);
            $sheet->setCellValue("AD{$row}", $item['total']);

            $sheet->getStyle("A{$row}:AD{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            
            foreach (range('A', 'AD') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $row++;  
        }

        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Booking By Diagnoses, Species, Gender.xlsx';
        $writer->save($newFilePath);

        
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Booking By Diagnoses, Species, Gender.xlsx"',
        ]);
    }
}
