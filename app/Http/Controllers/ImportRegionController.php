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
  

    public function upload(Request $request)
    {

            $request->validate([
                'provinsi' => 'required|max:10000',
                'kecamatan' => 'required|max:10000',
                'kabupaten' => 'required|max:10000',
                'kelurahan' => 'required|max:10000',
            ]);

        try{

             if ($request->input('provinsi'))
                Excel::import(new RegionImport, $request->input('provinsi'));

            if ($request->input('kecamatan'))
                Excel::import(new KecamatanImport, $request->input('kecamatan'));

             if ($request->input('kabupaten'))
               Excel::import(new KabupatenImport, $request->input('kabupaten'));

            if ($request->input('kelurahan'))
               Excel::import(new KelurahanImport, $request->input('kelurahan'));

            
              return 'SUCCESS';


        } catch (Exception $e) {

            Excel::rollback();
            return 'FAILED';
          
        }

    }

}
