<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function () {

    Route::get('location', [LocationController::class, 'location']);
    Route::get('locationdetail', [LocationController::class, 'locationdetail']);
    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::post('insertlocation', [LocationController::class, 'create']);
    Route::post('insertdatastatic', [LocationController::class, 'insertdatastatic']);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);
    Route::put('updatelocation', [LocationController::class, 'update']);
    Route::delete("deletecontactlocation", [LocationController::class, "deletecontactlocation"]);
    Route::delete("deletelocation", [LocationController::class, "delete"]);
    Route::delete('datastaticlocation', [DataStaticController::class, 'datastaticlocation']);
   

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

});
