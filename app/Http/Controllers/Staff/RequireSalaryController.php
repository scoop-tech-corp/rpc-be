<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\require_salary;
use App\Models\require_salary_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RequireSalaryController extends Controller
{
    public function Index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('require_salaries as r')
            ->join('jobTitle as jt', 'r.jobId', 'jt.id')
            ->select(
                'r.id',
                'jt.jobName as jobName',
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

        $data->map(function ($item) {
            // Asumsi: Ada tabel 'requirement_types' dengan kolom 'id' dan 'name'
            // yang memetakan typeId ke nama persyaratan (misalnya KTP, SIM)
            $requirements = DB::table('require_salary_details as rd')
                ->join('typeId as t', 'rd.typeId', '=', 't.id')
                ->where('rd.requireSallaryId', $item->id)
                ->where('rd.isDeleted', 0) // Pastikan detail juga tidak dihapus
                ->pluck('t.typeName') // Ambil hanya nama persyaratan
                ->toArray(); // Konversi ke array PHP

            $item->require = $requirements; // Tambahkan array 'require' ke objek
            return $item;
        });

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
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

    public function detail(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:require_salaries,id',
        ]);

        $data = DB::table('require_salaries as r')
            ->join('jobTitle as jt', 'r.jobId', 'jt.id')
            ->select(
                'r.id',
                'jt.id as jobId',
                'jt.jobName as jobName',
            )
            ->where('r.id', $request->id)
            ->where('r.isDeleted', 0)
            ->first();

        $detail = DB::table('require_salary_details as rd')
            ->join('typeId as t', 'rd.typeId', '=', 't.id')
            ->select('t.id', 't.typeName')
            ->where('rd.requireSallaryId', $request->id)
            ->where('rd.isDeleted', 0) // Ensure details are not deleted
            ->get();

        $data->detail = $detail; // Add the details to the main data object

        return response()->json($data, 200);
    }

    public function update(Request $request) // Removed $id parameter from here
    {
        // 1. Validate the incoming request data, including the 'id'
        $request->validate([
            'id' => 'required|integer|exists:require_salaries,id', // Validate that 'id' exists in the table
            'jobId' => 'required|integer',
            'types' => 'required|array',
            'types.*' => 'integer',
        ]);

        // Get the ID from the request body
        $id = $request->id;

        // Get the authenticated user's ID

        try {
            DB::beginTransaction();

            // 2. Find the RequireSalary record using the ID from the request body
            $requireSalary = require_salary::where('id', $id)
                ->where('isDeleted', 0) // Ensure it's not deleted
                ->first();

            if (!$requireSalary) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Require Salary record not found or already deleted.'
                ], 404); // Not Found
            }

            // 3. Update the RequireSalary record
            $requireSalary->update([
                'jobId' => $request->jobId,
                'userUpdateId' => $request->user()->id,
                // 'updated_at' is automatically handled by Laravel's timestamps
            ]);

            // 4. Update associated RequireSalaryDetail records
            // First, soft delete all existing details for this requireSallaryId
            require_salary_detail::where('requireSallaryId', $id)
                ->where('isDeleted', 0)
                ->update([
                    'isDeleted' => true,
                    'deletedBy' => $request->user()->id,
                    'deletedAt' => now(),
                    'userUpdateId' => $request->user()->id,
                ]);

            // Then, create new RequireSalaryDetail records based on the 'types' array
            $updatedRequireSalaryDetails = [];
            foreach ($request->types as $typeId) {
                $updatedRequireSalaryDetails[] = require_salary_detail::create([
                    'requireSallaryId' => $id, // Use the ID of the parent require_salary
                    'typeId' => $typeId,
                    'userId' => $request->user()->id,
                    // 'isDeleted' is false by default for new records
                ]);
            }

            DB::commit();

            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update data.',
                'error' => $e->getMessage()
            ], 500); // Internal Server Error
        }
    }

    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();

            // 1. Find the RequireSalary record by ID
            foreach ($request->id as $va) {
                $res = require_salary::find($va);

                if (!$res) {

                    return responseInvalid(['There is any Data not found!']);
                }
            }

            foreach ($request->id as $va) {

                $req = require_salary::find($va);

                require_salary_detail::where('requireSallaryId', $req->id)
                    ->where('isDeleted', 0) // Ensure it's not already deleted
                    ->update([
                        'isDeleted' => true,
                        'deletedBy' => $request->user()->id,
                        'deletedAt' => Carbon::now()
                    ]);

                $req->DeletedBy = $request->user()->id;
                $req->isDeleted = true;
                $req->DeletedAt = Carbon::now();
                $req->save();
            }

            DB::commit();

            return responseDelete();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
