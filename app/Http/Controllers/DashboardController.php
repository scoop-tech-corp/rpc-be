<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

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
                'percentage' => '75.35',
                'total' => '100',
                'isLoss' => 1
            ],
            'totalSaleValue' => [
                'percentage' => '27.5',
                'total' => '250',
                'isLoss' => 0
            ],
            'newCustomer' => [
                'percentage' => '48.8',
                'total' => '300',
                'isLoss' => 0
            ],
            'rebookRate' => [
                'percentage' => '22.5',
                'total' => '200',
                'isLoss' => 0
            ],
            'customerRetention' => [
                'percentage' => '40',
                'total' => '400',
                'isLoss' => 1
            ],
            'avgSaleValue' => [
                'percentage' => '68',
                'total' => '1,400',
                'isLoss' => 0
            ],
        ];

        return response()->json($data);
    }

    public function upcomingBookInpatien(Request $request)
    {
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

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
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

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
        if (!checkAccessIndex('dashboard-menu', $request->user()->roleId)) {
            return responseUnauthorize();
        }

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('recentActivities as ra')
            ->join('users as u', 'ra.userId', 'u.id')
            ->select(
                'ra.id as id',
                'ra.module as module',
                'ra.event as event',
                'ra.details as detail',
                'u.firstName as staff',
                DB::raw("DATE_FORMAT(ra.created_at, '%d %b, %Y %l:%i %p') as date")
            );

        if ($request->locationId) {

            $data = $data->whereIn('loc.id', $request->locationId);
        }

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }


        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('ra.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }
}
