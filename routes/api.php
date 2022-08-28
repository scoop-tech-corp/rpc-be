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
    Route::post("deletetelepon", [LocationController::class, "deletetelepon"]);
    Route::post("deleteemail", [LocationController::class, "deleteemail"]);
    Route::post("deletemessenger", [LocationController::class, "deletemessenger"]);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);
    Route::get('location', [LocationController::class, 'index']);
    Route::get('getlocationorderbyid', [LocationController::class, 'getlocationorderbyid']);
    Route::get('getlocationorderbyname', [LocationController::class, 'getlocationorderbyname']);
    Route::get('getlocationdetailbyid', [LocationController::class, 'getlocationdetailbyid']);
    Route::get('getlocationorderbyalamatjalan', [LocationController::class, 'getlocationorderbyalamatjalan']);
    Route::post('insertdatastatictelepon', [LocationController::class, 'insertdatastatictelepon']);
    Route::post('insertdatastaticpemakaian', [LocationController::class, 'insertdatastaticpemakaian']);
    Route::post('insertdatastaticmessenger', [LocationController::class, 'insertdatastaticmessenger']);

    Route::get('getindexdatastatic', [DataStaticController::class, 'getindexdatastatic']);
    Route::get('getindexdatastaticsortid', [DataStaticController::class, 'getindexdatastaticsortid']);
    Route::get('getindexdatastaticsortname', [DataStaticController::class, 'getindexdatastaticsortname']);
    Route::get('getindexdatastaticsortvalue', [DataStaticController::class, 'getindexdatastaticsortvalue']);
    Route::post('deletedatastatic', [DataStaticController::class, 'deletedatastatic']);
    Route::post('insertdatastatic', [DataStaticController::class, 'insertdatastatic']);

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

});
