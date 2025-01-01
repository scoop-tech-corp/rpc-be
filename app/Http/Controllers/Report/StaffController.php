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
}
