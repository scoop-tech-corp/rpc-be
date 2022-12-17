<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ImportRegionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Product\ProductClinicController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductInventoryController;
use App\Http\Controllers\Product\ProductSellController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function () {

    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('locationImages', [LocationController::class, 'searchImageLocation']);
    Route::post('location', [LocationController::class, 'insertLocation']);
    Route::get('location', [LocationController::class, 'getLocationHeader']);
    Route::get('detaillocation', [LocationController::class, 'getLocationDetail']);
    Route::get('datastaticlocation', [LocationController::class, 'getDataStaticLocation']);
    Route::get('provinsilocation', [LocationController::class, 'getProvinsiLocation']);
    Route::get('kabupatenkotalocation', [LocationController::class, 'getKabupatenLocation']);
    Route::get('exportlocation', [LocationController::class, 'exportLocation']);
    Route::delete('location', [LocationController::class, "deleteLocation"]);

    Route::get('location/list', [LocationController::class, 'locationList']);

    Route::put('location', [LocationController::class, 'updateLocation']);
    Route::put('facility', [FacilityController::class, 'updateFacility']);

    Route::post('imagelocation', [LocationController::class, 'uploadImageLocation']);
    Route::post('imagefacility', [FacilityController::class, 'uploadImageFacility']);

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
    Route::delete('facility', [FacilityController::class, 'deleteFacility']);
    Route::get('facilityimages', [FacilityController::class, 'searchImageFacility']);
    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('create', [ProductController::class, 'store']);
    Route::put('update/{product}', [ProductController::class, 'update']);
    Route::delete('delete/{product}', [ProductController::class, 'destroy']);

    Route::post('product/supplier', [ProductController::class, 'addProductSupplier']);
    Route::get('product/supplier', [ProductController::class, 'IndexProductSupplier']);

    Route::post('product/brand', [ProductController::class, 'addProductBrand']);
    Route::get('product/brand', [ProductController::class, 'IndexProductBrand']);

    Route::post('productstatus', [ProductController::class, 'addProductStatus']);
    Route::post('product', [ProductController::class, 'createNewProduct']);
    Route::delete('product', [ProductController::class, 'deleteProduct']);
    Route::get('product', [ProductController::class, 'indexProduct']);
    Route::get('productdetail', [ProductController::class, 'getProductDetail']);

    //MODULE PRODUCT
    //list produk
    Route::get('product/sell', [ProductSellController::class, 'Index']);
    Route::get('product/sell/detail', [ProductSellController::class, 'Detail']);
    Route::post('product/sell', [ProductSellController::class, 'Create']);
    Route::put('product/sell', [ProductSellController::class, 'Update']);
    Route::delete('product/sell', [ProductSellController::class, 'Delete']);

    Route::get('product/clinic', [ProductClinicController::class, 'index']);
    Route::get('product/clinic/detail', [ProductClinicController::class, 'detail']);
    Route::post('product/clinic', [ProductClinicController::class, 'Create']);
    Route::put('product/clinic', [ProductClinicController::class, 'Update']);
    Route::delete('product/clinic', [ProductClinicController::class, 'Delete']);

    Route::get('product/inventory', [ProductInventoryController::class, 'index']);
    Route::get('product/inventory/history/office', [ProductInventoryController::class, 'indexHistoryOffice']);
    Route::get('product/inventory/history/admin', [ProductInventoryController::class, 'indexHistoryAdmin']);
    Route::get('product/inventory/admin', [ProductInventoryController::class, 'indexAdmin']);
    Route::get('product/inventory/office', [ProductInventoryController::class, 'indexOffice']);
    
    Route::get('product/inventory/detail', [ProductInventoryController::class, 'detail']);

    Route::post('product/inventory', [ProductInventoryController::class, 'create']);

    Route::put('product/inventory', [ProductInventoryController::class, 'update']);
    Route::put('product/inventory/office', [ProductInventoryController::class, 'updateOffice']);
    Route::put('product/inventory/admin', [ProductInventoryController::class, 'updateAdmin']);
    
    Route::delete('product/inventory', [ProductInventoryController::class, 'delete']);

    //product category
    Route::get('product/category', [ProductController::class, 'IndexProductCategory']);
    Route::post('product/category', [ProductController::class, 'CreateProductCategory']);

    Route::get('product/sell/dropdown', [ProductController::class, 'IndexProductSell']);
    Route::get('product/clinic/dropdown', [ProductController::class, 'IndexProductClinic']);

    Route::post('product/usage', [ProductController::class, 'CreateUsage']);
    Route::get('product/usage', [ProductController::class, 'IndexUsage']);

    //MODULE CUSTOMER
    //customer group
    Route::get('customer/group', [CustomerController::class, 'Index']);
    Route::post('customer/group', [CustomerController::class, 'Create']);
});
