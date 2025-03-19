<?php

namespace App\Http\Controllers;

use App\Models\Timekeeper;
use Illuminate\Http\Request;
use Validator;
use DB;
use Illuminate\Support\Carbon;

class TimeKeeperController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('timekeepers as t')
            ->join('users as u', 't.userId', 'u.id')
            ->join('jobTitle as jt', 't.jobtitleId', 'jt.id')
            ->select(
                't.id',
                't.jobtitleId',
                'jt.jobName',
                't.shiftId',
                DB::raw("
                CASE
                    WHEN t.shiftId = 1 THEN 'Shift 1'
                    WHEN t.shiftId = 2 THEN 'Shift 2'
                    WHEN t.shiftId = 0 THEN 'Tidak ada Shift'
                    ELSE 'Unknown'
                END as shift
            "),
                't.time',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {
                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return responseIndex(0, $data);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('t.updated_at', 'desc');

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

        $data = DB::table('timekeepers as t')
            ->join('users as u', 't.userId', 'u.id')
            ->join('jobTitle as jt', 't.jobtitleId', 'jt.id')
            ->select(
                'jt.jobName',
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('jt.jobName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'jt.jobName';
        }

        $data = DB::table('timekeepers as t')
            ->join('users as u', 't.userId', 'u.id')
            ->join('jobTitle as jt', 't.jobtitleId', 'jt.id')
            ->select(
                't.time',
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('t.time', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 't.time';
        }

        return $temp_column;
    }

    public function insert(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'jobTitleId' => 'required|integer',
            'shiftId' => 'required|integer',
            'time' => 'required|date_format:H:i',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $title = Timekeeper::where('jobTitleId', '=', $request->jobTitleId)
            ->where('shiftId', '=', $request->shiftId)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($title) {
            return responseInvalid(['Job Title has already exists!']);
        }

        DB::beginTransaction();
        try {
            Timekeeper::create([
                'jobTitleId' => $request->jobTitleId,
                'shiftId' => $request->shiftId,
                'time' => $request->time,
                'userId' => $request->user()->id,
            ]);

            DB::commit();

            return responseCreate();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Insert Failed',
                'errors' => $e,
            ]);
        }
    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'jobTitleId' => 'required|integer',
            'shiftId' => 'required|integer',
            'time' => 'required|date_format:H:i',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $jobCheck = Timekeeper::where('jobTitleId', '=', $request->jobTitleId)
            ->where('id', '!=', $request->id)
            ->where('shiftId', '=', $request->shiftId)
            ->where('isDeleted', '=', 0)
            ->first();

        if ($jobCheck) {
            return responseInvalid(['Job Title has already exist!']);
        }

        $time = Timekeeper::where('id', '=', $request->id)
            ->where('isDeleted', '=', 0)
            ->first();

        if (!$time) {
            return responseInvalid(['There is no any Data found!']);
        }

        $time->jobTitleId = $request->jobTitleId;
        $time->shiftId = $request->shiftId;
        $time->time = $request->time;
        $time->userUpdateId = $request->user()->id;
        $time->updated_at = \Carbon\Carbon::now();
        $time->save();

        return responseUpdate();
    }

    public function delete(Request $request)
    {
        foreach ($request->id as $va) {
            $res = Timekeeper::find($va);

            if (!$res) {

                return responseInvalid(['There is any Data not found!']);
            }
        }

        foreach ($request->id as $va) {

            $menu = Timekeeper::find($va);

            $menu->DeletedBy = $request->user()->id;
            $menu->isDeleted = true;
            $menu->DeletedAt = Carbon::now();
            $menu->save();
        }

        return responseDelete();
    }

    public function listShift()
    {
        $data = [
            ['id' => 1, 'shift' => 'Shift 1'],
            ['id' => 2, 'shift' => 'Shift 2'],
            ['id' => 0, 'shift' => 'Tidak ada Shift'],
        ];

        return response()->json($data, 200);
    }
}
