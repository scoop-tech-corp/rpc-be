<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IdentityController extends Controller
{
    public function Index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('usersIdentifications as r')
            ->join('users as u', 'r.usersId', 'u.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->join('jobTitle as j', 'u.jobtitleId', 'j.id')
            ->Leftjoin('users as ua', 'r.approvedBy', 'ua.id')
            ->join('typeId as t', 'r.typeId', 't.id')
            ->select(
                'r.id',
                'u.firstName',  //nama
                't.typeName',   //tipe kartu
                'j.jobName',    //jabatan
                'r.identification', //nomernya
                'l.locationName', //lokasi
                'r.imagePath',
                DB::raw("
                CASE
                WHEN r.status = 0 or r.status = 1 THEN 'Menunggu Persetujuan'
                WHEN r.status = 2 THEN 'Disetujui'
                WHEN r.status = 3 THEN 'Ditolak'
                END as statusText"),
                'r.status',
                'r.reason',
                'ua.firstName as checkedBy',
                DB::raw("DATE_FORMAT(r.approvedAt, '%d/%m/%Y %H:%i:%s') as checkedAt"),
                DB::raw("DATE_FORMAT(r.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('ul.isMainLocation', '=', 1)
            ->where('r.isDeleted', '=', 0);

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

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->jobtitleId) {

            $data = $data->whereIn('j.id', $request->jobtitleId);
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('r.updated_at', 'desc');

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

    private function Search($request)
    {

        $temp_column = null;

        $data = DB::table('usersIdentifications as r')
            ->join('users as u', 'r.usersId', 'u.id')
            ->Leftjoin('users as ua', 'r.approvedBy', 'ua.id')
            ->join('typeId as t', 'r.typeId', 't.id')
            ->select(
                'u.firstName',
            )
            ->where('r.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }

    public function approval(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'status' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            //0/1 = Waiting for Approval
            //2 = Approved
            //3 = Reject
            foreach ($request->id as $id) {
                DB::table('usersIdentifications')
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

    public function create(Request $request)
    {
        $request->validate([
            'jobId' => 'required|integer',
            'types' => 'required|array', // 'types' should be an array
            'types.*' => 'integer', // Each element in 'types' array must be an integer
        ]);

        try {
            DB::beginTransaction();

            // 3. Create a new RequireSalary record
            $requireSalary = require_salary::create([
                'jobId' => $request->jobId,
                'userId' => $request->user()->id,
            ]);

            $requireSalaryId = $requireSalary->id;
            foreach ($request->types as $typeId) {
                require_salary_detail::create([
                    'requireSallaryId' => $requireSalaryId,
                    'typeId' => $typeId, // Use the current typeId from the loop
                    'userId' => $request->user()->id,
                ]);
            }

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

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'integer',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->id as $id) {
                DB::table('usersIdentifications')
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

    public function export(Request $request)
    {
        $data = DB::table('usersIdentifications as r')
            ->join('users as u', 'r.usersId', 'u.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->join('jobTitle as j', 'u.jobtitleId', 'j.id')
            ->Leftjoin('users as ua', 'r.approvedBy', 'ua.id')
            ->join('typeId as t', 'r.typeId', 't.id')
            ->select(
                'u.firstName',  //nama
                't.typeName',   //tipe kartu
                'j.jobName',    //jabatan
                'r.identification', //nomer kartunya
                'l.locationName', //lokasi
                DB::raw("
                CASE
                WHEN r.status = 0 or r.status = 1 THEN 'Menunggu Persetujuan'
                WHEN r.status = 2 THEN 'Disetujui'
                WHEN r.status = 3 THEN 'Ditolak'
                END as statusText"),
                'r.reason',
                'ua.firstName as approvedBy',
                DB::raw("DATE_FORMAT(r.approvedAt, '%d/%m/%Y %H:%i:%s') as approvedAt"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(r.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('ul.isMainLocation', '=', 1)
            ->where('r.isDeleted', '=', 0);

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->jobtitleId) {

            $data = $data->whereIn('j.id', $request->jobtitleId);
        }

        $data = $data->orderBy('r.updated_at', 'desc')->get();

        // Export logic here (e.g., to CSV, Excel, etc.)
        $spreadsheet = IOFactory::load(public_path() . '/template/staff/' . 'Template_Export_Verifikasi_Data_Staff.xlsx');

        $sheet = $spreadsheet->getSheet(0);
        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->firstName);
            $sheet->setCellValue("C{$row}", $item->jobName);
            $sheet->setCellValue("D{$row}", $item->locationName);
            $sheet->setCellValue("E{$row}", $item->typeName);
            $sheet->setCellValue("F{$row}", $item->identification);
            $sheet->setCellValue("G{$row}", $item->statusText);
            $sheet->setCellValue("H{$row}", $item->createdAt);
            $sheet->setCellValue("I{$row}", $item->approvedBy);
            $sheet->setCellValue("J{$row}", $item->approvedAt);

            $row++;
        }

        $fileName = 'Export Verifikasi Data Staff.xlsx';

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
