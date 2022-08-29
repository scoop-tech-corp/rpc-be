<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class DataStaticController extends Controller
{


 /**
     * @OA\Delete(
     * path="/api/datastaticlocation",
     * operationId="datastaticlocation",
     * tags={"Delete Data Static Location"},
     * summary="Delete Data Static Location",
     * description="Delete data static here , need id data static",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Delete Branch",
     *        example = "Delete Branch",
     *       value = {
     *           "id":1,
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer")
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

    public function datastaticlocation(Request $Request)
    {

        DB::beginTransaction();
        try
        {

            info($Request['id']);
            DB::table('data_static')
                ->where('id', '=', $Request['id'], )
                ->update(['isDeleted' => 1,]);

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
     * path="/api/datastatic",
     * operationId="datastatic",
     * tags={"Get Data Static"},
     * summary="Get Data Static",
     * description="get menu data static",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Delete Branch",
     *        example = "Delete Branch",
     *       value = {
     *           "id":1,
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer")
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

     /**
     * @OA\Get(
     * path="/api/datastatic",
     * operationId="datastatic",
     * tags={"Get Data Static"},
     * summary="Get Data Static",
     * description="get menu data static",
     *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Get Data Static",
     *         required=true,
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Get Data Static Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Get Data Static Successfully",
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

    public function datastatic(Request $request)
    {
        
        $items_per_page = 5;

        $data = DB::table('data_static')
                ->select('id',
                         'value',
                         'name',);

        if ($request->keyword) {

            $data = $data->where('value', 'like', '%' . $request->keyword . '%')
                         ->orwhere('name', 'like', '%' . $request->keyword . '%');
        }
        
        if($request->column){
            $data = $data->orderBy($request->column, $request->orderby);
        }
        
       if($request->total_per_page > 0){
       
            $items_per_page = $request->total_per_page;
        }
           
        $page = $request->page;

        $offset = ($page - 1) * $items_per_page;

         $count_data = $data->count();
         $count_result = $count_data - $offset;

         if ($count_result < 0) {
         $data = $data->offset(0)->limit($items_per_page)->get();
         }else {
             $data = $data->offset($offset)->limit($items_per_page)->get();
         }

         $total_paging = $count_data / $items_per_page;

         return response()->json(['total_paging' => ceil($total_paging),
            'data' => $data], 200);

    }


}
