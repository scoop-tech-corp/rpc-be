<?php

namespace App\Http\Controllers\Service;

use DB;
use Validator;
use Illuminate\Support\Carbon;
use App\Models\Treatment;
use App\Models\Diagnose;
use App\Models\TreatmentsItem;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\Service\ServiceTreatmentExport;
use Maatwebsite\Excel\Facades\Excel;

class TreatmentController extends Controller
{
    public function listTreatment(Request $request)
    {
        // fix: added null check before count() to avoid TypeError in PHP 8+
        $locationIds = $request->locationId;

        $query = DB::table('treatments')
            ->select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->distinct();

        if ($locationIds && count($locationIds) > 0) {
            $query->whereIn('location_id', $locationIds);
        }

        return responseList($query->get());
    }

    public function index(Request $request)
    {
        $data = $this->buildIndexQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }

    public function indexItem(Request $request)
    {
        $data = $this->buildIndexItemQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->merge(['location_id' => $request->location_id['value'] ?? 0]);

        // fix: validate name and location_id first, before any DB side effects
        $validate = Validator::make($request->all(), [
            'name'        => 'required',
            'location_id' => 'required|integer|exists:App\Models\location,id',
            'diagnose_id' => 'required',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        // fix: wrap in transaction so diagnose creation is rolled back if treatment creation fails
        DB::beginTransaction();
        try {
            if (isset($request->diagnose_id['isNew']) && $request->diagnose_id['isNew'] == true) {
                $diagnose = Diagnose::create([
                    'name'       => $request->diagnose_id['label'],
                    'status'     => 1,
                    'userId'     => auth()->user()->id,
                    'updated_at' => Carbon::now(),
                ]);
                $request->merge(['diagnose_id' => $diagnose->id]);
            } else {
                $request->merge(['diagnose_id' => $request->diagnose_id['value']]);
            }

            $result = Treatment::create([
                'name'        => $request->name,
                'location_id' => $request->location_id,
                'diagnose_id' => $request->diagnose_id,
                'status'      => 2,
                'column'      => 6,
                'userId'      => auth()->user()->id,
                'updated_at'  => Carbon::now(),
            ]);

            recentActivity(
                auth()->user()->id,
                'Treatment',
                'Add Treatment',
                'Created treatment "' . $request->name
            );

            DB::commit();
            return response()->json($result);
        } catch (\Exception $e) {
            DB::rollback();
            return responseError($e->getMessage(), 'Something went wrong');
        }
    }

    public function manageItem(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'start'         => 'required',
            'frequency_id'  => 'required|integer|exists:App\Models\ServiceFrequency,id',
            'duration'      => 'required|integer',
            'quantity'      => 'nullable|integer',
            'service_id'    => 'nullable|integer|exists:App\Models\Service,id',
            'treatments_id' => 'required|integer|exists:App\Models\Treatment,id',
            'product_type'  => 'nullable|string',
            'product_name'  => 'nullable|string',
            'notes'         => 'nullable|string',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        if ($request->isEdit) {
            $result = TreatmentsItem::where('id', $request->id)->first();

            if (!$result) {
                return responseError('id not found', 'Treatment Item not found!');
            }

            $result->start        = $request->start;
            $result->frequency_id = $request->frequency_id;
            $result->duration     = $request->duration;
            $result->quantity     = $request->quantity ?: 0;
            $result->notes        = $request->notes;
            $result->save();
        } else {
            if ($request->task_id) {
                $task = $request->task_id;

                if (isset($task['isNew'])) {
                    $task = Task::create([
                        'name'       => $task['label'],
                        'userId'     => auth()->user()->id,
                        'updated_at' => Carbon::now(),
                    ]);
                } else {
                    $task = Task::where('id', $task['value'])->first();
                    if (!$task) {
                        return responseError('id not found', 'Task not found!');
                    }
                }

                // fix: removed duplicate merge call that was inside isNew branch
                $request->merge(['task_id' => $task->id]);
            }

            $request->merge(['userId' => auth()->user()->id]);
            $result = TreatmentsItem::create($request->all());
        }

        return response()->json($result);
    }

    public function detail(Request $request)
    {
        $result = Treatment::where('treatments.id', $request->id)
            ->where('treatments.isDeleted', 0)
            ->join('users', 'treatments.userId', '=', 'users.id')
            ->join('diagnose as d', 'treatments.diagnose_id', '=', 'd.id')
            ->join('location as l', 'treatments.location_id', '=', 'l.id')
            ->select(
                'treatments.id', 'treatments.name as treatmentName', 'treatments.location_id',
                'd.name as diagnoseName', 'l.locationName', 'treatments.status',
                'treatments.created_at', 'treatments.column', 'treatments.updated_at',
                DB::raw("DATE_FORMAT(treatments.created_at, '%d/%m/%Y') as createdAt"),
                'users.firstName as createdBy'
            )
            ->first();

        if (!$result) {
            return responseError('id not found', 'Treatment not found!');
        }

        return response()->json($result);
    }

    public function export(Request $request)
    {
        $date     = Carbon::now()->format('d-m-y');
        $fileName = "Rekap Rencana Perawatan {$date}.xlsx";  // fix: removed dead $fileName = "" assignment

        return Excel::download(
            new ServiceTreatmentExport($request->orderValue, $request->orderColumn),
            $fileName
        );
    }

    public function update(Request $request)  // fix: removed unused $treatment parameter
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to update!']);  // fix: was "delete" in an update method
        }

        foreach ($request->id as $id) {
            if (!Treatment::find($id)) {
                return responseErrorValidation(['data with id ' . $id . ' not found!']);
            }
        }

        foreach ($request->id as $id) {
            $treatment = Treatment::find($id);

            if (isset($request->status)) {
                $treatment->status = $request->status;
            }

            if (isset($request->column)) {
                $maxItem = DB::table('treatmentsItems as tm')
                    ->where('tm.isDeleted', '=', 0)
                    ->where('tm.treatments_id', $id)  // fix: was $request->id (array), should be $id (current loop value)
                    ->select('tm.id', DB::raw("(tm.start - 1 + tm.duration) as maxDay"))
                    ->orderBy('maxDay', 'desc')
                    ->first();

                if (isset($maxItem->maxDay) && $maxItem->maxDay > $request->column) {
                    return responseErrorValidation('', 'Duration must be greater than ' . $maxItem->maxDay . '!');
                }

                $treatment->column = $request->column;
            }

            $treatment->save();

            recentActivity(
                auth()->user()->id,
                'Treatment',
                'Update Treatment',
                'Updated treatment "' . $treatment->name . '" at location ID ' . $treatment->location_id
            );
        }

        return responseSuccess($request->id, 'Updated Data Successful!');
    }

    public function destroy(Request $request)
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to delete!']);
        }

        foreach ($request->id as $id) {
            if (!Treatment::find($id)) {
                return responseErrorValidation(['data with id ' . $id . ' not found!']);
            }
        }

        foreach ($request->id as $id) {
            $treatment             = Treatment::find($id);
            $treatment->DeletedBy  = $request->user()->id;
            $treatment->isDeleted  = true;
            $treatment->DeletedAt  = Carbon::now();
            $treatment->save();

            recentActivity(
                $request->user()->id,
                'Treatment',
                'Delete Treatment',
                'Deleted treatment "' . $treatment->name . '" at location ID ' . $treatment->location_id
            );
        }

        return responseSuccess($request->id, 'Delete Data Successful!');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildIndexQuery(Request $request)
    {
        $query = DB::table('treatments as tm')
            ->where('tm.isDeleted', '=', 0)
            ->join('users', 'tm.userId', '=', 'users.id')
            ->join('diagnose as d', 'tm.diagnose_id', '=', 'd.id')
            ->join('location as l', 'tm.location_id', '=', 'l.id');

        if ($request->type) {
            $query->where('tm.type', $request->type);
        }

        if ($request->name) {
            $query->where('tm.name', 'like', '%' . $request->name . '%');
        }

        if ($request->diagnose_id) {
            $query->whereIn('tm.diagnose_id', $request->diagnose_id);
        }

        if ($request->location_id) {
            $query->whereIn('tm.location_id', $request->location_id);
        }

        if ($request->status) {
            $query->where('tm.status', $request->status);
        }

        if ($request->orderValue) {
            $orderByColumn = $request->orderColumn == 'createdAt' ? 'tm.created_at' : $request->orderColumn;
            $query->orderBy($orderByColumn, $request->orderValue);
        } else {
            $query->orderBy('tm.created_at', 'desc');
        }

        return $query->select(
            'tm.id', 'tm.name as treatmentName', 'tm.column',
            'd.name as diagnoseName', 'l.locationName', 'tm.status',
            'tm.created_at', 'tm.updated_at',
            DB::raw("DATE_FORMAT(tm.created_at, '%d/%m/%Y') as createdAt"),
            'users.firstName as createdBy'
        );
    }

    private function buildIndexItemQuery(Request $request)
    {
        return DB::table('treatmentsItems as ti')
            ->where('ti.isDeleted', '=', 0)
            ->where('treatments_id', $request->treatments_id)
            ->leftJoin('services as s', 'ti.service_id', '=', 's.id')
            ->leftJoin('task as t', 'ti.task_id', '=', 't.id')
            ->leftJoin('servicesFrequency as sf', 'ti.frequency_id', '=', 'sf.id')
            ->leftJoin('users', 'ti.userId', '=', 'users.id')
            ->orderBy('ti.updated_at', 'desc')
            ->select(
                'ti.id', 's.fullName as serviceName', 'sf.name as frequencyName',
                't.name as taskName', 'ti.product_name as productName', 'ti.created_at',
                'ti.quantity', 'ti.notes', 'ti.frequency_id as frequencyId',
                'ti.start', 'ti.duration', 'users.firstName as createdBy'
            );
    }
}
