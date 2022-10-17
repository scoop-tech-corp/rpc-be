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
     * tags={"Data Static"},
     * summary="Delete Data Static Location",
     * description="Delete data static here , need id data static",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Delete data static",
     *        example = "delete id 1",
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
     *          description="Delete data static Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Delete data static Successfully",
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

            DB::table('data_static')
                ->where('id', '=', $Request['id'], )
                ->update(['isDeleted' => 1,]);

            DB::commit();

            return response()->json([
                'result' => 'success',
            ]);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' =>  $e,
            ]);

        }

    }


/**
     * @OA\Get(
     * path="/api/datastatic",
     * operationId="datastatic",
     * tags={"Data Static"},
     * summary="Get Data Static",
     * description="Get Data Static",
    *  @OA\Parameter(
    *     name="body",
    *     in="path",
    *     required=true,
    *     @OA\JsonContent(
    *        type="object",
    *        @OA\Property(property="orderby", type="text",example="asc"),
    *        @OA\Property(property="column", type="text",example="value"),
    *        @OA\Property(property="keyword", type="text",example="Messenger"),
    *        @OA\Property(property="page", type="number",example="1"),
    *        @OA\Property(property="total_per_page", type="number",example="14"),
    *     ),
    * ),
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
        
        $defaultRowPerPage = 5;

        $data = DB::table('data_static')
                 ->select('id',
                          'value',
                          'name',);

        if ($request->search) {

            $data = $data->where('value', 'like', '%' . $request->search . '%')
                         ->orwhere('name', 'like', '%' . $request->search . '%');
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
         }else {
             $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
         }

         $total_paging = $count_data / $defaultRowPerPage;

         return response()->json(['total_paging' => ceil($total_paging),'data' => $data], 200);

    }


}
