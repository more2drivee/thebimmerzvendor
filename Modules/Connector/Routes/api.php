<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BusinessController;
use Modules\Connector\Http\Controllers\Api\MediaController;
use Modules\Connector\Http\Controllers\Api\OpenAIController;
use Modules\Connector\Http\Controllers\Api\NotificationController;
use Modules\Connector\Http\Controllers\Api\BookingController;
use Modules\Connector\Http\Controllers\Api\JobSheetController;
use Modules\Connector\Http\Controllers\Api\MessagesController;
use Modules\Connector\Http\Controllers\Api\dashboardController;
use Modules\Connector\Http\Controllers\Api\EndPointsController;
use Modules\Connector\Http\Controllers\Api\SparePartsController;
use Modules\Connector\Http\Controllers\Api\ContactLoginController;
use Modules\Connector\Http\Controllers\Api\DataJobSheetController;
use Modules\Connector\Http\Controllers\Api\JobsheetExitController;
use Modules\Connector\Http\Controllers\Api\MaintenanceNoteController;
use Modules\Connector\Http\Controllers\Api\ServicePackageApiController;
use Modules\Connector\Http\Controllers\Api\PackageProductApiController;
use Modules\Connector\Http\Controllers\Api\BlogController as ApiBlogController;
use Modules\Connector\Http\Controllers\Api\TechnicianAttendanceController;
use Modules\Connector\Http\Controllers\Api\TransactionTechnicianEfficiencyApiController;
use Modules\Connector\Http\Controllers\Api\PurchaseController as ApiPurchaseController;
use Modules\Connector\Http\Controllers\Api\AdvancePaymentController;
use Modules\Connector\Http\Controllers\Api\SmsController;
use Modules\Connector\Http\Controllers\Api\PrivacyPolicyController;
use Modules\ArtificialIntelligence\Http\Controllers\VINLookupController;
use Modules\ArtificialIntelligence\Http\Controllers\DiagnoseMessageController;
use Modules\Connector\Http\Controllers\Api\BundleApiController;
use Modules\Connector\Http\Controllers\Api\ClientFlaggedProductApiController;
use Modules\Connector\Http\Controllers\Api\JobEstimatorApiController;
use Modules\CheckCar\Http\Controllers\Api\BuySellBookingApiController;
use Modules\Connector\Http\Controllers\Api\LabourByVehicleController;
use Modules\Connector\Http\Controllers\Api\VersionController;
use Modules\Connector\Http\Controllers\Api\SocialAuthController;
use Modules\Connector\Http\Controllers\Api\PublicEcomController;

// Route::prefix('connector/api')->group(function () {
Route::post('/register', [ContactLoginController::class, 'saveDataRegister']);
Route::post('/contact/resend-registration-otp', [ContactLoginController::class, 'resendRegistrationOtp']);
Route::post('/contact/login', [ContactLoginController::class, 'login']);
Route::get('/contact/check/phone', [ContactLoginController::class, 'checkPhone']);
Route::post('/contact/forgot-password', [ContactLoginController::class, 'forgotPassword']);
Route::post('/contact/reset-password', [ContactLoginController::class, 'resetPassword']);
Route::post('/contact/upload-image', [dashboardController::class, 'uploadImage']);
Route::get('/contact/get-bussnis-logo', [dashboardController::class, 'uploadImageWithDomain']);
Route::get('/contact/check/phone/joborder', [BookingController::class, 'testcheckphone']);
Route::get('/contact/check/phone/estimator', [BookingController::class, 'testcheckphoneEstimator']);
Route::get('/contact/status', [BookingController::class, 'status']);
Route::get('/contact/productName/{id}', [BookingController::class, 'getProductName']);
Route::get('/connector/api/public-save-product', [BookingController::class, 'saveData']);
Route::get('connector/api/job-estimator-details', [BookingController::class, 'getJobEstimatorDetails']);
Route::get('/contact/saveProduct', [BookingController::class, 'saveData']);
Route::get('/connector/api/public/ecom-products', [PublicEcomController::class, 'products']);
Route::get('/connector/api/public/ecom-products-by-category/{category_id}', [PublicEcomController::class, 'productsByCAtegoryId']);
Route::get('/connector/api/public/ecom-product/{id}',[PublicEcomController::class, 'productById']);


// });
// Social Auth Routes (protected by auth:api)
Route::prefix('connector/api')->group(function () {
    Route::post('/auth/social-customer-login', [SocialAuthController::class, 'socialCustomerLogin']);
    Route::resource('taxonomy', 'Modules\Connector\Http\Controllers\Api\CategoryController')->only('index', 'show');
    Route::get('/Branshes', [BookingController::class, 'getBranch']);
    // Route::post('/auth/registration-with-social-media', [SocialAuthController::class, 'registrationWithSocialMedia']);
    // Route::post('/auth/existing-account-check', [SocialAuthController::class, 'existingAccountCheck']);
    Route::post('/auth/send-phone-verification-otp', [SocialAuthController::class, 'sendPhoneVerificationOtp']);
    Route::post('/auth/verify-phone-and-set-mobile', [SocialAuthController::class, 'verifyPhoneAndSetMobile']);
 

    Route::post('/auth/update-social-mobile', [SocialAuthController::class, 'updateSocialMobile']);
    Route::post('/auth/restore-deleted-account', [SocialAuthController::class, 'restoreDeletedAccount']);
    
    // Phone ownership confirmation flow (when phone linked to another account)
    Route::post('/auth/send-ownership-otp', [SocialAuthController::class, 'sendOwnershipOtp']);
    Route::post('/auth/verify-and-merge-accounts', [SocialAuthController::class, 'verifyAndMergeAccounts']);
});

Route::middleware(['timezone'])
    ->prefix('connector/api')
    ->name('connector.api.')
    ->group(function () {

        Route::get('/models/{id}', [BookingController::class, 'getModels'])
            ->name('models');

    });
Route::middleware('auth:api', 'timezone')->prefix('connector/api')->name('connector.api.')->group(function () {


    Route::get('/brands', [BookingController::class, 'getBrand']);

    Route::get('/services', [BookingController::class, 'getService']);
    Route::get('/Info/car', [BookingController::class, 'getInfo']);
    Route::get('/Info/customer', [BookingController::class, 'getInfoCustomer']);
    Route::post('/add/car', [BookingController::class, 'customerAddCar']);
    Route::post('/add/booking', [BookingController::class, 'customerBooking']);
    Route::post('/add/booking-pickup', [BookingController::class, 'customerPickupRequest']);
    
    
    Route::get('/customers/search', [Modules\Connector\Http\Controllers\Api\ContactController::class, 'search'])->name('connector.customers.search');
    Route::post('/contact/soft-delete', [ContactLoginController::class, 'softDeleteAccount']);
    Route::post('contactapi/quick-store', [Modules\Connector\Http\Controllers\Api\ContactController::class, 'quickStore']);
    Route::get('contactapi/by-vin', [Modules\Connector\Http\Controllers\Api\ContactController::class, 'contactsByVin']);
    Route::get('/customer/booking', [BookingController::class, 'getBookingCustomer']);
    Route::get('/customer/joborder', [BookingController::class, 'getJoborderCustomer']);
    Route::get('/customer/data/joborder/{id}', [BookingController::class, 'datajoborder']);
    Route::get('/status', [BookingController::class, 'status']);
    Route::get('/productName/{id}', [BookingController::class, 'getProductName']);
    Route::get('/resendLoginOtp', [ContactLoginController::class, 'resendLoginOtp']);

    // Job Estimators listing (ordered by status and date)
    Route::get('/job-estimators', [BookingController::class, 'getJobEstimators']);
    // Route::post('/job-estimators', [BookingController::class, 'createJobEstimator']);
    Route::post('/job-estimators/store', [JobEstimatorApiController::class, 'store']);
    Route::post('/storeContact', [BookingController::class, 'storeContact']);
    Route::post('/storeBooking', [BookingController::class, 'storeBooking']);
    Route::get('/getcars/{id}', [BookingController::class, 'getcars']);
    Route::get('/getAllContacts', [BookingController::class, 'getAllContacts']);
    Route::post('/storeCar', [BookingController::class, 'storeCar']);
    // Update contact device by ID (PATCH)
    Route::patch('/contact-devices/{id}', [BookingController::class, 'updateContactDevice']);


    Route::get('send-sms/infojoborder/{job_order_id}', [BusinessController::class, 'sendInfoJoborderAsSms']);
    Route::get('/send-sms/survey/{survey_id}/{job_order_id}', [BusinessController::class, 'sendsurveyAsSms']);

    // SMS Routes
    Route::prefix('sms')->controller(SmsController::class)->group(function () {
        Route::get('/messages', 'getMessages')->name('sms.messages');
        Route::get('/message-template', 'getMessageTemplate')->name('sms.template');
        Route::get('/job-sheet', 'getJobSheet')->name('sms.job-sheet');
        Route::post('/send', 'sendSms')->name('sms.send');
        Route::post('/send-bulk', 'sendBulkSms')->name('sms.send-bulk');
    });

    Route::resource('business-location', Modules\Connector\Http\Controllers\Api\BusinessLocationController::class)->only('index', 'show');
    Route::get('booking-app-locations', [Modules\Connector\Http\Controllers\Api\BusinessLocationController::class, 'booking_app_locations']);


    Route::resource('contactapi', Modules\Connector\Http\Controllers\Api\ContactController::class)->only('index', 'show', 'store', 'update');
    Route::post('contactapi-payment', [Modules\Connector\Http\Controllers\Api\ContactController::class, 'contactPay']);

    Route::resource('unit', Modules\Connector\Http\Controllers\Api\UnitController::class)->only('index', 'show');
    
    Route::get('taxonomy/{category_id}/subcategories', 'Modules\Connector\Http\Controllers\Api\CategoryController@getSubcategories');

    Route::resource('brand', Modules\Connector\Http\Controllers\Api\BrandController::class)->only('index', 'show');

    Route::resource('blog', ApiBlogController::class)->only('index', 'show');

    Route::resource('product', Modules\Connector\Http\Controllers\Api\ProductController::class)->only('index', 'show', 'store');

    // labor API
    Route::get('labor/flat-rates', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'listFlatRates']);
    Route::get('labor/flat-rate/{id}', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'getFlatRateDetails']);
    Route::post('labor/options-by-locations', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'optionsByLocations']);
    Route::get('labor', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'index']);
    Route::post('labor', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'store']);
    Route::get('labor/{id}', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'show']);
    Route::put('labor/{id}', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'update']);
    Route::delete('labor/{id}', [Modules\Connector\Http\Controllers\Api\ServiceController::class, 'destroy']);

    // Purchases (API)
    Route::post('purchase-drafts', [ApiPurchaseController::class, 'storeDraft']);
    // Create purchase from jobsheet and list purchased products by jobsheet
    Route::post('purchase-from-jobsheet', [ApiPurchaseController::class, 'storeFromJobsheet']);
    Route::get('jobsheet/{jobsheet_id}/purchased-products', [ApiPurchaseController::class, 'getPurchasedProductsByJobsheet']);
    


    Route::resource('package-products', \Modules\Connector\Http\Controllers\Api\PackageProductApiController::class)->only('index','show');
    
    Route::get('package-products/packages-by-category', [\Modules\Connector\Http\Controllers\Api\PackageProductApiController::class, 'getPackagesByCategory']);
    Route::get('package-products/package/{package_id}', [\Modules\Connector\Http\Controllers\Api\PackageProductApiController::class, 'getPackageWithProducts']);
    //endpoints
    Route::get('exit/permission', [JobsheetExitController::class, 'index']);
    Route::put('update/permission/{id}', [JobsheetExitController::class, 'updateExitPermission']);


    Route::prefix('maintenance-notes')->controller(MaintenanceNoteController::class)->group(function () {
        Route::post('/', 'store');     // Create a Maintenance Note
        Route::put('/{id}', 'update'); // Update a Maintenance Note
        Route::delete('/{id}', 'destroy'); // Delete a Maintenance Note
        // New separate endpoints
        Route::get('/by-job-sheet/{job_sheet_id}', 'getByJobSheet');
        Route::post('/create', 'storeNewPurchase');
        Route::put('/update/{id}', 'updateExisting');
    });


    Route::post('/uplaod-car-media', [MediaController::class, 'storeMedia']);
    Route::delete('/delete-media', [MediaController::class, 'deleteMedia']);
    Route::group([], function () {

        Route::get('/AllStaff', [EndPointsController::class, 'get_all_service_staff']);
        Route::get('/AllChecklist', [EndPointsController::class, 'get_all_checklist']);
        Route::get('/AllWorkshops', [EndPointsController::class, 'get_all_workshops']);
        Route::get('/AllStatus', [EndPointsController::class, 'get_all_status']);
        Route::get('/AllServices', [EndPointsController::class, 'types_of_services']);
        Route::get('/FuelStatus', [EndPointsController::class, 'fuel_status']);
        Route::post('/ask', [EndPointsController::class, 'ask']);
        // Technician assignment to workshops (requires today's attendance)
        Route::post('assignments/technician-to-workshop', [EndPointsController::class, 'assignTechnicianToWorkshop']);
        Route::get('assignments/technician-assignments', [EndPointsController::class, 'technicianAssignments']);
    });
    Route::prefix('spare-parts')->group(function () {
        Route::post('/store_spareparts', [SparePartsController::class, 'store_spareparts']);
        Route::post('/store', [SparePartsController::class, 'store']);
        Route::delete('/delete/{id}', [SparePartsController::class, 'destroy']); // Ensure ID is passed
        Route::get('/jobsheet/{job_order_id}', [SparePartsController::class, 'getSparePartsByJobsheet']);
        Route::patch('/{id}/delivered-status', [SparePartsController::class, 'patchDeliveredStatus']);
        Route::patch('/{id}/inventory-delivery', [SparePartsController::class, 'markInventoryDelivery']);
    });


    Route::resource('/bookings', BookingController::class);

    Route::get('dashboard/counter', [dashboardController::class, 'data']);
    Route::get('dashboard/table', [dashboardController::class, 'table']);
    Route::get('dashboard/draw', [dashboardController::class, 'draw']);
    Route::put('dashboard/table/{id}', [dashboardController::class, 'updatestatus']);
    Route::get('sidebar', [dashboardController::class, 'sidebar']);

    Route::get('jobsheet/data', [DataJobSheetController::class, 'data']);
    Route::get('workers/data', [DataJobSheetController::class, 'workers']);
    Route::put('notification', [DataJobSheetController::class, 'updateStatus']);


 
     // Route::get('/testupdate',[JobSheetController::class,'testUpdate']);
     Route::post('/testupdate', [JobSheetController::class, 'test']);
     Route::delete('/jobsheets/{job_sheet_id}/tagged-images/{media_id}', [JobSheetController::class, 'deleteTaggedImage']);
     Route::resource('/jobsheets', JobSheetController::class);
 
     // Route::get('/register', [ContactLoginController::class, 'register']);
     Route::post('/register', [ContactLoginController::class, 'saveDataRegiste']);
    Route::post('/login', [ContactLoginController::class, 'login']);

    Route::resource('/bookings', BookingController::class);
    // Route::post('/bookings/lookup-chassis', [VINLookupController::class, 'lookupChassis'])->name('api.lookup_chassis');
    Route::get('/bookings/jobsheets/call_back_ref', [BookingController::class, 'getJobSheetsByContactDevice']);
    Route::post('/bookings/save-pickup-location', [BookingController::class, 'savePickupLocation']);
    Route::get('all/message', [MessagesController::class, 'show']);
    Route::post('response', [MessagesController::class, 'storeResponse']);
    Route::post('chage/status', [MessagesController::class, 'messagechangestatus']);

    Route::get('dashboard/counter', [dashboardController::class, 'data']);
    Route::get('dashboard/table', [dashboardController::class, 'table']);
    Route::get('dashboard/draw', [dashboardController::class, 'draw']);
    Route::put('dashboard/table/{id}', [dashboardController::class, 'updatestatus']);

    Route::get('jobsheet/data', [DataJobSheetController::class, 'data']);

    // Route::get('/testupdate',[JobSheetController::class,'testUpdate']);
    Route::post('/testupdate', [JobSheetController::class, 'test']);
    Route::resource('/jobsheets', JobSheetController::class);
    Route::post('/complete-jobsheet', [SparePartsController::class, 'complete_jobsheet']);


    Route::get('/job-sheets/{id}/print', [JobSheetController::class, 'apiPrint']);


    Route::group([], function () {





        Route::get('/getObdProblem', [OpenAIController::class, 'getObdProblem']);
        Route::get('/getObdProblemPaginated', [OpenAIController::class, 'getObdProblemPaginated']);

        Route::get('/jobsheet_obds_ai/{id}', [DiagnoseMessageController::class, 'handle_jobsheet_obds_ai']);
        // Route::post('/import-brand-models', [OpenAIController::class, 'importBrandAndModels'])->name('import.BrandAndModels');


        // Route::post('/ask-gemini-flash', [OpenAIController::class, 'askGeminiFlash']);
    });

    Route::get('selling-price-group', [Modules\Connector\Http\Controllers\Api\ProductController::class, 'getSellingPriceGroup']);

    Route::get('variation/{id?}', [Modules\Connector\Http\Controllers\Api\ProductController::class, 'listVariations']);

    Route::resource('tax', 'Modules\Connector\Http\Controllers\Api\TaxController')->only('index', 'show');

    // Technician efficiency metrics by transaction
    Route::get('transactions/{transaction_id}/technician-efficiency', [TransactionTechnicianEfficiencyApiController::class, 'show']);

    Route::resource('table', Modules\Connector\Http\Controllers\Api\TableController::class)->only('index', 'show');

    Route::get('user/loggedin', [Modules\Connector\Http\Controllers\Api\UserController::class, 'loggedin']);
    Route::post('user-registration', [Modules\Connector\Http\Controllers\Api\UserController::class, 'registerUser']);
    Route::resource('user', Modules\Connector\Http\Controllers\Api\UserController::class)->only('index', 'show');

    Route::resource('types-of-service', Modules\Connector\Http\Controllers\Api\TypesOfServiceController::class)->only('index', 'show');

    Route::get('payment-accounts', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getPaymentAccounts']);

    Route::get('payment-methods', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getPaymentMethods']);

    // Specific routes must be defined BEFORE the resource route
    Route::get('sell/shift-stats', [Modules\Connector\Http\Controllers\Api\SellController::class, 'shiftStats']);
    Route::post('sell/clock-in', [Modules\Connector\Http\Controllers\Api\SellController::class, 'clockIn']);
    Route::post('sell/clock-out', [Modules\Connector\Http\Controllers\Api\SellController::class, 'clockOut']);
    Route::post('sell/proforma', [PublicEcomController::class, 'storeProforma']);
    Route::resource('sell', Modules\Connector\Http\Controllers\Api\SellController::class)->only('index', 'store', 'show', 'update', 'destroy');

    Route::post('sell-return', [Modules\Connector\Http\Controllers\Api\SellController::class, 'addSellReturn']);

    Route::get('list-sell-return', [Modules\Connector\Http\Controllers\Api\SellController::class, 'listSellReturn']);

    Route::post('update-shipping-status', [Modules\Connector\Http\Controllers\Api\SellController::class, 'updateSellShippingStatus']);

    Route::resource('expense', Modules\Connector\Http\Controllers\Api\ExpenseController::class)->only('index', 'store', 'show', 'update');
    Route::get('expense-refund', [Modules\Connector\Http\Controllers\Api\ExpenseController::class, 'listExpenseRefund']);

    Route::get('expense-categories', [Modules\Connector\Http\Controllers\Api\ExpenseController::class, 'listExpenseCategories']);

    Route::resource('cash-register', Modules\Connector\Http\Controllers\Api\CashRegisterController::class)->only('index', 'store', 'show', 'update');

    Route::get('business-details', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getBusinessDetails']);

    Route::get('about-us', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getAboutUs']);

    Route::get('loyalty-points', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getCustomerLoyaltyPoints']);
    Route::post('loyalty-points/redeem', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'redeemLoyaltyPoints']);

    Route::get('profit-loss-report', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getProfitLoss']);

    Route::get('product-stock-report', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getProductStock']);
    Route::get('notifications', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getNotifications']);
    Route::get('maintenance-notifications', [NotificationController::class, 'index']);
    Route::post('maintenance-notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('maintenance-notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

  // FCM Token Management Routes
    Route::prefix('fcm-tokens')->controller(\Modules\Connector\Http\Controllers\Api\FcmTokenController::class)->group(function () {
        Route::get('/', 'index')->name('fcm-tokens.index');                    
        Route::post('/', 'store')->name('fcm-tokens.store');                  
        Route::delete('/{id}', 'destroy')->name('fcm-tokens.destroy');         
        Route::patch('/{id}/status', 'updateStatus')->name('fcm-tokens.status'); 
        Route::post('/test-notification', 'testNotification')->name('fcm-tokens.test'); 
    });


    // Service Predictions (Smart Maintenance CRM)
    Route::prefix('service-predictions')->controller(\Modules\Connector\Http\Controllers\Api\ServicePredictionController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/dashboard-stats', 'dashboardStats');
        Route::get('/customer/{contact_id}', 'forCustomer');
        Route::post('/recalculate', 'recalculate');
    });

    Route::get('active-subscription', [Modules\Connector\Http\Controllers\Api\SuperadminController::class, 'getActiveSubscription']);
    Route::get('packages', [Modules\Connector\Http\Controllers\Api\SuperadminController::class, 'getPackages']);

    Route::get('get-attendance/{user_id}', [Modules\Connector\Http\Controllers\Api\AttendanceController::class, 'getAttendance']);
    Route::post('clock-in', [Modules\Connector\Http\Controllers\Api\AttendanceController::class, 'clockin']);
    Route::post('clock-out', [Modules\Connector\Http\Controllers\Api\AttendanceController::class, 'clockout']);
    Route::get('holidays', [Modules\Connector\Http\Controllers\Api\AttendanceController::class, 'getHolidays']);
    Route::post('update-password', [Modules\Connector\Http\Controllers\Api\UserController::class, 'updatePassword']);
    Route::post('forget-password', [Modules\Connector\Http\Controllers\Api\UserController::class, 'forgetPassword']);
    Route::get('get-location', [Modules\Connector\Http\Controllers\Api\CommonResourceController::class, 'getLocation']);

    Route::prefix('technicians')->controller(TechnicianAttendanceController::class)->group(function () {
        Route::get('/', 'index');                           // Get all technicians with attendance status and job assignments
        Route::post('/clock-in', 'clockIn');                // Clock in a technician
        Route::post('/clock-out', 'clockOut');              // Clock out a technician
        Route::post('/bulk-clock-in', 'bulkClockIn');       // Bulk clock in multiple technicians
        Route::post('/bulk-clock-out', 'bulkClockOut');     // Bulk clock out multiple technicians
        Route::get('/attendance-history', 'history');        // Get attendance history with filtering
    });

    Route::get('new_product', [Modules\Connector\Http\Controllers\Api\ProductSellController::class, 'newProduct'])->name('new_product');
    Route::get('new_sell', [Modules\Connector\Http\Controllers\Api\ProductSellController::class, 'newSell'])->name('new_sell');
    Route::get('new_contactapi', [Modules\Connector\Http\Controllers\Api\ProductSellController::class, 'newContactApi'])->name('new_contactapi');

    // Performance Management API Routes
    Route::prefix('performance')->group(function () {
        Route::get('/', [Modules\Connector\Http\Controllers\Api\PerformanceController::class, 'index']);
        Route::get('/dashboard', [Modules\Connector\Http\Controllers\Api\PerformanceController::class, 'dashboard']);
        Route::get('/technician/{user_id}', [Modules\Connector\Http\Controllers\Api\PerformanceController::class, 'show']);
    });

    // Timer Management API Routes
    Route::prefix('timers')->group(function () {
        Route::get('/', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'index']);
        Route::post('/start', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'startTimer']);
        Route::put('/pause/{timer_id}', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'pauseTimer']);
        Route::put('/resume/{timer_id}', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'resumeTimer']);
        Route::put('/complete/{timer_id}', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'completeTimer']);
        Route::post('/allocation', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'updateTimeAllocation']);
        Route::post('/play-all', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'playAll']);
        Route::post('/pause-all', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'pauseAll']);
        Route::post('/complete-all', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'completeAll']);
        Route::get('/history', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'history']);
        Route::post('/unassign-technician', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'unassignTechnicianAndDeleteTimers']);
        Route::get('/pre-phrases', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'getPrePhrases']);
        Route::post('/stop-reason', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'saveTimerStopReason']);
        Route::put('/stop-reason/{id}/end', [Modules\Connector\Http\Controllers\Api\TimerController::class, 'endTimerStopReason']);
    });

    // Assignment Management API Routes
    Route::prefix('assignments')->group(function () {
        Route::get('/', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'index']);
        Route::post('/', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'AssignWorkshop_toJobsheet']);
        Route::put('/', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'update']);
        Route::delete('/', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'destroy']);
        Route::post('/assign-technicians', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'assignTechnicians']);
        Route::post('/unassign-technicians', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'unassignTechnicians']);
        Route::get('/available-technicians', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'availableTechnicians']);
        Route::get('/available-workshops', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'Fetch_Workshops_jobsheet']);
        Route::get('/workshops-with-technicians', [Modules\Connector\Http\Controllers\Api\AssignController::class, 'getWorkshopsWithTechnicians']);
    });

        Route::post('/apply', [Modules\Connector\Http\Controllers\Api\AdvancePaymentController::class, 'applyAdvancePayment']);
        Route::get('/contact/{contact_id}', [Modules\Connector\Http\Controllers\Api\AdvancePaymentController::class, 'getContactAdvancePayments']);

        // Privacy Policy Routes
        Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show']);
        Route::put('/privacy-policy', [PrivacyPolicyController::class, 'update']);

        // Repair Order Import
        Route::post('/import/repair-orders', [\Modules\Connector\Http\Controllers\Api\RepairOrderImportController::class, 'importRepairOrders']);

    });

    Route::middleware('auth:api', 'timezone')->prefix('connector/api/crm')->name('connector.api.crm.')->group(function () {
        Route::resource('follow-ups', 'Modules\Connector\Http\Controllers\Api\Crm\FollowUpController')->only('index', 'store', 'show', 'update');

        Route::get('follow-up-resources', [Modules\Connector\Http\Controllers\Api\Crm\FollowUpController::class, 'getFollowUpResources']);

        Route::get('leads', [Modules\Connector\Http\Controllers\Api\Crm\FollowUpController::class, 'getLeads']);

        Route::post('call-logs', [Modules\Connector\Http\Controllers\Api\Crm\CallLogsController::class, 'saveCallLogs']);
    });

    Route::middleware('auth:api', 'timezone')->prefix('connector/api')->group(function () {
        Route::get('field-force', [Modules\Connector\Http\Controllers\Api\FieldForce\FieldForceController::class, 'index']);
        Route::post('field-force/create', [Modules\Connector\Http\Controllers\Api\FieldForce\FieldForceController::class, 'store']);
        Route::post('field-force/update-visit-status/{id}', [Modules\Connector\Http\Controllers\Api\FieldForce\FieldForceController::class, 'updateStatus']);
    });

// CheckCar - Car Inspection API
Route::middleware('auth:api', 'timezone')->prefix('connector/api/checkcar')->name('api.checkcar.')->group(function () {
    // Get inspection structure (categories, subcategories, elements, options, presets)
    Route::get('structure', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'getStructure'])->name('structure');

    // Inspections CRUD
    Route::get('inspections', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'index'])->name('inspections.index');
    Route::get('inspections/{id}', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'show'])->name('inspections.show');
    Route::get('inspections/by-jobsheet/{job_sheet_id}', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'getByJobSheet'])->name('inspections.by-jobsheet');
    Route::post('inspections', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'store'])->name('inspections.store');
    Route::put('inspections/{id}', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'update'])->name('inspections.update');
    Route::delete('inspections/{id}', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'destroy'])->name('inspections.destroy');

    // Inspection actions
    Route::post('inspections/{id}/complete', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'complete'])->name('inspections.complete');
    Route::post('inspections/{id}/share', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'generateShareLink'])->name('inspections.share');
    Route::post('inspections/{id}/send-sms', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'sendInspectionSms'])->name('inspections.send-sms');
    Route::delete('inspections/{id}/item-part', [Modules\Connector\Http\Controllers\Api\CheckCarController::class, 'deleteItemPart'])->name('inspections.item-part');

    // Buy & Sell Booking API
    Route::post('buy-sell/store-contact', [BuySellBookingApiController::class, 'storeContact'])->name('buy-sell.store-contact');
    Route::post('buy-sell/store', [BuySellBookingApiController::class, 'store'])->name('buy-sell.store');
});

Route::middleware('auth:api', 'timezone')->prefix('connector/api/bundles')->name('api.bundles.')->group(function () {
    Route::get('/', [BundleApiController::class, 'getBundles'])->name('index');
    Route::post('/virtual-product', [BundleApiController::class, 'createVirtualProduct'])->name('virtual-product');
});

Route::middleware('auth:api', 'timezone')->prefix('connector/api/generic-spare-parts')->name('api.generic-spare-parts.')->group(function () {
    Route::get('/', [BundleApiController::class, 'getGenericSpareParts'])->name('index');
    Route::post('/', [BundleApiController::class, 'createGenericSparePart'])->name('create');
});

// Client Flagged Products API
Route::middleware('auth:api', 'timezone')->prefix('connector/api/client-flagged-products')->name('api.client-flagged-products.')->group(function () {
    Route::get('/', [ClientFlaggedProductApiController::class, 'getProducts'])->name('index');
    Route::post('/create', [ClientFlaggedProductApiController::class, 'createProduct'])->name('create');
    Route::post('/sell', [ClientFlaggedProductApiController::class, 'sellProduct'])->name('sell');
});

// Labour by Vehicle API
Route::middleware('auth:api', 'timezone')->prefix('connector/api/labour-by-vehicle')->name('api.labour-by-vehicle.')->group(function () {

    Route::get('/jobsheet/{job_sheet_id}/labour-products', [LabourByVehicleController::class, 'getLabourProductsByJobSheet'])->name('jobsheet.labour-products');
});

// Version Update API
Route::middleware('auth:api', 'timezone')->prefix('connector/api/version')->name('api.version.')->group(function () {
    Route::get('/info', [VersionController::class, 'getVersionInfo'])->name('info');
    Route::post('/check', [VersionController::class, 'checkUpdate'])->name('check');
});

// Anonymous FCM Token Routes (No Authentication Required)
Route::prefix('connector/api/fcm-tokens/anonymous')->controller(\Modules\Connector\Http\Controllers\Api\FcmTokenController::class)->group(function () {
    Route::post('/register', 'storeAnonymous')->name('fcm-tokens.anonymous.register'); // Register anonymous FCM token
    Route::post('/subscribe-topic', 'subscribeToTopic')->name('fcm-tokens.anonymous.subscribe'); // Subscribe to topic
    Route::post('/unsubscribe-topic', 'unsubscribeFromTopic')->name('fcm-tokens.anonymous.unsubscribe'); // Unsubscribe from topic
    Route::post('/topics', 'getAnonymousTopics')->name('fcm-tokens.anonymous.topics'); // Get subscribed topics
});

// ═══════════════════════════════════════════════════════════════
// CarMarket - Vehicle Marketplace API
// ═══════════════════════════════════════════════════════════════

// Buyer / Public endpoints (browse, search, inquire)
Route::middleware('auth:api', 'timezone')->prefix('connector/api/carmarket')->name('api.carmarket.')->group(function () {
    // Browse & Search
    Route::get('/vehicles', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/{id}', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'show'])->name('vehicles.show');
    Route::get('/filters', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'filters'])->name('filters');
    Route::get('/filters-all', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'allFilters'])->name('filters.all');
    Route::get('/featured', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'featured'])->name('featured');
    Route::get('/brands/{brandId}/models', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'getModelsByBrand'])->name('brands.models');

    // Inquiries
    Route::post('/vehicles/{id}/inquiry', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'sendInquiry'])->name('vehicles.inquiry');

    // Favorites
    Route::post('/vehicles/{id}/favorite', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'toggleFavorite'])->name('vehicles.favorite');

    // Report
    Route::post('/vehicles/{id}/report', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'reportListing'])->name('vehicles.report');

    // Buyer-specific
    Route::get('/buyer/inquiries', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'myInquiries'])->name('buyer.inquiries');
    Route::get('/buyer/favorites', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'favorites'])->name('buyer.favorites');
    Route::post('/buyer/saved-searches', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'saveSearch'])->name('buyer.saved-searches.store');
    Route::get('/buyer/saved-searches', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'savedSearches'])->name('buyer.saved-searches.index');
    Route::delete('/buyer/saved-searches/{id}', [\Modules\CarMarket\Http\Controllers\Api\BuyerVehicleController::class, 'deleteSavedSearch'])->name('buyer.saved-searches.destroy');
});

// Seller endpoints (manage own listings)
Route::middleware('auth:api', 'timezone')->prefix('connector/api/carmarket/seller')->name('api.carmarket.seller.')->group(function () {
    Route::get('/dashboard', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'dashboard'])->name('dashboard');
    Route::get('/vehicles', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/{id}', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'show'])->name('vehicles.show');
    Route::post('/vehicles', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{id}', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{id}', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'destroy'])->name('vehicles.destroy');

    // Media management
    Route::post('/vehicles/{id}/media', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'uploadMedia'])->name('vehicles.media.upload');
    Route::delete('/vehicles/{vehicleId}/media/{mediaId}', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'deleteMedia'])->name('vehicles.media.delete');
    Route::post('/vehicles/{vehicleId}/media/{mediaId}/set-primary', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'setPrimaryMedia'])->name('vehicles.media.set-primary');

    // Mark sold
    Route::post('/vehicles/{id}/mark-sold', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'markSold'])->name('vehicles.mark-sold');

    // Seller inquiries
    Route::get('/inquiries', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'inquiries'])->name('inquiries.index');
    Route::post('/inquiries/{id}/reply', [\Modules\CarMarket\Http\Controllers\Api\SellerVehicleController::class, 'replyInquiry'])->name('inquiries.reply');
});
