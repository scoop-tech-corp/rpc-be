<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffAbsents;
use Illuminate\Http\Request;
use Validator;

class AbsentController extends Controller
{
    public function createAbsent(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'presentTime' => 'required|date_format:Y-m-d H:i:s',
            'longitude' => 'required|string',
            'latitude' => 'required|string',
            'status' => 'required|integer|in:1,2,3',
            'reason' => 'nullable|string',
            'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:5000',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid([$errors]);
        }

        $data = StaffAbsents::where('userId', '=', $request->user()->id)->first();

        $oldname = '';
        $realName = '';

        if ($request->hasfile('image')) {

            $files[] = $request->file('image');

            foreach ($files as $file) {

                $realName = $file->hashName();

                $file_size = $file->getSize();

                $file_size = $file_size / 1024;

                $oldname = $file->getClientOriginalName();

                $file->move(public_path() . '/AbsentImages/', $realName);
            }
        }

        StaffAbsents::create([
            'presentTime' => $request->presentTime,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'status' => $request->status,
            'reason' => $request->reason,
            'realImageName' => $oldname,
            'imagePath' =>  "/AbsentImages/" . $realName,
            'address' => '',
            'city' => '',
            'province' => '',
            'userId' => $request->user()->id,
        ]);

        return responseCreate();
    }
}
