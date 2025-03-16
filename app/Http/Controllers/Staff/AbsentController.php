<?php

namespace App\Http\Controllers\Staff;

use App\Exports\Absent\AbsentReport;
use App\Http\Controllers\Controller;
use App\Models\StaffAbsents;
use App\Models\Timekeeper;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Carbon;
use DB;
use Symfony\Component\HttpFoundation\Response;
use Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->join('jobTitle as jt', 'u.jobTitleId', 'jt.id')
            ->select(
                'sa.id',
                'u.firstName as name',
                // DB::raw("TRIM(CONCAT(CASE WHEN u.firstName = '' or u.firstName is null THEN '' ELSE CONCAT(u.firstName,' ') END
                // ,CASE WHEN u.middleName = '' or u.middleName is null THEN '' ELSE CONCAT(u.middleName,' ') END,
                // case when u.lastName = '' or u.lastName is null then '' else u.lastName end)) as name"),
                'j.jobName',
                'sa.shift',
                'sa.status',
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
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H:%i') END AS homeTime"),
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

        if ($request->user()->roleId <> 1 && $request->user()->roleId <> 6) {
            $data = $data->where('sa.userId', '=', $request->user()->id);
        }

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

        if ($request->staffJob) {
            $data = $data->whereIn('jt.id', $request->staffJob);
        }

        if ($request->orderValue) {

            if ($request->orderColumn == "name") {
                $data = $data->orderBy('u.firstName', $request->orderValue);
            } elseif ($request->orderColumn == "day") {
                $data = $data->orderBy('sa.presentTime', $request->orderValue);
            } elseif ($request->orderColumn == "presentTime") {
                $data = $data->orderBy('sa.presentTime', $request->orderValue);
            } elseif ($request->orderColumn == "homeTime") {
                $data = $data->orderBy('sa.homeTime', $request->orderValue);
            } elseif ($request->orderColumn == "presentStatus") {
                $data = $data->orderBy('ps.statusName', $request->orderValue);
            } elseif ($request->orderColumn == "homeStatus") {
                $data = $data->orderBy('ps1.statusName', $request->orderValue);
            } elseif ($request->orderColumn == "presentLocation") {
                $data = $data->orderBy('sa.cityPresent', $request->orderValue);
            } elseif ($request->orderColumn == "homeLocation") {
                $data = $data->orderBy('sa.cityHome', $request->orderValue);
            } else {
                $data = $data->orderBy($request->orderColumn, $request->orderValue);
            }
        }

        $data = $data->groupBy(
            'sa.id',
            'u.firstName',
            // 'u.middleName',
            // 'u.lastName',
            'j.jobName',
            'sa.shift',
            'sa.status',
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

        $dataTemp = $data->get();

        $count_data = $dataTemp->count();

        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
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
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                'u.id as userId',
                'j.jobName',
                'u.firstName as name',
                // DB::raw("TRIM(CONCAT(CASE WHEN u.firstName = '' or u.firstName is null THEN '' ELSE CONCAT(u.firstName,' ') END
                // ,CASE WHEN u.middleName = '' or u.middleName is null THEN '' ELSE CONCAT(u.middleName,' ') END,
                // case when u.lastName = '' or u.lastName is null then '' else u.lastName end)) as name"),
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
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS attendanceTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H:%i') END AS homecomingTime"),
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
        $jobName = "";

        if ($request->locationId) {
            $dataLocation = DB::table('location as l')
                ->select(DB::raw("GROUP_CONCAT(l.locationName SEPARATOR ', ') as location"))
                ->whereIn('l.id', $request->locationId)
                ->distinct()
                ->pluck('location')
                ->first();

            $location = " " . $dataLocation;
        }

        if ($request->staffJob) {
            $dataJob = DB::table('jobTitle')
                ->select(DB::raw("GROUP_CONCAT(jobName SEPARATOR ', ') as jobName"))
                ->whereIn('id', $request->staffJob)
                ->distinct()
                ->pluck('jobName')
                ->first();

            $jobName = " " . $dataJob;
        }

        if ($request->dateFrom && $request->dateTo) {
            $fromDate = Carbon::parse($request->dateFrom);
            $toDate = Carbon::parse($request->dateTo);

            $date = " " . $fromDate->format('dmy') . "-" . $toDate->format('dmy');
        }

        $fileName = "Rekap Absensi" . $jobName . $location . $date . ".xlsx";
        //-----------------------------
        $data = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->leftJoin('presentStatuses as ps1', 'sa.statusHome', 'ps1.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('jobTitle as j', 'u.jobTitleId', 'j.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                'u.firstName as name',
                'l.locationName',
                'j.jobName',
                'sa.shift',
                'sa.status',
                DB::raw("DATE_FORMAT(sa.presentTime, '%Y-%m-%d') AS day"),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS presentTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H:%i') END AS homeTime"),
                DB::raw("CASE WHEN sa.duration is null THEN '' ELSE CONCAT(
                    HOUR(sa.duration), ' jam ',
                    MINUTE(sa.duration), ' menit'
                ) END AS duration"),
                'ps.statusName as presentStatus',
                DB::raw("CASE WHEN ps1.statusName is null THEN '' ELSE ps1.statusName END as homeStatus"),
                'sa.cityPresent as presentLocation',
                DB::raw("CASE WHEN sa.cityHome is null THEN '' ELSE sa.cityHome END as homeLocation"),
            )
            ->where('sa.isDeleted', '=', 0)
            ->where('ul.isMainLocation', '=', 1);

        if (role($request->user()->id) != 'Office' && role($request->user()->id) != 'Administrator' && role($request->user()->id) != 'Manager') {
            $data = $data->where('sa.userId', '=', $request->user()->id);
        }

        if ($request->dateFrom && $request->dateTo) {

            $data = $data->whereBetween('sa.presentTime', [$request->dateFrom, $request->dateTo]);
        }

        $locations = $request->locationId;

        if (count($locations) > 0) {
            if (!$locations[0] == null) {
                $data = $data->whereIn('l.id', $request->locationId);
            }
        }

        $staffs = $request->staff;

        if (count($staffs) > 0) {
            if (!$staffs[0] == null) {
                $data = $data->whereIn('sa.userId', $request->staff);
            }
        }

        if ($request->statusPresent) {
            $statusPresents = $request->statusPresent;
            if (count($statusPresents) > 0) {
                if (!$statusPresents[0] == null) {
                    $data = $data->whereIn('sa.statusPresent', $request->statusPresent);
                }
            }
        }

        if ($request->staffJob) {
            $staffJobs = $request->staffJob;

            if (count($staffJobs) > 0) {
                if (!$staffJobs[0] == null) {
                    $data = $data->whereIn('j.id', $request->staffJob);
                }
            }
        }

        $data = $data->groupBy(
            'sa.id',
            'u.firstName',
            'l.locationName',
            'j.jobName',
            'sa.shift',
            'sa.status',
            'sa.presentTime',
            'sa.homeTime',
            'sa.duration',
            'ps.statusName',
            'ps1.statusName',
            'sa.cityPresent',
            'sa.cityHome'
        );

        $data = $data->orderBy('sa.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/absen/' . 'Template_Export_Absen.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->name);
            $sheet->setCellValue("C{$row}", $item->locationName);
            $sheet->setCellValue("D{$row}", $item->jobName);
            $sheet->setCellValue("E{$row}", $item->shift);

            if ($item->status == 'Terlambat') {
                $sheet->getStyle("F{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FF0000', // Red background color
                        ],
                    ],
                ]);
            } else if ($item->status == 'Tepat Waktu') {

                $sheet->getStyle("F{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '00FF00', // Green background color
                        ],
                    ],
                ]);
            }

            $sheet->setCellValue("F{$row}", $item->status);
            $sheet->setCellValue("G{$row}", $item->day);
            $sheet->setCellValue("H{$row}", $item->presentTime);
            $sheet->setCellValue("I{$row}", $item->homeTime);
            $sheet->setCellValue("J{$row}", $item->duration);
            $sheet->setCellValue("K{$row}", $item->presentStatus);
            $sheet->setCellValue("L{$row}", $item->homeStatus);
            $sheet->setCellValue("M{$row}", $item->presentLocation);
            $sheet->setCellValue("N{$row}", $item->homeLocation);

            $row++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . $fileName; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
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
                    'firstName as fullName',
                    //     DB::raw("TRIM(CONCAT(CASE WHEN firstName = '' or firstName is null THEN '' ELSE CONCAT(firstName,' ') END
                    // ,CASE WHEN middleName = '' or middleName is null THEN '' ELSE CONCAT(middleName,' ') END,
                    // case when lastName = '' or lastName is null then '' else lastName end)) as fullName")
                )
                ->where('isDeleted', '=', 0)
                ->get();

            return response()->json($data, 200);
        } else {
            return responseUnauthorize();
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

        $users = DB::table('users')
            ->leftjoin('usersRoles', 'usersRoles.id', '=', 'users.roleId')
            ->leftjoin('jobTitle', 'jobTitle.id', '=', 'users.jobTitleId')
            ->select(
                'users.id',
                'users.imagePath',
                'users.roleId',
                DB::raw("IF(usersRoles.roleName IS NULL, '', usersRoles.roleName) as roleName"),
                'jobTitle.id as jobTitleId',
                DB::raw("IF(jobTitle.jobName IS NULL,'', jobTitle.jobName) as jobName"),
                DB::raw("CONCAT(IFNULL(users.firstName,'') ,' ', IFNULL(users.lastName,'')) as name"),
            )
            ->where([
                ['users.id', '=', $request->user()->id]
            ])
            ->first();

        $keeper = Timekeeper::where('jobtitleId', '=', $users->jobTitleId)->get();

        if (count($keeper) > 1) {
            $validate = Validator::make($request->all(), [
                'shift' => 'required|integer|in:1,2',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid([$errors]);
            }
        }

        $currentDate = Carbon::now();
        $presentTime = $currentDate->format('d/m/Y H:i');
        $presentTime2 = $currentDate->format('Y-m-d H:i');

        $present = DB::table('staffAbsents')
            ->where('userId', '=', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($present) {
            if ($present->statusPresent == 1 && $request->status == 1) {
                return responseInvalid(['You have already absent today!']);
            }
        }

        if ($request->status == 4) {

            if (!$present) {
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

        $status = "";
        $shift = "";

        $keeperRes = Timekeeper::where('jobtitleId', '=', $users->jobTitleId);

        if (count($keeper) > 1) {
            $keeperRes = $keeperRes->where('shiftId', '=', $request->shift);
            $shift = 'Shift ' . $request->shift;
        }

        $keeperRes = $keeperRes->first();

        $time2 = $keeperRes->time;
        $time1 = Carbon::now();

        if ($time1->greaterThan($time2)) {
            $status = "Terlambat";
        } elseif ($time1->lessThan($time2)) {
            $status = "Tepat Waktu";
        } else {
            $status = "Tepat Waktu";
        }

        // if ($users->jobName == 'Dokter Hewan') {
        //     $shift = 'Shift ' . $request->shift;
        //     if ($request->shift == 1) {
        //         $time2 = Carbon::createFromFormat('H:i', '15:45', 'Asia/Jakarta')->setTimezone('UTC');
        //         //$time2 = Carbon::createFromFormat('H:i', '08:45');
        //     } elseif ($request->shift == 2) {
        //         $time2 = Carbon::createFromFormat('H:i', '21:00', 'Asia/Jakarta')->setTimezone('UTC');
        //         //$time2 = Carbon::createFromFormat('H:i', '14:00');
        //     }
        // } else if ($users->jobName == 'Paramedis') {
        //     $time2 = Carbon::createFromFormat('H:i', '15:45', 'Asia/Jakarta')->setTimezone('UTC');
        //     // $time2 = Carbon::parse('08:45');
        // } else if ($users->jobName == 'Kasir') {
        //     $time2 = Carbon::createFromFormat('H:i', '15:30', 'Asia/Jakarta')->setTimezone('UTC');
        //     // $time2 = Carbon::parse('08:30');
        // } else if ($users->jobName == 'Vetnurse') {
        //     $time2 = Carbon::createFromFormat('H:i', '15:30', 'Asia/Jakarta')->setTimezone('UTC');
        //     // $time2 = Carbon::parse('08:30');
        // } else {
        //     //if ($request->user()->jobName == 6) {
        //     $time2 = Carbon::createFromFormat('H:i', '19:30', 'Asia/Jakarta')->setTimezone('UTC');
        //     // $time2 = Carbon::parse('12:30');
        // }


        // $time1 = Carbon::now()->addHour(7); // Jam dan menit pertama

        // if ($time1->greaterThan($time2)) {
        //     $status = "Terlambat";
        // } elseif ($time1->lessThan($time2)) {
        //     $status = "Tepat Waktu";
        // } else {
        //     $status = "Tepat Waktu";
        // }

        if (!$present) {

            StaffAbsents::create([
                'presentTime' => $presentTime2,
                'presentLongitude' => $request->longitude,
                'presentLatitude' => $request->latitude,
                'statusPresent' => $request->status,
                'reasonPresent' => $reason,
                'realImageNamePresent' => $oldname,
                'imagePathPresent' =>  $path,
                'cityPresent' => $city,
                'provincePresent' => $province,
                'shift' => $shift,
                'status' => $status,
                'userId' => $request->user()->id,
            ]);
        } else {

            $currentDate = Carbon::now();

            $presentTime = $present->presentTime;
            $homeTime = $currentDate->format('Y-m-d H:i:s');

            // Convert strings to Carbon instances
            $carbonDatePresent = Carbon::parse($presentTime);
            $carbonDateHome = Carbon::parse($homeTime);

            $totalDuration =  $carbonDateHome->diffInSeconds($carbonDatePresent);

            $duration = gmdate('H:i:s', $totalDuration);

            if ($totalDuration <= 28800) {
                $status = 'Terlambat';
            } else {
                $status = 'Tepat Waktu';
            }

            StaffAbsents::where('id', '=', $present->id)
                ->update(
                    [
                        'status' => $status,
                        'homeTime' => $homeTime,
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
