<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReportCustomerController extends Controller
{
    public function indexGrowth(Request $request)
    {
        $last10Days = collect(range(0, 9))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('j M');
        });

        $data = [
            'charts' => [
                'series' => [
                    [
                        'name' => 'RPC Bandung',
                        'data' => [10, 10, 10, 10, 30, 20, 15, 20, 18, 29],
                    ],
                    [
                        'name' => 'RPC Condet',
                        'data' => [20, 40, 20, 10, 80, 30, 15, 20, 18, 29],
                    ],
                ],
                'categories' => $last10Days,
            ],
            'table' => [
                'data' => [
                    [
                        'location' => 'RPC Condet',
                        'new' => 15,
                        'inactive' => 4,
                        'deleted' => 2,
                    ],
                    [
                        'location' => 'RPC Hankam',
                        'new' => 20,
                        'inactive' => 2,
                        'deleted' => 5,
                    ],
                    [
                        'location' => 'RPC Tanjung Duren',
                        'new' => 13,
                        'inactive' => 1,
                        'deleted' => 2,
                    ],
                    [
                        'location' => 'RPC Sawangan',
                        'new' => 14,
                        'inactive' => 6,
                        'deleted' => 1,
                    ],
                    [
                        'location' => 'RPC Palembang',
                        'new' => 11,
                        'inactive' => 5,
                        'deleted' => 2,
                    ],
                ],
                'totalData' => [
                    'new' => 73,
                    'inactive' => 18,
                    'deleted' => 12,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportGrowth(Request $request)
    {
        $data =
            [
                [
                    'location' => 'RPC Condet',
                    'new' => 15,
                    'inactive' => 4,
                    'deleted' => 2,
                ],
                [
                    'location' => 'RPC Hankam',
                    'new' => 20,
                    'inactive' => 2,
                    'deleted' => 5,
                ],
                [
                    'location' => 'RPC Tanjung Duren',
                    'new' => 13,
                    'inactive' => 1,
                    'deleted' => 2,
                ],
                [
                    'location' => 'RPC Sawangan',
                    'new' => 14,
                    'inactive' => 6,
                    'deleted' => 1,
                ],
                [
                    'location' => 'RPC Palembang',
                    'new' => 11,
                    'inactive' => 5,
                    'deleted' => 2,
                ],
            ];

        $totalData = [
            'new' => 73,
            'inactive' => 18,
            'deleted' => 12,
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Growth.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['location']);
            $sheet->setCellValue("B{$row}", $item['new']);
            $sheet->setCellValue("C{$row}", $item['inactive']);
            $sheet->setCellValue("D{$row}", $item['deleted']);

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['new']);
        $sheet->setCellValue("C{$row}", $totalData['inactive']);
        $sheet->setCellValue("D{$row}", $totalData['deleted']);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Growth.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Growth.xlsx"',
        ]);
    }

    public function indexGrowthByGroup(Request $request)
    {

        $data = [
            'charts' => [
                'labels' => ['VIP', 'Cat Community', 'Cat Lover'],
                'series' => [150, 40, 60],
            ],
            'table' => [
                'data' => [
                    [
                        'reportingGroup' => 'VIP',
                        'total' => 150,
                        'new' => 5,
                        'inactive' => 5,
                        'deleted' => 5,
                    ],
                    [
                        'reportingGroup' => 'Cat Community',
                        'total' => 40,
                        'new' => 13,
                        'inactive' => 10,
                        'deleted' => 5,
                    ],
                    [
                        'reportingGroup' => 'Cat Lover',
                        'total' => 60,
                        'new' => 10,
                        'inactive' => 0,
                        'deleted' => 5,
                    ]
                ],
                'totalData' => [
                    'total' => 250,
                    'new' => 28,
                    'inactive' => 15,
                    'deleted' => 15,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportGrowthByGroup(Request $request)
    {

        $data =
            [
                [
                    'reportingGroup' => 'VIP',
                    'total' => 150,
                    'new' => 5,
                    'inactive' => 5,
                    'deleted' => 5,
                ],
                [
                    'reportingGroup' => 'Cat Community',
                    'total' => 40,
                    'new' => 13,
                    'inactive' => 10,
                    'deleted' => 5,
                ],
                [
                    'reportingGroup' => 'Cat Lover',
                    'total' => 60,
                    'new' => 10,
                    'inactive' => 0,
                    'deleted' => 5,
                ]

            ];

        $totalData = [
            'total' => 250,
            'new' => 28,
            'inactive' => 15,
            'deleted' => 15,
        ];


        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Growth_by_group.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['reportingGroup']);
            $sheet->setCellValue("B{$row}", $item['total']);
            $sheet->setCellValue("C{$row}", $item['new']);
            $sheet->setCellValue("D{$row}", $item['inactive']);
            $sheet->setCellValue("E{$row}", $item['deleted']);

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['total']);
        $sheet->setCellValue("C{$row}", $totalData['new']);
        $sheet->setCellValue("D{$row}", $totalData['inactive']);
        $sheet->setCellValue("E{$row}", $totalData['deleted']);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Growth by Group.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Growth by Group.xlsx"',
        ]);
    }

    public function indexTotal(Request $request)
    {

        $last10Days = collect(range(0, 9))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('j M');
        });

        $data = [
            'charts' => [
                'series' => [
                    [
                        'name' => 'RPC Condet',
                        'data' => [10, 10, 10, 10, 30, 20, 15, 20, 18, 29],
                    ],
                    [
                        'name' => 'RPC Hankam',
                        'data' => [20, 40, 20, 10, 80, 30, 15, 20, 18, 29],
                    ],
                ],
                'categories' => $last10Days,
            ],
            'table' => [
                'data' => [
                    [
                        'location' => 'RPC Condet',
                        'total' => 300,
                    ],
                    [
                        'location' => 'RPC Hankam',
                        'total' => 300,
                    ],
                ],
                'totalData' => [
                    'total' => 600,
                ],
            ]
        ];

        return response()->json($data);
    }

    public function exportTotal(Request $request)
    {
        $data = [
            [
                'location' => 'RPC Condet',
                'total' => 300,
            ],
            [
                'location' => 'RPC Hankam',
                'total' => 300,
            ],
        ];

        $totalData = [
            'total' => 600,
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Total.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['location']);
            $sheet->setCellValue("B{$row}", $item['total']);

            $row++;
        }

        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);

        $sheet->setCellValue("A{$row}", "Total");
        $sheet->setCellValue("B{$row}", $totalData['total']);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Total.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Total.xlsx"',
        ]);
    }

    public function indexLeaving(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'name' => 'Agus',
                    'location' => 'RPC Condet',
                    'date' => '12 May 2024',
                    'customerGroup' => 'VIP',
                    'customerFor' => '10 Days',
                    'total' => 50000,
                ],
                [
                    'name' => 'Budi',
                    'location' => 'RPC Condet',
                    'date' => '12 May 2024',
                    'customerGroup' => 'VIP',
                    'customerFor' => '10 Days',
                    'total' => 50000,
                ],
                [
                    'name' => 'Tono',
                    'location' => 'RPC Condet',
                    'date' => '12 May 2024',
                    'customerGroup' => 'VIP',
                    'customerFor' => '10 Days',
                    'total' => 50000,
                ],
                [
                    'name' => 'Susi',
                    'location' => 'RPC Condet',
                    'date' => '12 May 2024',
                    'customerGroup' => 'VIP',
                    'customerFor' => '10 Days',
                    'total' => 50000,
                ],
                [
                    'name' => 'Chandra',
                    'location' => 'RPC Condet',
                    'date' => '12 May 2024',
                    'customerGroup' => 'VIP',
                    'customerFor' => '10 Days',
                    'total' => 50000,
                ],
            ],
        ];

        return response()->json($data);
    }

    public function exportLeaving(Request $request)
    {
        $data = [
            [
                'name' => 'Agus',
                'location' => 'RPC Condet',
                'date' => '12 May 2024',
                'customerGroup' => 'VIP',
                'customerFor' => '10 Days',
                'total' => 50000,
            ],
            [
                'name' => 'Budi',
                'location' => 'RPC Condet',
                'date' => '12 May 2024',
                'customerGroup' => 'VIP',
                'customerFor' => '10 Days',
                'total' => 50000,
            ],
            [
                'name' => 'Tono',
                'location' => 'RPC Condet',
                'date' => '12 May 2024',
                'customerGroup' => 'VIP',
                'customerFor' => '10 Days',
                'total' => 50000,
            ],
            [
                'name' => 'Susi',
                'location' => 'RPC Condet',
                'date' => '12 May 2024',
                'customerGroup' => 'VIP',
                'customerFor' => '10 Days',
                'total' => 50000,
            ],
            [
                'name' => 'Chandra',
                'location' => 'RPC Condet',
                'date' => '12 May 2024',
                'customerGroup' => 'VIP',
                'customerFor' => '10 Days',
                'total' => 50000,
            ],
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Leaving.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", $item['location']);
            $sheet->setCellValue("C{$row}", $item['date']);
            $sheet->setCellValue("D{$row}", $item['customerGroup']);
            $sheet->setCellValue("E{$row}", $item['customerFor']);
            $sheet->setCellValue("F{$row}", $item['total']);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Leaving.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Leaving.xlsx"',
        ]);
    }

    public function indexList(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'memberNo' => 'RPC 123',
                    'name' => 'Agus',
                    'location' => 'RPC Condet',
                    'status' => 'Active',
                    'gender' => 'Male',
                    'telephone' => '081245654859',
                    'email' => 'agus@gmail.com',
                ],
                [
                    'memberNo' => 'RPC 124',
                    'name' => 'Budi',
                    'location' => 'RPC Condet',
                    'status' => 'Active',
                    'gender' => 'Male',
                    'telephone' => '081245654859',
                    'email' => 'budi@gmail.com',
                ],
                [
                    'memberNo' => 'RPC 125',
                    'name' => 'Susi',
                    'location' => 'RPC Condet',
                    'status' => 'Active',
                    'gender' => 'Female',
                    'telephone' => '081245654859',
                    'email' => 'susi@gmail.com',
                ],
                [
                    'memberNo' => 'RPC 126',
                    'name' => 'Eka',
                    'location' => 'RPC Condet',
                    'status' => 'Active',
                    'gender' => 'Female',
                    'telephone' => '081245654859',
                    'email' => 'eka@gmail.com',
                ],
                [
                    'memberNo' => 'RPC 127',
                    'name' => 'Andi',
                    'location' => 'RPC Condet',
                    'status' => 'Active',
                    'gender' => 'Male',
                    'telephone' => '081245654859',
                    'email' => 'andi@gmail.com',
                ],
            ],
        ];

        return response()->json($data);
    }

    public function exportList(Request $request)
    {

        $data = [
            [
                'memberNo' => 'RPC 123',
                'name' => 'Agus',
                'location' => 'RPC Condet',
                'status' => 'Active',
                'gender' => 'Male',
                'telephone' => '081245654859',
                'email' => 'agus@gmail.com',
            ],
            [
                'memberNo' => 'RPC 124',
                'name' => 'Budi',
                'location' => 'RPC Condet',
                'status' => 'Active',
                'gender' => 'Male',
                'telephone' => '081245654859',
                'email' => 'budi@gmail.com',
            ],
            [
                'memberNo' => 'RPC 125',
                'name' => 'Susi',
                'location' => 'RPC Condet',
                'status' => 'Active',
                'gender' => 'Female',
                'telephone' => '081245654859',
                'email' => 'susi@gmail.com',
            ],
            [
                'memberNo' => 'RPC 126',
                'name' => 'Eka',
                'location' => 'RPC Condet',
                'status' => 'Active',
                'gender' => 'Female',
                'telephone' => '081245654859',
                'email' => 'eka@gmail.com',
            ],
            [
                'memberNo' => 'RPC 127',
                'name' => 'Andi',
                'location' => 'RPC Condet',
                'status' => 'Active',
                'gender' => 'Male',
                'telephone' => '081245654859',
                'email' => 'andi@gmail.com',
            ],
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_List.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['memberNo']);
            $sheet->setCellValue("B{$row}", $item['name']);
            $sheet->setCellValue("C{$row}", $item['location']);
            $sheet->setCellValue("D{$row}", $item['status']);
            $sheet->setCellValue("E{$row}", $item['gender']);
            $sheet->setCellValue("F{$row}", $item['telephone']);
            $sheet->setCellValue("G{$row}", $item['email']);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer List.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer List.xlsx"',
        ]);
    }

    public function indexRefSpend(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'referenceBy' => 'Budi',
                    'reference' => 'Langsung Datang',
                    'customer' => 'Yudi',
                    'totalSpend' => 250000,
                ],
                [
                    'referenceBy' => 'Udin',
                    'reference' => 'Langsung Datang',
                    'customer' => 'Wira',
                    'totalSpend' => 750000,
                ],
                [
                    'referenceBy' => 'Hafis',
                    'reference' => 'Langsung Datang',
                    'customer' => 'Yudi',
                    'totalSpend' => 600000,
                ],
                [
                    'referenceBy' => 'Chandra',
                    'reference' => 'Langsung Datang',
                    'customer' => 'Yudi',
                    'totalSpend' => 20000,
                ],
                [
                    'referenceBy' => 'Rudi',
                    'reference' => 'Langsung Datang',
                    'customer' => 'Yudi',
                    'totalSpend' => 450000,
                ],
            ],
        ];

        return response()->json($data);
    }

    public function exportRefSpend(Request $request)
    {

        $data = [
            [
                'referenceBy' => 'Budi',
                'reference' => 'Langsung Datang',
                'customer' => 'Yudi',
                'totalSpend' => 250000,
            ],
            [
                'referenceBy' => 'Udin',
                'reference' => 'Langsung Datang',
                'customer' => 'Wira',
                'totalSpend' => 750000,
            ],
            [
                'referenceBy' => 'Hafis',
                'reference' => 'Langsung Datang',
                'customer' => 'Yudi',
                'totalSpend' => 600000,
            ],
            [
                'referenceBy' => 'Chandra',
                'reference' => 'Langsung Datang',
                'customer' => 'Yudi',
                'totalSpend' => 20000,
            ],
            [
                'referenceBy' => 'Rudi',
                'reference' => 'Langsung Datang',
                'customer' => 'Yudi',
                'totalSpend' => 450000,
            ],
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Ref_Spend.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['referenceBy']);
            $sheet->setCellValue("B{$row}", $item['reference']);
            $sheet->setCellValue("C{$row}", $item['customer']);
            $sheet->setCellValue("D{$row}", $item['totalSpend']);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Customer Referral Spend.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Customer Referral Spend.xlsx"',
        ]);
    }

    public function indexSubAccount(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'customerName' => 'Budi',
                    'petName' => 'Ciko',
                    'condition' => 'Good',
                    'type' => 'Kucing',
                    'race' => 'Anggora',
                    'gender' => 'Jantan',
                    'sterile' => 'sudah',
                    'birthDate' => '01/10/2022',
                    'color' => 'white',
                ],
                [
                    'customerName' => 'Chika',
                    'petName' => 'Hiro',
                    'condition' => 'Good',
                    'type' => 'Kucing',
                    'race' => 'Anggora',
                    'gender' => 'Jantan',
                    'sterile' => 'sudah',
                    'birthDate' => '02/05/2022',
                    'color' => 'white',
                ],
                [
                    'customerName' => 'Rudi',
                    'petName' => 'Rocky',
                    'condition' => 'Good',
                    'type' => 'Anjing',
                    'race' => 'Bulldog',
                    'gender' => 'Jantan',
                    'sterile' => 'belum',
                    'birthDate' => '01/10/2020',
                    'color' => 'black',
                ],
                [
                    'customerName' => 'Budi',
                    'petName' => 'Ciko',
                    'condition' => 'Good',
                    'type' => 'Kucing',
                    'race' => 'Anggora',
                    'gender' => 'Jantan',
                    'sterile' => 'sudah',
                    'birthDate' => '01/10/2022',
                    'color' => 'white',
                ],
                [
                    'customerName' => 'Budi',
                    'petName' => 'Ciko',
                    'condition' => 'Good',
                    'type' => 'Kucing',
                    'race' => 'Anggora',
                    'gender' => 'Jantan',
                    'sterile' => 'sudah',
                    'birthDate' => '01/10/2022',
                    'color' => 'white',
                ],
            ],
        ];

        return response()->json($data);
    }

    public function exportSubAccount(Request $request)
    {

        $data = [
            [
                'customerName' => 'Budi',
                'petName' => 'Ciko',
                'condition' => 'Good',
                'type' => 'Kucing',
                'race' => 'Anggora',
                'gender' => 'Jantan',
                'sterile' => 'sudah',
                'birthDate' => '01/10/2022',
                'color' => 'white',
            ],
            [
                'customerName' => 'Chika',
                'petName' => 'Hiro',
                'condition' => 'Good',
                'type' => 'Kucing',
                'race' => 'Anggora',
                'gender' => 'Jantan',
                'sterile' => 'sudah',
                'birthDate' => '02/05/2022',
                'color' => 'white',
            ],
            [
                'customerName' => 'Rudi',
                'petName' => 'Rocky',
                'condition' => 'Good',
                'type' => 'Anjing',
                'race' => 'Bulldog',
                'gender' => 'Jantan',
                'sterile' => 'belum',
                'birthDate' => '01/10/2020',
                'color' => 'black',
            ],
            [
                'customerName' => 'Budi',
                'petName' => 'Ciko',
                'condition' => 'Good',
                'type' => 'Kucing',
                'race' => 'Anggora',
                'gender' => 'Jantan',
                'sterile' => 'sudah',
                'birthDate' => '01/10/2022',
                'color' => 'white',
            ],
            [
                'customerName' => 'Budi',
                'petName' => 'Ciko',
                'condition' => 'Good',
                'type' => 'Kucing',
                'race' => 'Anggora',
                'gender' => 'Jantan',
                'sterile' => 'sudah',
                'birthDate' => '01/10/2022',
                'color' => 'white',
            ],
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Customer_Sub_Account.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['customerName']);
            $sheet->setCellValue("B{$row}", $item['petName']);
            $sheet->setCellValue("C{$row}", $item['condition']);
            $sheet->setCellValue("D{$row}", $item['type']);
            $sheet->setCellValue("E{$row}", $item['race']);
            $sheet->setCellValue("F{$row}", $item['gender']);
            $sheet->setCellValue("G{$row}", $item['sterile']);
            $sheet->setCellValue("H{$row}", $item['birthDate']);
            $sheet->setCellValue("I{$row}", $item['color']);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Sub Account List.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Sub Account List.xlsx"',
        ]);
    }
}
