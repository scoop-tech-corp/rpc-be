<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LocationController extends Controller
{

    public function deletetelepon(Request $request)
    {

        DB::beginTransaction();
        try
        {

            DB::table('location_telepon')
                ->where('codeLocation', '=', $request->input('codeLocation'),
                    'pemakaian', '=', $request->input('pemakaian'),
                    'nomorTelepon', '=', $request->input('nomorTelepon'),
                    'tipe', '=', $request->input('tipe'),
                )
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::commit();

            return ('SUCCESS');
            //return back()->with('SUCCESS', 'Data has been successfully inserted');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');
            //return back()->with('ERROR', 'Your error message');
        }

    }

    public function deleteemail(Request $request)
    {

        DB::beginTransaction();
        try
        {

            DB::table('location_email')
                ->where('codeLocation', '=', $request->input('codeLocation'),
                    'pemakaian', '=', $request->input('pemakaian'),
                    'namaPengguna', '=', $request->input('namaPengguna'),
                    'tipe', '=', $request->input('tipe'),
                )
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::commit();

            return ('SUCCESS');
            //return back()->with('SUCCESS', 'Data has been successfully inserted');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');
            //return back()->with('ERROR', 'Your error message');
        }

    }

    public function deletemessenger(Request $request)
    {

        DB::beginTransaction();
        try
        {

            DB::table('location_messenger')
                ->where('codeLocation', '=', $request->input('codeLocation'),
                    'pemakaian', '=', $request->input('pemakaian'),
                    'namaMessenger', '=', $request->input('namaMessenger'),
                    'tipe', '=', $request->input('tipe'),
                )
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::commit();

            return ('SUCCESS');
            //return back()->with('SUCCESS', 'Data has been successfully inserted');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');
            //return back()->with('ERROR', 'Your error message');
        }

    }

    public function delete(Request $request)
    {

        DB::beginTransaction();
        try
        {

            DB::table('location')
                ->where('codeLocation', '=', $request->input('codeLocation'))
                ->update([
                    'isDeleted' => 1,
                ]);

            deletemessenger($request);
            deleteemail($request);
            deletetelepon($request);

            DB::commit();

            return ('SUCCESS');
            //return back()->with('SUCCESS', 'Data has been successfully inserted');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');
            //return back()->with('ERROR', 'Your error message');
        }

    }

    public function uploadexceltest(Request $request)
    {

        $request->validate([
            'file' => 'required|max:10000',
        ]);

        // echo($request->file);

        Excel::import(new UsersImport, $request->file);
        return 'true';
    }

    public function update(Request $request)
    {

        DB::beginTransaction();
        try
        {

            DB::table('location')
                ->where('codeLocation', '=', $request->input('codeLocation'))
                ->update([
                    'locationName' => $request->input('locationName'),
                    'isBranch' => $request->input('isBranch'),
                    'status' => $request->input('status'),
                    'introduction' => $request->input('introduction'),
                    'description' => $request->input('description'),
                    'image' => $request->input('image'),
                    'imageTitle' => $request->input('imageTitle'),

                ]);

            foreach ($request->operational_days as $val) {
                DB::table('location_operational_hours_details')
                    ->where('codeLocation', '=', $request->input('codeLocation'))
                    ->update([
                        'days_name' => $val['days_name'],
                        'from_time' => $val['from_time'],
                        'to_time' => $val['to_time'],
                        'all_day' => $val['all_day'],

                    ]);
            }

            //return 'success';

        } catch (Exception $e) {

            DB::rollback();

            return back()->with('ERROR', 'Your error message');
        }

    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try
        {

            $getvaluesp = strval(collect(DB::select('call procedure_name'))[0]->randomString);

            $request->validate([
                'locationName' => 'required|max:255',
                'isBranch' => 'required',
                'status' => 'required',
                'introduction' => 'required',
                'description' => 'required',
                'image' => 'required',
            ]);

            DB::table('location')->insert([
                'codeLocation' => $getvaluesp,
                'locationName' => $request->input('locationName'),
                'isBranch' => $request->input('isBranch'),
                'status' => $request->input('status'),
                'introduction' => $request->input('introduction'),
                'description' => $request->input('description'),
                'image' => $request->input('image'),
                'imageTitle' => $request->input('imageTitle'),
                'isDeleted' => 0,
            ]);

            DB::table('location_alamat_detail')->insert([
                'codeLocation' => $getvaluesp,
                'alamatJalan' => $request->input('alamat_location')[0]['alamatJalan'],
                'infoTambahan' => $request->input('alamat_location')[0]['infoTambahan'],
                'kotaID' => $request->input('alamat_location')[0]['kotaID'],
                'provinsiID' => $request->input('alamat_location')[0]['provinsiID'],
                'kodePos' => $request->input('alamat_location')[0]['kodePos'],
                'negara' => $request->input('alamat_location')[0]['negara'],
                'parkir' => $request->input('alamat_location')[0]['parkir'],
                'pemakaian' => $request->input('alamat_location')[0]['pemakaian'],
            ]);

            foreach ($request->operational_days as $val) {

                DB::table('location_operational')->insert([
                    'codeLocation' => $getvaluesp,
                    'days_name' => $val['days_name'],
                    'from_time' => $val['from_time'],
                    'to_time' => $val['to_time'],
                    'all_day' => $val['all_day'],
                ]);

            }

            foreach ($request->messenger as $val) {

                DB::table('location_messenger')->insert([
                    'codeLocation' => $getvaluesp,
                    'pemakaian' => $val['pemakaian'],
                    'namaMessenger' => $val['namaMessenger'],
                    'tipe' => $val['tipe'],
                    'isDeleted' => 0,
                ]);

            }

            foreach ($request->email as $val) {

                DB::table('location_email')->insert([
                    'codeLocation' => $getvaluesp,
                    'pemakaian' => $val['pemakaian'],
                    'namaPengguna' => $val['namaPengguna'],
                    'tipe' => $val['tipe'],
                    'isDeleted' => 0,
                ]);

            }

            foreach ($request->telepon as $val) {

                DB::table('location_telepon')->insert([
                    'codeLocation' => $getvaluesp,
                    'pemakaian' => $val['pemakaian'],
                    'nomorTelepon' => $val['nomorTelepon'],
                    'tipe' => $val['tipe'],
                    'isDeleted' => 0,
                ]);

            }

            DB::commit();

            return ('SUCCESS');

            //return back()->with('SUCCESS', 'Data has been successfully inserted');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

            // return back()->with('ERROR', 'Your error message');
        }

    }

    public function index()
    {

        //08/25/2022
        //$object = new stdClass();
        //$data2 =new stdClass();
        $data2=[];
        $location = DB::table('location')
            ->get();

        $decoded = json_decode($location, true);

        foreach ($decoded as $val) {

            $param_location = DB::table('location')
                ->select('location.codeLocation as codeLocation',
                    'location.locationName as locationName',
                    'location.isBranch as isBranch',
                    'location.status as status',
                    'location.introduction as introduction',
                    'location.description as description',
                    'location.image as image',
                    'location.imageTitle as imageTitle',

                )
                ->where('location.codeLocation', '=', $val['codeLocation'])
                ->first();

            $alamat_location = DB::table('location_alamat_detail')
                ->select('location_alamat_detail.alamatJalan as alamatJalan',
                    'location_alamat_detail.infoTambahan as infoTambahan',
                    'location_alamat_detail.kotaID as kotaID',
                    'location_alamat_detail.provinsiID as provinsiID',
                    'location_alamat_detail.kodePos as kodePos',
                    'location_alamat_detail.negara as negara',

                )
                ->where('location_alamat_detail.codeLocation', '=', $val['codeLocation'])
                ->get();

            $param_location->alamat_location = $alamat_location;


            $operational_location = DB::table('location_operational')
                ->select('location_operational.days_name as days_name',
                    'location_operational.from_time as from_time',
                    'location_operational.to_time as to_time',
                    'location_operational.all_day as all_day',
                )
                ->where('location_operational.codeLocation', '=', $val['codeLocation'])
                ->get();

            $param_location->operational_location = $operational_location;

            // $operation_location = DB::table('location_operational')
            //     ->select('location_operational.days_name as days_name',
            //         'location_operational.from_time as to_time',
            //     )
            //     ->where('location_operational.codeLocation', '=', $val['codeLocation'])
            //     ->get();

            // $param_location->operation = $operation_location;
            // $data2 = $param_location;
            // $data2 = $data2->push((object)[$param_location]);
            // $data2 += $param_location;
            //array_push($data2, $param_location);
           // return response()->json($param_location, 200);
           //$data2 = json($param_location, 200);

          // $data2 = array_merge(array($data2), array($param_location));


          //json_encode(array_merge(json_decode($data2, true),json_decode($param_location, true)));

        //    json_encode(
        //     array_merge(
        //         json_decode($data2, true),
        //         json_decode($param_location, true)
        //     )
        //     );
          // $data2 = json_decode(json_encode($param_location), true);

          array_push($data2, $param_location);
        }

        return response()->json($data2, 200);
        //08/25/2022

        //08/25/2022
        // $location = DB::table('location')
        //     ->select('location.codeLocation as codeLocation'
        //     )
        //     ->first();

        // $alamat_location = DB::table('location_alamat_detail')
        //     ->select(
        //         'location_alamat_detail.alamatJalan as alamatJalan')
        //     ->where('location_alamat_detail.codeLocation', '=', 'ad7e99ea')
        //     ->get();

        // $location->alamat_location = $alamat_location;
        //08/25/2022

        //   foreach ($decoded as $d) {

        //         foreach($d as $k=>$v)
        //         {

        //            // echo "$k - $v\n";
        //         }

        //    }

        //      foreach ($location as $val) {
        //         //echo response()->json($val, 200);
        //         // $j_string_decoded = json_decode($val, true);

        //      //  echo j_string_decoded['id'];

        //    }

        // foreach ($request as $val) {
        //     DB::table('location_operational_hours_details')
        //         ->where('codeLocation', '=', $request->input('codeLocation'))
        //         ->update([
        //             'days_name' => $val['days_name'],
        //             'from_time' => $val['from_time'],
        //             'to_time' => $val['to_time'],
        //             'all_day' => $val['all_day'],

        //         ]);
        // }

        //08/25/2022
        // $location = DB::table('location')
        //     ->select('location.codeLocation as codeLocation'
        //     )
        //     ->first();

        // $alamat_location = DB::table('location_alamat_detail')
        //     ->select(
        //         'location_alamat_detail.alamatJalan as alamatJalan')
        //     ->where('location_alamat_detail.codeLocation', '=', 'ad7e99ea')
        //     ->get();

        // $location->alamat_location = $alamat_location;

        // return response()->json($location, 200);
        //08/25/2022

        // $branch = DB::table('location')
        //         ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
        //         ->select('location.codeLocation as codeLocation',
        //                 'location.isBranch as isBranch',
        //                 'location.status as status',
        //                 'location.introduction as introduction',
        //                 'location.image as image',
        //                 'location.imageTitle as imageTitle',
        //         //DB::raw('CONCAT( ''['',(GROUP_CONCAT(JSON_OBJECT(location_operational_hours_details.days_name , location_operational_hours_details.from_time ,location_operational_hours_details.to_time,location_operational_hours_details.all_day )),'']'') as OperationalTime')
        //             DB:raw(" CONCAT(''['', GROUP_CONCAT(JSON_OBJECT(location_alamat_detail.alamatJalan)),'']'') as list "),

        //                // 'JSON_ARRAYAGG(JSON_OBJECT(location_alamat_detail.alamatJalan , location_alamat_detail.infoTambahan ,location_alamat_detail.kotaID )) as asd',
        //              )
        //                 ->groupBy('location.codeLocation')

        //     ->leftjoin('locations_alamats_details', 'locations_alamats_details.codeLocation', '=', 'locations.id')
        //     ->leftjoin('location_operational_hours_details', 'location_operational_hours_details.codeLocation', '=', 'locations.id')
        //     ->select('locations.locationName as LocationName',
        //         'locations.introduction as introduction',
        //         'locations.description as description',
        //         'locations.image as image',
        //         'locations.imageTitle as imageTitle',
        //         'locations.id as CodeLocation',
        //         DB::raw('count(location_operational_hours_details.codeLocation) as OperationalDays'),
        //         DB::raw(' GROUP_CONCAT(JSON_OBJECT(location_operational_hours_details.days_name , location_operational_hours_details.from_time ,location_operational_hours_details.to_time,location_operational_hours_details.all_day )) as OperationalTime'),
        //         DB::raw('count(locations_alamats_details.codeLocation) as Operational_Alamat'))
        // //  ->select('locations.locationName as CodeLocation','locations.locationName as locationName','locations.isBranch as isBranch','locations.status as status',DB::raw('count(location_operational_hours_details.codeLocation) as jumlahAlamat'))
        //     ->groupBy(
        //         'locations.locationName',
        //         'locations.introduction',
        //         'locations.description',
        //         'locations.image',
        //         'locations.imageTitle',
        //         'locations.id',
        //         'location_operational_hours_details.codeLocation',
        //         'locations_alamats_details.codeLocation', )
        // ->get();

        // ->select('branches.id', 'branch_code', 'branch_name',
        //     'users.fullname as created_by',
        //     DB::raw("DATE_FORMAT(branches.created_at, '%d %b %Y') as created_at"), 'branches.address')
        // ->where('branches.isDeleted', '=', 0);

        // $branch = $branch->orderBy('id', 'desc');

        //return response()->json($branch, 200);

        // return 'yolo';
        // $branch = DB::table('branches')
        // ->join('users', 'branches.user_id', '=', 'users.id')
        // ->select('branches.id', 'branch_code', 'branch_name',
        //     'users.fullname as created_by',
        //     DB::raw("DATE_FORMAT(branches.created_at, '%d %b %Y') as created_at"), 'branches.address')
        // ->where('branches.isDeleted', '=', 0);

        // $posts = Post::latest()->paginate(100);
        // return new PostResource(true, 'List Data Locations', $posts);

        // return response()->json($branch, 200);

        // if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
        //     return response()->json([
        //         'message' => 'The user role was invalid.',
        //         'errors' => ['Akses User tidak diizinkan!'],
        //     ], 403);
        // }

        // $branch = DB::table('branches')
        //     ->join('users', 'branches.user_id', '=', 'users.id')
        //     ->select('branches.id', 'branch_code', 'branch_name', 'users.fullname as created_by',
        //         DB::raw("DATE_FORMAT(branches.created_at, '%d %b %Y') as created_at"), 'branches.address')
        //     ->where('branches.isDeleted', '=', 0);

        // if ($request->keyword) {
        //     $branch = $branch->where('branch_code', 'like', '%' . $request->keyword . '%')
        //         ->orwhere('branches.branch_name', 'like', '%' . $request->keyword . '%')
        //         ->orwhere('branches.address', 'like', '%' . $request->keyword . '%')
        //         ->orwhere('users.fullname', 'like', '%' . $request->keyword . '%');
        // }

        // if ($request->orderby) {

        //     $branch = $branch->orderBy($request->column, $request->orderby);
        // }

        // $branch = $branch->orderBy('id', 'desc');

        // $branch = $branch->get();

        // return response()->json($branch, 200);

    }

}
