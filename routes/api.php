<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ImportRegionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('logout', [ApiController::class, 'logout']);

    Route::post('location', [LocationController::class, 'insertLocation']);
    Route::get('location', [LocationController::class, 'getLocationHeader']);
    Route::get('detaillocation', [LocationController::class, 'getLocationDetail']);
    Route::get('datastaticlocation', [LocationController::class, 'getDataStaticLocation']);
    Route::get('provinsilocation', [LocationController::class, 'getProvinsiLocation']);
    Route::get('kabupatenkotalocation', [LocationController::class, 'getKabupatenLocation']);
    Route::get('exportlocation', [LocationController::class, 'exportLocation']);
    Route::delete('location', [LocationController::class, "deleteLocation"]);
    Route::put('location', [LocationController::class, 'updateLocation']);
    Route::post('upload', [ImportRegionController::class, 'uploadRegion']);
    Route::get('datastatic', [DataStaticController::class, 'datastatic']);
    Route::post('datastatic', [LocationController::class, 'insertdatastatic']);
    Route::delete('datastatic', [DataStaticController::class, 'datastaticlocation']);
    Route::post('uploadexceltest', [LocationController::class, 'uploadexceltest']);
    Route::get('facility', [FacilityController::class, 'facilityMenuHeader']);
    Route::get('facilityexport', [FacilityController::class, 'facilityExport']);
    Route::get('facilitylocation', [FacilityController::class, 'facilityLocation']);
    Route::get('facilitydetail', [FacilityController::class, 'facilityDetail']);
    Route::post('facility', [FacilityController::class, 'createFacility']);
    Route::put('facility', [FacilityController::class, 'updateFacility']);
    Route::delete('facility', [FacilityController::class, 'deleteFacility']);
    
    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

    
    Route::post('productsupplier', [ProductController::class, 'addProductSupplier']);
    Route::post('productbrand', [ProductController::class, 'addProductBrand']);
    Route::post('productstatus', [ProductController::class, 'addProductStatus']);
    Route::post('product', [ProductController::class, 'createNewProduct']);
    Route::delete('product', [ProductController::class, 'deleteProduct']);
    Route::get('product', [ProductController::class, 'indexProduct']);
    Route::get('productdetail', [ProductController::class, 'getProductDetail']);
});
