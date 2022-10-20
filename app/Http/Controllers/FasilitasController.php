<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Exports\exportFacility;
use Maatwebsite\Excel\Facades\Excel;

class FasilitasController extends Controller
{
   
    /**
     * @OA\Post(
     * path="/api/facility",
     * operationId="facility",
     * tags={"Facility"},
     * summary="Insert data facility",
     * description="Insert data faciltiy",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Insert data facility",
     *        example = "Insert data facility",
     * value = {
     *       "facilityName" : "Kandang Maxi",
     *    "locationName" : "RPC Permata Hijau Pekanbaru",
    *    "capacity" : 1,
    *    "status" : 1,
    *    "introduction" : "Kandang maxi Extra bed for you love pet",
    *    "description" : "Ukuran 8M Cocok untuk tipe anjing besar, seperti golden retriever",
    * "unit":  { 
    *              {
    *                  "unitName": "Unit Testing 1",
    *                  "status": 1,
    *                  "notes": "Unit Testing 1.1"
    *              },
    *              {
    *                  "unitName": "Unit Testing 1",
    *                  "status": 1,
    *                  "notes": "Unit Testing 1.2"
    *              },
    *              {
    *                  "unitName": "Unit Testing 2",
    *                  "status": 1,
    *                  "notes": "Unit Testing 2.1"
    *              },
    *              {
    *                  "unitName": "Unit Testing 4",
    *                  "status": 1,
    *                  "notes": "Unit Testing 4"
    *              }
    *           }
    *       }
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=201,
    *          description="Register data facility successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=200,
    *          description="Register data facilty Successfully",
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
    public function createFacility(Request $request)
    {
        DB::beginTransaction();

        try
        {
           
            $request->validate(['facilityName' => 'required',
                                'locationName' => 'required',
                                'capacity' => 'required',
                                'status' => 'required',
                                'introduction' => 'required',
                                'description' => 'required',]);

            $getvaluesp = strval(collect(DB::select('call generate_codeFacility'))[0]->randomString);

            DB::table('facility')->insert(['facilityCode' => $getvaluesp,
                                            'facilityName' => $request->input('facilityName'),
                                            'locationName' => $request->input('locationName'),
                                            'capacity' => $request->input('capacity'),
                                            'status' => $request->input('status'),
                                            'introduction' => $request->input('introduction'),
                                            'description' => $request->input('description'),
                                            'isDeleted' => 0,
                                            'created_at' => now(), ]);

            foreach ($request->unit as $val) {

                DB::table('facility_unit')->insert(['facilityCode' => $getvaluesp,
                                                    'unitName' => $val['unitName'],
                                                    'status' => $val['status'],
                                                    'notes' => $val['notes'],
                                                    'isDeleted' => 0,
                                                    'created_at' => now(), 
                                                  ]);

            }


            foreach ($request->images as $val) {

                DB::table('facility_images')->insert(['facilityCode' => $getvaluesp,
                                                    'imageName' => $val['imageName'],
                                                    'imagePath' => $val['imagePath'],
                                                    'isDeleted' => 0,
                                                    'created_at' => now(), 
                                                  ]);

            }


            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully inserted new facility',
            ]);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' =>  $e,
            ]);

        }

    }

    
    /**
     * @OA\Delete(
     * path="/api/facility",
     * operationId="delete facility and facility unit",
     * tags={"Facility"},
     * summary="delete facility and facility unit",
     * description="Delete facility will update status isDeleted into 1)",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Delete facility",
     *        example = "Delete facility",
     *        value = {
     *           "facilityCode":"XYZ123",
     *         },))
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Delete facility Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Delete facility Successfully",
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
    public function deleteFacility(Request $request)
    {
        DB::beginTransaction();

        $request->validate(['facilityCode' => 'required']);

        try
        {

            foreach($request->facilityCode as $val){

                DB::table('facility')
                ->where('facilityCode', '=', $val)
                ->update(['isDeleted' => 1,
                         'updated_at' => now()]);
                     
                DB::table('facility_Unit')
                ->where('facilityCode', '=', $val)
                ->update(['isDeleted' => 1,
                          'updated_at' => now()]);

                DB::table('facility_images')
                ->where('facilityCode', '=', $val)
                ->update(['isDeleted' => 1,
                          'updated_at' => now()]);

                DB::commit();

            }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted facility'
            ]);


        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' =>  $e,
            ]);

        }

    }



/**
* @OA\Get(
* path="/api/facilitydetail/{facilityCode}",
* operationId="detail facility",
* tags={"Facility"},
* summary="Get spesific data facility",
* description="Get spesific fasilitas",
    *     @OA\Parameter(
    *         in="path",
    *         name="facilityCode",
    *         @OA\Schema(type="string",example="XYZ123")
    *     ),
*      @OA\Response(
*          response=201,
*          description="get facility Successfully",
*          @OA\JsonContent()
*       ),
*      @OA\Response(
*          response=200,
*          description="get facility Successfully",
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
public function facilityDetail(Request $request)
{

    $request->validate(['facilityCode' => 'required|max:10000']);
    $facilityCode = $request->input('facilityCode');
		
    $checkIfValueExits = DB::table('facility')
                        ->where('facility.facilityCode', '=', $facilityCode)
                        ->first();

     if ($checkIfValueExits === null) {

            return response()->json([
            'result' => 'Failed',
            'message' =>  "Data not exists, please try another facility code",
            ]);

    }else{

         $facility = DB::table('facility')
                        ->select('facility.facilityCode as facilityCode',
                                 'facility.facilityName as facilityName',
                                 'facility.locationName as locationName',
                                 'facility.introduction as introduction',
                                 'facility.description as description',
                                 'facility.capacity as capacity',
                                 'facility.status as status', )
                         ->where(['facility.facilityCode' => $facilityCode],
                                 ['location.isDeleted', '=', '0'],)
                         ->first();
                         
    $fasilitas_unit = DB::table('facility_unit')
                       ->select('facility_unit.unitName as unitName',
                                'facility_unit.status as status',
                                'facility_unit.notes as notes', )
                        ->where(['facility_unit.facilityCode' => $facilityCode],
                                ['location.isDeleted', '=', '0'],)
                        ->get();

    $facility->unit = $fasilitas_unit;
        
    

    $fasilitas_images = DB::table('facility_images')
                       ->select('facility_images.imageName as imageName',
                                'facility_images.imagePath as imagePath', )
                        ->where(['facility_images.facilityCode' => $facilityCode],
                                ['facility_images.isDeleted', '=', '0'],)
                        ->get();

    $facility->images = $fasilitas_images;

    return response()->json($facility, 200);

 }


}



/**
 * @OA\put(
 * path="/api/facility",
 * operationId="update facility",
 * tags={"Facility"},
 * summary="Update data facility",
 * description="Update data faciltiy",
 *     @OA\RequestBody(
 *         @OA\JsonContent(* @OA\Examples(
 *        summary="Update data facility",
 *        example = "Update data facility",
 * value = 
*{
*  "id": 1,
*  "facilityCode": "XYZ123",
*  "facilityName": "Kandang Maxi",
*  "locationName": "RPC Permata Hijau Pekanbaru",
*  "introduction": "Kandang maxi Extra bed for you love pet",
*  "description": "Ukuran 8M Cocok untuk tipe anjing besar, seperti golden retriever",
*  "capacity": 1,
*  "status": 1,
*  "unit": {
*    {
*      "id": 1,
*      "facilityCode": "XYZ123",
*      "unitName": "Unit Testing 1",
*      "status": 1,
*      "notes": "Unit Testing 1.1"
*    },
*    {
*      "id": 2,
*      "facilityCode": "XYZ123",
*      "unitName": "Unit Testing 1",
*      "status": 1,
*      "notes": "Unit Testing 1.2"
*    },
*    {
*      "id": 3,
*      "facilityCode": "XYZ123",
*      "unitName": "Unit Testing 2",
*      "status": 1,
*      "notes": "Unit Testing 2.1"
*    },
*    {
*      "id": 4,
*      "facilityCode": "XYZ123",
*      "unitName": "Unit Testing 4",
*      "status": 1,
*      "notes": "Unit Testing 4"
*    }
*  }
*},
*            ),
*        ),
*    ),
*      @OA\Response(
*          response=201,
*          description="Register data facility successfully",
*          @OA\JsonContent()
*       ),
*      @OA\Response(
*          response=200,
*          description="Register data facilty Successfully",
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
public function updateFacility(Request $request)
    {
        DB::beginTransaction();

        $request->validate(['facilityCode' => 'required' ,
                            'facilityName' => 'required',
                            'locationName' => 'required',
                            'capacity' => 'required',
                            'status' => 'required',
                            'introduction' => 'required',
                            'description' => 'required',
                    ]);
    
        try
        {

             DB::table('facility')
               ->where('facilityCode', '=', $request->input('facilityCode'))
               ->update(['facilityName' => $request->input('facilityName'),
                         'locationName' => $request->input('locationName'),
                         'capacity' => $request->input('capacity'),
                         'status' => $request->input('status'),
                         'introduction' => $request->input('introduction'),
                         'description' => $request->input('description'),
                         'updated_at' => now(),
                    ]);

            
            
             /**Delete facility unit*/
             DB::table('facility_unit')->where('facilityCode', '=', $request->input('facilityCode'))->delete();
            
             foreach ($request->unit as $val) {

                DB::table('facility_unit')->insert(['facilityCode' => $request->input('facilityCode'),
                                                    'unitName' => $val['unitName'],
                                                    'status' => $val['status'],
                                                    'notes' => $val['notes'],
                                                    'isDeleted' => 0,
                                                    'created_at' => now(), 
                                                    ]);

            }      
              /**End Delete facility unit*/



            /**Delete facility images*/
            DB::table('facility_unit')->where('facilityCode', '=', $request->input('facilityCode'))->delete();
            
            foreach ($request->unit as $val) {

               DB::table('facility_unit')->insert(['facilityCode' => $request->input('facilityCode'),
                                                   'unitName' => $val['unitName'],
                                                   'status' => $val['status'],
                                                   'notes' => $val['notes'],
                                                   'isDeleted' => 0,
                                                   'created_at' => now(), 
                                                   ]);

            }      
             /**End Delete facility images*/
              


            DB::commit();

            return response()->json([
                'result' => 'success',
            ]);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' =>  $e,
            ]);

        }
  

    }

    

    /**
     * @OA\Get(
     * path="/api/facility/",
     * operationId="facilityMenuHeader",
     * tags={"Facility"},
     * summary="Get facility menu header",
     * description="Get facility menu header",
    * @OA\Parameter(
    *      in="path",
    *      name="request",
    *     @OA\JsonContent(
    *        type="object",
    *        @OA\Property(property="rowPerPage", type="number" , example="1"),
    *        @OA\Property(property="goToPage", type="number", example="11"),
    *        @OA\Property(property="orderValue", type="string" , example="asc"),
    *        @OA\Property(property="orderColumn", type="string", example="facilityName"),
    *        @OA\Property(property="search", type="string" , example=""),
    *     ),
    * ),
     *   @OA\Response(
     *          response=201,
     *          description="Get Data facility Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Get Data facility Successfully",
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
    public function facilityMenuHeader(Request $request)
    {

        $defaultRowPerPage = 5;

        $data = DB::table('facility')
                 ->select('facility.id as id',
                          'facility.facilityCode as facilityCode',
                          'facility.facilityName as facilityName',
                          'facility.locationName as locationName',
                          'facility.capacity as capacity',
                 DB::raw("CASE WHEN facility.status=1 then 'Active' else 'Non Active' end as status" ),)    
                ->where([['facility.isDeleted' ,"=", 0 ]]);

        if ($request->search) {

            $res = $this->Search($request);
            
            if ($res) {
                $data = $data->where($res, 'like', '%' . $request->search . '%');
            } else {
                $data = [];
                return response()->json(['totalPagination' => 0,
                    'data' => $data], 200);
            }

        }

        if ($request->orderColumn && $request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        if ($request->rowPerPage > 0) {
            $defaultRowPerPage = $request->rowPerPage;
        }

        $goToPage = $request->goToPage;

        $offset = ($goToPage - 1) * $defaultRowPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($defaultRowPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
        }

        $total_paging = $count_data / $defaultRowPerPage;
        return response()->json(['totalPagination' => ceil($total_paging), 'data' => $data], 200);

    }



    private function Search($request)
    {
        $columntable = '';

        $data = DB::table('facility')
                 ->select('facility.id as id',
                          'facility.facilityCode as facilityCode',
                          'facility.facilityName as facilityName',
                          'facility.locationName as locationName',
                          'facility.capacity as capacity',
                 DB::raw("CASE WHEN facility.status=1 then 'Active' else 'Non Active' end as status" ),)    
                ->where([['facility.isDeleted' ,"=", 0 ]]);

        if ($request->search) {
            $data = $data->where('facilityName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();
            
        if (count($data)) {
            $temp_column = 'facilityName';
            return $temp_column;
        }  



        $data = DB::table('facility')
                 ->select('facility.id as id',
                          'facility.facilityCode as facilityCode',
                          'facility.facilityName as facilityName',
                          'facility.locationName as locationName',
                          'facility.capacity as capacity',
                 DB::raw("CASE WHEN facility.status=1 then 'Active' else 'Non Active' end as status" ),)    
                ->where([['facility.isDeleted' ,"=", 0 ]]);

        if ($request->search) {
            $data = $data->where('locationName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();
            
        if (count($data)) {
            $temp_column = 'locationName';
            return $temp_column;
        }  



        $data = DB::table('facility')
                 ->select('facility.id as id',
                          'facility.facilityCode as facilityCode',
                          'facility.facilityName as facilityName',
                          'facility.locationName as locationName',
                          'facility.capacity as capacity',
                 DB::raw("CASE WHEN facility.status=1 then 'Active' else 'Non Active' end as status" ),)    
                ->where([['facility.isDeleted' ,"=", 0 ]]);

        if ($request->search) {
             $data = $data->where('capacity', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();
        
        if (count($data)) {
         $temp_column = 'capacity';
         return $temp_column;
        }  

    }






    /**
     * @OA\Get(
     * path="/api/facilityexport",
     * operationId="facilityexport",
     * tags={"Facility"},
     * summary="export facility excel",
     * description="export facility excel",
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
    public function facilityExport(Request $request)
    {

        try
        {
            return Excel::download(new exportFacility, 'Facility.xlsx');

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);

        }

    }


    /**
     * @OA\Get(
     * path="/api/facilitylocation",
     * operationId="facilitylocation",
     * tags={"Facility"},
     * summary="Get Location for dropdown in facility",
     * description="Get Location for dropdown in facility",
     *   @OA\Response(
     *          response=201,
     *          description="Generate Data Location Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Generate Data Location Successfully",
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
    public function facilityLocation(Request $request)
    {

        try
        {

            $getLocationFasilitas = DB::table('location')
                                     ->select('location.id as id',
                                              'location.locationName as locationName', )
                                      ->where('location.isDeleted', '=', '0')
                                      ->get();

            return response()->json($getLocationFasilitas, 200);

        } catch (Exception $e) {

            return response()->json([
                'result' => 'Failed',
                'message' => $e,
            ]);
        }

    }


}
