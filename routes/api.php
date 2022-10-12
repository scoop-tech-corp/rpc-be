<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\FasilitasController;
use App\Http\Controllers\ImportRegionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('logout', [ApiController::class, 'logout']);

    Route::post('location', [LocationController::class, 'insertLocation']);
    Route::get('location/{request}', [LocationController::class, 'getLocationHeader']);
    Route::get('detaillocation/{codeLocation}', [LocationController::class, 'getLocationDetail']);
    Route::get('datastaticlocation', [LocationController::class, 'getDataStaticLocation']);
    Route::get('provinsilocation', [LocationController::class, 'getProvinsiLocation']);
    Route::get('kabupatenkotalocation/{provinceId}', [LocationController::class, 'getKabupatenLocation']);
    Route::get('exportlocation', [LocationController::class, 'exportLocation']);
    
    Route::delete('location', [LocationController::class, "deleteLocation"]);
    Route::put('location', [LocationController::class, 'updateLocation']);
    Route::post('upload', [ImportRegionController::class, 'uploadRegion']);

    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::post('datastatic', [LocationController::class, 'insertdatastatic']);
    Route::delete('datastatic', [DataStaticController::class, 'datastaticlocation']);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);



    Route::get('facility/{request}', [FasilitasController::class, 'facilityMenuHeader']);
    Route::get('facilityexport', [FasilitasController::class, 'facilityExport']);
    Route::get('facilitylocation', [FasilitasController::class, 'facilityLocation']);
    Route::get('facilitydetail/{facilityCode}', [FasilitasController::class, 'facilityDetail']);
    Route::post('facility', [FasilitasController::class, 'createFacility']);
    
    Route::put('facility', [FasilitasController::class, 'updateFacility']);
    Route::delete('facility', [FasilitasController::class, 'deleteFacility']);
    
    
   
    

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

    Route::post('productSupplier', [ProductController::class, 'addProductSupplier']);
    Route::post('productBrand', [ProductController::class, 'addProductBrand']);
});
