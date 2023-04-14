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
use App\Http\Controllers\Product\TransferProductController;
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


    Route::group(['prefix' => 'location'], function () {

        //location
        Route::get('/locationpdf', [LocationController::class, 'cetak_pdf']);
        Route::get('/locationImages', [LocationController::class, 'searchImageLocation']);
        Route::post('/', [LocationController::class, 'insertLocation']);
        Route::get('/', [LocationController::class, 'getLocationHeader']);
        Route::get('/detaillocation', [LocationController::class, 'getLocationDetail']);
        Route::get('/datastaticlocation', [LocationController::class, 'getDataStaticLocation']);
        Route::get('/provinsilocation', [LocationController::class, 'getProvinsiLocation']);
        Route::get('/kabupatenkotalocation', [LocationController::class, 'getKabupatenLocation']);
        Route::get('/exportlocation', [LocationController::class, 'exportLocation']);
        Route::delete('/', [LocationController::class, "deleteLocation"]);
        Route::get('/list', [LocationController::class, 'locationList']);
        Route::put('/', [LocationController::class, 'updateLocation']);
        Route::post('/uploadexceltest', [LocationController::class, 'uploadexceltest']);
        Route::post('/datastatic', [LocationController::class, 'insertdatastatic']);
        Route::post('/imagelocation', [LocationController::class, 'uploadImageLocation']);

        //facility
        Route::get('/facility', [FacilityController::class, 'facilityMenuHeader']);
        Route::put('/facility', [FacilityController::class, 'updateFacility']);
        Route::post('/facility', [FacilityController::class, 'createFacility']);
        Route::delete('/facility', [FacilityController::class, 'deleteFacility']);
        Route::get('/facility/facilityexport', [FacilityController::class, 'facilityExport']);
        Route::get('/facility/facilitylocation', [FacilityController::class, 'facilityLocation']);
        Route::get('/facility/facilitydetail', [FacilityController::class, 'facilityDetail']);
        Route::get('/facility/facilityimages', [FacilityController::class, 'searchImageFacility']);
        Route::post('/facility/imagefacility', [FacilityController::class, 'uploadImageFacility']);

        //data static
        Route::get('/datastatic', [DataStaticController::class, 'datastatic']);
        Route::delete('/datastatic', [DataStaticController::class, 'datastaticlocation']);
        
        //product
        Route::get('/product/transfer', [LocationController::class, 'locationTransferProduct']);
    });



    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);

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
        
        Route::post('/adjust', [ProductController::class, 'adjust']);

        Route::get('/sell/dropdown/split', [ProductController::class, 'IndexProductSellSplit']);

        Route::get('/log', [ProductController::class, 'indexLog']);
        Route::get('/transaction', [ProductController::class, 'transaction']);

        Route::get('/transfernumber', [TransferProductController::class, 'transferProductNumber']);
        Route::post('/transfer', [TransferProductController::class, 'create']);
        Route::get('/transfer', [TransferProductController::class, 'index']);
        Route::put('/transfer/approval', [TransferProductController::class, 'approval']);
        Route::post('/transfer/receive', [TransferProductController::class, 'receive']);
        Route::get('/transfer/detail', [TransferProductController::class, 'detail']);
        Route::get('/transfer/export', [TransferProductController::class, 'export']);

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

    Route::group(['prefix' => 'customer'], function () {

        Route::post('/', [CustomerController::class, 'createCustomer']);
        Route::put('/', [CustomerController::class, 'updateCustomer']); // add
        Route::get('/', [CustomerController::class, 'indexCustomer']); // add
        Route::delete('/', [CustomerController::class, 'deleteCustomer']); // add
        Route::get('/detail', [CustomerController::class, 'getDetailCustomer']); // add
        Route::post('/images', [CustomerController::class, 'uploadImageCustomer']); // add
        Route::get('/export', [CustomerController::class, 'exportCustomer']); //add

        Route::get('/group', [CustomerController::class, 'getCustomerGroup']);
        Route::post('/group', [CustomerController::class, 'createCustomerGroup']);

        Route::get('/reference', [CustomerController::class, 'getReferenceCustomer']);
        Route::post('/reference', [CustomerController::class, 'insertReferenceCustomer']);

        Route::get('/title', [CustomerController::class, 'getTitleCustomer']); // title : tuan nyonya
        Route::post('/title', [CustomerController::class, 'insertTitleCustomer']); // title : tuan nyonya

        Route::get('/occupation', [CustomerController::class, 'getCustomerOccupation']); // kerja : programmer, wirausaha
        Route::post('/occupation', [CustomerController::class, 'insertCustomerOccupation']); // kerja : programmer, wirausaha

        Route::get('/pet', [CustomerController::class, 'getPetCategory']); // binatang : anjing, kucing, ular, serangga, burung
        Route::post('/pet', [CustomerController::class, 'insertPetCategory']); // binatang : anjing, kucing, ular, serangga, burung


        Route::post('/source', [CustomerController::class, 'insertSourceCustomer']);
        Route::get('/source', [CustomerController::class, 'getSourceCustomer']);
    });


    //STAFF
    Route::group(['prefix' => 'staff'], function () {

        Route::get('/rolesid', [StaffController::class, 'getRoleName']);
        Route::post('/', [StaffController::class, 'insertStaff']);
        Route::delete('/', [StaffController::class, 'deleteStaff']);
        Route::get('/rolestaff', [StaffController::class, 'getRoleStaff']);
        Route::get('/typeid', [StaffController::class, 'getTypeId']);
        Route::get('/payperiod', [StaffController::class, 'getPayPeriod']);
        Route::get('/jobtitle', [StaffController::class, 'getJobTitle']);
        Route::post('/typeid', [StaffController::class, 'insertTypeId']);
        Route::post('/payperiod', [StaffController::class, 'insertPayPeriod']);
        Route::post('/jobtitle', [StaffController::class, 'insertJobTitle']);
        Route::post('/imageStaff', [StaffController::class, 'uploadImageStaff']);
        Route::get('/staffdetail', [StaffController::class, 'getDetailStaff']);
        Route::put('/', [StaffController::class, 'updateStaff']);
        Route::get('/', [StaffController::class, 'index']);
        Route::get('/list', [StaffController::class, 'listStaff']);
        Route::get('/exportstaff', [StaffController::class, 'exportStaff']);
        Route::post('/sendEmail', [StaffController::class, 'sendEmailVerification']);
        Route::put('/statusStaff', [StaffController::class, 'updateStatusUsers']);
        Route::post('/holidaysdate', [StaffController::class, 'getAllHolidaysDate']);

        Route::get('/leave/workingdate', [StaffLeaveController::class, 'getWorkingDays']);
        Route::get('/leave/leavetype', [StaffLeaveController::class, 'getLeaveRequest']);
        Route::post('/leave', [StaffLeaveController::class, 'insertLeaveStaff']);
        Route::post('/leave/statusleave', [StaffLeaveController::class, 'setStatusLeaveRequest']);
        Route::post('/leave/adjustleave', [StaffLeaveController::class, 'adjustLeaveRequest']);
        Route::put('/leave/adjustbalance', [StaffLeaveController::class, 'adjustBalance']);
        Route::get('/leave', [StaffLeaveController::class, 'getIndexRequestLeave']);
        Route::get('/leave/balancetype', [StaffLeaveController::class, 'getDropdownBalanceType']);
        Route::get('/leave/leavebalance', [StaffLeaveController::class, 'getIndexStaffBalance']);
        Route::get('/leave/exportleave', [StaffLeaveController::class, 'exportLeaveRequest']);
        Route::get('/leave/exportbalance', [StaffLeaveController::class, 'exportBalance']);
        Route::get('/leave/allactive', [StaffLeaveController::class, 'getAllStaffActive']);
        Route::get('/leave/staffid', [StaffLeaveController::class, 'getUsersId']);
        Route::put('/leave/approveall', [StaffLeaveController::class, 'approveAll']);
        Route::put('/leave/rejectall', [StaffLeaveController::class, 'rejectAll']);

        Route::get('/product/transfer', [StaffController::class, 'staffListTransferProduct']);
    });

    //GLOBAL VARIABLE
    Route::get('kabupaten', [GlobalVariableController::class, 'getKabupaten']);
    Route::get('provinsi', [GlobalVariableController::class, 'getProvinsi']);
    Route::get('datastaticglobal', [GlobalVariableController::class, 'getDataStatic']);
    Route::post('datastaticglobal', [GlobalVariableController::class, 'insertDataStatic']);
    Route::post('uploadregion', [GlobalVariableController::class, 'uploadRegion']);
});
