<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Queue\QueueController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\CageManagementController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataStaticController;
use App\Http\Controllers\TimeKeeperController;
use App\Http\Controllers\ImportRegionController;
use App\Http\Controllers\Staff\AbsentController;
use App\Http\Controllers\Staff\ProfileController;
use App\Http\Controllers\GlobalVariableController;
use App\Http\Controllers\MenuManagementController;
use App\Http\Controllers\Product\BundleController;
use App\Http\Controllers\Report\BookingController;
use App\Http\Controllers\Report\DepositController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\RestockController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\SupplierController;
use App\Http\Controllers\Staff\StaffLeaveController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerMergeController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerFeedbackController;
use App\Http\Controllers\Customer\CustomerSupportRequestController;
use App\Http\Controllers\Installment\InstallmentController;

use App\Http\Controllers\Staff\StaffPayrollController;

use App\Http\Controllers\Product\ProductSellController;
use App\Http\Controllers\Staff\SecurityGroupController;
use App\Http\Controllers\Report\ReportProductController;
use App\Http\Controllers\ReportMenuManagementController;
use App\Http\Controllers\Transaction\BreedingController;
use App\Http\Controllers\Transaction\PetHotelController;
use App\Http\Controllers\Transaction\PetSalonController;
use App\Http\Controllers\Transaction\TransactionDashboardController;
use App\Http\Controllers\Product\ProductClinicController;
use App\Http\Controllers\Report\ReportCustomerController;
use App\Http\Controllers\Staff\DataStaticStaffController;
use App\Http\Controllers\VerifyUserandPasswordController;
use App\Http\Controllers\Customer\ImportCustomerController;
use App\Http\Controllers\Product\TransferProductController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Product\ProductDashboardController;
use App\Http\Controllers\Product\ProductInventoryController;
use App\Http\Controllers\Transaction\MaterialDataController;
use App\Http\Controllers\Customer\TemplateCustomerController;
use App\Http\Controllers\Transaction\TransPetClinicController;
use App\Http\Controllers\AccessControl\AccessControlController;
use App\Http\Controllers\Booking\BookingController as BookingBookingController;
use App\Http\Controllers\Customer\DataStaticCustomerController;
use App\Http\Controllers\Finance\DataStaticController as FinanceDataStaticController;
use App\Http\Controllers\Finance\ExpensesController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\QuotationController;
use App\Http\Controllers\Finance\FinanceSalesController;
use App\Http\Controllers\Finance\FinancePaymentRecordController;
use App\Http\Controllers\Finance\FinancePiutangController;
use App\Http\Controllers\Finance\FinanceRefundController;
use App\Http\Controllers\Product\BatchController;
use App\Http\Controllers\Product\StockOpnameController;
use App\Http\Controllers\Staff\AccessControlSchedulesController;
use App\Http\Controllers\Transaction\TransactionPetShopController;
use App\Http\Controllers\Report\SalesController as ReportSalesController;
use App\Http\Controllers\Report\StaffController as ReportStaffController;
use App\Http\Controllers\Report\ExpensesController as ReportExpensesController;
use App\Http\Controllers\Promotion\{DataStaticController as PromotionDataStaticController, PartnerController, DiscountController as DiscountPromotionController, PromotionDashboardController};
use App\Http\Controllers\Service\{ServiceController, DataStaticServiceController, TreatmentController, DiagnoseController, FrequencyController, TaskController, CategoryController as ServiceCategoryController, ContractTemplateController, ServiceDashboardController};
use App\Http\Controllers\Staff\IdentityController;
use App\Http\Controllers\Staff\OverWorkController;
use App\Http\Controllers\Staff\RequireSalaryController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Product\LoanProductController;
use App\Http\Controllers\Product\DeliveryAgentController;
use App\Http\Controllers\Product\DeliveryOrderController;

Route::post('login', [ApiController::class, 'login']);
Route::post('register', [ApiController::class, 'register']);

Route::post('/send-otp', [OtpController::class, 'sendOtp']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
Route::post('/change-password', [OtpController::class, 'changePassword']);

Route::put('user/{user}/online', [ApiController::class, 'online']);
// Route::post('/realtime/auth', function(){
//     return true;
// });

// Queue Display — public endpoint (token-based, no auth required)
Route::get('/queue/display', [QueueController::class, 'display']);

Route::group(['middleware' => ['jwt.verify']], function () {

    //location
    Route::post('logout', [ApiController::class, 'logout']);

    // Dropdown metode pembayaran — dipakai global di checkout hotel/salon/breeding
    Route::get('payment-method/list', [MaterialDataController::class, 'listPaymentMethodForTransaction']);

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/overview', [DashboardController::class, 'overview']);
        Route::get('/upbookingclinic', [DashboardController::class, 'upcomingBookingClinic']);
        Route::get('/upbookinghotel', [DashboardController::class, 'upcomingBookingHotel']);
        Route::get('/upbookingsalon', [DashboardController::class, 'upcomingBookingSalon']);
        Route::get('/upbookingbreeding', [DashboardController::class, 'upcomingBookingBreeding']);
        Route::get('/activity', [DashboardController::class, 'recentActivity']);
    });

    Route::group(['prefix' => 'location'], function () {

        //location
        Route::get('/locationpdf', [LocationController::class, 'cetak_pdf']);
        Route::post('/import', [LocationController::class, 'import']);
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
        Route::get('/list/transaction', [LocationController::class, 'locationListTransaction']);
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

        Route::get('/facility/cage', [FacilityController::class, 'cage']);

        Route::post('/facility/import', [FacilityController::class, 'import']);

        Route::get('/facility/location', [FacilityController::class, 'listFacilityWithLocation']);

        // ── Cage Management ──────────────────────────────────────
        Route::prefix('cage-management')->group(function () {
            Route::get('/',                [CageManagementController::class, 'index']);
            Route::post('/',               [CageManagementController::class, 'store']);
            Route::get('/detail',          [CageManagementController::class, 'show']);
            Route::put('/',                [CageManagementController::class, 'update']);
            Route::delete('/',             [CageManagementController::class, 'destroy']);

            Route::get('/inspection',      [CageManagementController::class, 'getInspections']);
            Route::post('/inspection',     [CageManagementController::class, 'storeInspection']);

            Route::get('/maintenance',     [CageManagementController::class, 'getMaintenances']);
            Route::post('/maintenance',    [CageManagementController::class, 'storeMaintenance']);
            Route::put('/maintenance',     [CageManagementController::class, 'updateMaintenance']);

            Route::get('/cleaning-log',    [CageManagementController::class, 'getCleaningLogs']);
            Route::post('/cleaning-log',   [CageManagementController::class, 'storeCleaningLog']);

            Route::get('/export',          [CageManagementController::class, 'export']);
            Route::get('/import-template', [CageManagementController::class, 'downloadTemplate']);
            Route::post('/import',         [CageManagementController::class, 'import']);
        });

        //data static
        Route::get('/datastatic', [DataStaticController::class, 'datastatic']);
        Route::delete('/datastatic', [DataStaticController::class, 'datastaticlocation']);

        //product
        Route::get('/product/transfer', [LocationController::class, 'locationTransferProduct']);
        Route::get('/product/transfer/destination', [LocationController::class, 'locationDestination']);
    });

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);


    Route::group(['prefix' => 'booking'], function () {
        Route::post('/', [BookingBookingController::class, 'create']);
        Route::put('/', [BookingBookingController::class, 'update']);
        Route::get('/', [BookingBookingController::class, 'index']);
        Route::get('/detail', [BookingBookingController::class, 'detail']);
        Route::put('/cancel', [BookingBookingController::class, 'cancelBooking']);
        Route::put('/accept', [BookingBookingController::class, 'acceptBooking']);
        Route::put('/reject', [BookingBookingController::class, 'rejectBooking']);
        Route::delete('/', [BookingBookingController::class, 'delete']);
        Route::get('/list', [BookingBookingController::class, 'getList']);
    });

    Route::group(['prefix' => 'queue'], function () {
        Route::get('/', [QueueController::class, 'index']);
        Route::post('/', [QueueController::class, 'store']);
        Route::post('/convert', [QueueController::class, 'convertFromBooking']);
        Route::put('/status', [QueueController::class, 'updateStatus']);
        Route::delete('/', [QueueController::class, 'destroy']);
        Route::put('/reset', [QueueController::class, 'reset']);
        Route::get('/booking-candidates', [QueueController::class, 'bookingCandidates']);
    });
    //MODULE PRODUCT
    //list produk
    Route::group(['prefix' => 'product'], function () {

        Route::get('/dashboard', [ProductDashboardController::class, 'index']);

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

        Route::get('/transfer', [TransferProductController::class, 'index']);
        Route::post('/transfer', [TransferProductController::class, 'create']);
        Route::put('/transfer', [TransferProductController::class, 'update']);
        Route::delete('/transfer', [TransferProductController::class, 'delete']);

        Route::get('/transfernumber', [TransferProductController::class, 'transferProductNumber']);
        Route::post('/transfer/approval', [TransferProductController::class, 'approval']);
        Route::post('/transfer/sent', [TransferProductController::class, 'sentReceiver']);
        Route::post('/transfer/receive', [TransferProductController::class, 'receive']);
        Route::get('/transfer/detail', [TransferProductController::class, 'detail']);
        Route::get('/transfer/detail/history', [TransferProductController::class, 'detailHistory']);
        Route::get('/transfer/export', [TransferProductController::class, 'export']);


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

        Route::group(['prefix' => 'batch'], function () {
            Route::get('/list-batch', [BatchController::class, 'ListBatch']);
            Route::get('/detail', [BatchController::class, 'DetailBatch']);
            Route::get('/list-batch-transfer', [BatchController::class, 'ListBatchTransfer']);
            Route::get('/detail-transfer', [BatchController::class, 'DetailBatchTransfer']);
        });

        Route::group(['prefix' => 'stock-opname'], function () {

            Route::get('/', [StockOpnameController::class, 'index']);
            Route::get('/scan-barcode', [StockOpnameController::class, 'scanBarcode']);
            Route::post('/', [StockOpnameController::class, 'create']);
            Route::get('/generate-so-number', [StockOpnameController::class, 'generateSONumber']);
            Route::post('/input-product', [StockOpnameController::class, 'inputProducts']);
            Route::get('/detail', [StockOpnameController::class, 'detail']);
            Route::put('/', [StockOpnameController::class, 'update']);
            Route::put('/start', [StockOpnameController::class, 'startStockOpname']);
            Route::put('/approval-director', [StockOpnameController::class, 'approvalDirector']);
            Route::put('/approval-checker', [StockOpnameController::class, 'approvalChecker']);
            Route::delete('/', [StockOpnameController::class, 'delete']);
            Route::get('/export', [StockOpnameController::class, 'export']);
            Route::get('/print', [StockOpnameController::class, 'printPdf']);
        });

        Route::group(['prefix' => 'loan-product'], function () {
            Route::get('/generate-loan-number', [LoanProductController::class, 'generateLoanNumber']);
            Route::get('/', [LoanProductController::class, 'index']);
            Route::get('/detail', [LoanProductController::class, 'detail']);
            Route::post('/', [LoanProductController::class, 'create']);
            Route::put('/', [LoanProductController::class, 'update']);
            Route::delete('/', [LoanProductController::class, 'delete']);
            Route::post('/submit', [LoanProductController::class, 'submit']);
            Route::post('/approval', [LoanProductController::class, 'approval']);
            Route::post('/loan-out', [LoanProductController::class, 'loanOut']);
            Route::post('/return', [LoanProductController::class, 'returnLoan']);
            Route::get('/download-pdf', [LoanProductController::class, 'downloadPdf']);
        });

        Route::group(['prefix' => 'delivery-agent'], function () {
            Route::get('/', [DeliveryAgentController::class, 'index']);
            Route::get('/detail', [DeliveryAgentController::class, 'detail']);
            Route::get('/dropdown', [DeliveryAgentController::class, 'dropdown']);
            Route::post('/', [DeliveryAgentController::class, 'create']);
            Route::put('/', [DeliveryAgentController::class, 'update']);
            Route::put('/status', [DeliveryAgentController::class, 'changeStatus']);
            Route::delete('/', [DeliveryAgentController::class, 'delete']);
        });

        Route::group(['prefix' => 'delivery-order'], function () {
            Route::get('/generate-number', [DeliveryOrderController::class, 'generateDeliveryNumber']);
            Route::get('/', [DeliveryOrderController::class, 'index']);
            Route::get('/detail', [DeliveryOrderController::class, 'detail']);
            Route::post('/', [DeliveryOrderController::class, 'create']);
            Route::put('/', [DeliveryOrderController::class, 'update']);
            Route::delete('/', [DeliveryOrderController::class, 'delete']);
            Route::post('/assign', [DeliveryOrderController::class, 'assign']);
            Route::post('/pickup', [DeliveryOrderController::class, 'pickup']);
            Route::post('/start', [DeliveryOrderController::class, 'startDelivery']);
            Route::post('/complete', [DeliveryOrderController::class, 'complete']);
            Route::post('/failed', [DeliveryOrderController::class, 'failed']);
            Route::post('/cancel', [DeliveryOrderController::class, 'cancel']);
            Route::get('/download-pdf', [DeliveryOrderController::class, 'downloadPdf']);
        });

        Route::get('/datastatic', [ProductController::class, 'indexDataStatic']);
        Route::delete('/datastatic', [ProductController::class, 'deleteDataStatic']);
    });

    Route::group(['prefix' => 'customer'], function () {

        Route::post('/', [CustomerController::class, 'createCustomer']);
        Route::put('/', [CustomerController::class, 'updateCustomer']); // add
        Route::get('/', [CustomerController::class, 'indexCustomer']); // add
        Route::delete('/', [CustomerController::class, 'deleteCustomer']); // add
        Route::get('/detail', [CustomerController::class, 'getDetailCustomer']); // add
        Route::post('/images', [CustomerController::class, 'uploadImageCustomer']); // add
        Route::get('/export', [CustomerController::class, 'exportCustomer']); //add
        Route::get('/export-pdf', [CustomerController::class, 'exportCustomerPdf']); //add
        Route::get('/typeid', [CustomerController::class, 'getTypeIdCustomer']);
        Route::post('/typeid', [CustomerController::class, 'insertTypeIdCustomer']);
        Route::get('/dashboard', [CustomerDashboardController::class, 'index']);


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

        Route::get('/list', [CustomerController::class, 'customerListWithLocation']);
        Route::get('/petlist', [CustomerController::class, 'petListWithCustomer']);

        Route::group(['prefix' => 'merge'], function () {
            Route::get('/preview', [CustomerMergeController::class, 'preview']);
            Route::post('/execute', [CustomerMergeController::class, 'execute']);
            Route::get('/history', [CustomerMergeController::class, 'history']);
            Route::get('/export', [CustomerMergeController::class, 'exportHistory']);
        });

        Route::group(['prefix' => 'template'], function () {

            Route::get('/', [TemplateCustomerController::class, 'index']);
            Route::get('/download', [TemplateCustomerController::class, 'download']);
        });

        Route::group(['prefix' => 'import'], function () {

            Route::get('/', [ImportCustomerController::class, 'index']);
            Route::post('/', [ImportCustomerController::class, 'import']);
        });

        Route::group(['prefix' => 'feedback'], function () {
            Route::get('/', [CustomerFeedbackController::class, 'index']);
            Route::post('/', [CustomerFeedbackController::class, 'create']);
            Route::put('/', [CustomerFeedbackController::class, 'update']);
            Route::delete('/', [CustomerFeedbackController::class, 'delete']);
        });

        Route::group(['prefix' => 'support-request'], function () {
            Route::get('/',            [CustomerSupportRequestController::class, 'index']);
            Route::post('/',           [CustomerSupportRequestController::class, 'create']);
            Route::put('/',            [CustomerSupportRequestController::class, 'update']);
            Route::delete('/',         [CustomerSupportRequestController::class, 'delete']);
            Route::get('/history',     [CustomerSupportRequestController::class, 'history']);
            Route::get('/my-requests', [CustomerSupportRequestController::class, 'myRequests']);
            Route::post('/self-submit',[CustomerSupportRequestController::class, 'selfSubmit']);
        });

        Route::group(['prefix' => 'datastatic'], function () {

            Route::post('/', [DataStaticCustomerController::class, 'insertDataStaticCustomer']);

            Route::get('/customer', [DataStaticCustomerController::class, 'getDataStaticCustomer']);
            Route::get('/', [DataStaticCustomerController::class, 'indexDataStaticCustomer']);
            Route::delete('/', [DataStaticCustomerController::class, 'deleteDataStaticCustomer']);
        });
    });

    Route::group(['prefix' => 'promotion'], function () {

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/', [PromotionDashboardController::class, 'index']);
        });

        Route::group(['prefix' => 'discount'], function () {
            Route::post('/', [DiscountPromotionController::class, 'create']);
            Route::get('/', [DiscountPromotionController::class, 'index']);
            Route::get('/export', [DiscountPromotionController::class, 'export']);
            Route::get('/list-type', [DiscountPromotionController::class, 'listType']);
            Route::get('/active-today', [DiscountPromotionController::class, 'activeTodayPromos']);
            Route::get('/detail', [DiscountPromotionController::class, 'detail']);
            Route::put('/', [DiscountPromotionController::class, 'update']);
            Route::delete('/', [DiscountPromotionController::class, 'delete']);

            Route::post('/checkpromo', [DiscountPromotionController::class, 'checkPromo']);
        });


        Route::get('/datastatic', [PromotionDataStaticController::class, 'index']);
        Route::delete('/datastatic', [PromotionDataStaticController::class, 'delete']);

        Route::post('/datastatic/typephone', [PromotionDataStaticController::class, 'insertTypePhone']);
        Route::get('/datastatic/typephone', [PromotionDataStaticController::class, 'listTypePhone']);

        Route::post('/datastatic/typemessenger', [PromotionDataStaticController::class, 'insertTypeMessenger']);
        Route::get('/datastatic/typemessenger', [PromotionDataStaticController::class, 'listTypeMessenger']);

        Route::post('/datastatic/usage', [PromotionDataStaticController::class, 'insertUsage']);
        Route::get('/datastatic/usage', [PromotionDataStaticController::class, 'listUsage']);

        Route::group(['prefix' => 'partner'], function () {

            Route::post('/', [PartnerController::class, 'create']);
            Route::get('/', [PartnerController::class, 'index']);
            Route::get('/export', [PartnerController::class, 'export']);
            Route::get('/detail', [PartnerController::class, 'detail']);
            Route::put('/', [PartnerController::class, 'update']);
            Route::delete('/', [PartnerController::class, 'delete']);
        });
    });

    //STAFF
    Route::group(['prefix' => 'staff'], function () {

        Route::get('/dashboard', [StaffDashboardController::class, 'index']);

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
        Route::get('/listmanager', [StaffController::class, 'listStaffManagerAdmin']);
        Route::get('/stafflist-location-jobtitle', [StaffController::class, 'listStaffWithLocationJobTitle']);
        Route::get('/salary/check', [StaffController::class, 'salaryCheck']);

        Route::get('/list/location/doctor', [StaffController::class, 'listStaffDoctorWithLocation']);
        Route::get('/list/location', [StaffController::class, 'listStaffWithLocation']);

        Route::get('/exportstaff', [StaffController::class, 'exportStaff']);
        Route::post('/import', [StaffController::class, 'importStaff']);
        Route::get('/template', [StaffController::class, 'template']);
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
        Route::get('/profile/late', [ProfileController::class, 'staffLate']);
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
        Route::post('/profile/image', [ProfileController::class, 'uploadImageProfile']);

        Route::get('/salary-slip', [StaffPayrollController::class, 'index']);
        Route::put('/salary-slip', [StaffPayrollController::class, 'update']);
        Route::get('/salary-slip/detail', [StaffPayrollController::class, 'detail']);
        Route::get('/salary-slip/export', [StaffPayrollController::class, 'export']);
        Route::post('/salary-slip', [StaffPayrollController::class, 'create']);
        Route::delete('/salary-slip', [StaffPayrollController::class, 'delete']);
        Route::get('/salary-slip/generate-slip', [StaffPayrollController::class, 'generatePayrollSlip']);

        Route::get('/salary-slip/check-personal-data', [StaffPayrollController::class, 'checkPersonalData']);

        Route::post('req-salary', [RequireSalaryController::class, 'create']);
        Route::get('/req-salary', [RequireSalaryController::class, 'index']);
        Route::get('/req-salary/export', [RequireSalaryController::class, 'export']);
        Route::get('/req-salary/detail', [RequireSalaryController::class, 'detail']);
        Route::put('/req-salary', [RequireSalaryController::class, 'update']);
        Route::delete('/req-salary', [RequireSalaryController::class, 'delete']);

        Route::post('identity', [IdentityController::class, 'create']);
        Route::get('/identity', [IdentityController::class, 'index']);
        Route::get('/identity/export', [IdentityController::class, 'export']);
        Route::put('/identity', [IdentityController::class, 'approval']);
        Route::delete('/identity', [IdentityController::class, 'delete']);

        Route::group(['prefix' => 'overwork'], function () {
            Route::get('/full-shift', [OverWorkController::class, 'indexFullShift']);
            Route::get('/full-shift/export', [OverWorkController::class, 'exportFullShift']);
            Route::post('/full-shift', [OverWorkController::class, 'createFullShift']);
            Route::put('/full-shift', [OverWorkController::class, 'updateFullShift']);
            Route::put('/full-shift/approval', [OverWorkController::class, 'approvalFullShift']);
            Route::delete('/full-shift', [OverWorkController::class, 'deleteFullShift']);

            Route::get('/long-shift', [OverWorkController::class, 'indexLongShift']);
            Route::get('/long-shift/export', [OverWorkController::class, 'exportLongShift']);
            Route::post('/long-shift', [OverWorkController::class, 'createLongShift']);
            Route::put('/long-shift', [OverWorkController::class, 'updateLongShift']);
            Route::put('/long-shift/approval', [OverWorkController::class, 'approvalLongShift']);
            Route::delete('/long-shift', [OverWorkController::class, 'deleteLongShift']);
        });
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

    Route::group(['prefix' => 'absent'], function () {
        Route::post('/', [AbsentController::class, 'createAbsent']);
        Route::get('/staff-list', [AbsentController::class, 'staffListAbsent']);
        Route::get('/index', [AbsentController::class, 'Index']);
        Route::get('/present-list', [AbsentController::class, 'presentStatusList']);
        Route::get('/detail', [AbsentController::class, 'Detail']);
        Route::get('/export', [AbsentController::class, 'Export']);
    });

    // Chat
    Route::group(['prefix' => 'chat'], function () {
        Route::get('/list-user', [ChatController::class, 'list']);
        Route::get('/detail', [ChatController::class, 'detail']);
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'create']);
        Route::post('/read', [ChatController::class, 'read']);
    });

    // Notifications
    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/',           [NotificationController::class, 'index']);
        Route::post('/read/{id}', [NotificationController::class, 'markRead']);
        Route::post('/read-all',  [NotificationController::class, 'markAllRead']);
    });

    Route::group(['prefix' => 'menu'], function () {

        Route::get('/last-order-menu-group', [MenuManagementController::class, 'lastOrderMenuGroup']);
        Route::get('/last-order-child-menu-group', [MenuManagementController::class, 'lastOrderChildMenu']);
        Route::get('/last-order-grand-child-menu-group', [MenuManagementController::class, 'lastOrderGrandChildMenu']);

        Route::get('/timekeeper', [TimeKeeperController::class, 'index']);
        Route::post('/timekeeper', [TimeKeeperController::class, 'insert']);
        Route::put('/timekeeper', [TimeKeeperController::class, 'update']);
        Route::delete('/timekeeper', [TimeKeeperController::class, 'delete']);

        Route::get('/list-menu-group', [MenuManagementController::class, 'listMenuGroup']);
        Route::get('/menu-group', [MenuManagementController::class, 'indexMenuGroup']);
        Route::post('/menu-group', [MenuManagementController::class, 'insertMenuGroup']);
        Route::put('/menu-group', [MenuManagementController::class, 'updateMenuGroup']);
        Route::delete('/menu-group', [MenuManagementController::class, 'deleteMenuGroup']);

        Route::get('/list-child-menu-group', [MenuManagementController::class, 'listChildrenMenu']);
        Route::get('/child-menu-group', [MenuManagementController::class, 'indexChildrenMenu']);
        Route::get('/detail-child-menu-group', [MenuManagementController::class, 'detailChildrenMenu']);
        Route::post('/child-menu-group', [MenuManagementController::class, 'insertChildrenMenu']);
        Route::put('/child-menu-group', [MenuManagementController::class, 'updateChildMenu']);
        Route::delete('/child-menu-group', [MenuManagementController::class, 'deleteChildMenu']);

        Route::get('/grand-child-menu-group', [MenuManagementController::class, 'indexGrandChildMenu']);
        Route::get('/detail-grand-child-menu-group', [MenuManagementController::class, 'detailGrandChildMenu']);
        Route::post('/grand-child-menu-group', [MenuManagementController::class, 'insertGrandChildMenu']);
        Route::put('/grand-child-menu-group', [MenuManagementController::class, 'updateGrandChildMenu']);
        Route::delete('/grand-child-menu-group', [MenuManagementController::class, 'deleteGrandChildMenu']);

        Route::get('/profile', [MenuManagementController::class, 'indexMenuProfile']);
        Route::post('/profile', [MenuManagementController::class, 'insertMenuProfile']);
        Route::put('/profile', [MenuManagementController::class, 'updateMenuProfile']);
        Route::delete('/profile', [MenuManagementController::class, 'deleteMenuProfile']);

        Route::get('/setting', [MenuManagementController::class, 'indexMenuSetting']);
        Route::post('/setting', [MenuManagementController::class, 'insertMenuSetting']);
        Route::put('/setting', [MenuManagementController::class, 'updateMenuSetting']);
        Route::delete('/setting', [MenuManagementController::class, 'deleteMenuSetting']);

        Route::get('/menu-report', [ReportMenuManagementController::class, 'Index']);
        Route::post('/menu-report', [ReportMenuManagementController::class, 'Insert']);
        Route::get('/menu-report/detail', [ReportMenuManagementController::class, 'Detail']);
        Route::put('/menu-report', [ReportMenuManagementController::class, 'Update']);
        Route::delete('/menu-report', [ReportMenuManagementController::class, 'Delete']);
    });

    // Transaction
    Route::group(['prefix' => 'transaction'], function () {
        Route::get('/dashboard', [TransactionDashboardController::class, 'index']);
        Route::get('/category', [TransactionController::class, 'TransactionCategory']);

        Route::group(['prefix' => 'petclinic'], function () {
            Route::get('/', [TransPetClinicController::class, 'index']);
            Route::post('/', [TransPetClinicController::class, 'create']);
            Route::get('/detail', [TransPetClinicController::class, 'detail']);
            Route::put('/', [TransPetClinicController::class, 'update']);
            Route::delete('/', [TransPetClinicController::class, 'delete']);
            Route::get('/export', [TransPetClinicController::class, 'export']);
            Route::get('/stats', [TransPetClinicController::class, 'getStats']);

            Route::get('/ordernumber', [TransPetClinicController::class, 'orderNumber']);

            Route::post('/accept', [TransPetClinicController::class, 'acceptionTransaction']);
            Route::post('/reassign', [TransPetClinicController::class, 'reassignDoctor']);

            Route::post('/petcheck', [TransPetClinicController::class, 'createPetCheck']);
            Route::get('/load-petcheck', [TransPetClinicController::class, 'loadDataPetCheck']);
            Route::get('/check-condition', [TransPetClinicController::class, 'getCheckCondition']);
            Route::post('/serviceandrecipe', [TransPetClinicController::class, 'serviceandrecipe']);
            Route::post('/checkpromo', [TransPetClinicController::class, 'checkPromo']); // deprecated, use /calculate
            Route::get('/beforepayment', [TransPetClinicController::class, 'showDataBeforePayment']);
            Route::post('/discount', [TransPetClinicController::class, 'transactionDiscount']); // deprecated, use /calculate

            // New unified promo + calculation endpoint
            Route::post('/calculate', [TransPetClinicController::class, 'calculate']);
            Route::get('/payment-methods', [TransPetClinicController::class, 'getPaymentMethods']);

            Route::get('/invoice', [TransPetClinicController::class, 'printInvoce']);
            // ===== RAWAT INAP =====
            // Treatment Plan
            Route::post('/inpatient/treatment-plan', [TransPetClinicController::class, 'storeTreatmentPlan']);
            Route::get('/inpatient/treatment-plan', [TransPetClinicController::class, 'getTreatmentPlan']);

            // Policy Agreement
            Route::get('/inpatient/policies', [TransPetClinicController::class, 'getPoliciesForAgreement']);
            Route::post('/inpatient/policy-agreement', [TransPetClinicController::class, 'savePolicyAgreement']);

            // Papan Kerja Harian
            Route::get('/inpatient/papan-kerja', [TransPetClinicController::class, 'getPapanKerjaHarian']);
            Route::match(['post', 'put'], '/inpatient/papan-kerja/done', [TransPetClinicController::class, 'markPapanKerjaHarianDone']);

            // Additional Treatments
            Route::get('/inpatient/additional-treatments', [TransPetClinicController::class, 'getAdditionalTreatments']);
            Route::post('/inpatient/additional-treatments', [TransPetClinicController::class, 'addAdditionalTreatment']);
            Route::get('/inpatient/available-items', [TransPetClinicController::class, 'getAvailableItems']);

            // Prepayments (DP)
            Route::get('/inpatient/prepayments', [TransPetClinicController::class, 'getPrepayments']);
            Route::post('/inpatient/prepayments', [TransPetClinicController::class, 'addPrepayment']);
            Route::get('/inpatient/prepayments/receipt', [TransPetClinicController::class, 'prepaymentReceipt']);
            Route::get('/inpatient/estimated-cost', [TransPetClinicController::class, 'getEstimatedCost']);

            // Inpatient Detail & Checkout
            Route::get('/inpatient/detail', [TransPetClinicController::class, 'getInpatient']);
            Route::post('/inpatient/checkout', [TransPetClinicController::class, 'initiateCheckout']);

            //pembayaran rawat inap
            Route::post('/payment/inpatient', [TransPetClinicController::class, 'paymentInpatient']);
            // Route::post('/invoice/inpatient', [TransPetClinicController::class, 'printInvoceOutpatient']);
            Route::post('/upload-payment-proof', [TransPetClinicController::class, 'uploadPaymentProof']);
            Route::post('/confirm-payment', [TransPetClinicController::class, 'confirmPayment']);
            Route::post('/reject-payment', [TransPetClinicController::class, 'rejectPayment']);

            //pembayaran rawat jalan
            Route::post('/payment/outpatient', [TransPetClinicController::class, 'paymentOutpatient']);
            Route::get('/payment/outpatient/history', [TransPetClinicController::class, 'getPaymentHistory']);
            Route::post('/invoice/outpatient', [TransPetClinicController::class, 'printInvoceOutpatient']);
        });

        Route::group(['prefix' => 'petshop'], function () {
            Route::get('/', [TransactionPetShopController::class, 'index']);
            Route::post('/', [TransactionPetShopController::class, 'create']);
            Route::get('/detail', [TransactionPetShopController::class, 'detail']);
            Route::put('/', [TransactionPetShopController::class, 'update']);
            Route::delete('/', [TransactionPetShopController::class, 'delete']);
            Route::get('/export', [TransactionPetShopController::class, 'export']);
            Route::post('/discount', [TransactionPetShopController::class, 'transactionDiscount']);
            Route::post('/upload-payment-proof', [TransactionPetShopController::class, 'uploadPaymentProof']);
            Route::post('/confirmPayment', [TransactionPetShopController::class, 'confirmPayment']);
            Route::post('/reject-payment', [TransactionPetShopController::class, 'rejectPayment']);
            Route::get('/generateInvoice/{id}', [TransactionPetShopController::class, 'generateInvoice']);
        });

        Route::group(['prefix' => 'pethotel'], function () {
            Route::get('/stats', [PetHotelController::class, 'getStats']);
            Route::get('/', [PetHotelController::class, 'index']);
            Route::post('/', [PetHotelController::class, 'create']);
            Route::get('/detail', [PetHotelController::class, 'detail']);
            Route::put('/', [PetHotelController::class, 'update']);
            Route::delete('/', [PetHotelController::class, 'delete']);
            Route::get('/export', [PetHotelController::class, 'export']);

            Route::post('/accept', [PetHotelController::class, 'acceptionTransaction']);
            Route::post('/reassign', [PetHotelController::class, 'reassignDoctor']);

            Route::post('/petcheck', [PetHotelController::class, 'createPetCheck']);
            Route::get('/check-condition', [PetHotelController::class, 'getCheckCondition']);
            Route::post('/treatment', [PetHotelController::class, 'Treatment']);

            Route::get('/beforepayment', [PetHotelController::class, 'showDataBeforePayment']);
            Route::post('/checkpromo', [PetHotelController::class, 'checkPromo']);

            Route::post('/calculate', [PetHotelController::class, 'calculate']);
            Route::get('/payment-methods', [PetHotelController::class, 'getPaymentMethods']);
            Route::get('/payment/history', [PetHotelController::class, 'getPaymentHistory']);

            Route::post('/discount', [PetHotelController::class, 'transactionDiscount']);
            Route::post('/payment', [PetHotelController::class, 'payment']);

            Route::get('/invoice', [PetHotelController::class, 'printInvoce']);
            Route::post('/invoice/outpatient', [PetHotelController::class, 'printInvoceOutpatient']);
            Route::get('/checkout/invoice', [PetHotelController::class, 'checkoutInvoice']);
            Route::post('/upload-payment-proof', [PetHotelController::class, 'uploadPaymentProof']);
            Route::post('/confirm-payment', [PetHotelController::class, 'confirmPayment']);
            Route::post('/reject-payment', [PetHotelController::class, 'rejectPayment']);

            Route::get('/papan-kerja-harian', [PetHotelController::class, 'getPapanKerjaHarian']);
            Route::post('/papan-kerja-harian/done', [PetHotelController::class, 'markPapanKerjaHarianDone']);
            Route::get('/papan-kerja-vetnurse', [PetHotelController::class, 'getPapanKerjaVetnurse']);
            Route::post('/papan-kerja-vetnurse/done', [PetHotelController::class, 'markPapanKerjaVetnurseDone']);

            Route::get('/available-items', [PetHotelController::class, 'getAvailableItems']);
            Route::get('/additional-treatment', [PetHotelController::class, 'getAdditionalTreatments']);
            Route::post('/additional-treatment', [PetHotelController::class, 'addAdditionalTreatment']);
            Route::delete('/additional-treatment/{id}', [PetHotelController::class, 'deleteAdditionalTreatment']);
            Route::post('/extend-stay', [PetHotelController::class, 'extendStay']);

            Route::get('/prepayment', [PetHotelController::class, 'getPrepayments']);
            Route::post('/prepayment', [PetHotelController::class, 'addPrepayment']);
            Route::get('/prepayment/{id}/receipt', [PetHotelController::class, 'prepaymentReceipt']);

            Route::get('/policies', [PetHotelController::class, 'getPoliciesForAgreement']);
            Route::post('/policy-agreement', [PetHotelController::class, 'savePolicyAgreement']);

            Route::post('/checkout/initiate', [PetHotelController::class, 'initiateCheckOut']);
            Route::get('/checkout/summary', [PetHotelController::class, 'getCheckoutSummary']);
            Route::put('/checkout/discount', [PetHotelController::class, 'updateCheckoutDiscount']);
            Route::post('/checkout/payment', [PetHotelController::class, 'checkoutPayment']);
        });

        Route::group(['prefix' => 'breeding'], function () {

            Route::get('/', [BreedingController::class, 'index']);
            Route::post('/', [BreedingController::class, 'create']);
            Route::get('/detail', [BreedingController::class, 'detail']);
            Route::put('/', [BreedingController::class, 'update']);
            Route::delete('/', [BreedingController::class, 'delete']);
            Route::get('/export', [BreedingController::class, 'export']);
            Route::get('/stats', [BreedingController::class, 'getStats']);

            Route::post('/accept', [BreedingController::class, 'acceptionTransaction']);
            Route::post('/reassign', [BreedingController::class, 'reassignDoctor']);

            Route::post('/petcheck', [BreedingController::class, 'createPetCheck']);

            Route::post('/treatment', [BreedingController::class, 'Treatment']);

            // Policy Agreement
            Route::get('/policies', [BreedingController::class, 'getPoliciesForBreeding']);
            Route::post('/policy-agreement', [BreedingController::class, 'savePolicyAgreement']);

            // Prepayment / DP
            Route::get('/prepayments', [BreedingController::class, 'getPrepayments']);
            Route::post('/prepayments', [BreedingController::class, 'addPrepayment']);
            Route::delete('/prepayments', [BreedingController::class, 'deletePrepayment']);
            Route::get('/prepayments/receipt', [BreedingController::class, 'prepaymentReceipt']);

            // Papan Kerja Harian
            Route::get('/papan-kerja', [BreedingController::class, 'getPapanKerjaHarian']);
            Route::post('/papan-kerja', [BreedingController::class, 'addPapanKerjaHarian']);
            Route::post('/papan-kerja/done', [BreedingController::class, 'markPapanKerjaHarianDone']);

            // Additional Treatment
            Route::get('/additional-treatments', [BreedingController::class, 'getAdditionalTreatments']);
            Route::post('/additional-treatments', [BreedingController::class, 'addAdditionalTreatment']);
            Route::delete('/additional-treatments', [BreedingController::class, 'deleteAdditionalTreatment']);

            // Checkout
            Route::post('/checkout', [BreedingController::class, 'initiateCheckOut']);
            Route::get('/checkout/summary', [BreedingController::class, 'getCheckoutSummary']);

            Route::get('/payment-methods', [BreedingController::class, 'getPaymentMethods']);

            Route::get('/beforepayment', [BreedingController::class, 'showDataBeforePayment']);
            Route::post('/checkpromo', [BreedingController::class, 'checkPromo']);

            Route::post('/discount', [BreedingController::class, 'transactionDiscount']);
            Route::post('/payment', [BreedingController::class, 'payment']);
            Route::post('/upload-payment-proof', [BreedingController::class, 'uploadPaymentProof']);
            Route::post('/confirm-payment', [BreedingController::class, 'confirmPayment']);
            Route::post('/reject-payment', [BreedingController::class, 'rejectPayment']);

            Route::get('/invoice', [BreedingController::class, 'printInvoce']);
        });

        Route::group(['prefix' => 'petsalon'], function () {
            Route::get('/stats', [PetSalonController::class, 'getStats']);
            Route::get('/', [PetSalonController::class, 'index']);
            Route::post('/', [PetSalonController::class, 'create']);
            Route::get('/detail', [PetSalonController::class, 'detail']);
            Route::put('/', [PetSalonController::class, 'update']);
            Route::delete('/', [PetSalonController::class, 'delete']);
            Route::get('/export', [PetSalonController::class, 'export']);

            Route::post('/accept', [PetSalonController::class, 'acceptionTransaction']);
            Route::post('/reassign', [PetSalonController::class, 'reassignDoctor']);

            Route::post('/petcheck', [PetSalonController::class, 'createPetCheck']);

            Route::post('/treatment', [PetSalonController::class, 'Treatment']);

            // Policy Agreement
            Route::get('/policies', [PetSalonController::class, 'getPoliciesForAgreement']);
            Route::post('/policy-agreement', [PetSalonController::class, 'savePolicyAgreement']);

            // Groomer tandai selesai + assign kandang + kirim WA
            Route::post('/salon-done', [PetSalonController::class, 'markSalonDone']);

            // Customer hadir → inisiasi pembayaran
            Route::post('/checkout', [PetSalonController::class, 'initiateCheckout']);

            Route::get('/beforepayment', [PetSalonController::class, 'showDataBeforePayment']);
            Route::post('/checkpromo', [PetSalonController::class, 'checkPromo']);

            Route::post('/discount', [PetSalonController::class, 'transactionDiscount']);
            Route::post('/payment', [PetSalonController::class, 'payment']);
            Route::post('/upload-payment-proof', [PetSalonController::class, 'uploadPaymentProof']);
            Route::post('/confirm-payment', [PetSalonController::class, 'confirmPayment']);
            Route::post('/reject-payment', [PetSalonController::class, 'rejectPayment']);

            Route::get('/invoice', [PetSalonController::class, 'printInvoce']);
        });

        Route::get('/materialdata', [MaterialDataController::class, 'index']);
        Route::post('/materialdata', [MaterialDataController::class, 'store']);
        Route::delete('/materialdata', [MaterialDataController::class, 'delete']);

        Route::post('/list', [TransPetClinicController::class, 'createList']);
        Route::get('/listdata/paymentmethod', [MaterialDataController::class, 'listPaymentMethod']);
        Route::get('/listdata/weight', [TransPetClinicController::class, 'listDataWeight']);
        Route::get('/listdata/temperature', [TransPetClinicController::class, 'listDatatemperature']);
        Route::get('/listdata/breath', [TransPetClinicController::class, 'listDatabreath']);
        Route::get('/listdata/sound', [TransPetClinicController::class, 'listDatasound']);
        Route::get('/listdata/heart', [TransPetClinicController::class, 'listDataheart']);
        Route::get('/listdata/vaginal', [TransPetClinicController::class, 'listDatavaginal']);

        Route::post('/', [TransactionController::class, 'create']);
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/detail', [TransactionController::class, 'detail']);
        Route::delete('/', [TransactionController::class, 'delete']);
        Route::put('/', [TransactionController::class, 'update']);

        Route::post('/accept', [TransactionController::class, 'acceptionTransaction']);
        Route::post('/reassign', [TransactionController::class, 'reassignDoctor']);
        Route::post('/hplcheck', [TransactionController::class, 'HPLCheck']);
        Route::post('/petcheck', [TransactionController::class, 'petCheck']);
        Route::post('/treatment', [TransactionController::class, 'Treatment']);

        Route::get('/export', [TransactionController::class, 'export']);
    });

    // Service
    Route::group(['prefix' => 'service'], function () {

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/', [ServiceDashboardController::class, 'index']);
        });

        Route::group(['prefix' => 'category'], function () {
            Route::get('/export', [ServiceCategoryController::class, 'export']);
            Route::get('/', [ServiceCategoryController::class, 'index']);
            Route::post('/', [ServiceCategoryController::class, 'create']);
            Route::put('/', [ServiceCategoryController::class, 'update']);
            Route::delete('/', [ServiceCategoryController::class, 'delete']);
        });
        Route::group(['prefix' => 'list'], function () {
            Route::get('/category', [ServiceController::class, 'findByCategory']);
            Route::get('/export', [ServiceController::class, 'export']);
            Route::get('/detail', [ServiceController::class, 'detail']);
            Route::get('/', [ServiceController::class, 'index']);
            Route::get('/template', [ServiceController::class, 'downloadTemplate']);
            Route::post('/import', [ServiceController::class, 'Import']);
            Route::post('/', [ServiceController::class, 'create']);
            Route::put('/', [ServiceController::class, 'update']);
            Route::delete('/', [ServiceController::class, 'destroy']);
            Route::get('/location', [ServiceController::class, 'ListServiceWithLocation']);
        });
        Route::group(['prefix' => 'treatment'], function () {
            Route::get('/export', [TreatmentController::class, 'export']);
            Route::get('/item', [TreatmentController::class, 'indexItem']);
            Route::get('/', [TreatmentController::class, 'index']);
            Route::get('/list', [TreatmentController::class, 'listTreatment']);
            Route::get('/detail', [TreatmentController::class, 'detail']);
            Route::post('/', [TreatmentController::class, 'store']);
            Route::put('/', [TreatmentController::class, 'update']);
            Route::put('/item', [TreatmentController::class, 'manageItem']);
            // BUG 7 FIX: removed duplicate GET /detail route (was on line 888)
            Route::delete('/', [TreatmentController::class, 'destroy']);
        });

        Route::group(['prefix' => 'data-static'], function () {
            Route::get('/', [DataStaticServiceController::class, 'index']);
            Route::delete('/', [DataStaticServiceController::class, 'delete']);
        });

        Route::group(['prefix' => 'diagnose'], function () {
            Route::get('/', [DiagnoseController::class, 'index']);
        });

        Route::group(['prefix' => 'frequency'], function () {
            Route::get('/', [FrequencyController::class, 'index']);
        });

        Route::group(['prefix' => 'task'], function () {
            Route::get('/', [TaskController::class, 'index']);
        });

        Route::group(['prefix' => 'contract'], function () {
            Route::get('/', [ContractTemplateController::class, 'index']);
            Route::post('/', [ContractTemplateController::class, 'create']);
            Route::get('/detail', [ContractTemplateController::class, 'detail']);
            Route::put('/', [ContractTemplateController::class, 'update']);
            Route::delete('/', [ContractTemplateController::class, 'delete']);
            Route::get('/export', [ContractTemplateController::class, 'export']);
            Route::get('/list', [ContractTemplateController::class, 'getListContract']);
        });
    });

    Route::group(['prefix' => 'finance'], function () {

        Route::get('/vendor', [FinanceController::class, 'listVendor']);
        Route::post('/vendor', [FinanceController::class, 'addVendor']);
        Route::get('/category', [FinanceController::class, 'listCategory']);
        Route::post('/category', [FinanceController::class, 'addCategory']);
        Route::get('/expense-type', [FinanceController::class, 'listExpenseType']);
        Route::post('/expense-type', [FinanceController::class, 'addExpenseType']);
        Route::get('/department', [FinanceController::class, 'listDepartment']);
        Route::post('/department', [FinanceController::class, 'addDepartment']);
        Route::get('/payment-status', [FinanceController::class, 'listPaymentStatus']);
        Route::post('/payment-status', [FinanceController::class, 'addPaymentStatus']);
        Route::get('/payment-method', [FinanceController::class, 'listPaymentMethod']);
        Route::post('/payment-method', [FinanceController::class, 'addPaymentMethod']);


        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/', [FinanceDashboardController::class, 'index']);
        });

        Route::group(['prefix' => 'expense'], function () {
            Route::get('/', [ExpensesController::class, 'index']);
            Route::post('/', [ExpensesController::class, 'create']);
            Route::get('/detail', [ExpensesController::class, 'detail']);
            Route::post('/approval', [ExpensesController::class, 'approval']);
            Route::get('/export', [ExpensesController::class, 'export']);
            Route::delete('/', [ExpensesController::class, 'delete']);
        });

        Route::group(['prefix' => 'data-static'], function () {
            Route::get('/', [FinanceDataStaticController::class, 'index']);
            Route::delete('/', [FinanceDataStaticController::class, 'delete']);
        });

        Route::group(['prefix' => 'quotation'], function () {
            Route::get('/', [QuotationController::class, 'index']);
            Route::post('/', [QuotationController::class, 'create']);
            Route::put('/', [QuotationController::class, 'update']);
            Route::delete('/', [QuotationController::class, 'delete']);
            Route::get('/detail', [QuotationController::class, 'detail']);
            Route::post('/status', [QuotationController::class, 'updateStatus']);
            Route::post('/duplicate', [QuotationController::class, 'duplicate']);
            Route::post('/convert', [QuotationController::class, 'convertToTransaction']);
            Route::get('/print', [QuotationController::class, 'printQuotation']);
            Route::get('/export-excel', [QuotationController::class, 'exportExcel']);
            Route::get('/customer-dropdown', [QuotationController::class, 'customerDropdown']);
            Route::get('/pet-dropdown', [QuotationController::class, 'petDropdown']);
            Route::get('/discount-options', [QuotationController::class, 'discountOptions']);
            Route::post('/calculate-discount', [QuotationController::class, 'calculateDiscount']);
        });

        Route::group(['prefix' => 'sales'], function () {
            Route::get('/', [FinanceSalesController::class, 'index']);
            Route::get('/summary', [FinanceSalesController::class, 'summary']);
            Route::get('/export', [FinanceSalesController::class, 'exportExcel']);
            Route::get('/payment-methods', [FinanceSalesController::class, 'paymentMethods']);
            Route::get('/payment-detail', [FinanceSalesController::class, 'paymentDetail']);
            Route::post('/add-payment', [FinanceSalesController::class, 'addPayment']);
        });

        Route::group(['prefix' => 'payment-records'], function () {
            Route::get('/', [FinancePaymentRecordController::class, 'index']);
            Route::get('/export', [FinancePaymentRecordController::class, 'exportExcel']);
            Route::get('/payment-methods', [FinancePaymentRecordController::class, 'paymentMethods']);
        });

        Route::group(['prefix' => 'piutang'], function () {
            Route::get('/', [FinancePiutangController::class, 'index']);
            Route::get('/aging-summary', [FinancePiutangController::class, 'agingSummary']);
            Route::get('/export', [FinancePiutangController::class, 'exportExcel']);
        });

        Route::group(['prefix' => 'refund'], function () {
            Route::get('/',                  [FinanceRefundController::class, 'index']);
            Route::get('/summary',           [FinanceRefundController::class, 'summary']);
            Route::get('/invoice-lookup',    [FinanceRefundController::class, 'invoiceLookup']);
            Route::get('/payment-methods',   [FinanceRefundController::class, 'paymentMethods']);
            Route::get('/export',            [FinanceRefundController::class, 'exportExcel']);
            Route::post('/',                 [FinanceRefundController::class, 'store']);
            Route::delete('/{id}',           [FinanceRefundController::class, 'destroy']);
        });
    });

    Route::group(['prefix' => 'report'], function () {

        Route::group(['prefix' => 'booking'], function () {
            Route::get('/location', [BookingController::class, 'indexLocation']);
            Route::get('/status', [BookingController::class, 'indexStatus']);
            Route::get('/cancellationreason', [BookingController::class, 'indexCancel']);
            Route::get('/list', [BookingController::class, 'indexList']);
            Route::get('/diagnose', [BookingController::class, 'indexDiagnose']);
            Route::get('/diagnosespecies', [BookingController::class, 'indexSpecies']);

            Route::get('/location/export', [BookingController::class, 'exportLocation']);
            Route::get('/status/export', [BookingController::class, 'exportStatus']);
            Route::get('/cancellationreason/export', [BookingController::class, 'exportCancel']);
            Route::get('/list/export', [BookingController::class, 'exportList']);
            Route::get('/diagnose/export', [BookingController::class, 'exportDiagnose']);
            Route::get('/diagnosespecies/export', [BookingController::class, 'exportSpecies']);

            Route::get('/diagnoseoptions', [BookingController::class, 'getDiagnoseOptions']);
            Route::get('/diagnosespeciesgender', [BookingController::class, 'indexDiagnosesSpeciesGender']);
            Route::get('/diagnosespeciesgender/export', [BookingController::class, 'exportDiagnosesSpeciesGender']);
        });

        Route::group(['prefix' => 'customer'], function () {

            Route::get('/growth', [ReportCustomerController::class, 'indexGrowth']);
            Route::get('/growthgroup', [ReportCustomerController::class, 'indexGrowthByGroup']);
            Route::get('/total', [ReportCustomerController::class, 'indexTotal']);
            Route::get('/leaving', [ReportCustomerController::class, 'indexLeaving']);
            Route::get('/list', [ReportCustomerController::class, 'indexList']);
            Route::get('/refspend', [ReportCustomerController::class, 'indexRefSpend']);
            Route::get('/subaccount', [ReportCustomerController::class, 'indexSubAccount']);

            Route::get('/growth/export', [ReportCustomerController::class, 'exportGrowth']);
            Route::get('/growthgroup/export', [ReportCustomerController::class, 'exportGrowthByGroup']);
            Route::get('/total/export', [ReportCustomerController::class, 'exportTotal']);
            Route::get('/leaving/export', [ReportCustomerController::class, 'exportLeaving']);
            Route::get('/list/export', [ReportCustomerController::class, 'exportList']);
            Route::get('/refspend/export', [ReportCustomerController::class, 'exportRefSpend']);
            Route::get('/subaccount/export', [ReportCustomerController::class, 'exportSubAccount']);
        });

        Route::group(['prefix' => 'deposit'], function () {
            Route::get('/list', [DepositController::class, 'indexList']);
            Route::get('/list/export', [DepositController::class, 'exportList']);
            Route::get('/summary', [DepositController::class, 'indexSummary']);
            Route::get('/summary/export', [DepositController::class, 'exportSummary']);
        });

        Route::group(['prefix' => 'expenses'], function () {
            Route::get('/list', [ReportExpensesController::class, 'indexList']);
            Route::get('/list/export', [ReportExpensesController::class, 'exportList']);
            Route::get('/summary', [ReportExpensesController::class, 'indexSummary']);
            Route::get('/summary/export', [ReportExpensesController::class, 'exportSummary']);
            Route::get('/options/payment', [ReportExpensesController::class, 'optionPayment']);
            Route::get('/options/status', [ReportExpensesController::class, 'optionStatus']);
            Route::get('/options/submiter', [ReportExpensesController::class, 'optionSubmiter']);
            Route::get('/options/recipient', [ReportExpensesController::class, 'optionRecipient']);
            Route::get('/options/category', [ReportExpensesController::class, 'optionCategory']);
            Route::get('/options/supplier', [ReportExpensesController::class, 'optionSupplier']);
        });

        Route::group(['prefix' => 'products'], function () {
            Route::get('/stockcount', [ReportProductController::class, 'indexStockCount']);
            Route::get('/stockcount/export', [ReportProductController::class, 'exportStockCount']);
            Route::get('/lowstock', [ReportProductController::class, 'indexLowStock']);
            Route::get('/lowstock/export', [ReportProductController::class, 'exportLowStock']);
            Route::get('/cost', [ReportProductController::class, 'indexCost']);
            Route::get('/cost/export', [ReportProductController::class, 'exportCost']);
            Route::get('/nostock', [ReportProductController::class, 'indexNoStock']);
            Route::get('/nostock/export', [ReportProductController::class, 'exportNoStock']);
            // Route::get('/detail', [ReportProductController::class, 'detail']);
            Route::get('/reminders', [ReportProductController::class, 'indexReminders']);
            Route::get('/reminders/export', [ReportProductController::class, 'exportReminders']);
            Route::get('/batches', [ReportProductController::class, 'indexBatches']);
            Route::get('/batches/export', [ReportProductController::class, 'exportBatches']);
            Route::get('/expiry', [ReportProductController::class, 'indexExpiry']);
            Route::get('/expiry/export', [ReportProductController::class, 'exportExpiry']);
        });

        Route::group(['prefix' => 'sales'], function () {
            Route::get('/items', [ReportSalesController::class, 'indexItems']);
            Route::get('/items/export', [ReportSalesController::class, 'exportItems']);
            Route::get('/summary', [ReportSalesController::class, 'indexSummary']);
            Route::get('/summary/export', [ReportSalesController::class, 'exportSummary']);
            Route::get('/salesbyservice', [ReportSalesController::class, 'indexSalesByService']);
            Route::get('/salesbyservice/export', [ReportSalesController::class, 'exportSalesByService']);
            Route::get('/salesbyproduct', [ReportSalesController::class, 'indexSalesByProduct']);
            Route::get('/salesbyproduct/export', [ReportSalesController::class, 'exportSalesByProduct']);
            Route::get('/paymentlist', [ReportSalesController::class, 'indexPaymentList']);
            Route::get('/paymentlist/export', [ReportSalesController::class, 'exportPaymentList']);
            Route::get('/details', [ReportSalesController::class, 'indexDetails']);
            Route::get('/details/export', [ReportSalesController::class, 'exportDetails']);
            Route::get('/unpaid', [ReportSalesController::class, 'indexUnpaid']);
            Route::get('/unpaid/export', [ReportSalesController::class, 'exportUnpaid']);
            Route::get('/discountsummary', [ReportSalesController::class, 'indexDiscountSummary']);
            Route::get('/paymentsummary', [ReportSalesController::class, 'indexPaymentSummary']);
            Route::get('/netincome', [ReportSalesController::class, 'indexNetIncome']);
            Route::get('/dailyaudit', [ReportSalesController::class, 'indexDailyAudit']);
            Route::get('/dailyaudit/export', [ReportSalesController::class, 'exportDailyAudit']);
            Route::get('/staffservicesales', [ReportSalesController::class, 'indexStaffServiceSales']);
            Route::get('/staffservicesales/export', [ReportSalesController::class, 'exportStaffServiceSales']);
        });

        Route::group(['prefix' => 'service'], function () {});

        Route::group(['prefix' => 'staff'], function () {

            Route::get('/login', [ReportStaffController::class, 'indexStaffLogin']);
            Route::get('/late', [ReportStaffController::class, 'indexStaffLate']);
            Route::get('/leave', [ReportStaffController::class, 'indexStaffLeave']);
            Route::get('/peformance', [ReportStaffController::class, 'indexStaffPeformance']);

            Route::get('/login/export', [ReportStaffController::class, 'exportStaffLogin']);
            Route::get('/late/export', [ReportStaffController::class, 'exportStaffLate']);
            Route::get('/leave/export', [ReportStaffController::class, 'exportStaffLeave']);
            Route::get('/peformance/export', [ReportStaffController::class, 'exportStaffPeformance']);
        });
    });

    // ── INSTALLMENT (Cicilan) ────────────────────────────────────────────────
    Route::group(['prefix' => 'installment'], function () {
        Route::get('/',              [InstallmentController::class, 'index']);
        Route::get('/detail',        [InstallmentController::class, 'detail']);
        Route::get('/summary',       [InstallmentController::class, 'summary']);
        Route::post('/',             [InstallmentController::class, 'create']);
        Route::post('/payment',      [InstallmentController::class, 'recordPayment']);
        Route::get('/late-fee',      [InstallmentController::class, 'previewLateFee']);
        Route::delete('/',           [InstallmentController::class, 'cancel']);
    });

    //GLOBAL VARIABLE
    Route::get('kabupaten', [GlobalVariableController::class, 'getKabupaten']);
    Route::get('provinsi', [GlobalVariableController::class, 'getProvinsi']);
    Route::get('datastaticglobal', [GlobalVariableController::class, 'getDataStatic']);
    Route::post('datastaticglobal', [GlobalVariableController::class, 'insertDataStatic']);
    Route::post('uploadregion', [GlobalVariableController::class, 'uploadRegion']);
});
