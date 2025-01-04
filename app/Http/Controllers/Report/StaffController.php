<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
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
            );

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
            );

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween(DB::raw('DATE(s.created_at)'), [$request->dateFrom, $request->dateTo]);
        }

        if ($request->locationId) {

            $data = $data->whereIn('ul.locationId', $request->locationId);
        }

        if ($request->staffId) {

            $data = $data->whereIn('u.id', $request->staffId);
        }

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
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

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

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staff) {
            $data = $data->whereIn('sa.userId', $request->staff);
        }

        if ($request->orderValue) {

            if ($request->orderColumn == "name") {
                $data = $data->orderBy('u.firstName', $request->orderValue);
            } elseif ($request->orderColumn == "day" || $request->orderColumn == "attendanceTime") {
                $data = $data->orderBy('sa.presentTime', $request->orderValue);
            } elseif ($request->orderColumn == "homecomingTime") {
                $data = $data->orderBy('sa.homeTime', $request->orderValue);
            } else {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }
        }

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

        $data = $data->orderBy('sa.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $dataTemp = $data->get();

        $count_data = $dataTemp->count();

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->tosql();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
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

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staff) {
            $data = $data->whereIn('sa.userId', $request->staff);
        }

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
}
