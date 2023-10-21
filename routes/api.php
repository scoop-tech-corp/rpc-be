<?php

use App\Http\Controllers\Staff\AbsentController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ImportRegionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Product\BundleController;
use App\Http\Controllers\Product\ProductClinicController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\SupplierController;
use App\Http\Controllers\Product\ProductInventoryController;
use App\Http\Controllers\Product\ProductSellController;
use App\Http\Controllers\Product\TransferProductController;
use App\Http\Controllers\Product\RestockController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\GlobalVariableController;
use App\Http\Controllers\VerifyUserandPasswordController;
use App\Http\Controllers\Staff\StaffLeaveController;
use App\Http\Controllers\Staff\DataStaticStaffController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Staff\SecurityGroupController;
use App\Http\Controllers\AccessControl\AccessControlController;
use App\Http\Controllers\Customer\DataStaticCustomerController;
use App\Http\Controllers\Staff\AccessControlSchedulesController;
use App\Http\Controllers\Staff\ProfileController;

use App\Http\Controllers\Service\{ServiceController, TreatmentController, DiagnoseController, FrequencyController, TaskController, CategoryController as ServiceCategoryController};


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

        Route::get('/facility/location', [FacilityController::class, 'listFacilityWithLocation']);

        //data static
        Route::get('/datastatic', [DataStaticController::class, 'datastatic']);
        Route::delete('/datastatic', [DataStaticController::class, 'datastaticlocation']);

        //product
        Route::get('/product/transfer', [LocationController::class, 'locationTransferProduct']);
        Route::get('/product/transfer/destination', [LocationController::class, 'locationDestination']);
    });



    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);

    //MODULE PRODUCT
    //list produk
    Route::group(['prefix' => 'product'], function () {

        Route::post('/supplier', [SupplierController::class, 'create']);
        Route::delete('/supplier', [SupplierController::class, 'delete']);
        Route::put('/supplier', [SupplierController::class, 'update']);

        Route::get('/supplier', [SupplierController::class, 'index']);
        Route::get('/supplier/detail', [SupplierController::class, 'detail']);

        Route::post('/supplier/usage', [SupplierController::class, 'createSupplierUsage']);
        Route::post('/supplier/phone', [SupplierController::class, 'createSupplierTypePhone']);
        Route::post('/supplier/messenger', [SupplierController::class, 'createSupplierTypeMessenger']);

        Route::get('/supplier/usage', [SupplierController::class, 'listSupplierUsage']);
        Route::get('/supplier/phone', [SupplierController::class, 'listSupplierTypePhone']);
        Route::get('/supplier/messenger', [SupplierController::class, 'listSupplierTypeMessenger']);
        Route::get('/supplier/export', [SupplierController::class, 'export']);

        Route::post('/brand', [ProductController::class, 'addProductBrand']);
        Route::get('/brand', [ProductController::class, 'IndexProductBrand']);

        Route::get('/sell', [ProductSellController::class, 'Index']);

        Route::get('/sell/template', [ProductSellController::class, 'downloadTemplate']);
        Route::post('/sell/import', [ProductSellController::class, 'Import']);

        Route::get('/sell/detail', [ProductSellController::class, 'Detail']);
        Route::post('/sell', [ProductSellController::class, 'Create']);
        Route::put('/sell', [ProductSellController::class, 'Update']);
        Route::post('/sell/image', [ProductSellController::class, 'updateImages']);
        Route::delete('/sell', [ProductSellController::class, 'Delete']);
        Route::get('/sell/export', [ProductSellController::class, 'Export']);
        Route::post('/sell/split', [ProductSellController::class, 'Split']);

        Route::get('/clinic', [ProductClinicController::class, 'index']);

        Route::get('/clinic/template', [ProductClinicController::class, 'downloadTemplate']);
        Route::post('/clinic/import', [ProductClinicController::class, 'Import']);

        Route::get('/clinic/detail', [ProductClinicController::class, 'detail']);
        Route::post('/clinic', [ProductClinicController::class, 'Create']);
        Route::put('/clinic', [ProductClinicController::class, 'Update']);
        Route::post('/clinic/image', [ProductClinicController::class, 'updateImages']);
        Route::delete('/clinic', [ProductClinicController::class, 'Delete']);
        Route::get('/clinic/export', [ProductClinicController::class, 'Export']);

        Route::get('/inventory', [ProductInventoryController::class, 'index']);
        Route::get('/inventory/export', [ProductInventoryController::class, 'exportInventory']);
        Route::get('/inventory/history', [ProductInventoryController::class, 'indexHistory']);
        Route::get('/inventory/history/export', [ProductInventoryController::class, 'exportHistory']);
        Route::get('/inventory/approval', [ProductInventoryController::class, 'indexApproval']);
        Route::get('/inventory/approval/export', [ProductInventoryController::class, 'exportApproval']);

        Route::get('/inventory/detail', [ProductInventoryController::class, 'detail']);
        Route::post('/inventory', [ProductInventoryController::class, 'create']);
        Route::put('/inventory', [ProductInventoryController::class, 'update']);
        Route::put('/inventory/approval', [ProductInventoryController::class, 'updateApproval']);
        Route::delete('/inventory', [ProductInventoryController::class, 'delete']);

        Route::get('/inventory/template', [ProductInventoryController::class, 'downloadTemplate']);
        Route::post('/inventory/import', [ProductInventoryController::class, 'Import']);

        //product category
        Route::get('/category', [CategoryController::class, 'index']);
        Route::put('/category', [CategoryController::class, 'update']);
        Route::delete('/category', [CategoryController::class, 'delete']);
        Route::get('/category/detail/sell', [CategoryController::class, 'detailSell']);
        Route::get('/category/detail/clinic', [CategoryController::class, 'detailClinic']);
        Route::get('/category/export', [CategoryController::class, 'export']);
        Route::post('/category', [CategoryController::class, 'create']);

        //service Category



        Route::get('/sell/dropdown', [ProductController::class, 'IndexProductSell']);
        Route::get('/clinic/dropdown', [ProductController::class, 'IndexProductClinic']);

        Route::get('/sell/list/location', [ProductController::class, 'ListProductSellWithLocation']);
        Route::get('/clinic/list/location', [ProductController::class, 'ListProductClinicWithLocation']);

        Route::post('/usage', [ProductController::class, 'CreateUsage']);
        Route::get('/usage', [ProductController::class, 'IndexUsage']);

        Route::post('/adjust', [ProductController::class, 'adjust']);

        Route::get('/sell/dropdown/split', [ProductController::class, 'IndexProductSellSplit']);

        Route::get('/log', [ProductController::class, 'indexLog']);
        Route::get('/transaction', [ProductController::class, 'transaction']);

        Route::get('/transfernumber', [TransferProductController::class, 'transferProductNumber']);
        Route::post('/transfer', [TransferProductController::class, 'create']);
        Route::get('/transfer', [TransferProductController::class, 'index']);
        Route::post('/transfer/approval', [TransferProductController::class, 'approval']);
        Route::post('/transfer/sent', [TransferProductController::class, 'sentReceiver']);
        Route::post('/transfer/receive', [TransferProductController::class, 'receive']);
        Route::get('/transfer/detail', [TransferProductController::class, 'detail']);
        Route::get('/transfer/detail/history', [TransferProductController::class, 'detailHistory']);
        Route::get('/transfer/export', [TransferProductController::class, 'export']);
        Route::put('/transfer', [TransferProductController::class, 'update']);

        Route::post('/transfer/multiple', [TransferProductController::class, 'createMultiple']);
        Route::get('/transfer/producttwobranch', [TransferProductController::class, 'productListWithTwoBranch']);

        Route::get('/restock', [RestockController::class, 'index']);
        Route::post('/restock', [RestockController::class, 'create']);
        Route::put('/restock', [RestockController::class, 'update']);
        Route::delete('/restock', [RestockController::class, 'delete']);

        Route::post('/restock/multiple', [RestockController::class, 'createMultiple']);
        Route::get('/restock/export', [RestockController::class, 'export']);
        Route::get('/restock/export/pdf', [RestockController::class, 'exportPDF']);

        Route::post('/restock/tracking', [RestockController::class, 'createTracking']);
        Route::get('/restock/detail', [RestockController::class, 'detail']);
        Route::get('/restock/detail/history', [RestockController::class, 'detailHistory']);
        Route::get('/restock/detail/supplier', [RestockController::class, 'listSupplier']);

        Route::post('/restock/approval', [RestockController::class, 'approval']);
        Route::post('/restock/sentsupplier', [RestockController::class, 'sentSupplier']);
        Route::post('/restock/receive', [RestockController::class, 'confirmReceive']);

        //product bundle
        Route::get('/bundle', [BundleController::class, 'index']);
        Route::get('/bundle/detail', [BundleController::class, 'detail']);
        Route::post('/bundle', [BundleController::class, 'create']);
        Route::put('/bundle', [BundleController::class, 'update']);
        Route::put('/bundle/status', [BundleController::class, 'changeStatus']);
        Route::delete('/bundle', [BundleController::class, 'delete']);

        Route::get('/datastatic', [ProductController::class, 'indexDataStatic']);
        Route::delete('/datastatic', [ProductController::class, 'deleteDataStatic']);
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
        Route::get('/typeid', [CustomerController::class, 'getTypeIdCustomer']);
        Route::post('/typeid', [CustomerController::class, 'insertTypeIdCustomer']);

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

        Route::put('/pet', [CustomerController::class, 'updatePetAge']);



        Route::post('/datastatic', [DataStaticCustomerController::class, 'insertDataStaticCustomer']);
        Route::get('/datastatic/customer', [DataStaticCustomerController::class, 'getDataStaticCustomer']);
        Route::get('/datastatic', [DataStaticCustomerController::class, 'indexDataStaticCustomer']);
        Route::delete('/datastatic', [DataStaticCustomerController::class, 'deleteDataStaticCustomer']);
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
        Route::get('/list/location', [StaffController::class, 'listStaffWithLocation']);

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

        Route::get('/datastatic', [DataStaticStaffController::class, 'indexDataStaticStaff']);
        Route::delete('/datastatic', [DataStaticStaffController::class, 'deleteDataStaticStaff']);
        Route::get('/datastatic/staff', [DataStaticStaffController::class, 'getDataStaticStaff']);
        Route::post('/datastatic', [DataStaticStaffController::class, 'insertDataStaticStaff']);

        Route::get('/schedule/menulist', [AccessControlSchedulesController::class, 'getMenuList']);
        Route::get('/schedule', [AccessControlSchedulesController::class, 'index']);
        Route::get('/schedule/export', [AccessControlSchedulesController::class, 'export']);
        Route::post('/schedule', [AccessControlSchedulesController::class, 'insertAccessControlSchedules']);
        Route::get('/schedule/liststaff', [AccessControlSchedulesController::class, 'getUsersFromLocationId']);
        Route::get('/schedule/detailshedules', [AccessControlSchedulesController::class, 'detailAllSchedules']);
        Route::get('/schedule/detail', [AccessControlSchedulesController::class, 'detailSchedules']);
        Route::delete('/schedule', [AccessControlSchedulesController::class, 'deleteAccessControlSchedules']);
        Route::put('/schedule', [AccessControlSchedulesController::class, 'updateAccessControlSchedules']);

        Route::get('/profile', [ProfileController::class, 'detailProfile']);
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
        Route::post('/profile/image', [ProfileController::class, 'uploadImageProfile']);
    });


    //Security Group
    Route::group(['prefix' => 'securitygroup'], function () {

        Route::get('/', [SecurityGroupController::class, 'index']);
        Route::post('/', [SecurityGroupController::class, 'insertSecurityGroup']);
        Route::delete('/', [SecurityGroupController::class, 'deleteSecurityGroup']);
        Route::get('/detail', [SecurityGroupController::class, 'detailSecurityGroup']);
        Route::get('/users', [SecurityGroupController::class, 'dropdownUsersSecurityGroup']);
        Route::put('/', [SecurityGroupController::class, 'updateSecurityGroup']);
    });


    //Access Control
    Route::group(['prefix' => 'accesscontrol'], function () {

        Route::get('/user', [AccessControlController::class, 'index']);
        Route::get('/history', [AccessControlController::class, 'indexHistory']);
        Route::get('/', [AccessControlController::class, 'indexAccessControlDashboard']);
        Route::get('/accesstype', [AccessControlController::class, 'dropdownAccessType']);
        Route::get('/menumaster', [AccessControlController::class, 'dropdownMenuMaster']);
        Route::get('/menumaster/index', [AccessControlController::class, 'indexMenuMaster']);
        Route::get('/menulist', [AccessControlController::class, 'dropdownMenuList']);
        Route::get('/menulist/index', [AccessControlController::class, 'indexMenuList']);
        Route::post('/menulist', [AccessControlController::class, 'insertMenutList']);
        Route::post('/menumaster', [AccessControlController::class, 'insertMenuMaster']);
        Route::put('/menu', [AccessControlController::class, 'updateAccessControlMenu']);
        Route::put('/menulist', [AccessControlController::class, 'updateMenuList']);
        Route::put('/menumaster', [AccessControlController::class, 'updateMenuMaster']);
        Route::delete('/menu', [AccessControlController::class, 'deleteAccessControlMenu']);
    });

    Route::group(['prefix' => 'absent'], function(){
        Route::post('/', [AbsentController::class, 'createAbsent']);
    });


    // Service
    Route::group(['prefix' => 'service'], function(){
        Route::group(['prefix' => 'category'], function(){
            Route::get('/export', [ServiceCategoryController::class, 'export']);
            Route::get('/', [ServiceCategoryController::class, 'index']);
            Route::post('/', [ServiceCategoryController::class, 'create']);
            Route::put('/', [ServiceCategoryController::class, 'update']);
            Route::delete('/', [ServiceCategoryController::class, 'delete']);    
        });
        Route::group(['prefix' => 'list'], function(){
            Route::get('/category', [ServiceController::class, 'findByCategory']);
            Route::get('/export', [ServiceController::class, 'export']);
            Route::get('/detail', [ServiceController::class, 'detail']);
            Route::get('/', [ServiceController::class, 'index']);
            Route::get('/template', [ServiceController::class, 'downloadTemplate']);
            Route::post('/import', [ServiceController::class, 'Import']);
            Route::post('/', [ServiceController::class, 'create']);
            Route::put('/', [ServiceController::class, 'update']);
            Route::delete('/', [ServiceController::class, 'destroy']);
        });
        Route::group(['prefix' => 'treatment'], function(){
            Route::get('/export', [TreatmentController::class, 'export']);
            Route::get('/item', [TreatmentController::class, 'indexItem']);
            Route::get('/', [TreatmentController::class, 'index']);
            Route::get('/detail', [TreatmentController::class, 'detail']);
            Route::post('/', [TreatmentController::class, 'store']);
            Route::put('/', [TreatmentController::class, 'update']);
            Route::put('/item', [TreatmentController::class, 'addNewItem']);
            Route::get('/detail', [TreatmentController::class, 'detail']); 
            Route::delete('/', [TreatmentController::class, 'destroy']);
        });

        Route::group(['prefix' => 'diagnose'], function(){
            Route::get('/', [DiagnoseController::class, 'index']);
        });

        Route::group(['prefix' => 'frequency'], function(){
            Route::get('/', [FrequencyController::class, 'index']);
        });

        Route::group(['prefix' => 'task'], function(){
            Route::get('/', [TaskController::class, 'index']);
        });
    });


    //GLOBAL VARIABLE
    Route::get('kabupaten', [GlobalVariableController::class, 'getKabupaten']);
    Route::get('provinsi', [GlobalVariableController::class, 'getProvinsi']);
    Route::get('datastaticglobal', [GlobalVariableController::class, 'getDataStatic']);
    Route::post('datastaticglobal', [GlobalVariableController::class, 'insertDataStatic']);
    Route::post('uploadregion', [GlobalVariableController::class, 'uploadRegion']);
});
