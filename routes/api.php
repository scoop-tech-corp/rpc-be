<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('register', [ApiController::class, 'register']);
    Route::post('insertlocation', [LocationController::class, 'create']);
    Route::post('updatelocation', [LocationController::class, 'update']);
    Route::post("deletelocation", [LocationController::class, "delete"]);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);
    
    Route::post("deletecontactlocation", [LocationController::class, "deletecontactlocation"]);
    Route::get('location', [LocationController::class, 'location']);
    Route::get('locationdetail', [LocationController::class, 'locationdetail']);
    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::delete('datastaticlocation', [DataStaticController::class, 'datastaticlocation']);
    Route::post('insertdatastatic', [LocationController::class, 'insertdatastatic']);

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

});
