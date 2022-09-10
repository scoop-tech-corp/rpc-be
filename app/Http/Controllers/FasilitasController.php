<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Imports\UsersImport;
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
     *        @OA\Property(property="orderby", type="text",example="asc"),
     *        @OA\Property(property="column", type="text",example="fasilitasName"),
     *        @OA\Property(property="keyword", type="text",example="RPC"),
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
    public function getheader(Request $request)
    {

        $items_per_page = 5;
        
        $data = DB::table('fasilitas')
            ->select('fasilitas.id as id',
                'fasilitas.codeFasilitas as codeFasilitas',
                'fasilitas.fasilitasName as fasilitasName',
                'fasilitas.locationName as locationName',
                'fasilitas.capacity as capacity',
                'fasilitas.status as status', );


                //info($request->keyword != "");

        if ($request->keyword) {
            info("keyword,");
            $data = $data->where('fasilitas.fasilitasName', 'like', '%' . $request->keyword . '%')
                ->orwhere('fasilitas.locationName', 'like', '%' . $request->keyword . '%')
                ->orwhere('fasilitas.capacity', 'like', '%' . $request->keyword . '%')
                ->orwhere('fasilitas.status', 'like', '%' . $request->keyword . '%');
        }

        if ($request->column) {
            info("column,");
            $data = $data->orderBy($request->column, $request->orderby);
        }

        if ($request->total_per_page > 0) {
            info("perpage,");
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
