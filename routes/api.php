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
    Route::post('location', [LocationController::class, 'create']);

    Route::post('location', [LocationController::class, 'createNew']);

    Route::get('location', [LocationController::class, 'location']);
    Route::delete('location', [LocationController::class, "delete"]);
    Route::delete("locationcontact", [LocationController::class, "deletecontactlocation"]);
    Route::put('location', [LocationController::class, 'update']);
    Route::get('detaillocation', [LocationController::class, 'locationdetail']);
    Route::post('upload', [ImportRegionController::class, 'upload']);
    Route::get('export', [LocationController::class, 'export']);
    Route::get('region', [LocationController::class, 'region']);
    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::post('datastatic', [LocationController::class, 'insertdatastatic']);
    Route::delete('datastatic', [DataStaticController::class, 'datastaticlocation']);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);
    Route::post('fasilitas', [FasilitasController::class, 'create']);
    Route::get('fasilitas', [FasilitasController::class, 'getheader']);
    Route::get('detailfasilitas', [FasilitasController::class, 'fasilitasdetail']);
    Route::get('exportfasilitas', [FasilitasController::class, 'export']);
    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

    Route::post('productSupplier', [ProductController::class, 'addProductSupplier']);

});
