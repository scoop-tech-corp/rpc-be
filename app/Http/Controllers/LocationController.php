<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LocationController extends Controller
{

 /**
     * @OA\GET(
     * path="/api/location",
     * operationId="location",
     * tags={"Location"},
     * summary="Get Location",
     * description="Get Location List Branch",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               @OA\Property(property="orderby", type="text"),
     *               @OA\Property(property="column", type="text"),
     *               @OA\Property(property="keyword", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="get data Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

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

        $branch = DB::table('location')
            ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
            ->select('location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location.status as status',
                'location.introduction as introduction',
                'location_alamat_detail.alamatJalan as alamatJalan', )
            ->get();

        return response()->json($branch, 200);

    }

    public function getlocationorderbyid(Request $request)
    {

        $order = $request->input('order');

        $branch = DB::table('location')
            ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
            ->select('location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location.status as status',
                'location.introduction as introduction',
                'location_alamat_detail.alamatJalan as alamatJalan', )
            ->orderBy('location.codeLocation', $order)
            ->get();

        return response()->json($branch, 200);

    }

    public function getlocationorderbyname(Request $request)
    {
        $order = $request->input('order');

        $branch = DB::table('location')
            ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
            ->select('location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location.status as status',
                'location.introduction as introduction',
                'location_alamat_detail.alamatJalan as alamatJalan', )
            ->orderBy('location.locationName', $order)
            ->get();

        return response()->json($branch, 200);

    }

    public function getlocationorderbyalamatjalan(Request $request)
    {
        $order = $request->input('order');

        $branch = DB::table('location')
            ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
            ->select('location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location.status as status',
                'location.introduction as introduction',
                'location_alamat_detail.alamatJalan as alamatJalan', )
            ->orderBy('location_alamat_detail.alamatJalan', $order)
            ->get();

        return response()->json($branch, 200);

    }


    public function getlocationdetailbyid(Request $request)
    {

        $codeLocation = $request->input('codeLocation');

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
            ->where('location.codeLocation', '=', $codeLocation)
            ->first();

        $alamat_location = DB::table('location_alamat_detail')
            ->select('location_alamat_detail.alamatJalan as alamatJalan',
                'location_alamat_detail.infoTambahan as infoTambahan',
                'location_alamat_detail.kotaID as kotaID',
                'location_alamat_detail.provinsiID as provinsiID',
                'location_alamat_detail.kodePos as kodePos',
                'location_alamat_detail.negara as negara',

            )
            ->where('location_alamat_detail.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->alamat_location = $alamat_location;

        $operational_location = DB::table('location_operational')
            ->select('location_operational.days_name as days_name',
                'location_operational.from_time as from_time',
                'location_operational.to_time as to_time',
                'location_operational.all_day as all_day',
            )
            ->where('location_operational.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->operational_location = $operational_location;

        $email_location = DB::table('location_email')
            ->select('location_email.pemakaian as pemakaian',
                'location_email.namaPengguna as namaPengguna',
                'location_email.tipe as tipe',
            )
            ->where('location_email.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->email_location = $email_location;

        $messenger_location = DB::table('location_messenger')
            ->select('location_messenger.pemakaian as pemakaian',
                'location_messenger.namaMessenger as namaMessenger',
                'location_messenger.tipe as tipe', )
            ->where('location_messenger.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->messenger_location = $messenger_location;

        $telepon_location = DB::table('location_telepon')
            ->select('location_telepon.pemakaian as pemakaian',
                'location_telepon.nomorTelepon as nomorTelepon',
                'location_telepon.tipe as tipe',
            )
            ->where('location_telepon.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->telepon_location = $telepon_location;

        $data_static_pemakaian = DB::table('data_static')
            ->select('data_static.value as value',
                'data_static.name as name',
            )
            ->where('data_static.value', '=', 'pemakaian')
            ->get();
        $param_location->data_static_pemakaian = $data_static_pemakaian;

        $data_static_telepon = DB::table('data_static')
            ->select('data_static.value as value',
                'data_static.name as name',
            )
            ->where('data_static.value', '=', 'telepon')
            ->get();
        $param_location->data_static_telepon = $data_static_telepon;

        $data_static_messenger = DB::table('data_static')
            ->select('data_static.value as value',
                'data_static.name as name',
            )
            ->where('data_static.value', '=', 'messenger')
            ->get();
        $param_location->data_static_messenger = $data_static_messenger;

        return response()->json($param_location, 200);
    }

    public function insertdatastatictelepon(Request $request)
    {
        DB::beginTransaction();

        try
        {
            $request->validate([
                'name' => 'required|max:2555',
            ]);

            DB::table('data_static')->insert([
                'value' => 'Telepon',
                'name' => $request->input('name'),
                'isDeleted' => 0,
            ]);

            DB::commit();

            return ('SUCCESS');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

    }

    public function insertdatastaticpemakaian(Request $request)
    {
        DB::beginTransaction();

        try
        {
            $request->validate([
                'name' => 'required|max:2555',
            ]);

            DB::table('data_static')->insert([
                'value' => 'Pemakaian',
                'name' => $request->input('name'),
                'isDeleted' => 0,
            ]);

            DB::commit();

            return ('SUCCESS');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

    }

    public function insertdatastaticmessenger(Request $request)
    {
        DB::beginTransaction();

        try
        {
            $request->validate([
                'name' => 'required|max:2555',
            ]);

            DB::table('data_static')->insert([
                'value' => 'Messenger',
                'name' => $request->input('name'),
                'isDeleted' => 0,
            ]);

            DB::commit();

            return ('SUCCESS');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

    }

}
