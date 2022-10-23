<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Exports\exportFacility;
use Maatwebsite\Excel\Facades\Excel;

class FacilityController extends Controller
{
   
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

        if ($request->unit) {

            $arraunit= json_decode($request->unit,true);
       
            foreach ($arraunit as $val) {

                DB::table('facility_unit')->insert(['facilityCode' => $getvaluesp,
                                                    'unitName' => $val['unitName'],
                                                    'status' => $val['status'],
                                                    'notes' => $val['notes'],
                                                    'isDeleted' => 0,
                                                    'created_at' => now(), 
                                                  ]);

            }
        }


        if ($request->hasfile('images')) {  

            $files[] = $request->file('images');
            $json_array = json_decode($request->imagesName,true);
            $int = 0 ;

               foreach ($files as $file) {

                  foreach ($file as $fil) {
                  
                     $name = $fil->hashName();                 
                     $fil->move(public_path() . '/FacilityImages/', $name);
 
                     $fileName = "/FacilityImages/" . $name;
 
                         DB::table('facility_images')
                         ->insert(['facilityCode' => $getvaluesp,
                                     'labelName' => $json_array[$int]['name'],
                                     'realImageName' => $fil->getClientOriginalName(),
                                     'imageName' => $name,
                                     'imagePath' => $fileName,
                                     'isDeleted' => 0,
                                     'created_at' => now()
                                 ]);
                    $int = $int + 1;
                 }
             }
         
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
                     
                DB::table('facility_unit')
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


public function facilityDetail(Request $request)
{

    $request->validate(['facilityCode' => 'required|max:10000']);
    $facilityCode = $request->input('facilityCode');
		
    $checkIfValueExits = DB::table('facility')
                           ->where([['facility.facilityCode', '=',  $facilityCode,],
                                    ['facility.isDeleted', '=', '0']]) 
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
                       ->select('facility_images.labelName as labelName',
                                'facility_images.realImageName as realImageName',
                                'facility_images.imageName as imageName',
                                'facility_images.imagePath as imagePath', )
                        ->where(['facility_images.facilityCode' => $facilityCode],
                                ['facility_images.isDeleted', '=', '0'],)
                        ->get();
                        
    $facility->images = $fasilitas_images;

    return response()->json($facility, 200);

 }

}



public function searchImageFacility(Request $request)
{

    $request->validate(['facilityCode' => 'required|max:10000']);

    $checkIfValueExits = DB::table('facility_images')
                        ->where([['facility_images.facilityCode', '=', $request->input('facilityCode')],
                                ['facility_images.isDeleted', '=', '0']])
                        ->first();

    if ($checkIfValueExits === null) {

            return response()->json([
            'result' => 'Failed',
            'message' =>  "Data not exists",
            ]);

    }else{

        $images = DB::table('facility_images')
                ->select('facility_images.labelName as labelName',
                        'facility_images.realImageName as realImageName',
                        'facility_images.imageName as imageName',
                        'facility_images.imagePath as imagePath',)
                ->where([['facility_images.facilityCode', '=', $request->input('facilityCode')],
                        ['facility_images.isDeleted', '=', '0']]);
              

        if ($request->name) {
            $res = $this->SearchImages($request);

            if ($res) {
                $images = $images->where($res, 'like', '%' . $request->name . '%');
            } else {
                $images = [];
                return response()->json($images, 200);
            }
        }
        $images = $images->orderBy('facility_images.created_at', 'desc');
        $images = $images->get();
        return response()->json(['images' => $images],200);


    }

}


private function SearchImages($request)
{

   
    $data = DB::table('facility_images')
            ->select('facility_images.labelName as labelName',
                     'facility_images.realImageName as realImageName',
                     'facility_images.imageName as imageName',
                     'facility_images.imagePath as imagePath',)
            ->where([['facility_images.facilityCode', '=', $request->facilityCode],
                    ['facility_images.isDeleted', '=', '0']]);

    if ($request->name) {
        $data = $data->where('facility_images.labelName', 'like', '%' . $request->name . '%');
    }

    $data = $data->get();
        
    if (count($data)) {
        $temp_column = 'facility_images.labelName';
        return $temp_column;
    }    
   
    // $data = DB::table('facility_images')
    //         ->select('facility_images.labelName as labelName',
    //                 'facility_images.realImageName as realImageName',
    //                 'facility_images.imageName as imageName',
    //                 'facility_images.imagePath as imagePath',)
    //         ->where([['facility_images.facilityCode', '=', $request->facilityCode],
    //                 ['facility_images.isDeleted', '=', '0']]);

    // if ($request->name) {
    //     $data = $data->where('facility_images.realImageName', 'like', '%' . $request->name . '%');
    // }

    // $data = $data->get();
        
    // if (count($data)) {
    //     $temp_column = 'facility_images.realImageName';
    //     return $temp_column;
    // } 

}



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
                
            if($request->unit){

                $arraunit= json_decode($request->unit,true);
                
                foreach ($arraunit as $val) {

                    DB::table('facility_unit')->insert(['facilityCode' => $request->input('facilityCode'),
                                                        'unitName' => $val['unitName'],
                                                        'status' => $val['status'],
                                                        'notes' => $val['notes'],
                                                        'isDeleted' => 0,
                                                        'created_at' => now(), 
                                                        ]);

                }    

            }  
              /**End Delete facility unit*/
              
            /**Delete facility images*/
            DB::table('facility_images')->where('facilityCode', '=', $request->input('facilityCode'))->delete();
        
            if ($request->hasfile('images')) {  

                $files[] = $request->file('images');
                $json_array = json_decode($request->imagesName,true);
                $int = 0 ;
    
                    foreach ($files as $file) {
    
                        foreach ($file as $fil) {
                        
                            $name = $fil->hashName();                 
                            $fil->move(public_path() . '/FacilityImages/', $name);
        
                            $fileName = "/FacilityImages/" . $name;
        
                                DB::table('facility_images')
                                ->insert(['facilityCode' => $request->input('facilityCode'),
                                            'labelName' => $json_array[$int]['name'],
                                            'realImageName' => $fil->getClientOriginalName(),
                                            'imageName' => $name,
                                            'imagePath' => $fileName,
                                            'isDeleted' => 0,
                                            'created_at' => now()
                                        ]);
                        $int = $int + 1;
                        }
                    }
                
                }
       
             /**End Delete facility images*/
           
            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'successfuly update new facility'
            ]);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'result' => 'failed',
                'message' =>  $e,
            ]);

        }
  

    }

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

        $data = $data->orderBy('facility.created_at', 'desc');

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
