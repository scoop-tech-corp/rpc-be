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
            'presentTime' => 'required|date_format:d/m/Y H:i',
            'longitude' => 'nullable|string',
            'latitude' => 'nullable|string',
            'status' => 'required|integer|in:1,2,3,4',
            'reason' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'image' => 'image|mimes:jpg,png,jpeg,gif,svg|max:5000',
        ]);

        $presentTime = date('Y-m-d H:i', strtotime($request->presentTime));

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

        $address = '';
        $city = '';
        $province = '';
        $reason = '';

        if ($request->address != '') {
            $address = $request->address;
        }

        if ($request->city != '') {
            $city = $request->city;
        }

        if ($request->province != '') {
            $province = $request->province;
        }

        if ($request->reason != '') {
            $reason = $request->reason;
        }

        StaffAbsents::create([
            'presentTime' => $presentTime,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'status' => $request->status,
            'reason' => $reason,
            'realImageName' => $oldname,
            'imagePath' =>  $path,
            'address' => $address,
            'city' => $city,
            'province' => $province,
            'userId' => $request->user()->id,
        ]);

        return responseCreate();
    }
}
