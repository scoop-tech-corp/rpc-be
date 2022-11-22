<?php

namespace App\Http\Controllers;

use DB;
use App\Imports\RegionImport;
use App\Imports\KabupatenImport;
use App\Imports\KecamatanImport;
use App\Imports\KelurahanImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportRegionController extends Controller
{
  
/**
     * @OA\Post(
     * path="/api/upload",
     * operationId="Bulk Insert Region",
     * tags={"Bulk Mapping Region"},
     * summary="Notes: Upload mapping data only need to execute once",
     * description=" Upload only need execute once",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"provinsi","kabupaten"},
     *               @OA\Property(property="provinsi", type="file"),
     *               @OA\Property(property="kabupaten", type="file"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Import Provinsi and Kabupaten Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Import Provinsi and Kabupaten Successfully",
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
    public function uploadRegion(Request $request)
    {

        try{
           
            set_time_limit(500);

            Excel::import(new RegionImport, $request->file('provinsi')->store('provinsi'));
            Excel::import(new KabupatenImport, $request->file('kabupaten')->store('kabupaten'));

           return response()->json([
                'result' => 'success',
                'message' => 'Success Reupload Region',
            ]);

        } catch (Exception $e) {

            Excel::rollback();
           
            return response()->json([
                'result' => 'failed',
                'token' =>  $e,
            ]);
          
        }

    }

}
