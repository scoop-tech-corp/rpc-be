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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function listTreatment(Request $request)
    {
        $arr = $request->locationId;

        if (count($arr) == 0) {

            $data = DB::table('treatments')
                ->select('id', 'name')
                ->where('isDeleted', '=', 0)
                ->distinct()
                ->get();
        } else {
            $data = DB::table('treatments')
                ->select('id', 'name')
                ->wherein('location_id', $arr)
                ->where('isDeleted', '=', 0)
                ->distinct()
                ->get();
        }

        return responseList($data);
    }

    public function index(Request $request)
    {
        function buildQuery(Request $request)
        {
            $data = DB::table('treatments as tm')->where('tm.isDeleted', '=', 0);

            if ($request->type) {
                $data = $data->where('tm.type', $request->type);
            }

            $data = $data->join('users', 'tm.userId', '=', 'users.id')
                ->join('diagnose as d', 'tm.diagnose_id', '=', 'd.id')
                ->join('location as l', 'tm.location_id', '=', 'l.id');

            if ($request->name) {
                $data = $data->where('tm.name', 'like', '%' . $request->name . '%');
            }

            if ($request->diagnose_id) {
                $data = $data->whereIn('tm.diagnose_id', $request->diagnose_id);
            }

            if ($request->location_id) {
                $data = $data->whereIn('tm.location_id', $request->location_id);
            }

            if ($request->status) {
                $data = $data->where('tm.status', $request->status);
            }

            if ($request->orderValue) {
                $orderByColumn = $request->orderColumn == 'createdAt' ? 'tm.created_at' : $request->orderColumn;
                $data = $data->orderBy($orderByColumn, $request->orderValue);
            } else {
                $data = $data->orderBy('tm.created_at', 'desc');
            }

            return $data->select('tm.id', 'tm.name as treatmentName', 'tm.column', 'd.name as diagnoseName', 'l.locationName', 'tm.status', 'tm.created_at', 'tm.updated_at', DB::raw("DATE_FORMAT(tm.created_at, '%d/%m/%Y') as createdAt"), 'users.firstName as createdBy');
        }

        $data = buildQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }
    public function indexItem(Request $request)
    {
        function buildQuery(Request $request)
        {
            $data = DB::table('treatmentsItems as ti')->where('ti.isDeleted', '=', 0)
                ->where('treatments_id', $request->treatments_id)
                ->leftJoin('services as s', 'ti.service_id', '=', 's.id')
                ->leftJoin('task as t', 'ti.task_id', '=', 't.id')
                ->leftJoin('servicesFrequency as sf', 'ti.frequency_id', '=', 'sf.id')
                ->leftJoin('users', 'ti.userId', '=', 'users.id');

            $data = $data->orderBy('ti.updated_at', 'desc');

            return $data->select('ti.id', 's.fullName as serviceName', 'sf.name as frequencyName', 't.name as taskName', 'ti.product_name as productName', 'ti.created_at', 'ti.quantity', 'ti.notes', 'ti.frequency_id as frequencyId', 'ti.start', 'ti.duration', 'users.firstName as createdBy');
        }

        $data = buildQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->merge(['location_id' => isset($request->location_id['value']) ? $request->location_id['value'] : 0]);

        if (isset($request->diagnose_id) && !isset($request->diagnose_id['isNew'])) {
            $request->merge(['diagnose_id' => $request->diagnose_id['value']]);
        } else if (isset($request->diagnose_id) && isset($request->diagnose_id['isNew']) && $request->diagnose_id['isNew'] == true) {
            $diagnose = Diagnose::create([
                'name' => $request->diagnose_id['label'],
                'status' => 1,
                'userId' => auth()->user()->id,
                'updated_at' => Carbon::now(),
            ]);
            $request->merge(['diagnose_id' => $diagnose->id]);
        }


        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'location_id' => 'required|integer|exists:App\Models\location,id',
            'diagnose_id' => 'required|integer|exists:App\Models\Diagnose,id',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        $result = Treatment::create([
            'name' => $request->name,
            'location_id' => $request->location_id,
            'diagnose_id' => $request->diagnose_id,
            'status' => 2,
            'column' => 6,
            'userId' => auth()->user()->id,
            'updated_at' => Carbon::now(),
        ]);

        recentActivity(
            auth()->user()->id,
            'Treatment',
            'Add Treatment',
            'Created treatment "' . $request->name
        );

        return response()->json($result);
    }
    public function manageItem(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'start' => 'required',
            'frequency_id' => 'required|integer|exists:App\Models\ServiceFrequency,id',
            'duration' => 'required|integer',
            'quantity' => 'nullable|integer',
            'service_id' => 'nullable|integer|exists:App\Models\Service,id',
            'treatments_id' => 'required|integer|exists:App\Models\Treatment,id',
            'product_type' => 'nullable|string',
            'product_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());
        $result = '';

        if ($request->isEdit) {

            $result = TreatmentsItem::where('id', $request->id)->first();

            if (!$result) {
                return responseError('id not found', 'Treatment Item not found!');
            }

            $result->start = $request->start;
            $result->frequency_id = $request->frequency_id;
            $result->duration = $request->duration;
            $result->quantity = $request->quantity ? $request->quantity : 0;
            $result->notes = $request->notes;
            // save
            $result->save();
        } else {
            if ($request->task_id) {
                $task = $request->task_id;

                if (isset($task['isNew'])) {
                    $task = Task::create([
                        'name' => $task['label'],
                        'userId' => auth()->user()->id,
                        'updated_at' => Carbon::now(),
                    ]);
                    $request->merge(['task_id' => $task->id]);
                } else {
                    $task = Task::where('id', $task['value'])->first();
                    if (!$task) {
                        return responseError('id not found', 'Task not found!');
                    }
                }

                $request->merge(['task_id' => $task->id]);
            }

            $request->merge(['userId' => auth()->user()->id]);
            $result = TreatmentsItem::create($request->all());
        }

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request)
    {
        $result = Treatment::where('treatments.id', $request->id)
            ->where('treatments.isDeleted', 0)
            ->join('users', 'treatments.userId', '=', 'users.id')
            ->join('diagnose as d', 'treatments.diagnose_id', '=', 'd.id')
            ->join('location as l', 'treatments.location_id', '=', 'l.id')
            ->select('treatments.id', 'treatments.name as treatmentName', 'treatments.location_id', 'd.name as diagnoseName', 'l.locationName', 'treatments.status', 'treatments.created_at', 'treatments.column', 'treatments.updated_at', DB::raw("DATE_FORMAT(treatments.created_at, '%d/%m/%Y') as createdAt"), 'users.firstName as createdBy')
            ->first();

        if (!$result) {
            return responseError('id not found', 'Treatment not found!');
        }
        return response()->json($result);
    }


    public function export(Request $request)
    {
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

        $fileName = "Rekap Rencana Perawatan " . $date . ".xlsx";

        return Excel::download(
            new ServiceTreatmentExport(
                $request->orderValue,
                $request->orderColumn,
            ),
            $fileName
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Treatment $treatment)
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to delete!']);
        }

        foreach ($request->id as $va) {
            $res = Treatment::find($va);

            if (!$res) {
                return responseErrorValidation(['data with id ' . $va .  ' not found!']);
            }
        }

        foreach ($request->id as $va) {
            $cat = Treatment::find($va);
            if (isset($request->status)) {
                $cat->status = $request->status;
            }
            if (isset($request->column)) {
                $data = DB::table('treatmentsItems as tm')
                    ->where('tm.isDeleted', '=', 0)
                    ->where('tm.treatments_id', $request->id)
                    ->select('tm.id',  DB::raw("(tm.start - 1 +tm.duration) as maxDay"))
                    ->orderBy('maxDay', 'desc')
                    ->first();

                if (isset($data->maxDay) && $data->maxDay > $request->column) {
                    return responseErrorValidation('', 'Duration must be greater than ' . $data->maxDay .  '!');
                }
                $cat->column = $request->column;
            }
            $cat->save();

            recentActivity(
                auth()->user()->id,
                'Treatment',
                'Update Treatment',
                'Updated treatment "' . $cat->name . '" at location ID ' . $cat->location_id
            );
        }
        return responseSuccess($request->id, 'Updated Data Successful!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to delete!']);
        }

        foreach ($request->id as $va) {
            $res = Treatment::find($va);

            if (!$res) {
                return responseErrorValidation(['data with id ' . $va .  ' not found!']);
            }
        }

        foreach ($request->id as $va) {
            $cat = Treatment::find($va);
            $cat->DeletedBy = $request->user()->id;
            $cat->isDeleted = true;
            $cat->DeletedAt = Carbon::now();
            $cat->save();

            recentActivity(
                $request->user()->id,
                'Treatment',
                'Delete Treatment',
                'Deleted treatment "' . $cat->name . '" at location ID ' . $cat->location_id
            );
        }

        return responseSuccess($request->id, 'Delete Data Successful!');
    }
}
