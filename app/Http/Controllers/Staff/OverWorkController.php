<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\FullShift;
use App\Models\LongShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverWorkController extends Controller
{
    public function indexFullShift(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('full_shifts as f')
            ->join('users as u', 'f.userId', 'u.id')
            ->join('location as l', 'f.locationId', 'l.id')
            ->leftjoin('users as ua', 'ua.id', 'f.approvedBy')
            ->select(
                'f.id',
                'u.firstName as name',
                'l.locationName',
                'f.fullShiftDate',
                'f.reason',
                'f.status',
                DB::raw("
                CASE
                WHEN f.status = 0 THEN 'Waiting for Approval'
                WHEN f.status = 1 THEN 'Approved'
                WHEN f.status = 2 THEN 'Reject'
                END as statusText"),
                'ua.firstName as approvedName',
                DB::raw("DATE_FORMAT(f.approvedAt, '%d/%m/%Y %H:%i:%s') as approvedAt"),
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
            ->join('location as l', 'f.locationId', 'l.id')
            ->leftjoin('users as ua', 'ua.id', 'f.approvedBy')
            ->select(
                'f.id',
                'u.firstName as name',
                'l.locationName',
                'f.longShiftDate',
                'f.reason',
                'f.status',
                DB::raw("
                CASE
                WHEN f.status = 0 THEN 'Waiting for Approval'
                WHEN f.status = 1 THEN 'Approved'
                WHEN f.status = 2 THEN 'Reject'
                END as statusText"),
                'ua.firstName as approvedName',
                DB::raw("DATE_FORMAT(f.approvedAt, '%d/%m/%Y %H:%i:%s') as approvedAt"),
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
}
