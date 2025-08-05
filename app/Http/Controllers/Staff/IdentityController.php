<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdentityController extends Controller
{
    public function Index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('usersIdentifications as r')
            ->join('users as u', 'r.usersId', 'u.id')
            ->join('typeId as t', 'r.typeId', 't.id')
            ->select(
                'r.id',
                'u.firstName as name',
                't.typeName',
                'r.identification',
                'r.imagePath',
                DB::raw("
                CASE
                WHEN r.status = 0 or r.status = 1 THEN 'Waiting for Approval'
                WHEN r.status = 2 THEN 'Approved'
                WHEN r.status = 3 THEN 'Reject'
                END as statusText"),
                'r.status',
                'r.reason',
                'r.approvedBy',
                DB::raw("DATE_FORMAT(r.approvedAt, '%d/%m/%Y %H:%i:%s') as approvedAt"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(r.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('r.isDeleted', '=', 0);

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

    public function approval(Request $request)
    {
        $request->validate([
            'id.*' => 'required|array',
            'status' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            //1 = Waiting for Approval
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
            'id.*' => 'integer|exists:usersIdentifications,id',
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
}
