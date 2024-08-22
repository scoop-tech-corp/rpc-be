<?php

namespace App\Http\Controllers\Staff;

use App\Exports\Absent\AbsentReport;
use App\Http\Controllers\Controller;
use App\Models\StaffAbsents;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Carbon;
use DB;
use Symfony\Component\HttpFoundation\Response;
use Excel;

class AbsentController extends Controller
{
    public function Index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->leftJoin('presentStatuses as ps1', 'sa.statusHome', 'ps1.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                DB::raw("TRIM(CONCAT(CASE WHEN u.firstName = '' or u.firstName is null THEN '' ELSE CONCAT(u.firstName,' ') END
                ,CASE WHEN u.middleName = '' or u.middleName is null THEN '' ELSE CONCAT(u.middleName,' ') END,
                case when u.lastName = '' or u.lastName is null then '' else u.lastName end)) as name"),
                DB::raw("
                CONCAT(
                    CASE DAYOFWEEK(sa.presentTime)
                        WHEN 1 THEN 'Minggu'
                        WHEN 2 THEN 'Senin'
                        WHEN 3 THEN 'Selasa'
                        WHEN 4 THEN 'Rabu'
                        WHEN 5 THEN 'Kamis'
                        WHEN 6 THEN 'Jumat'
                        WHEN 7 THEN 'Sabtu'
                    END,
                    ', ',
                    DATE_FORMAT(sa.presentTime, '%e %b %Y')
                ) AS day
                "),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS presentTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H.%i') END AS homeTime"),
                DB::raw("CASE WHEN sa.duration is null THEN '' ELSE CONCAT(
                    HOUR(sa.duration), ' jam ',
                    MINUTE(sa.duration), ' menit'
                ) END AS duration"),
                'ps.statusName as presentStatus',
                DB::raw("CASE WHEN ps1.statusName is null THEN '' ELSE ps1.statusName END as homeStatus"),
                'sa.cityPresent as presentLocation',
                DB::raw("CASE WHEN sa.cityHome is null THEN '' ELSE sa.cityHome END as homeLocation"),
            )
            ->where('sa.isDeleted', '=', 0);

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween('sa.presentTime', [$request->dateFrom, $request->dateTo]);
        }

        if ($request->locationId) {

            $data = $data->whereIn('l.id', $request->locationId);
        }

        if ($request->staff) {
            $data = $data->whereIn('sa.userId', $request->staff);
        }

        if ($request->statusPresent) {
            $data = $data->whereIn('sa.statusPresent', $request->statusPresent);
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->groupBy(
            'sa.id',
            'u.firstName',
            'u.middleName',
            'u.lastName',
            'sa.presentTime',
            'sa.homeTime',
            'sa.duration',
            'ps.statusName',
            'ps1.statusName',
            'sa.cityPresent',
            'sa.cityHome'
        );

        $data = $data->orderBy('sa.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->tosql();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    public function Detail(Request $request)
    {
        $data = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->leftJoin('presentStatuses as ps1', 'sa.statusHome', 'ps1.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                'u.id as userId',
                DB::raw("TRIM(CONCAT(CASE WHEN u.firstName = '' or u.firstName is null THEN '' ELSE CONCAT(u.firstName,' ') END
                ,CASE WHEN u.middleName = '' or u.middleName is null THEN '' ELSE CONCAT(u.middleName,' ') END,
                case when u.lastName = '' or u.lastName is null then '' else u.lastName end)) as name"),
                DB::raw("
            CONCAT(
                CASE DAYOFWEEK(sa.presentTime)
                    WHEN 1 THEN 'Minggu'
                    WHEN 2 THEN 'Senin'
                    WHEN 3 THEN 'Selasa'
                    WHEN 4 THEN 'Rabu'
                    WHEN 5 THEN 'Kamis'
                    WHEN 6 THEN 'Jumat'
                    WHEN 7 THEN 'Sabtu'
                END,
                ', ',
                DATE_FORMAT(sa.presentTime, '%e %M %Y')
            ) AS day
            "),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H.%i') AS attendanceTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H.%i') END AS homecomingTime"),
                DB::raw("CASE WHEN sa.duration is null THEN '' ELSE CONCAT(
                HOUR(sa.duration), ' jam ',
                MINUTE(sa.duration), ' menit'
            ) END AS duration"),

                DB::raw("CASE WHEN ps.statusName is null THEN '' ELSE ps.statusName END as attendanceStatus"),
                DB::raw("CASE WHEN ps1.statusName is null THEN '' ELSE ps1.statusName END as homecomingStatus"),

                DB::raw("CASE WHEN sa.reasonPresent is null THEN '' ELSE sa.reasonPresent END as attendanceReason"),
                DB::raw("CASE WHEN sa.reasonHome is null THEN '' ELSE sa.reasonHome END as homecomingReason"),

                DB::raw("CASE WHEN sa.cityPresent is null THEN '' ELSE sa.cityPresent END as attendanceLocation"),
                DB::raw("CASE WHEN sa.cityHome is null THEN '' ELSE sa.cityHome END as homecomingLocation"),

                DB::raw("CASE WHEN sa.imagePathPresent is null THEN '' ELSE sa.imagePathPresent END as attendanceImagePath"),
                DB::raw("CASE WHEN sa.imagePathHome is null THEN '' ELSE sa.imagePathHome END as homecomingImagePath"),
            )
            ->where('sa.id', '=', $request->id)
            ->first();

        $dataLoc = DB::table('usersLocation as ul')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(DB::raw("GROUP_CONCAT(l.locationName SEPARATOR ', ') as location"))
            ->where('ul.usersId', '=', $data->userId)
            ->groupBy('usersId')
            ->pluck('location')
            ->first();

        $data->location = $dataLoc;

        return response()->json($data, 200);
    }

    public function Export(Request $request)
    {
        $fileName = "";
        $date = "";
        $location = "";

        if ($request->locationId) {
            $dataLocation = DB::table('location as l')
                ->select(DB::raw("GROUP_CONCAT(l.locationName SEPARATOR ', ') as location"))
                ->whereIn('l.id', $request->locationId)
                ->distinct()
                ->pluck('location')
                ->first();

            $location = " " . $dataLocation;
        }


        if ($request->dateFrom && $request->dateTo) {
            $fromDate = Carbon::parse($request->dateFrom);
            $toDate = Carbon::parse($request->dateTo);

            $date = " " . $fromDate->format('dmy') . "-" . $toDate->format('dmy');
        }

        $fileName = "Rekap Absensi" . $location . $date . ".xlsx";

        return Excel::download(
            new AbsentReport(
                $request->orderValue,
                $request->orderColumn,
                $request->dateFrom,
                $request->dateTo,
                $request->locationId,
                $request->staff,
                $request->statusPresent
            ),
            $fileName
        );
    }

    public function presentStatusList()
    {
        $data = DB::table('presentStatuses')
            ->select('id', 'statusName')
            ->get();

        return response()->json($data, 200);
    }

    public function staffListAbsent(Request $request)
    {
        if (adminAccess($request->user()->id)) {

            $data = DB::table('users')
                ->select(
                    'id',
                    DB::raw("TRIM(CONCAT(CASE WHEN firstName = '' or firstName is null THEN '' ELSE CONCAT(firstName,' ') END
                ,CASE WHEN middleName = '' or middleName is null THEN '' ELSE CONCAT(middleName,' ') END,
                case when lastName = '' or lastName is null then '' else lastName end)) as fullName")
                )
                ->where('isDeleted', '=', 0)
                ->get();

            return response()->json($data, 200);
        } else {
            return response()->json(['error' => 'Unauthorized access!'], Response::HTTP_FORBIDDEN);
        }
    }

    public function createAbsent(Request $request)
    {

        if ($request->status == 1 || $request->status == 4) {
            $validate = Validator::make($request->all(), [
                'presentTime' => 'required|date_format:d/m/Y H:i',
                'longitude' => 'nullable|string',
                'latitude' => 'nullable|string',
                'status' => 'required|integer|in:1,2,3,4',
                'reason' => 'nullable|string',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'province' => 'nullable|string',
                'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:5000',
            ]);
        } else {
            $validate = Validator::make($request->all(), [
                'presentTime' => 'required|date_format:d/m/Y H:i',
                'longitude' => 'nullable|string',
                'latitude' => 'nullable|string',
                'status' => 'required|integer|in:1,2,3,4',
                'reason' => 'nullable|string',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'province' => 'nullable|string',
                'image' => 'nullable',
            ]);
        }

        $presentTime = date('Y-m-d H:i', strtotime($request->presentTime));

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return responseInvalid([$errors]);
        }

        $present = DB::table('staffAbsents')
            ->where('userId', '=', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($present && $request->status != 3) {
            return responseInvalid(['You have already absent today!']);
        }

        if ($request->status == 4) {

            if (count($present) <= 0) {
                return responseInvalid(['You can not absent home today! Cause you have not been absent present today!']);
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

        $city = '';
        $province = '';
        $reason = '';

        if ($request->city != '') {
            $city = $request->city;
        }

        if ($request->province != '') {
            $province = $request->province;
        }

        if ($request->reason != '') {
            $reason = $request->reason;
        }

        if (!$present) {
            StaffAbsents::create([
                'presentTime' => $presentTime,
                'presentLongitude' => $request->longitude,
                'presentLatitude' => $request->latitude,
                'statusPresent' => $request->status,
                'reasonPresent' => $reason,
                'realImageNamePresent' => $oldname,
                'imagePathPresent' =>  $path,
                'cityPresent' => $city,
                'provincePresent' => $province,
                'userId' => $request->user()->id,
            ]);
        } else {

            $presentTime = Carbon::parse($present->presentTime);
            $homeTime = Carbon::parse($request->presentTime);
            $totalDuration =  $homeTime->diffInSeconds($presentTime);

            $duration = gmdate('H:i:s', $totalDuration);

            StaffAbsents::where('id', '=', $present->id)
                ->update(
                    [
                        'homeTime' => $presentTime,
                        'duration' => $duration,
                        'homeLongitude' => $request->longitude,
                        'homeLatitude' => $request->latitude,
                        'statusHome' => $request->status,
                        'reasonHome' => $reason,
                        'realImageNameHome' => $oldname,
                        'imagePathHome' =>  $path,
                        'cityHome' => $city,
                        'provinceHome' => $province,
                    ]
                );
        }

        return responseCreate();
    }
}
