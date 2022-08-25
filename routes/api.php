<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DataStaticController;

Route::post('login', [ApiController::class, 'authenticate']);
Route::post('register', [ApiController::class, 'register']);
Route::group(['middleware' => ['jwt.verify']], function() {


Route::post('insertlocation', [LocationController::class, 'create']);
Route::post('updatelocation', [LocationController::class, 'update']);
Route::post("deletelocation", [LocationController::class, "delete"]);
Route::post("deletetelepon", [LocationController::class, "deletetelepon"]);
Route::post("deleteemail", [LocationController::class, "deleteemail"]);
Route::post("deletemessenger", [LocationController::class, "deletemessenger"]);
Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);
Route::get('getlocation', [LocationController::class, 'index']);
Route::get('insertdatastatic', [LocationController::class, 'insertdatastatic']);

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
Route::put('update/{product}',  [ProductController::class, 'update']);
Route::delete('delete/{product}',  [ProductController::class, 'destroy']);



});