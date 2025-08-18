<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\FullShift;
use App\Models\LongShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OverWorkController extends Controller
{
    public function indexFullShift(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('full_shifts as f')
            ->join('users as u', 'f.userId', 'u.id')
            ->join('jobTitle as jt', 'u.jobTitleId', 'jt.id')
            ->join('location as l', 'f.locationId', 'l.id')
            ->leftjoin('users as ua', 'ua.id', 'f.approvedBy')
            ->select(
                'f.id',
                'u.firstName as name',
                'jt.jobName',
                'l.locationName',
                'f.fullShiftDate',
                'f.reason',
                DB::raw("
                CASE
                WHEN f.status = 0 THEN 'Menunggu Persetujuan'
                WHEN f.status = 1 THEN 'Diterima'
                WHEN f.status = 2 THEN 'Ditolak'
                END as status"),
                'ua.firstName as checkedBy',
                'f.reasonChecker',
                DB::raw("DATE_FORMAT(f.approvedAt, '%d/%m/%Y %H:%i:%s') as checkedAt"),
                DB::raw("DATE_FORMAT(f.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('f.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staffId) {

            $data = $data->whereIn('f.userId', $request->staffId);
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('f.updated_at', 'desc');

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

    public function indexLongShift(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('long_shifts as f')
            ->join('users as u', 'f.userId', 'u.id')
            ->join('jobTitle as jt', 'u.jobTitleId', 'jt.id')
            ->join('location as l', 'f.locationId', 'l.id')
            ->leftjoin('users as ua', 'ua.id', 'f.approvedBy')
            ->select(
                'f.id',
                'u.firstName as name',
                'jt.jobName',
                'l.locationName',
                'f.longShiftDate',
                'f.reason',
                DB::raw("
                CASE
                WHEN f.status = 0 THEN 'Menunggu Persetujuan'
                WHEN f.status = 1 THEN 'Diterima'
                WHEN f.status = 2 THEN 'Ditolak'
                END as status"),
                'ua.firstName as checkedBy',
                'f.reasonChecker',
                DB::raw("DATE_FORMAT(f.approvedAt, '%d/%m/%Y %H:%i:%s') as checkedAt"),
                DB::raw("DATE_FORMAT(f.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('f.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staffId) {

            $data = $data->whereIn('f.userId', $request->staffId);
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('f.updated_at', 'desc');

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

    public function createFullShift(Request $request)
    {
        $request->validate([
            'fullShiftDate' => 'required|date',
            'reason' => 'required|string',
        ]);

        $locationId = DB::table('usersLocation')
            ->where('usersId', $request->user()->id)
            ->where('isMainLocation', 1)
            ->value('locationId');

        try {
            DB::beginTransaction();

            // 3. Create a new RequireSalary record
            FullShift::create([
                'locationId' => $locationId,
                'fullShiftDate' => $request->fullShiftDate,
                'reason' => $request->reason,
                'status' => 0,
                'userId' => $request->user()->id,
            ]);

            DB::commit();

            return responseCreate();
        } catch (\Exception $e) {
            // If any error occurs, rollback the transaction
            DB::rollBack();

            // 7. Return an error response
            return response()->json([
                'message' => 'Failed to create data.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error status code
        }
    }

    public function updateFullShift(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:full_shifts,id',
            'fullShiftDate' => 'required|date',
            'reason' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            DB::table('full_shifts')
                ->where('id', $request->id)
                ->update([
                    'fullShiftDate' => $request->fullShiftDate,
                    'reason' => $request->reason,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function approvalFullShift(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'status' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            //0 = Menunggu Persetujuan
            //1 = Approved
            //2 = Reject
            foreach ($request->id as $id) {
                DB::table('full_shifts')
                    ->where('id', $id)
                    ->update([
                        'status' => $request->status,
                        'reason' => $request->reason,
                        'approvedBy' => $request->user()->id,
                        'approvedAt' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update status.', 'error' => $e->getMessage()], 500);
        }
    }

    public function createLongShift(Request $request)
    {
        $request->validate([
            'longShiftDate' => 'required|date',
            'reason' => 'required|string',
        ]);

        $locationId = DB::table('usersLocation')
            ->where('usersId', $request->user()->id)
            ->where('isMainLocation', 1)
            ->value('locationId');

        try {
            DB::beginTransaction();

            // 3. Create a new RequireSalary record
            LongShift::create([
                'locationId' => $locationId,
                'longShiftDate' => $request->longShiftDate,
                'reason' => $request->reason,
                'status' => 0,
                'userId' => $request->user()->id,
            ]);

            DB::commit();

            return responseCreate();
        } catch (\Exception $e) {
            // If any error occurs, rollback the transaction
            DB::rollBack();

            // 7. Return an error response
            return response()->json([
                'message' => 'Failed to create data.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error status code
        }
    }

    public function updateLongShift(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:long_shifts,id',
            'longShiftDate' => 'required|date',
            'reason' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            DB::table('long_shifts')
                ->where('id', $request->id)
                ->update([
                    'longShiftDate' => $request->longShiftDate,
                    'reason' => $request->reason,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function approvalLongShift(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'status' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            //0 = Menunggu Persetujuan
            //1 = Approved
            //2 = Reject
            foreach ($request->id as $id) {
                DB::table('long_shifts')
                    ->where('id', $id)
                    ->update([
                        'status' => $request->status,
                        'reason' => $request->reason,
                        'approvedBy' => $request->user()->id,
                        'approvedAt' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update status.', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteFullShift(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'integer|exists:full_shifts,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->id as $id) {
                DB::table('full_shifts')
                    ->where('id', $id)
                    ->update([
                        'isDeleted' => 1,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return responseDelete();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteLongShift(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'integer|exists:long_shifts,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->id as $id) {
                DB::table('long_shifts')
                    ->where('id', $id)
                    ->update([
                        'isDeleted' => 1,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return responseDelete();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportFullShift(Request $request)
    {
        $data = DB::table('full_shifts as f')
            ->join('users as u', 'f.userId', 'u.id')
            ->join('jobTitle as jt', 'u.jobTitleId', 'jt.id')
            ->join('location as l', 'f.locationId', 'l.id')
            ->leftjoin('users as ua', 'ua.id', 'f.approvedBy')
            ->select(
                'u.firstName',
                'jt.jobName',
                'l.locationName',
                'f.fullShiftDate',
                'f.reason',
                DB::raw("
                CASE
                WHEN f.status = 0 THEN 'Menunggu Persetujuan'
                WHEN f.status = 1 THEN 'Diterima'
                WHEN f.status = 2 THEN 'Ditolak'
                END as status"),
                'ua.firstName as checkedBy',
                'f.reasonChecker',
                DB::raw("DATE_FORMAT(f.approvedAt, '%d/%m/%Y %H:%i:%s') as checkedAt"),
                DB::raw("DATE_FORMAT(f.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('f.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staffId) {

            $data = $data->whereIn('f.userId', $request->staffId);
        }

        $data = $data->orderBy('f.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/staff/' . 'Template_Full_Shift.xlsx');

        $sheet = $spreadsheet->getSheet(0);
        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->firstName);
            $sheet->setCellValue("C{$row}", $item->jobName);
            $sheet->setCellValue("D{$row}", $item->locationName);
            $sheet->setCellValue("E{$row}", $item->fullShiftDate);
            $sheet->setCellValue("F{$row}", $item->reason);
            $sheet->setCellValue("G{$row}", $item->status);
            $sheet->setCellValue("H{$row}", $item->reasonChecker);
            $sheet->setCellValue("I{$row}", $item->createdAt);
            $sheet->setCellValue("J{$row}", $item->checkedBy);
            $sheet->setCellValue("K{$row}", $item->checkedAt);

            $row++;
        }

        $fileName = 'Export Full Shift Staff.xlsx';

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . $fileName; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportLongShift(Request $request)
    {
        $data = DB::table('long_shifts as f')
            ->join('users as u', 'f.userId', 'u.id')
            ->join('jobTitle as jt', 'u.jobTitleId', 'jt.id')
            ->join('location as l', 'f.locationId', 'l.id')
            ->leftjoin('users as ua', 'ua.id', 'f.approvedBy')
            ->select(
                'u.firstName',
                'jt.jobName',
                'l.locationName',
                'f.longShiftDate',
                'f.reason',
                DB::raw("
                CASE
                WHEN f.status = 0 THEN 'Menunggu Persetujuan'
                WHEN f.status = 1 THEN 'Diterima'
                WHEN f.status = 2 THEN 'Ditolak'
                END as status"),
                'ua.firstName as checkedBy',
                'f.reasonChecker',
                DB::raw("DATE_FORMAT(f.approvedAt, '%d/%m/%Y %H:%i:%s') as checkedAt"),
                DB::raw("DATE_FORMAT(f.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('f.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staffId) {

            $data = $data->whereIn('f.userId', $request->staffId);
        }

        $data = $data->orderBy('f.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/staff/' . 'Template_Long_Shift.xlsx');

        $sheet = $spreadsheet->getSheet(0);
        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->firstName);
            $sheet->setCellValue("C{$row}", $item->jobName);
            $sheet->setCellValue("D{$row}", $item->locationName);
            $sheet->setCellValue("E{$row}", $item->longShiftDate);
            $sheet->setCellValue("F{$row}", $item->reason);
            $sheet->setCellValue("G{$row}", $item->status);
            $sheet->setCellValue("H{$row}", $item->reasonChecker);
            $sheet->setCellValue("I{$row}", $item->createdAt);
            $sheet->setCellValue("J{$row}", $item->checkedBy);
            $sheet->setCellValue("K{$row}", $item->checkedAt);

            $row++;
        }

        $fileName = 'Export Long Shift Staff.xlsx';

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . $fileName; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
