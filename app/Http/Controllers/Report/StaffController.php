<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StaffController extends Controller
{
    public function indexStaffLogin(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('staffLogins as s')
            ->join('users as u', 'u.id', 's.staffId')
            ->join('usersLocation as ul', 'u.id', 'ul.usersId')
            ->select(
                's.id',
                'u.firstName as name',
                DB::raw("DATE_FORMAT(s.created_at, '%d %b %Y') as date"),
                DB::raw("TIME_FORMAT(s.created_at, '%H:%i %p') AS time"),
                's.ipAddress as ipAddress',
                's.device as device',
            )
            ->where('ul.isMainLocation', '=', 1);

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween(DB::raw('DATE(s.created_at)'), [$request->dateFrom, $request->dateTo]);
        }

        if ($request->locationId) {

            $data = $data->whereIn('ul.locationId', $request->locationId);
        }

        if ($request->staffId) {

            $data = $data->whereIn('u.id', $request->staffId);
        }

        if ($request->orderValue) {

            if ($request->orderColumn == 'name') {
                $data = $data->orderBy('u.firstName', $request->orderValue);
            } elseif ($request->orderValue == 'date' || $request->orderValue == 'time') {
                $data = $data->orderBy('s.created_at', $request->orderValue);
            } else {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }
        }

        $data = $data->orderBy('s.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    public function exportStaffLogin(Request $request)
    {

        $data = DB::table('staffLogins as s')
            ->join('users as u', 'u.id', 's.staffId')
            ->join('usersLocation as ul', 'u.id', 'ul.usersId')
            ->select(
                's.id',
                'u.firstName as name',
                DB::raw("DATE_FORMAT(s.created_at, '%d %b %Y') as date"),
                DB::raw("TIME_FORMAT(s.created_at, '%H:%i %p') AS time"),
                's.ipAddress as ipAddress',
                's.device as device',
            )
            ->where('ul.isMainLocation', '=', 1);

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween(DB::raw('DATE(s.created_at)'), [$request->dateFrom, $request->dateTo]);
        }

        $locations = $request->locationId;

        if (count($locations) > 0) {
            if (!$locations[0] == null) {
                $data = $data->whereIn('ul.locationId', $request->locationId);
            }
        }

        $staffs = $request->staffId;

        if (count($staffs) > 0) {
            if (!$staffs[0] == null) {
                $data = $data->whereIn('u.id', $request->staffId);
            }
        }

        // if ($request->locationId) {

        //     $data = $data->whereIn('ul.locationId', $request->locationId);
        // }

        // if ($request->staffId) {

        //     $data = $data->whereIn('u.id', $request->staffId);
        // }

        $data = $data->orderBy('s.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Staff_Login.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item->name);
            $sheet->setCellValue("B{$row}", $item->date);
            $sheet->setCellValue("C{$row}", $item->time);
            $sheet->setCellValue("D{$row}", $item->ipAddress);
            $sheet->setCellValue("E{$row}", $item->device);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Staff Login.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Staff Login.xlsx"',
        ]);
    }

    public function indexStaffLate(Request $request)
    {
        if ($request->dateFrom && $request->dateTo) {
            $startDate = $request->dateFrom;
            $endDate = $request->dateTo;

            $start = Carbon::createFromFormat('Y-m-d', $startDate);
            $end = Carbon::createFromFormat('Y-m-d', $endDate);


            $rangeDate = [];
            $rangeDateFormat = [];
            while ($start <= $end) {
                // Add the formatted date to the array
                $rangeDate[] = $start->format('j M');
                $rangeDateFormat[] = $start->format('Y-m-d');

                // Move to the next day (you can change this to add weeks or months, etc.)
                $start->addDay();
            }
        } else {

            $rangeDate = collect(range(9, 0))->map(function ($daysAgo) {
                return Carbon::today()->subDays($daysAgo)->format('j M');
            });

            $rangeDateFormat = collect(range(9, 0))->map(function ($daysAgo) {
                return Carbon::today()->subDays($daysAgo)->format('Y-m-d');
            });
        }

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $table = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                'u.firstName as name',
                'sa.shift',
                'j.jobName',
                DB::raw("
                CONCAT(
                    CASE DAYOFWEEK(sa.presentTime)
                        WHEN 1 THEN 'Minggu'
                        WHEN 2 THEN 'Senin'
                        WHEN 3 THEN 'Selasa'
                        WHEN 4 THEN 'Rabu'
                        WHEN 5 THEN 'Kamis'
                        WHEN 6 THEN 'Jumat'
                        WHEN 7 THEN 'Sabtu'
                    END,
                    ', ',
                    DATE_FORMAT(sa.presentTime, '%e %b %Y')
                ) AS day
                "),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS attendanceTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H:%i') END AS homecomingTime"),
            )
            ->where('sa.isDeleted', '=', 0)
            ->where('ps.id', '=', 1)
            ->where('sa.status', '=', 'Terlambat');

        if ($request->dateFrom && $request->dateTo) {

            $table = $table->whereBetween('sa.created_at', [$request->dateFrom, $request->dateTo]);
        } else {
            $todayStart = Carbon::now();

            $nineDaysAgo = Carbon::now()->subDays(9);

            $table = $table->whereDate('sa.created_at', '>=', $nineDaysAgo)
                ->whereDate('sa.created_at', '<=', $todayStart);
        }

        if ($request->locationId) {

            $table = $table->whereIn('l.id', $request->locationId);
        }

        if ($request->staff) {
            $table = $table->whereIn('sa.userId', $request->staff);
        }

        if ($request->orderValue) {

            if ($request->orderColumn == "name") {
                $table = $table->orderBy('u.firstName', $request->orderValue);
            } elseif ($request->orderColumn == "day" || $request->orderColumn == "attendanceTime") {
                $table = $table->orderBy('sa.presentTime', $request->orderValue);
            } elseif ($request->orderColumn == "homecomingTime") {
                $table = $table->orderBy('sa.homeTime', $request->orderValue);
            } else {
                $table = $table->orderBy($request->orderColumn, $request->orderValue);
            }
        }

        $table = $table->groupBy(
            'sa.id',
            'u.firstName',
            'j.jobName',
            'sa.shift',
            'sa.status',
            'sa.presentTime',
            'sa.homeTime',
            'sa.duration',
            'ps.statusName',
            'sa.cityPresent',
            'sa.cityHome'
        );

        $table = $table->orderBy('sa.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $dataTemp = $table->get();

        $count_data = $dataTemp->count();

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $table = $table->offset(0)->limit($itemPerPage)->get();
        } else {
            $table = $table->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        $graph = DB::table('location')
            ->select('id', 'locationName');

        if ($request->locationId) {

            $graph = $graph->whereIn('id', $request->locationId);
        }

        $graph = $graph->where('isDeleted', '=', 0)
            ->get();

        foreach ($graph as $item) {

            foreach ($rangeDateFormat as $valueDate) {

                $dat = DB::table('staffAbsents as sa')
                    ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
                    ->join('users as u', 'sa.userId', 'u.id')
                    ->join('usersLocation as ul', 'ul.usersId', 'u.id')
                    ->join('location as l', 'ul.locationId', 'l.id')
                    ->where('ps.id', '=', 1)
                    ->where('ul.locationId', '=', $item->id)
                    ->where('sa.isDeleted', '=', 0)
                    ->where('sa.status', '=', 'Terlambat')
                    ->whereDate('sa.created_at', '=', $valueDate)
                    ->where('ul.isMainLocation', '=', 1)
                    ->count();

                $arr_total[] = $dat;
            }

            $series[] = [
                'name' => $item->locationName,
                'data' => $arr_total,
            ];

            unset($arr_total);
        }

        $data = [
            'charts' => [
                'series' => $series,
                'categories' => $rangeDate,
            ],
            'table' => [
                'totalPagination' => ceil($totalPaging),
                'data' => $table
            ]
        ];

        return response()->json($data);
    }

    public function exportStaffLate(Request $request)
    {

        $data = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->leftJoin('presentStatuses as ps1', 'sa.statusHome', 'ps1.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                'u.firstName as name',
                'sa.shift',
                'j.jobName',
                DB::raw("
                CONCAT(
                    CASE DAYOFWEEK(sa.presentTime)
                        WHEN 1 THEN 'Minggu'
                        WHEN 2 THEN 'Senin'
                        WHEN 3 THEN 'Selasa'
                        WHEN 4 THEN 'Rabu'
                        WHEN 5 THEN 'Kamis'
                        WHEN 6 THEN 'Jumat'
                        WHEN 7 THEN 'Sabtu'
                    END,
                    ', ',
                    DATE_FORMAT(sa.presentTime, '%e %b %Y')
                ) AS day
                "),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS attendanceTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H:%i') END AS homecomingTime"),
            )
            ->where('sa.isDeleted', '=', 0)
            ->where('sa.status', '=', 'Terlambat');

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween('sa.presentTime', [$request->dateFrom, $request->dateTo]);
        }

        $locations = $request->locationId;

        if (count($locations) > 0) {
            if (!$locations[0] == null) {
                $data = $data->whereIn('l.id', $request->locationId);
            }
        }

        $staffs = $request->staffId;

        if (count($staffs) > 0) {
            if (!$staffs[0] == null) {
                $data = $data->whereIn('sa.userId', $request->staffId);
            }
        }

        // if ($request->locationId) {

        //     $data = $data->whereIn('l.id', $request->locationId);
        // }

        // if ($request->staff) {
        //     $data = $data->whereIn('sa.userId', $request->staff);
        // }

        $data = $data->groupBy(
            'sa.id',
            'u.firstName',
            'j.jobName',
            'sa.shift',
            'sa.status',
            'sa.presentTime',
            'sa.homeTime',
            'sa.duration',
            'ps.statusName',
            'ps1.statusName',
            'sa.cityPresent',
            'sa.cityHome'
        );

        $data = $data->orderBy('sa.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Staff_Late.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->name);
            $sheet->setCellValue("C{$row}", $item->shift);
            $sheet->setCellValue("D{$row}", $item->jobName);
            $sheet->setCellValue("E{$row}", $item->day);
            $sheet->setCellValue("F{$row}", $item->attendanceTime);
            $sheet->setCellValue("G{$row}", $item->homecomingTime);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Staff Late.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Staff Late.xlsx"',
        ]);
    }

    public function indexStaffLeave(Request $request)
    {
        $last10Days = collect(range(0, 9))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('j M');
        });

        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $table = DB::table('leaveRequest as lr')
            ->join('users as u', 'lr.usersid', 'u.id')
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersid', 'u.id')
            ->join('location as l', 'l.id', 'ul.locationId')
            ->select(
                'u.firstName as name',
                'l.locationName',
                'j.jobName',
                'lr.leaveType',
                DB::raw("DATE_FORMAT(lr.fromDate, '%d %b %Y') as startDate"),
                DB::raw("DATE_FORMAT(lr.toDate, '%d %b %Y') as endDate"),
                'lr.duration as days'
            )
            ->where('lr.status', '=', 'approve')
            ->where('ul.isMainLocation', '=', 1);

        if ($request->dateFrom && $request->dateTo) {

            $table = $table->whereBetween('lr.fromDate', [$request->dateFrom, $request->dateTo]);
        }

        if ($request->locationId) {

            $table = $table->whereIn('l.id', $request->locationId);
        }

        if ($request->staff) {
            $table = $table->whereIn('u.id', $request->staff);
        }

        if ($request->leaveType) {
            $table = $table->whereIn('lr.leaveType', $request->leaveType);
        }

        if ($request->orderValue) {

            if ($request->orderColumn == "startDate") {
                $table = $table->orderBy('lr.fromDate', $request->orderValue);
            } elseif ($request->orderColumn == "endDate") {
                $table = $table->orderBy('lr.toDate', $request->orderValue);
            } elseif ($request->orderColumn == "days") {
                $table = $table->orderBy('lr.duration', $request->orderValue);
            } else {
                $table = $table->orderBy($request->orderColumn, $request->orderValue);
            }
        }

        $table = $table->orderBy('lr.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $dataTemp = $table->get();

        $count_data = $dataTemp->count();

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $table = $table->offset(0)->limit($itemPerPage)->get();
        } else {
            $table = $table->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        $data = [
            'charts' => [
                'series' => [
                    [
                        'name' => 'RPC Condet',
                        'data' => [10, 10, 10, 10, 30, 20, 15, 20, 18, 29],
                    ],
                    [
                        'name' => 'RPC Hankam',
                        'data' => [25, 100, 120, 90, 77, 63, 22, 95, 45, 31],
                    ],
                    [
                        'name' => 'RPC Tanjung Duren',
                        'data' => [20, 40, 20, 10, 80, 30, 15, 20, 18, 29],
                    ],
                    [
                        'name' => 'RPC Sawangan',
                        'data' => [30, 20, 60, 5, 20, 10, 12, 78, 54, 34],
                    ],
                    [
                        'name' => 'RPC Palembang',
                        'data' => [60, 20, 10, 17, 23, 65, 48, 34, 12, 29],
                    ],
                ],
                'categories' => $last10Days,
            ],
            'table' => [
                'totalPagination' => ceil($totalPaging),
                'data' => $table
            ]
        ];

        return response()->json($data);
    }

    public function exportStaffLeave(Request $request)
    {
        $data = DB::table('leaveRequest as lr')
            ->join('users as u', 'lr.usersid', 'u.id')
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersid', 'u.id')
            ->join('location as l', 'l.id', 'ul.locationId')
            ->select(
                'u.firstName as name',
                'l.locationName',
                'j.jobName',
                'lr.leaveType',
                DB::raw("DATE_FORMAT(lr.fromDate, '%d %b %Y') as startDate"),
                DB::raw("DATE_FORMAT(lr.toDate, '%d %b %Y') as endDate"),
                'lr.duration as days'
            )
            ->where('lr.status', '=', 'approve')
            ->where('ul.isMainLocation', '=', 1);

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween('lr.fromDate', [$request->dateFrom, $request->dateTo]);
        }

        // if ($request->locationId) {

        //     $data = $data->whereIn('l.id', $request->locationId);
        // }

        // if ($request->staff) {
        //     $data = $data->whereIn('u.id', $request->staff);
        // }
        $locations = $request->locationId;

        if (count($locations) > 0) {
            if (!$locations[0] == null) {
                $data = $data->whereIn('l.id', $request->locationId);
            }
        }

        $staffs = $request->staffId;

        if (count($staffs) > 0) {
            if (!$staffs[0] == null) {
                $data = $data->whereIn('u.id', $request->staffId);
            }
        }

        $leaveTypes = $request->leaveType;

        if (count($leaveTypes) > 0) {
            if (!$leaveTypes[0] == null) {
                $data = $data->whereIn('lr.leaveType', $request->leaveType);
            }
        }

        // if ($request->leaveType) {
        //     $data = $data->whereIn('lr.leaveType', $request->leaveType);
        // }

        $data = $data->orderBy('lr.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Staff_Leave.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->name);
            $sheet->setCellValue("C{$row}", $item->locationName);
            $sheet->setCellValue("D{$row}", $item->jobName);
            $sheet->setCellValue("E{$row}", $item->leaveType);
            $sheet->setCellValue("F{$row}", $item->startDate);
            $sheet->setCellValue("G{$row}", $item->endDate);
            $sheet->setCellValue("H{$row}", $item->days);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Staff Leave.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Staff Leave.xlsx"',
        ]);
    }

    public function indexStaffPeformance(Request $request)
    {

        $data = [
            'totalPagination' => 1,
            'data' => [
                [
                    'name' => 'Budi',
                    'booking' => 2,
                    'services' => 5,
                    'bookingDuration' => 12,
                    'bookingValue' => 2,
                    'classes' => 6,
                    'attendees' => 9,
                    'classDuration' => 12,
                    'classValue' => 10,
                    'totalDuration' => 7,
                    'totalAnnualLeave' => 1,
                    'totalSickLeave' => 3,
                    'totalLeaveRemaining' => 13,
                    'totalLate' => 1,
                ],
                [
                    'name' => 'Susi',
                    'booking' => 2,
                    'services' => 5,
                    'bookingDuration' => 12,
                    'bookingValue' => 2,
                    'classes' => 6,
                    'attendees' => 9,
                    'classDuration' => 12,
                    'classValue' => 10,
                    'totalDuration' => 7,
                    'totalAnnualLeave' => 1,
                    'totalSickLeave' => 3,
                    'totalLeaveRemaining' => 13,
                    'totalLate' => 1,
                ],
                [
                    'name' => 'Agus',
                    'booking' => 2,
                    'services' => 5,
                    'bookingDuration' => 12,
                    'bookingValue' => 2,
                    'classes' => 6,
                    'attendees' => 9,
                    'classDuration' => 12,
                    'classValue' => 10,
                    'totalDuration' => 7,
                    'totalAnnualLeave' => 1,
                    'totalSickLeave' => 3,
                    'totalLeaveRemaining' => 13,
                    'totalLate' => 1,
                ],
                [
                    'name' => 'Raka',
                    'booking' => 2,
                    'services' => 5,
                    'bookingDuration' => 12,
                    'bookingValue' => 2,
                    'classes' => 6,
                    'attendees' => 9,
                    'classDuration' => 12,
                    'classValue' => 10,
                    'totalDuration' => 7,
                    'totalAnnualLeave' => 1,
                    'totalSickLeave' => 3,
                    'totalLeaveRemaining' => 13,
                    'totalLate' => 1,
                ],
                [
                    'name' => 'Erik',
                    'booking' => 2,
                    'services' => 5,
                    'bookingDuration' => 12,
                    'bookingValue' => 2,
                    'classes' => 6,
                    'attendees' => 9,
                    'classDuration' => 12,
                    'classValue' => 10,
                    'totalDuration' => 7,
                    'totalAnnualLeave' => 1,
                    'totalSickLeave' => 3,
                    'totalLeaveRemaining' => 13,
                    'totalLate' => 1,
                ],
            ],
        ];

        return response()->json($data);
    }

    public function exportStaffPeformance(Request $request)
    {
        $data = [

            [
                'name' => 'Budi',
                'booking' => 2,
                'services' => 5,
                'bookingDuration' => 12,
                'bookingValue' => 2,
                'classes' => 6,
                'attendees' => 9,
                'classDuration' => 12,
                'classValue' => 10,
                'totalDuration' => 7,
                'totalAnnualLeave' => 1,
                'totalSickLeave' => 3,
                'totalLeaveRemaining' => 13,
                'totalLate' => 1,
            ],
            [
                'name' => 'Susi',
                'booking' => 2,
                'services' => 5,
                'bookingDuration' => 12,
                'bookingValue' => 2,
                'classes' => 6,
                'attendees' => 9,
                'classDuration' => 12,
                'classValue' => 10,
                'totalDuration' => 7,
                'totalAnnualLeave' => 1,
                'totalSickLeave' => 3,
                'totalLeaveRemaining' => 13,
                'totalLate' => 1,
            ],
            [
                'name' => 'Agus',
                'booking' => 2,
                'services' => 5,
                'bookingDuration' => 12,
                'bookingValue' => 2,
                'classes' => 6,
                'attendees' => 9,
                'classDuration' => 12,
                'classValue' => 10,
                'totalDuration' => 7,
                'totalAnnualLeave' => 1,
                'totalSickLeave' => 3,
                'totalLeaveRemaining' => 13,
                'totalLate' => 1,
            ],
            [
                'name' => 'Raka',
                'booking' => 2,
                'services' => 5,
                'bookingDuration' => 12,
                'bookingValue' => 2,
                'classes' => 6,
                'attendees' => 9,
                'classDuration' => 12,
                'classValue' => 10,
                'totalDuration' => 7,
                'totalAnnualLeave' => 1,
                'totalSickLeave' => 3,
                'totalLeaveRemaining' => 13,
                'totalLate' => 1,
            ],
            [
                'name' => 'Erik',
                'booking' => 2,
                'services' => 5,
                'bookingDuration' => 12,
                'bookingValue' => 2,
                'classes' => 6,
                'attendees' => 9,
                'classDuration' => 12,
                'classValue' => 10,
                'totalDuration' => 7,
                'totalAnnualLeave' => 1,
                'totalSickLeave' => 3,
                'totalLeaveRemaining' => 13,
                'totalLate' => 1,
            ],

        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Staff_Peformance.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", $item['booking']);
            $sheet->setCellValue("C{$row}", $item['services']);
            $sheet->setCellValue("D{$row}", $item['bookingDuration']);
            $sheet->setCellValue("E{$row}", $item['bookingValue']);
            $sheet->setCellValue("F{$row}", $item['classes']);
            $sheet->setCellValue("G{$row}", $item['attendees']);
            $sheet->setCellValue("H{$row}", $item['classDuration']);
            $sheet->setCellValue("I{$row}", $item['classValue']);
            $sheet->setCellValue("J{$row}", $item['totalDuration']);
            $sheet->setCellValue("K{$row}", $item['totalAnnualLeave']);
            $sheet->setCellValue("L{$row}", $item['totalSickLeave']);
            $sheet->setCellValue("M{$row}", $item['totalLeaveRemaining']);
            $sheet->setCellValue("N{$row}", $item['totalLate']);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Report Staff Peformance.xlsx'; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Report Staff Peformance.xlsx"',
        ]);
    }
}
