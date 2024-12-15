<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        $data = [
            'chartsBookingCategory' => [
                'labels' => ['Layanan Kesehatan Hewan', 'Pet Salon', 'Rawat Inap Zona', 'Penitipan Vet', 'Vaksinasi', 'Other'],
                'series' => [44, 55, 13, 60, 70, 20],
            ],
            'chartsReportingGroup' => [
                'labels' => ['VIP', 'Other', 'Komunitas'],
                'series' => [44, 55, 13],
            ],
            'bookings' => [
                'total' => '75.35%',
                'isLoss' => 1
            ],
            'totalSaleValue' => [
                'total' => '27.5%',
                'isLoss' => 0
            ],
            'newCustomer' => [
                'total' => '48.8%',
                'isLoss' => 0
            ],
            'rebookRate' => [
                'total' => '22.5%',
                'isLoss' => 0
            ],
            'customerRetention' => [
                'total' => '40%',
                'isLoss' => 1
            ],
            'avgSaleValue' => [
                'total' => '68%',
                'isLoss' => 0
            ],
        ];

        return response()->json($data);
    }

    public function upcomingBookInpatien(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'id' => 9,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '05/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 8,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 7,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 6,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 5,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
            ],
        ];

        return response()->json($data);
    }

    public function upcomingBookOutpatien(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'id' => 9,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '05/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 8,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 7,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 6,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
                [
                    'id' => 5,
                    'startTime' => '03/12/2024 9:00 AM',
                    'endTime' => '06/12/2024 9:00 AM',
                    'location' => 'RPC Hankam',
                    'customer' => 'Rusli',
                    'serviceName' => 'Operasi',
                    'staff' => 'Yusuf',
                    'status' => 'On Progress',
                    'bookingNote' => '',
                ],
            ],
        ];

        return response()->json($data);
    }

    public function recentActivity(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'id' => 9,
                    'date' => '19 Nov, 2022 12:00 AM',
                    'staff' => 'Hafis',
                    'module' => 'Customer',
                    'event' => 'Change Data',
                    'detail' => 'Change user ID',
                ],
                [
                    'id' => 10,
                    'date' => '19 Nov, 2022 12:05 AM',
                    'staff' => 'Hafis',
                    'module' => 'Product',
                    'event' => 'Update Stock',
                    'detail' => 'Update Stock Product A',
                ],
                [
                    'id' => 11,
                    'date' => '9 Nov, 2022 12:05 AM',
                    'staff' => 'Hafis',
                    'module' => 'Staff',
                    'event' => 'Delete Data',
                    'detail' => 'Delete Account for dummy 1',
                ],
                [
                    'id' => 12,
                    'date' => '9 Nov, 2022 12:05 AM',
                    'staff' => 'Hafis',
                    'module' => 'Staff',
                    'event' => 'Delete Data',
                    'detail' => 'Delete Account for dummy 2',
                ],
                [
                    'id' => 13,
                    'date' => '9 Nov, 2022 12:05 AM',
                    'staff' => 'Hafis',
                    'module' => 'Staff',
                    'event' => 'Delete Data',
                    'detail' => 'Delete Account for dummy 3',
                ],
            ],
        ];

        return response()->json($data);
    }
}
