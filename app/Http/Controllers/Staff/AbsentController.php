<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffAbsents;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Carbon;
use DB;

class AbsentController extends Controller
{
    public function createAbsent(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'presentTime' => 'required|date_format:Y-m-d H:i:s',
            'longitude' => 'required|string',
            'latitude' => 'required|string',
            'status' => 'required|integer|in:1,2,3,4',
            'reason' => 'nullable|string',
            'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:5000',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid([$errors]);
        }

        $present = DB::table('StaffAbsents')
            ->where('userId', '=', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->get();

        if (count($present) > 0) {
            return responseInvalid(['You have already absent today!']);
        }

        if ($request->status == 4) {

            if (count($present) <= 0) {
                return responseInvalid(['You can not miss going home today! Cause you have not been absent today!']);
            }
        }

        if (($request->status == 2 || $request->status == 3) && $request->reason == '') {
            return responseInvalid(['Reason should be filled!']);
        }

        $oldname = '';
        $realName = '';
        $path = '';

        if ($request->hasfile('image')) {

            $files[] = $request->file('image');

            foreach ($files as $file) {

                $realName = $file->hashName();

                $file_size = $file->getSize();

                $file_size = $file_size / 1024;

                $oldname = $file->getClientOriginalName();

                $file->move(public_path() . '/AbsentImages/', $realName);

                $path = "/AbsentImages/" . $realName;
            }
        }

        StaffAbsents::create([
            'presentTime' => $request->presentTime,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'status' => $request->status,
            'reason' => $request->reason,
            'realImageName' => $oldname,
            'imagePath' =>  $path,
            'address' => '',
            'city' => '',
            'province' => '',
            'userId' => $request->user()->id,
        ]);

        return responseCreate();
    }
}
