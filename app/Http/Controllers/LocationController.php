<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use App\Exports\exportValue;
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

            } elseif ($request->input('keyword') == "telephone") {

                $table_name = 'location_telephone';
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
     * @OA\Get(
     * path="/api/export",
     * operationId="export",
     * tags={"Location"},
     * summary="export Location excel",
     * description="export Location excel",
     *   @OA\Response(
     *          response=201,
     *          description="Generate Excel Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Generate Excel Successfully",
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
    public function export(Request $request){

        return Excel::download(new exportValue, 'Location.xlsx');
     
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

        //danny

        $request->validate([
            'codeLocation' => 'required',
        ]);

        DB::beginTransaction();
        try
        {

            DB::table('location')
                ->where('codeLocation', '=',  $request->input('codeLocation'))
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_detail_address')
                ->where('codeLocation', '=',  $request->input('codeLocation'))
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_email')
                ->where('codeLocation', '=', $request->input('codeLocation'))
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_messenger')
                ->where('codeLocation', '=', $request->input('codeLocation'))
                ->update([
                    'isDeleted' => 1,
                ]);

            DB::table('location_telephone')
                ->where('codeLocation', '=', $request->input('codeLocation'))
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
     * value = 
    *  {
    *  "codeLocation": "abc123",
    *  "locationName": "RPC Permata Hijau Jakarta",
    *  "isBranch": "1",
    *  "status": "0",
    *  "introduction":"RPC Permata Hijau Jakarta, your satisfation is out top priority",
    *  "description":"Dibangun di tahun 2022, RPC Permata Hijau Jakarta sudah melayani berbagai lebih dari 100 ribu client diberbagai wilayah dijakarta, fasilitas yang lengkap dan terjamin security",
    *  "image":"D:\\ImageFolder\\ExamplePath\\ImageRPCPermataHijauJakarta.jpg",
    *  "imageTitle":"ImageRPCPermataHijauJakarta.jpg",
    *  "alamat_location":{
    *      { 
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
    *		"days_name": "Monday",
    *		"from_time": "10:00PM",
    *		"to_time": "10:00PM",
    *		"all_day": 1
    *      },
    *       {
    *		"days_name": "Monday",
    *		"from_time": "10:00PM",
    *		"to_time": "10:00PM",
    *		"all_day": 1
    *      },
    *	   {
    *		"days_name": "Tuesday",
    *		"from_time": "12:00PM",
    *		"to_time": "13:00PM",
    *		"all_day": 1
    *      },
    *	   {
    *		"days_name": "Wednesday",
    *		"from_time": "10:00PM",
    *		"to_time": "10:00PM",
    *		"all_day": 1
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

            // $data = DB::table('location')
            //     ->select('codeLocation')
            //     ->where('id', '=', $request->input('id'))
            //     ->first()->codeLocation;

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
            
            foreach ($request->detailAddress as $val) {
                DB::table('location_detail_address')
                    ->where('codeLocation', '=',  $request->input('codeLocation'))
                    ->update([
                        'addressName' => $val['addressName'],
                        'additionalInfo' => $val['additionalInfo'],
                        'cityName' => $val['cityName'],
                        'provinceName' => $val['provinceName'],
                        'districtName' => $val['districtName'],
                        'postalCode' => $val['postalCode'],
                        'country' => $val['country'],
                        'parking' => $val['parking'],
                        'usage' => $val['usage'],
                    ]);
            }



            foreach ($request->operationalHour as $val) {
                DB::table('location_operational')
                    ->where('codeLocation', '=', $request->input('codeLocation'))
                    ->update([
                        'dayName' => $val['dayName'],
                        'fromTime' => $val['fromTime'],
                        'toTime' => $val['toTime'],
                        'allDay' => $val['allDay'],

                    ]);
            }



            foreach ($request->messenger as $val) {
                DB::table('location_messenger')
                    ->where('codeLocation', '=', $request->input('codeLocation'))
                    ->update([
                        'messengerName' => $val['messengerName'],
                        'type' => $val['type'],
                        'usage' => $val['usage'],
                    ]);
            }


            foreach ($request->email as $val) {
                DB::table('location_email')
                    ->where('codeLocation', '=', $request->input('codeLocation'))
                    ->update([
                        'username' => $val['username'],
                        'usage' => $val['usage'],
                        'type' => $val['type'],
                    ]);
            }


            foreach ($request->telephone as $val) {
                DB::table('location_telephone')
                    ->where('codeLocation', '=', $request->input('codeLocation'))
                    ->update([
                        'usage' => $val['usage'],
                        'phoneNumber' => $val['phoneNumber'],
                        'type' => $val['type'],
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
     * value = {
    *        "locationName": "RPC Permata Hijau Pekanbaru",
    *        "isBranch": 0,
    *        "status": 1,
    *        "introduction":"RPC Permata Hijau Pekanbaru, the best pet shop in the pekanbaru",
    *        "description":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum fuga, alias placeat necessitatibus dolorem ea autem   tempore omnis asperiores nostrum, excepturi a unde mollitia blanditiis iusto. Dolorum tempora enim atque.",
    *        "image":"D:\\ImageFolder\\ExamplePath\\ImageRPCPermataHijau.jpg",
    *        "imageTitle":"ImageRPCPermataHijau.jpg",
    *        "detailAddress":{
    *                {
    *                    "addressName": "Jalan U 27 B Palmerah Barat no 206 Jakarta Barat 11480",
    *                    "additionalInfo": "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
    *                    "cityName": "Jakarta Barat",
    *                    "provinceName": "DKI Jakarta",
    *                    "districtName": "Palmerah",
    *                    "postalCode": "11480",
    *                    "country": "Indonesia",
    *                    "parking": 1,
    *                    "usage": "Indekos"
    *                }, 
    *                {
    *                    "addressName": "Jalan Keluarga sebelah binus syahdan",
    *                    "additionalInfo": "Didepan nasi goreng kuning arema, disebelah bubur pasudan",
    *                    "cityName": "Jakarta Barat",
    *                    "provinceName": "DKI Jakarta",
    *                    "districtName": "Palmerah",
    *                    "postalCode": "11480",
    *                    "country": "Indonesia",
    *                    "parking": 1,
    *                    "usage": "Utama"
    *                }
    *            },
    *            
    *        "operationalHour": {
    *                                {
    *                                    "dayName": "Monday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                }, 
    *                                {
    *                                    "dayName": "Tuesday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                },
    *                                {
    *                                    "dayName": "Wednesday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                },
    *                                {
    *                                    "dayName": "Thursday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                },
    *                                {
    *                                    "dayName": "Friday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                },
    *                                {
    *                                     "dayName": "Saturday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                },
    *                                {
    *                                    "dayName": "Sunday",
    *                                    "fromTime": "",
    *                                    "toTime": "",
    *                                    "allDay": 1
    *                                }
    *                            },
    *        "messenger":{
    *                        {
    *                            
    *                            "messengerName":"(021) 3851185",
    *							"type":"Fax",
    *							"usage":"Utama"
    *                            
    *                        },
    *                        {
    *
    *                            "messengerName":"(021) 012345678",
    *                            "type":"Office",
    *							"usage":"Personal"
    *                        }
    *                    },
    *        "email":{	
    *                    {
    *                        
    *                        "username":"wahyudidanny23@gmail.com",
    *                        "type":"Personal",
    *						"usage":"Utama"
    *                    }, 
    *                    {
    *                       
    *                        "username":"wahyudidanny25@gmail.com",
    *						"type":"Secondary",
    *                        "usage":"Personal"
    *                    }
    *                },
    *        "telephone":{
    *                    {
    *                      
    *                        "phoneNumber":"087888821648",
    *                        "type":"Telepon Selular",
    *						"usage":"Utama"
    *                    }, 
    *                    {
    *                        
    *                        "phoneNumber":"085265779499",
    *                        "type":"Whatshapp",
    *						"usage":"Secondary"
    *                    }
    *                }
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

            foreach ($request->detailAddress as $val) {
                DB::table('location_detail_address')->insert([
                     'codeLocation' => $getvaluesp,
                     'addressName' => $val['addressName'],
                     'additionalInfo' => $val['additionalInfo'],
                     'cityName' => $val['cityName'],
                     'provinceName' => $val['provinceName'],
                     'districtName' => $val['districtName'],
                     'postalCode' => $val['postalCode'],
                     'country' => $val['country'],
                     'parking' => $val['parking'],
                     'usage' =>$val['usage'],
                     'isDeleted' => 0,
                 ]);
             }

            foreach ($request->operationalHour as $val) {

                DB::table('location_operational')->insert([
                    'codeLocation' => $getvaluesp,
                    'dayName' => $val['dayName'],
                    'fromTime' => $val['fromTime'],
                    'toTime' => $val['toTime'],
                    'allDay' => $val['allDay'],
                ]);

            }

            foreach ($request->messenger as $val) {

                DB::table('location_messenger')->insert([
                    'codeLocation' => $getvaluesp,
                    'messengerName' => $val['messengerName'],
                    'type' => $val['type'],
                    'usage' => $val['usage'],
                    'isDeleted' => 0,
                ]);

            }

            foreach ($request->email as $val) {

                DB::table('location_email')->insert([
                    'codeLocation' => $getvaluesp,
                    'username' => $val['username'],
                    'type' => $val['type'],
                    'usage' => $val['usage'],
                    'isDeleted' => 0,
                ]);

            }

            foreach ($request->telephone as $val) {

                DB::table('location_telephone')->insert([
                    'codeLocation' => $getvaluesp,
                    'phoneNumber' => $val['phoneNumber'],
                    'type' => $val['type'],
                    'usage' => $val['usage'],
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
     *        @OA\Property(property="rowPerPage", type="number",example="10"),
     *        @OA\Property(property="goToPage", type="number",example="6"),
     *        @OA\Property(property="orderColumn", type="array", collectionFormat="multi", 
  *                @OA\Items(
 *                      @OA\Property(
 *                         property="value",
 *                         type="string",
 *                         example="asc"
 *                      ),
 *                      @OA\Property(
 *                         property="fieldName",
 *                         type="string",
 *                         example="locationName"
 *                      ),
 *                ),
     *          ),
     *        @OA\Property(property="search", type="text",example=""),
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

        //danny
        //$rowPerPage = 5;
        $rowPerPage= 5;
        
        $data = DB::table('location')
            ->leftjoin('location_detail_address', 'location_detail_address.codeLocation', '=', 'location.codeLocation')
            ->leftjoin('location_telephone', 'location_telephone.codeLocation', '=', 'location.codeLocation')
            ->select('location.id as id',
                'location.codeLocation as codeLocation',
                'location.locationName as locationName',
                'location.isBranch as isBranch',
                'location_detail_address.addressName as addressName', 
                'location_detail_address.cityName as cityName',    
                DB::raw("CONCAT(location_telephone.phoneNumber ,' ', location_telephone.usage) as phoneNumber"),
                'location.status as status',)
             ->where([
                      ['location_detail_address.usage', '=', 'utama'], 
                     ['location_telephone.usage', '=', 'utama'],
                     ['location.isDeleted', '=', '0']
             ]);

        if ($request->search) {

            $data = $data->where('location.codeLocation', 'like', '%' . $request->search . '%')
                   ->orwhere('location.locationName', 'like', '%' . $request->search . '%')
                    ->orwhere('location_detail_address.addressName', 'like', '%' . $request->search . '%')
                    ->orwhere('location_detail_address.cityName', 'like', '%' . $request->search . '%');
        }

        if ($request->orderColumn) {
            $data = $data->orderBy($request->orderColumn['fieldName'], $request->orderColumn['value']);
        }
      
        if ($request->rowPerPage > 0) {
            $rowPerPage = $request->rowPerPage;
        }

         $goToPage = $request->goToPage;
       
         $offset = ($goToPage - 1) * $rowPerPage;

         $count_data = $data->count();
         $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($rowPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($rowPerPage)->get();
        }

        $total_paging = $count_data / $rowPerPage;
        return response()->json(['totalData' => ceil($total_paging),'data' => $data], 200);


       // return response()->json($data->get(), 200);
        // $count_result = $count_data - $offset;

    
      // $total_paging = $count_data / $rowPerPage;
          //  echo($total_paging);
    //    return response()->json(['total_data' => ceil($total_paging),'data' => $data], 200);


        // $items_per_page = 5;

        // $data = DB::table('location')
        //     ->leftjoin('location_alamat_detail', 'location_alamat_detail.codeLocation', '=', 'location.codeLocation')
        //     ->select('location.id as id',
        //         'location.codeLocation as codeLocation',
        //         'location.locationName as locationName',
        //         'location.isBranch as isBranch',
        //         'location.status as status',
        //         'location.introduction as introduction',
        //         'location_alamat_detail.alamatJalan as alamatJalan', );

        // if ($request->keyword) {

        //     $data = $data->where('location.codeLocation', 'like', '%' . $request->keyword . '%')
        //         ->orwhere('location.locationName', 'like', '%' . $request->keyword . '%')
        //         ->orwhere('location.introduction', 'like', '%' . $request->keyword . '%')
        //         ->orwhere('location_alamat_detail.alamatJalan', 'like', '%' . $request->keyword . '%');
        // }

        // if ($request->column) {
        //     $data = $data->orderBy($request->column, $request->orderby);
        // }

        // if ($request->total_per_page > 0) {

        //     $items_per_page = $request->total_per_page;
        // }

        // $page = $request->page;

        // $offset = ($page - 1) * $items_per_page;

        // $count_data = $data->count();
        // $count_result = $count_data - $offset;

        // if ($count_result < 0) {
        //     $data = $data->offset(0)->limit($items_per_page)->get();
        // } else {
        //     $data = $data->offset($offset)->limit($items_per_page)->get();
        // }

        // $total_paging = $count_data / $items_per_page;

        // return response()->json(['total_paging' => ceil($total_paging),
        //     'data' => $data], 200);

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

        $codeLocation = $request->input('codeLocation');

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
            ->where('location.codeLocation', '=', $codeLocation)
            ->first();

        $location_detail_address = DB::table('location_detail_address')
            ->select('location_detail_address.addressName as addressName',
                'location_detail_address.additionalInfo as additionalInfo',
                'location_detail_address.cityName as cityName',
                'location_detail_address.provinceName as provinceName',
                'location_detail_address.districtName as districtName',
                'location_detail_address.postalCode as postalCode',
                'location_detail_address.country as country',
                'location_detail_address.parking as parking',
                'location_detail_address.usage as usage',
            )
            ->where('location_detail_address.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->detailAddress = $location_detail_address;

        $operationalHour = DB::table('location_operational')
            ->select('location_operational.dayName as dayName',
                'location_operational.fromTime as fromTime',
                'location_operational.toTime as toTime',
                'location_operational.allDay as allDay',
            )
            ->where('location_operational.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->operationalHour = $operationalHour;

        $messenger_location = DB::table('location_messenger')
                    ->select('location_messenger.messengerName as messengerName',
                        'location_messenger.type as type',
                        'location_messenger.usage as usage', )
                    ->where('location_messenger.codeLocation', '=', $codeLocation)
                    ->get();

        $param_location->messenger = $messenger_location;

        $email_location = DB::table('location_email')
            ->select( 'location_email.username as username',
                        'location_email.type as type',
                        'location_email.usage as usage',)
            ->where('location_email.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->email = $email_location;

        $telepon_location = DB::table('location_telephone')
            ->select('location_telephone.phoneNumber as phoneNumber',
                        'location_telephone.type as type',
                         'location_telephone.usage as usage',)
            ->where('location_telephone.codeLocation', '=', $codeLocation)
            ->get();

        $param_location->telephone = $telepon_location;

        $dataStaticUsage = DB::table('data_static')
            ->select('data_static.value as value',
                'data_static.name as name',
            )
            ->where('data_static.value', '=', 'Usage')
            ->get();
        $param_location->dataStaticUsage = $dataStaticUsage;

        $data_static_telepon = DB::table('data_static')
            ->select('data_static.value as value',
                'data_static.name as name',
            )
            ->where('data_static.value', '=', 'Telephone')
            ->get();
        $param_location->dataStaticTelephone = $data_static_telepon;

        $data_static_messenger = DB::table('data_static')
            ->select('data_static.value as value',
                'data_static.name as name',
            )
            ->where('data_static.value', '=', 'messenger')
            ->get();
        $param_location->dataStaticMessenger = $data_static_messenger;

        $data_region = DB::table('provinsi')
        ->leftjoin('kabupaten', 'kabupaten.kodeProvinsi', '=', 'provinsi.kodeProvinsi')
        ->leftjoin('kecamatan', 'kecamatan.kodeKabupaten', '=', 'kabupaten.kodeKabupaten')
        ->select('provinsi.namaProvinsi as provinceName',
                'kabupaten.namaKabupaten as cityName',
                'kecamatan.namaKecamatan as districtName',)
        ->get();
        
        $param_location->dataRegion = $data_region;

        return response()->json($param_location, 200);
    }


     /**
     * @OA\Get(
     * path="/api/region",
     * operationId="region",
     * tags={"Location"},
     * summary="Get Region",
     * description="Get Region",
     *  @OA\Parameter(
     *     name="body",
     *     in="path",
     *     required=true,
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="provinsi", type="text",example="ACEH"),
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
    public function region(Request $request)
    {

        $request->validate([
            'provinsi' => 'required|max:10000',
        ]);

         $data_region = DB::table('provinsi')
            ->leftjoin('kabupaten', 'kabupaten.kodeProvinsi', '=', 'provinsi.kodeProvinsi')
            ->leftjoin('kecamatan', 'kecamatan.kodeKabupaten', '=', 'kabupaten.kodeKabupaten')
            ->select('provinsi.namaProvinsi as namaProvinsi',
                    'kabupaten.namaKabupaten as namaKabupaten',
                    'kecamatan.namaKecamatan as namaKecamatan',)
            ->where('provinsi.namaProvinsi', '=', $request->input('provinsi'))     
            ->get();
 
        return response()->json($data_region, 200);

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
