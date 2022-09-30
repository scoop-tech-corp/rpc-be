<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Imports\UsersImport;
use App\Exports\exportValue;
use App\Exports\exportFacility;
use Maatwebsite\Excel\Facades\Excel;

class FasilitasController extends Controller
{
  
    public function create(Request $request)
    {
        DB::beginTransaction();

        try
        {
            $request->validate([
                'fasilitasName' => 'required',
                'locationName' => 'required',
                'capacity' => 'required',
                'status' => 'required',
                'introduction' => 'required',
                'description' => 'required',
            ]);



            $getvaluesp = strval(collect(DB::select('call generate_codeFacility'))[0]->randomString);

             DB::table('fasilitas')->insert([
                        'codeFasilitas' => $getvaluesp,
                        'fasilitasName' => $request->input('fasilitasName'),
                        'locationName' => $request->input('locationName'),
                        'capacity' => $request->input('capacity'),
                        'status' => $request->input('status'),
                        'introduction' => $request->input('introduction'),
                        'description' => $request->input('description'),               
                        'isDeleted' => 0,
                    ]);
            
                foreach ($request->unit as $val) {
                    $unitname = strval(array_keys($val)[0]);

                   foreach ($val as $key=>$asd) {


                    foreach ($asd as $columnval) {
                        DB::table('fasilitas_unit')->insert([
                            'codeFasilitas' => $getvaluesp,
                            'unitName' => $unitname ,
                            'status' => $columnval['status'],
                            'notes' => $columnval['notes'],
                            'isDeleted' => 0,
                        ]); 
                    }
                 
                 
                   }
                }
             
            DB::commit();

            return ('SUCCESS');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

    }



     /**
     * @OA\Get(
     * path="/api/fasilitas",
     * operationId="fasilitas",
     * tags={"Fasilitas"},
     * summary="Get Fasilitas",
     * description="get Fasilitas",
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
    *                         example="fasilitasName"
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
    public function getheader(Request $request)
    {

        $rowPerPage = 5;
        
        $data = DB::table('fasilitas')
                 ->select('fasilitas.id as id',
                          'fasilitas.codeFasilitas as codeFasilitas',
                          'fasilitas.fasilitasName as fasilitasName',
                          'fasilitas.locationName as locationName',
                          'fasilitas.capacity as capacity',
                          'fasilitas.status as status', )
                 ->where([['fasilitas.isDeleted', '=', '0']]);


        if ($request->search) {
           
            $data = $data->where('fasilitas.fasilitasName', 'like', '%' . $request->search . '%')
                            ->orwhere('fasilitas.locationName', 'like', '%' . $request->search . '%');
        }

        // if ($request->orderColumn) {
        //     $data = $data->orderBy($request->orderColumn['fieldName'], $request->orderColumn['value']);
        // }

        if ($request->orderColumn && $request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
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

    }


     /**
     * @OA\Get(
     * path="/api/exportfasilitas",
     * operationId="exportfasilitas",
     * tags={"Fasilitas"},
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
           
        try
        {
            //danny
            
            return Excel::download(new exportFacility, 'Facility.xlsx');

        } catch (Exception $e) {

            DB::rollback();

            return ('FAILED');

        }

        
    }





 /**
     * @OA\Get(
     * path="/api/locationfasilitas",
     * operationId="locationfasilitas",
     * tags={"Fasilitas"},
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
    public function getLocationFasilitas(Request $request){
           
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
                'success' => 'Failed',
                'token' =>  $e,
            ]);
        }

        
    }



/**
     * @OA\Get(
     * path="/api/detailfasilitas",
     * operationId="detailfasilitas",
     * tags={"Fasilitas"},
     * summary="Get Fasilitas Detail",
     * description="Get Fasilitas Detail",
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
    public function fasilitasdetail(Request $request)
    {
        $request->validate([
             'codeFasilitas' => 'required|string|max:8',
        ]);

        $codeFasilitas = $request->input('codeFasilitas');

        $param_fasilitas = DB::table('fasilitas')
            ->select('fasilitas.id as id',
                'fasilitas.codeFasilitas as codeFasilitas',
                'fasilitas.fasilitasName as fasilitasName',
                'fasilitas.locationName as locationName',
                'fasilitas.capacity as capacity',
                'fasilitas.status as status',)
            ->where('fasilitas.codeFasilitas', '=', $codeFasilitas)
            ->first();
  
        $fasilitas_unit = DB::table('fasilitas_unit')
            ->select('fasilitas_unit.unitName as unitName',
                     'fasilitas_unit.notes as notes',)
            ->where('fasilitas_unit.codeFasilitas', '=', $codeFasilitas)
            ->get();

        if ($fasilitas_unit != null) {
            $param_fasilitas->unit = $fasilitas_unit;
        }

        // $map_location = DB::table('location')
        //     ->select('location.locationName as locationName')
        //     ->get();

        // $result = json_decode($map_location, true);
     
        return response()->json($param_fasilitas, 200);
            
    }




}
