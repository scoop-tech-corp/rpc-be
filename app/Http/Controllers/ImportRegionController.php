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
     * description="The data is way to huge to put in seeder, and data raw it self already represent as CSV<br>
     *              so data already delimeted to column and save as .xlsx ( you can find the file in the folder<br>
     *              Filemapping, except for kelurahan, the data is around 80.000 so it take lot of time to bulk<br>
     *              already tried to partition but still didn't work) ",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Bulk Insert Mapping Region",
     *        example = "Bulk Insert Mapping Region include : provinsi, kecamatan, kabupaten, kelurahan",
     *          value = {
     *          "provinsi": "D:\\PROJECT\\LARAVEL\\pos-rpc\\app\\Filemapping\\Provinsi.xlsx",
     *          "kecamatan": "D:\\PROJECT\\LARAVEL\\pos-rpc\\app\\Filemapping\\Kecamatan.xlsx",
     *          "kabupaten": "D:\\PROJECT\\LARAVEL\\pos-rpc\\app\\Filemapping\\Kabupaten.xlsx",
     *          "kelurahan": "D:\\PROJECT\\LARAVEL\\pos-rpc\\app\\Filemapping\\Kelurahan.xlsx",
     *           },
     *          )),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"provinsi","kecamatan","kabupaten","kelurahan"},
     *               @OA\Property(property="provinsi", type="file"),
     *               @OA\Property(property="kecamatan", type="file"),
     *               @OA\Property(property="kabupaten", type="file"),
     *               @OA\Property(property="kelurahan", type="file"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Register Fasilitas Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Register Fasilitas Successfully",
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
    public function upload(Request $request)
    {

            // $request->validate([
            //     'provinsi' => 'required|max:10000',
            //     'kecamatan' => 'required|max:10000',
            //     'kabupaten' => 'required|max:10000',
            //     'kelurahan' => 'required|max:10000',
            // ]);

        try{
            // set_time_limit(500);

            //  if('csv' == $request->input('extention')) {     
            //     $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            //   } else if('xls' == $request->input('extention')) {     
            //     $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            //   } else     
            //     $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

            //  if ($request->input('provinsi'))
            //     Excel::import(new RegionImport, $request->input('provinsi'));

            // if ($request->input('kecamatan'))
            //     Excel::import(new KecamatanImport, $request->input('kecamatan'));

            //  if ($request->input('kabupaten'))
            //    Excel::import(new KabupatenImport, $request->input('kabupaten'));

            // if ($request->input('kelurahan'))
            //    Excel::import(new KelurahanImport, $request->input('kelurahan'));

            Excel::import(new RegionImport, $request->file('provinsi')->store('provinsi'));
            Excel::import(new KecamatanImport, $request->file('kecamatan')->store('kecamatan'));
            Excel::import(new KabupatenImport, $request->file('kabupaten')->store('kabupaten'));


              return 'SUCCESS';


        } catch (Exception $e) {

            Excel::rollback();
            return 'FAILED';
          
        }

    }

}
