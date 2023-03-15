<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ImportRegionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Product\BundleController;
use App\Http\Controllers\Product\ProductClinicController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductInventoryController;
use App\Http\Controllers\Product\ProductSellController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\GlobalVariableController;
use App\Http\Controllers\VerifyUserandPasswordController;
use App\Http\Controllers\Staff\StaffLeaveController;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function () {

    //location 


    Route::post('logout', [ApiController::class, 'logout']);

    Route::get('locationpdf', [LocationController::class, 'cetak_pdf']);
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



    Route::post('productstatus', [ProductController::class, 'addProductStatus']);
    Route::post('product', [ProductController::class, 'createNewProduct']);
    Route::delete('product', [ProductController::class, 'deleteProduct']);
    Route::get('product', [ProductController::class, 'indexProduct']);
    Route::get('productdetail', [ProductController::class, 'getProductDetail']);

    //MODULE PRODUCT
    //list produk
    Route::group(['prefix' => 'product'], function () {

        Route::post('/supplier', [ProductController::class, 'addProductSupplier']);
        Route::get('/supplier', [ProductController::class, 'IndexProductSupplier']);

        Route::post('/brand', [ProductController::class, 'addProductBrand']);
        Route::get('/brand', [ProductController::class, 'IndexProductBrand']);

        Route::get('/sell', [ProductSellController::class, 'Index']);
        Route::get('/sell/detail', [ProductSellController::class, 'Detail']);
        Route::post('/sell', [ProductSellController::class, 'Create']);
        Route::put('/sell', [ProductSellController::class, 'Update']);
        Route::post('/sell/image', [ProductSellController::class, 'updateImages']);
        Route::delete('/sell', [ProductSellController::class, 'Delete']);
        Route::get('/sell/export', [ProductSellController::class, 'Export']);
        Route::post('/sell/split', [ProductSellController::class, 'Split']);

        Route::get('/clinic', [ProductClinicController::class, 'index']);
        Route::get('/clinic/detail', [ProductClinicController::class, 'detail']);
        Route::post('/clinic', [ProductClinicController::class, 'Create']);
        Route::put('/clinic', [ProductClinicController::class, 'Update']);
        Route::post('/clinic/image', [ProductClinicController::class, 'updateImages']);
        Route::delete('/clinic', [ProductClinicController::class, 'Delete']);
        Route::get('/clinic/export', [ProductClinicController::class, 'Export']);

        Route::get('/inventory', [ProductInventoryController::class, 'index']);
        Route::get('/inventory/history', [ProductInventoryController::class, 'indexHistory']);
        Route::get('/inventory/history/export', [ProductInventoryController::class, 'exportHistory']);
        Route::get('/inventory/approval', [ProductInventoryController::class, 'indexApproval']);
        Route::get('/inventory/approval/export', [ProductInventoryController::class, 'exportApproval']);

        Route::get('/inventory/detail', [ProductInventoryController::class, 'detail']);

        Route::post('/inventory', [ProductInventoryController::class, 'create']);

        Route::put('/inventory', [ProductInventoryController::class, 'update']);
        Route::put('/inventory/approval', [ProductInventoryController::class, 'updateApproval']);

        Route::delete('/inventory', [ProductInventoryController::class, 'delete']);

        //product category
        Route::get('/category', [ProductController::class, 'IndexProductCategory']);
        Route::post('/category', [ProductController::class, 'CreateProductCategory']);

        Route::get('/sell/dropdown', [ProductController::class, 'IndexProductSell']);
        Route::get('/clinic/dropdown', [ProductController::class, 'IndexProductClinic']);

        Route::post('/usage', [ProductController::class, 'CreateUsage']);
        Route::get('/usage', [ProductController::class, 'IndexUsage']);

        //product bundle
        Route::get('/bundle', [BundleController::class, 'index']);
        Route::get('/bundle/detail', [BundleController::class, 'detail']);
        Route::post('/bundle', [BundleController::class, 'create']);
        Route::put('/bundle', [BundleController::class, 'update']);
        Route::put('/bundle/status', [BundleController::class, 'changeStatus']);
        Route::delete('/bundle', [BundleController::class, 'delete']);
    });

    //MODULE CUSTOMER
    //customer group
    Route::get('customer/group', [CustomerController::class, 'Index']);
    Route::post('customer/group', [CustomerController::class, 'Create']);
    Route::post('customer', [CustomerController::class, 'CreateCustomer']);

    //STAFF
    Route::get('rolesid', [StaffController::class, 'getRoleName']);
    Route::post('staff', [StaffController::class, 'insertStaff']);
    Route::delete('staff', [StaffController::class, 'deleteStaff']);
    Route::get('rolestaff', [StaffController::class, 'getRoleStaff']);
    Route::get('typeid', [StaffController::class, 'getTypeId']);
    Route::get('payperiod', [StaffController::class, 'getPayPeriod']);
    Route::get('jobtitle', [StaffController::class, 'getJobTitle']);
    Route::post('typeid', [StaffController::class, 'insertTypeId']);
    Route::post('payperiod', [StaffController::class, 'insertPayPeriod']);
    Route::post('jobtitle', [StaffController::class, 'insertJobTitle']);
    Route::post('imageStaff', [StaffController::class, 'uploadImageStaff']);
    Route::get('staffdetail', [StaffController::class, 'getDetailStaff']);
    Route::put('staff', [StaffController::class, 'updateStaff']);
    Route::get('staff', [StaffController::class, 'index']);
    Route::get('exportstaff', [StaffController::class, 'exportStaff']);
    Route::post('sendEmail', [StaffController::class, 'sendEmailVerification']);
    Route::put('statusStaff', [StaffController::class, 'updateStatusUsers']);

    Route::post('holidaysdate', [StaffController::class, 'getAllHolidaysDate']);

    //STAFF LEAVE
    // naming url dirapikan statusleaverequest
    Route::get('staff/workingdate', [StaffLeaveController::class, 'getWorkingDays']);
    Route::get('staff/leavetype', [StaffLeaveController::class, 'getLeaveRequest']);
    Route::post('staff/leave', [StaffLeaveController::class, 'insertLeaveStaff']);
    Route::post('staff/statusleave', [StaffLeaveController::class, 'setStatusLeaveRequest']);
    Route::post('staff/adjustleave', [StaffLeaveController::class, 'adjustLeaveRequest']);
    Route::put('staff/adjustbalance', [StaffLeaveController::class, 'adjustBalance']);
    Route::get('staff/leave', [StaffLeaveController::class, 'getIndexRequestLeave']);
    Route::get('staff/balancetype', [StaffLeaveController::class, 'getDropdownBalanceType']);
    Route::get('staff/leavebalance', [StaffLeaveController::class, 'getIndexStaffBalance']);
    Route::get('staff/exportleave', [StaffLeaveController::class, 'exportLeaveRequest']);
    Route::get('staff/exportbalance', [StaffLeaveController::class, 'exportBalance']);
    Route::get('staff/allactive', [StaffLeaveController::class, 'getAllStaffActive']);
    Route::get('staff/staffid', [StaffLeaveController::class, 'getUsersId']);
    Route::put('staff/approveall', [StaffLeaveController::class, 'approveAll']);
    Route::put('staff/rejectall', [StaffLeaveController::class, 'rejectAll']);
    
    //GLOBAL VARIABLE
    Route::get('kabupaten', [GlobalVariableController::class, 'getKabupaten']);
    Route::get('provinsi', [GlobalVariableController::class, 'getProvinsi']);
    Route::get('datastaticglobal', [GlobalVariableController::class, 'getDataStatic']);
    Route::post('datastaticglobal', [GlobalVariableController::class, 'insertDataStatic']);
    Route::post('uploadregion', [GlobalVariableController::class, 'uploadRegion']);

    // Naufal task
    Route::get('reference', [CustomerController::class, 'getReference']);
    Route::post('reference', [CustomerController::class, 'insertReference']);

    Route::get('title', [CustomerController::class, 'getTitle']);
    Route::post('title', [CustomerController::class, 'insertTitle']);

    Route::get('source', [CustomerController::class, 'getSource']);
    Route::post('source', [CustomerController::class, 'insertSource']);
});
