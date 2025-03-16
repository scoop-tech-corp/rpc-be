<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductDashboardController extends Controller
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
            'productSold' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'productSoldQty' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'productSoldValue' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],

            'topSeller' => [
                [
                    'productName' => 'Vosea',
                    'total' => 120,
                ],
                [
                    'productName' => 'Kaotin',
                    'total' => 111,
                ],
                [
                    'productName' => 'Doxy',
                    'total' => 15,
                ],
                [
                    'productName' => 'Whiskas 1kg',
                    'total' => 90,
                ],
            ],

            'salesByCategory' => [
                'labels' => ['Vaksin', 'Obat Klinik Oral', 'Obat Klinik Tropikal', 'Cat Food'],
                'series' => [150, 40, 60, 70],
            ]
        ];

        return response()->json($data);
    }
}
