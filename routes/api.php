<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FasilitasController;
use App\Http\Controllers\ImportRegionController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function () {

    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('location', [LocationController::class, 'location']);
    Route::post('location', [LocationController::class, 'create']);
    Route::put('location', [LocationController::class, 'update']);
    Route::get('detaillocation', [LocationController::class, 'locationdetail']);
    Route::delete("location", [LocationController::class, "deletecontactlocation"]);
    Route::delete("location", [LocationController::class, "delete"]);

    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::post('datastatic', [LocationController::class, 'insertdatastatic']);
    Route::delete('datastatic', [DataStaticController::class, 'datastaticlocation']);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);

    Route::get('region', [LocationController::class, 'region']);
    Route::post('upload', [ImportRegionController::class, 'upload']);
    Route::post('fasilitas', [FasilitasController::class, 'create']);
    Route::get('fasilitas', [FasilitasController::class, 'getheader']);
    Route::get('detailfasilitas', [FasilitasController::class, 'fasilitasdetail']);
    


    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

});
