<?php

namespace App\Http\Controllers;

use App\Models\accessReportMenu;
use Illuminate\Http\Request;
use Validator;
use DB;
use Illuminate\Support\Carbon;

class ReportMenuManagementController extends Controller
{
    public function Index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('menuGroups as mg')
            ->join('users as u', 'mg.userId', 'u.id')
            ->select(
                'mg.id',
                'mg.groupName',
                'mg.orderMenu',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(mg.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('mg.isDeleted', '=', 0);

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

        $data = $data->orderBy('mg.updated_at', 'desc');

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

        $data = DB::table('menuGroups as mg')
            ->join('users as u', 'mg.userId', 'u.id')
            ->select(
                'mg.groupName',
            )
            ->where('mg.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('mg.groupName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'mg.groupName';
        }

        $data = DB::table('menuGroups as mg')
            ->join('users as u', 'mg.userId', 'u.id')
            ->select(
                'u.firstName',
            )
            ->where('mg.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }

    public function Insert(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'groupName' => 'required|string',
            'menuName' => 'required|string',
            'url' => 'required|string',
            'roleId' => 'required|integer',
            'accessTypeId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $menu = accessReportMenu::where('url', 'like', '%' . $request->url . '%')
            ->where('isDeleted', '=', 0)
            ->first();

        if ($menu) {
            return responseInvalid(['Menu Report has already exists!']);
        }

        DB::beginTransaction();
        try {
            accessReportMenu::create([
                'groupName' => $request->groupName,
                'menuName' => $request->menuName,
                'url' => $request->url,
                'roleId' => $request->roleId,
                'accessTypeId' => $request->accessTypeId,
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

    public function Update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
            'groupName' => 'required|string',
            'menuName' => 'required|string',
            'url' => 'required|string',
            'roleId' => 'required|integer',
            'accessTypeId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid($errors);
        }

        $menu = accessReportMenu::where('id', '=', $request->id)
            ->where('isDeleted', '=', 0)
            ->first();

        if (!$menu) {
            return responseInvalid(['There is no any Data found!']);
        }

        $menu->groupName = $request->groupName;
        $menu->menuName = $request->menuName;
        $menu->url = $request->url;
        $menu->roleId = $request->roleId;
        $menu->accessTypeId = $request->accessTypeId;
        $menu->userUpdateId = $request->user()->id;
        $menu->updated_at = \Carbon\Carbon::now();
        $menu->save();

        return responseUpdate();
    }

    public function Delete(Request $request)
    {
        foreach ($request->id as $va) {
            $res = accessReportMenu::find($va);

            if (!$res) {

                return responseInvalid(['There is any Data not found!']);
            }
        }

        foreach ($request->id as $va) {

            $menu = accessReportMenu::find($va);

            $menu->DeletedBy = $request->user()->id;
            $menu->isDeleted = true;
            $menu->DeletedAt = Carbon::now();
            $menu->save();
        }

        return responseDelete();
    }
}
