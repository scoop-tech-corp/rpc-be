<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ServiceDashboardController extends Controller
{
    public function index()
    {
        $last10Days = collect(range(0, 9))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('j');
        });

        $data = [
            'charts' => [
                'series' => [
                    [
                        'name' => 'Previous',
                        'data' => [10, 10, 10, 10, 30, 20, 15, 20, 18, 29],
                    ],
                    [
                        'name' => 'Current',
                        'data' => [20, 40, 20, 10, 80, 30, 15, 20, 18, 29],
                    ],
                ],
                'categories' => $last10Days,
            ],
            'bookings' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'bookingsQty' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'bookingsValue' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],

            'mostPopular' => [
                [
                    'serviceName' => 'Jasa Dokter Hewan',
                    'bookings' => 120,
                ],
                [
                    'serviceName' => 'Salon Kucing',
                    'bookings' => 111,
                ],
                [
                    'serviceName' => 'Rawat Inap',
                    'bookings' => 15,
                ],
                [
                    'serviceName' => 'Steril',
                    'bookings' => 90,
                ],
            ],

            'bookingsByCategory' => [
                'labels' => ['Layanan Kesehatan Hewan', 'Pet Salon', 'Rawat Inap Zona', 'Vaksinasi'],
                'series' => [150, 40, 60, 70],
            ]
        ];

        return response()->json($data);
    }
}
