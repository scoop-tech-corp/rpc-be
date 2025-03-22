<?php

namespace App\Http\Controllers\Promotion;

use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class PromotionDashboardController extends Controller
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
            'promotions' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'promotionsQty' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'promotionsValue' => [
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],

            'mostPopular' => [
                [
                    'promotionName' => 'Ramadhan Promo',
                    'promotions' => 120,
                ],
                [
                    'promotionName' => 'New Year Sales',
                    'promotions' => 111,
                ],
                [
                    'promotionName' => 'Independence Day',
                    'promotions' => 15,
                ],
                [
                    'promotionName' => 'Weekend Promo',
                    'promotions' => 90,
                ],
            ],

            'promotionsByCategory' => [
                'labels' => ['Free Iten', 'Discount', 'Bundle', 'Based Sales'],
                'series' => [150, 40, 60, 70],
            ]
        ];

        return response()->json($data);
    }
}
