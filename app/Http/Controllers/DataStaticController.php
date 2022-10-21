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


            foreach($Request->id as $val){

                DB::table('data_static')
                    ->where('id', '=', $val, )
                    ->update(['isDeleted' => 1,]);

                DB::commit();
                
             }

            return response()->json([
                'result' => 'success',
                'message' => 'Successfully deleted data static'
            ]);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'Failed',
                'message' =>  $e,
            ]);

        }

    }


    public function datastatic(Request $request)
    {
        
        $defaultRowPerPage = 5;

        $data = DB::table('data_static')
                 ->select('id','value','name',)
                 ->where('isDeleted', '=', '0');

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

        $data = $data->orderBy('created_at', 'desc');

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

        $data = DB::table('data_static')
                 ->select('id','value','name',)
                 ->where('isDeleted', '=', '0');

        if ($request->search) {
            $data = $data->where('value', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();
            
        if (count($data)) {
            $temp_column = 'value';
            return $temp_column;
        }  

        $data = DB::table('data_static')
                 ->select('id','value','name',)
                 ->where('isDeleted', '=', '0');

        if ($request->search) {
            $data = $data->where('name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();
            
        if (count($data)) {
            $temp_column = 'name';
            return $temp_column;
        }  

    }

}
