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
    Route::post('location', [LocationController::class, 'create']);

    Route::get('locationnew', [LocationController::class, 'createNew']);

    Route::get('locationprovinsi', [LocationController::class, 'locationProvinsi']);
    Route::get('locationkabupatenkota', [LocationController::class, 'locationKabupaten']);

    Route::get('location', [LocationController::class, 'location']);
    Route::delete('location', [LocationController::class, "delete"]);
    Route::put('location', [LocationController::class, 'update']);
    Route::get('detaillocation', [LocationController::class, 'locationDetail']);
    Route::post('upload', [ImportRegionController::class, 'upload']);
    Route::get('export', [LocationController::class, 'export']);

   

    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::post('datastatic', [LocationController::class, 'insertdatastatic']);
    Route::delete('datastatic', [DataStaticController::class, 'datastaticlocation']);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);

    Route::post('facility', [FasilitasController::class, 'createFacility']);
    Route::put('facility', [FasilitasController::class, 'updateFacility']);
    Route::delete('facility', [FasilitasController::class, 'deleteFacility']);
    Route::get('facilitydetail', [FasilitasController::class, 'facilityDetail']);
    Route::get('facilitylocation', [FasilitasController::class, 'facilityLocation']);
    Route::get('facility', [FasilitasController::class, 'facilityMenuHeader']);
    Route::get('facilityexport', [FasilitasController::class, 'facilityExport']);

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
