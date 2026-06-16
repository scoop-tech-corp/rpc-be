<?php

namespace App\Http\Controllers\Transaction;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class TransactionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'totalTransaksi'     => ['total' => '1.248', 'percentage' => '12.5',  'isLoss' => 0],
            'totalRevenue'       => ['total' => '184.750.000', 'percentage' => '8.3', 'isLoss' => 0],
            'transaksiSelesai'   => ['total' => '1.102', 'percentage' => '5.7',  'isLoss' => 0],
            'customerBaru'       => ['total' => '87',    'percentage' => '3.2',  'isLoss' => 1],

            'chartsVolume'       => $this->dummyVolumeChart(),
            'chartsRevenue'      => $this->dummyRevenueChart(),
            'chartsLayanan'      => $this->dummyLayananChart(),
            'chartsCabang'       => $this->dummyCabangChart(),
        ];

        return response()->json($data);
    }

    private function dummyVolumeChart(): array
    {
        $categories = collect(range(0, 12))->map(
            fn($d) => Carbon::today()->subDays(12 - $d)->format('j M')
        )->values()->toArray();

        return [
            'series' => [
                ['name' => 'Previous', 'data' => [28, 32, 20, 35, 40, 30, 25, 38, 42, 36, 29, 34, 31]],
                ['name' => 'Current',  'data' => [35, 40, 28, 42, 50, 38, 33, 45, 55, 48, 37, 42, 39]],
            ],
            'categories' => $categories,
        ];
    }

    private function dummyRevenueChart(): array
    {
        $categories = collect(range(0, 12))->map(
            fn($d) => Carbon::today()->subDays(12 - $d)->format('j M')
        )->values()->toArray();

        return [
            'series' => [
                ['name' => 'Previous', 'data' => [8200000,  9100000,  7500000,  10200000, 11500000, 9800000,  8700000,  10900000, 12300000, 11100000, 9200000,  10500000, 9800000]],
                ['name' => 'Current',  'data' => [10500000, 12200000, 9800000,  13500000, 15800000, 12100000, 11300000, 14200000, 16900000, 15300000, 12800000, 14100000, 13200000]],
            ],
            'categories' => $categories,
        ];
    }

    private function dummyLayananChart(): array
    {
        return [
            'labels' => ['Pet Clinic', 'Pet Hotel', 'Pet Salon', 'Breeding', 'Pet Shop'],
            'series' => [380, 210, 295, 98, 265],
        ];
    }

    private function dummyCabangChart(): array
    {
        return [
            'series' => [
                ['name' => 'Transaksi', 'data' => [420, 310, 275, 243]],
            ],
            'categories' => ['Cabang Utama', 'Cabang Selatan', 'Cabang Timur', 'Cabang Barat'],
        ];
    }
}
