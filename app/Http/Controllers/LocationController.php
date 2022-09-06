<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LocationController extends Controller
{

    /**
     * @OA\Delete(
     * path="/api/contactlocation",
     * operationId="Delete Contact Location",
     * tags={"Location"},
     * summary="Delete Contact Location",
     * description="Delete Contact Location , ex: email, messenger, operational(each represent column table, ex: email->location_email)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Delete Contact Location",
     *        example = "Delete Contact Location",
     *        value = {
     *           "keyword":"telepon",
     *           "id":1,
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               @OA\Property(property="keyword", type="text"),
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Delete branch Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Delete branch Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function deletecontactlocation(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:10000',
        ]);

        DB::beginTransaction();
        try
        {

            if ($request->input('keyword') == "email") {

                $table_name = 'location_email';

            } elseif ($request->input('keyword') == "messenger") {

                $table_name = 'location_messenger';

            } elseif ($request->input('keyword') == "telepon") {

                $table_name = 'location_telepon';
            }

            DB::table($table_name)
                ->where('id', '=', $request->input('id'), )
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

    /**
     * @OA\Delete(
     * path="/api/location",
     * operationId="delete location",
     * tags={"Location"},
     * summary="Delete Location",
     * description="Delete Location , by delete location will update status isDeleted into 1)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Delete Location",
     *        example = "Delete Location",
     *        value = {
     *           "id":1,
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Delete location Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Delete location Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function delete(Request $request)
    {

        DB::beginTransaction();
        try
        {

            $data = DB::table('location')
                ->select('codeLocation')
                ->where('id', '=', $request->input('id'))
                ->first()->codeLocation;

            DB::table('location')
                ->where('codeLocation', '=', $data)
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_alamat_detail')
                ->where('codeLocation', '=', $data)
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_email')
                ->where('codeLocation', '=', $data)
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_messenger')
                ->where('codeLocation', '=', $data)
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_telepon')
                ->where('codeLocation', '=', $data)
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

    public function uploadexceltest(Request $request)
    {

        $request->validate([
            'file' => 'required|max:10000',
        ]);

        // echo($request->file);

        Excel::import(new UsersImport, $request->file);
        return 'true';
    }

    /**
     * @OA\Put(
     * path="/api/location",
     * operationId="Update Location",
     * tags={"Location"},
     * summary="Update Location",
     * description="Update Location",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="update Location",
     *        example = "update Location",
     * value = {
     *  "id":"1",
     *  "locationName": "RPC Permata Hijau Jakarta",
     *  "isBranch": "1",
     *  "status": "0",
     *  "introduction":"RPC Permata Hijau Jakarta, your satisfation is out top priority",
     *  "description":"Dibangun di tahun 2022, RPC Permata Hijau Jakarta sudah melayani berbagai lebih dari 100 ribu client diberbagai wilayah dijakarta, fasilitas yang lengkap dan terjamin security",
     *  "image":"D:\\ImageFolder\\ExamplePath\\ImageRPCPermataHijauJakarta.jpg",
     *  "imageTitle":"ImageRPCPermataHijauJakarta.jpg",
     *  "alamat_location":{
     *      {
     *            "codeLocation":"366f70e7",
     *            "alamatJalan": "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
     *            "infoTambahan": "Patokan Jalan : terminal busway jakarta selatan itc permata hijau",
     *            "kotaID": "Jakarta Selatan",
     *            "provinsiID": "Kebayoran Lama",
     *            "kodePos": 12210,
     *            "negara": "Indonesia",
     *            "parkir": "Yes",
     *            "pemakaian": "Apartement"
     *         }
     *    },
     *  "operational_days":
     *    {
     *        {
     *
     *        "days_name": "Monday",
     *        "from_time": "10:00PM",
     *        "to_time": "10:00PM",
     *        "all_day": 1
     *      },
     *       {
     *        "days_name": "Monday",
     *        "from_time": "10:00PM",
     *        "to_time": "10:00PM",
     *        "all_day": 1
     *      },
     *       {
     *        "days_name": "Tuesday",
     *        "from_time": "12:00PM",
     *        "to_time": "13:00PM",
     *        "all_day": 1
     *      },
     *       {
     *        "days_name": "Wednesday",
     *        "from_time": "10:00PM",
     *        "to_time": "10:00PM",
     *        "all_day": 1
     *      }
     *    },
     *    "messenger":
     *    {
     *        {
     *           "pemakaian":"Utama",
     *           "namaMessenger":"(021) 3851185",
     *           "tipe":"Fax"
     *        },
     *        {
     *           "pemakaian":"Utama",
     *           "namaMessenger":"(021) 012345678",
     *           "tipe":"Office"
     *        }
     *    },
     *    "email":{
     *
     *         {
     *           "pemakaian":"Utama",
     *           "namaPengguna":"wahyudidanny23@gmail.com",
     *           "tipe":"Personal"
     *        },
     *        {
     *           "pemakaian":"Secondary",
     *           "namaPengguna":"wahyudidanny25@gmail.com",
     *           "tipe":"Personal"
     *        }
     *   },
     *    "telepon":{
     *         {
     *           "pemakaian":"Utama",
     *           "nomorTelepon":"087888821648",
     *           "tipe":"Telepon Selular"
     *        },
     *        {
     *           "pemakaian":"Secondary",
     *           "nomorTelepon":"085265779499",
     *           "tipe":"Whatshapp"
     *        }
     *   }
     *
     *},
     *          )),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"locationName","isBranch","password"},
     *               @OA\Property(property="name", type="text"),

     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Update Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Update Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function update(Request $request)
    {

        DB::beginTransaction();
        try
        {

            $data = DB::table('location')
                ->select('codeLocation')
                ->where('id', '=', $request->input('id'))
                ->first()->codeLocation;

            DB::table('location')
                ->where('id', '=', $request->input('id'))
                ->update([
                    'locationName' => $request->input('locationName'),
                    'isBranch' => $request->input('isBranch'),
                    'status' => $request->input('status'),
                    'introduction' => $request->input('introduction'),
                    'description' => $request->input('description'),
                    'image' => $request->input('image'),
                    'imageTitle' => $request->input('imageTitle'),

                ]);
            
                DB::table('location_alamat_detail')
                ->where('codeLocation', '=', $data)
                ->update([
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
                DB::table('location_operational')
                    ->where('codeLocation', '=', $data)
                    ->update([
                        'days_name' => $val['days_name'],
                        'from_time' => $val['from_time'],
                        'to_time' => $val['to_time'],
                        'all_day' => $val['all_day'],

                    ]);
            }



            foreach ($request->messenger as $val) {
                DB::table('location_messenger')
                    ->where('codeLocation', '=', $data)
                    ->update([
                        'pemakaian' => $val['pemakaian'],
                        'namaMessenger' => $val['namaMessenger'],
                        'tipe' => $val['tipe'],
                    ]);
            }


            foreach ($request->email as $val) {
                DB::table('location_email')
                    ->where('codeLocation', '=', $data)
                    ->update([
                        'pemakaian' => $val['pemakaian'],
                        'namaPengguna' => $val['namaPengguna'],
                        'tipe' => $val['tipe'],
                    ]);
            }


            foreach ($request->telepon as $val) {
                DB::table('location_telepon')
                    ->where('codeLocation', '=', $data)
                    ->update([
                        'pemakaian' => $val['pemakaian'],
                        'nomorTelepon' => $val['nomorTelepon'],
                        'tipe' => $val['tipe'],
                    ]);
            }


            return 'SUCCESS';

        } catch (Exception $e) {

            DB::rollback();
            return 'FAILED';
            //return back()->with('ERROR', 'Your error message');
        }

    }

    /**
     * @OA\Post(
     * path="/api/location",
     * operationId="Insert Location",
     * tags={"Location"},
     * summary="Insert Location",
     * description="Insert Location",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Insert Location",
     *        example = "Insert Location",
     *value = {
     *    "locationName": "RPC Permata Hijau Pekanbaru",
     *    "isBranch": "0",
     *    "status": "1",
     *    "introduction":"RPC Permata Hijau Pekanbaru, the best pet shop in the pekanbaru",
     *    "description":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum fuga, alias placeat necessitatibus dolorem ea autem tempore omnis asperiores nostrum, excepturi a unde mollitia blanditiis iusto. Dolorum tempora enim atque.",
     *    "image":"D:\\ImageFolder\\ExamplePath\\ImageRPCPermataHijau.jpg",
     *    "imageTitle":"ImageRPCPermataHijau.jpg",
     *    "alamat_location":{
     *        {
     *                "alamatJalan": "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
     *                "infoTambahan": "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
     *                "kotaID": "Jakarta Barat",
     *                "provinsiID": "Kemanggisan",
     *                "kodePos": "11480",
     *                "negara": "Indonesia",
     *                "parkir": "Yes",
     *                "pemakaian": "Indekos"
     *            }
     *        },
     *    "operational_days":
     *        {
     *            {
     *
     *            "days_name": "Monday",
     *            "from_time": "10:00PM",
     *            "to_time": "10:00PM",
     *            "all_day": 1
     *        },
     *        {
     *            "days_name": "Monday",
     *            "from_time": "10:00PM",
     *            "to_time": "10:00PM",
     *            "all_day": 1
     *        },
     *        {
     *            "days_name": "Tuesday",
     *            "from_time": "12:00PM",
     *            "to_time": "13:00PM",
     *            "all_day": 1
     *        },
     *        {
     *            "days_name": "Wednesday",
     *            "from_time": "10:00PM",
     *            "to_time": "10:00PM",
     *            "all_day": 1
     *        }
     *        },
     *        "messenger":
     *        {
     *            {
     *            "pemakaian":"Utama",
     *            "namaMessenger":"(021) 3851185",
     *            "tipe":"Fax"
     *            },
     *            {
     *            "pemakaian":"Utama",
     *            "namaMessenger":"(021) 012345678",
     *            "tipe":"Office"
     *            }
     *        },
     *        "email":{
     *
     *            {
     *            "pemakaian":"Utama",
     *            "namaPengguna":"wahyudidanny23@gmail.com",
     *            "tipe":"Personal"
     *            },
     *            {
     *            "pemakaian":"Secondary",
     *            "namaPengguna":"wahyudidanny25@gmail.com",
     *            "tipe":"Personal"
     *            }
     *    },
     *        "telepon":{
     *            {
     *            "pemakaian":"Utama",
     *            "nomorTelepon":"087888821648",
     *            "tipe":"Telepon Selular"
     *            },
     *            {
     *            "pemakaian":"Secondary",
     *            "nomorTelepon":"085265779499",
     *            "tipe":"Whatshapp"
     *            }
     *    }
     * },
     *          )),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"locationName","isBranch","password"},
     *               @OA\Property(property="name", type="text"),

     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Register Successfully",
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
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function create(Request $request)
    {
        DB::beginTransaction();

        try
        {

            $getvaluesp = strval(collect(DB::select('call generate_codeLocation'))[0]->randomString);

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
                'isDeleted' => 0,
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

    /**
     * @OA\Get(
     * path="/api/location",
     * operationId="location",
     * tags={"Location"},
     * summary="Get Location",
     * description="get Location",
     *  @OA\Parameter(
     *     name="body",
     *     in="path",
     *     required=true,
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="orderby", type="text",example="asc"),
     *        @OA\Property(property="column", type="text",example="codeLocation, locationName, isBranch, status, introduction"),
     *        @OA\Property(property="keyword", type="text",example="Jakarta"),
     *        @OA\Property(property="page", type="number",example="1"),
     *        @OA\Property(property="total_per_page", type="number",example="5"),
     *     ),
     * ),
     *   @OA\Response(
     *          response=201,
     *          description="Get Data Location Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Get Data Location Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function location(Request $request)
    {

        $items_per_page = 5;

        $data = DB::table('location')
            ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
            ->select('location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location.status as status',
                'location.introduction as introduction',
                'location_alamat_detail.alamatJalan as alamatJalan', );

        if ($request->keyword) {

            $data = $data->where('location.codeLocation', 'like', '%' . $request->keyword . '%')
                ->orwhere('location.locationName', 'like', '%' . $request->keyword . '%')
                ->orwhere('location.introduction', 'like', '%' . $request->keyword . '%')
                ->orwhere('location_alamat_detail.alamatJalan', 'like', '%' . $request->keyword . '%');
        }

        if ($request->column) {
            $data = $data->orderBy($request->column, $request->orderby);
        }

        if ($request->total_per_page > 0) {

            $items_per_page = $request->total_per_page;
        }

        $page = $request->page;

        $offset = ($page - 1) * $items_per_page;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($items_per_page)->get();
        } else {
            $data = $data->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

        return response()->json(['total_paging' => ceil($total_paging),
            'data' => $data], 200);

    }

    /**
     * @OA\Get(
     * path="/api/detaillocation",
     * operationId="detaillocation",
     * tags={"Location"},
     * summary="Get Location detail",
     * description="get Location detail",
     *  @OA\Parameter(
     *     name="body",
     *     in="path",
     *     required=true,
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="id", type="text",example="1"),
     *     ),
     * ),
     *   @OA\Response(
     *          response=201,
     *          description="Get Data Location Detail Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Get Data Location Detail Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function locationdetail(Request $request)
    {

        $id = $request->input('id');

        $param_location = DB::table('location')
            ->select('location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location.status as status',
                'location.introduction as introduction',
                'location.description as description',
                'location.image as image',
                'location.imageTitle as imageTitle',
            )
            ->where('location.id', '=', $id)
            ->first();

        $alamat_location = DB::table('location_alamat_detail')
            ->select('location_alamat_detail.alamatJalan as alamatJalan',
                'location_alamat_detail.infoTambahan as infoTambahan',
                'location_alamat_detail.kotaID as kotaID',
                'location_alamat_detail.provinsiID as provinsiID',
                'location_alamat_detail.kodePos as kodePos',
                'location_alamat_detail.negara as negara',
            )
            ->where('location_alamat_detail.id', '=', $id)
            ->get();

        $param_location->alamat_location = $alamat_location;

        $operational_location = DB::table('location_operational')
            ->select('location_operational.days_name as days_name',
                'location_operational.from_time as from_time',
                'location_operational.to_time as to_time',
                'location_operational.all_day as all_day',
            )
            ->where('location_operational.id', '=', $id)
            ->get();

        $param_location->operational_location = $operational_location;

        $email_location = DB::table('location_email')
            ->select('location_email.pemakaian as pemakaian',
                'location_email.namaPengguna as namaPengguna',
                'location_email.tipe as tipe',
            )
            ->where('location_email.id', '=', $id)
            ->get();

        $param_location->email_location = $email_location;

        $messenger_location = DB::table('location_messenger')
            ->select('location_messenger.pemakaian as pemakaian',
                'location_messenger.namaMessenger as namaMessenger',
                'location_messenger.tipe as tipe', )
            ->where('location_messenger.id', '=', $id)
            ->get();

        $param_location->messenger_location = $messenger_location;

        $telepon_location = DB::table('location_telepon')
            ->select('location_telepon.pemakaian as pemakaian',
                'location_telepon.nomorTelepon as nomorTelepon',
                'location_telepon.tipe as tipe',
            )
            ->where('location_telepon.id', '=', $id)
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


 /**
     * @OA\Post(
     * path="/api/datastatic",
     * operationId="Insert Data Static",
     * tags={"Location"},
     * summary="Insert Data Static",
     * description="Insert Data Static",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Insert Data static",
     *        example = "Insert Data Static",
     *      value = {
     *          "keyword": "Pemakaian",
     *           "name": "Sharing Account",
     *          })),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"keyword","name"},
     *               @OA\Property(property="keyword", type="text"),
     *               @OA\Property(property="name", type="text"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Register Successfully",
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
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function insertdatastatic(Request $request)
    {

        $request->validate([
            'keyword' => 'required|max:2555',
        ]);

        DB::beginTransaction();

        try
        {

            DB::table('data_static')->insert([
                'value' => $request->input('keyword'),
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
