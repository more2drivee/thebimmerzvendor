<?php

use App\Http\Controllers\Install;
use App\Http\Controllers\Restaurant;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
// use App\Http\Controllers\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BackUpController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\LabelsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellPosController;
use App\Http\Controllers\TaxRateController;

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\GroupTaxController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ShortUrlController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\WarrantyController;
use Modules\Repair\Entities\MaintenanceNote;
use App\Http\Controllers\ApiSettingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SellReturnController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\ImportSalesController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\POSDashboardController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\InvoiceLayoutController;
use App\Http\Controllers\InvoiceSchemeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\SellDashboardController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\AccountReportsController;
use App\Http\Controllers\DataPermissionController;
use App\Http\Controllers\ImportProductsController;
use App\Http\Controllers\LedgerDiscountController;
use App\Http\Controllers\PackageProductController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ServicePackageController;
use App\Http\Controllers\TypesOfServiceController;
use App\Http\Controllers\DocumentAndNoteController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\MissingPurchaseController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\BusinessLocationController;
use App\Http\Controllers\ContactDashboardController;
use App\Http\Controllers\GenericSparePartController;
use App\Http\Controllers\LocationSettingsController;
use App\Http\Controllers\AccountsDashboardController;
use App\Http\Controllers\ProductExtensionsController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\ProductsDashboardController;
use App\Http\Controllers\PurchaseDashboardController;
use App\Http\Controllers\PurchaseReceivingController;
use App\Http\Controllers\SellingPriceGroupController;
use App\Http\Controllers\VariationTemplateController;
use App\Http\Controllers\ImportOpeningStockController;
use App\Http\Controllers\TransactionPaymentController;
use App\Http\Controllers\ProductOrganizationController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\Admin\AdminDashboardController;


use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\SalesCommissionAgentController;
use App\Http\Controllers\CoreProductManagementController;
use App\Http\Controllers\DashboardConfiguratorController;
use App\Http\Controllers\CombinedPurchaseReturnController;
use App\Http\Controllers\SimpleCarProductImportController;
use App\Http\Controllers\UniversalProductImportController;
use App\Http\Controllers\InventoryDeliveryReturnController;
use Modules\Connector\Http\Controllers\Api\MessagesController;
use Modules\Repair\Http\Controllers\MaintenanceNoteController;
use Modules\ArtificialIntelligence\Http\Controllers\VINLookupController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include_once 'install_r.php';

// // Public VIN lookup endpoint - no authentication required
// Route::post('/booking/lookup-chassis', [Modules\ArtificialIntelligence\Http\Controllers\VINLookupController::class, 'lookupChassis'])->name('booking.lookup_chassis');

// Public short URL redirect (no auth)
Route::get('/s/{code}', [ShortUrlController::class, 'redirect'])->name('short_url.redirect');

Route::middleware(['setData'])->group(function () {

    \Illuminate\Support\Facades\Auth::routes();

    Route::get('/business/register', [BusinessController::class, 'getRegister'])->name('business.getRegister');
    Route::post('/business/register', [BusinessController::class, 'postRegister'])->name('business.postRegister');
    Route::post('/business/register/check-username', [BusinessController::class, 'postCheckUsername'])->name('business.postCheckUsername');
    Route::post('/business/register/check-email', [BusinessController::class, 'postCheckEmail'])->name('business.postCheckEmail');

    Route::get('/invoice/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_invoice');
    Route::get('/quote/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_quote');

    Route::get('/pay/{token}', [SellPosController::class, 'invoicePayment'])
        ->name('invoice_payment');
    Route::post('/confirm-payment/{id}', [SellPosController::class, 'confirmPayment'])
        ->name('confirm_payment');
});

//Routes for authenticated users only
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    // Service Packages & Package Products Web Routes
    Route::get('service-packages', [ServicePackageController::class, 'index'])->name('service-packages.index');
    Route::get('service-packages/datatable', [ServicePackageController::class, 'datatable'])->name('service-packages.datatable');
    Route::get('service-packages/create', [ServicePackageController::class, 'create'])->name('service-packages.create');
    Route::post('service-packages', [ServicePackageController::class, 'store'])->name('service-packages.store');
    Route::get('service-packages/{id}/edit', [ServicePackageController::class, 'edit'])->name('service-packages.edit');
    Route::put('service-packages/{id}', [ServicePackageController::class, 'update'])->name('service-packages.update');
    Route::delete('service-packages/{id}', [ServicePackageController::class, 'destroy'])->name('service-packages.destroy');
    Route::get('service-packages/get-models/{deviceId}', [ServicePackageController::class, 'getRepairDeviceModels'])->name('service-packages.get-models');
    Route::get('package-products', [PackageProductController::class, 'index'])->name('package-products.index');
    Route::get('package-products/datatable', [PackageProductController::class, 'datatable'])->name('package-products.datatable');
    Route::get('package-products/create', [PackageProductController::class, 'create'])->name('package-products.create');
    Route::post('package-products', [PackageProductController::class, 'store'])->name('package-products.store');
    Route::get('package-products/{id}/edit', [PackageProductController::class, 'edit'])->name('package-products.edit');
    Route::put('package-products/{id}', [PackageProductController::class, 'update'])->name('package-products.update');
    Route::delete('package-products/{id}', [PackageProductController::class, 'destroy'])->name('package-products.destroy');


    Route::get('message/{id}', [MessagesController::class, 'showMessage'])->name('show.message');
    Route::get('change/status/{id}', [MessagesController::class, 'changestatus'])->name('chage.status');
    Route::get('qrcode/secret', [MessagesController::class, 'qrcode'])->name('qrcode');
    Route::get('contact/show/dd', [ContactController::class, 'getaddcontact']);

    // Route::get('/get-models', [ContactController::class, 'getModels']);




    Route::get('pos/payment/{id}', [SellPosController::class, 'edit'])->name('edit-pos-payment');
    Route::get('service-staff-availability', [SellPosController::class, 'showServiceStaffAvailibility']);
    Route::get('pause-resume-service-staff-timer/{user_id}', [SellPosController::class, 'pauseResumeServiceStaffTimer']);
    Route::get('mark-as-available/{user_id}', [SellPosController::class, 'markAsAvailable']);

    Route::resource('purchase-requisition', PurchaseRequisitionController::class)->except(['edit', 'update']);
    Route::post('/get-requisition-products', [PurchaseRequisitionController::class, 'getRequisitionProducts'])->name('get-requisition-products');
    Route::get('get-purchase-requisitions/{location_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitions']);
    Route::get('get-purchase-requisition-lines/{purchase_requisition_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitionLines']);

    Route::get('/sign-in-as-user/{id}', [ManageUserController::class, 'signInAsUser'])->name('sign-in-as-user');

    Route::post('/update-vehicle-status', [SellController::class, 'updateVehicleStatus']);

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    // Route::get('/', [HomeController::class, 'indexNew'])->name('home_page');
    Route::get('/home_page', [HomeController::class, 'indexNew'])->name('home_page');
    Route::get('/dashboard_item/{id}', [HomeController::class, 'dashboardHome'])->name('dashboard_item');
    Route::get('/home/get-totals', [HomeController::class, 'getTotals']);
    Route::get('/home/product-stock-alert', [HomeController::class, 'getProductStockAlert']);
    Route::get('/home/purchase-payment-dues', [HomeController::class, 'getPurchasePaymentDues']);
    Route::get('/home/sales-payment-dues', [HomeController::class, 'getSalesPaymentDues']);
    Route::post('/attach-medias-to-model', [HomeController::class, 'attachMediasToGivenModel'])->name('attach.medias.to.model');
    Route::get('/calendar', [HomeController::class, 'getCalendar'])->name('calendar');
    Route::get('/about-settings', [AppController::class, 'getAboutSettings'])
    ->name('about.settings');
    // Firebase FCM token route
    Route::post('/fcm-token/update', [\App\Http\Controllers\Api\FcmTokenController::class, 'update']);
    
    // Firebase test page
    Route::get('/firebase-test', function () {
        return view('firebase-test');
    })->name('firebase.test');

    Route::post('/test-email', [BusinessController::class, 'testEmailConfiguration']);
    Route::post('/test-sms', [BusinessController::class, 'testSmsConfiguration']);
    Route::get('/business/settings', [BusinessController::class, 'getBusinessSettings'])->name('business.getBusinessSettings');
    Route::post('/business/update', [BusinessController::class, 'postBusinessSettings'])->name('business.postBusinessSettings');
    Route::get('/user/profile', [UserController::class, 'getProfile'])->name('user.getProfile');
    Route::post('/user/update', [UserController::class, 'updateProfile'])->name('user.updateProfile');
    Route::post('/user/update-password', [UserController::class, 'updatePassword'])->name('user.updatePassword');



    Route::resource('brands', BrandController::class);

    Route::get('blogs/upload-image', [BlogController::class, 'uploadImage'])->name('blog.upload_image');
    Route::get('blogs/create-category', [BlogController::class, 'createCategory'])->name('blog.create_category');
    Route::post('blogs/store-category', [BlogController::class, 'storeCategory'])->name('blog.store_category');

    Route::resource('blogs', BlogController::class);

    // Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', TaxRateController::class);

    Route::resource('units', UnitController::class);

    Route::resource('ledger-discount', LedgerDiscountController::class)->only('edit', 'destroy', 'store', 'update');

    Route::post('check-mobile', [ContactController::class, 'checkMobile']);
    Route::get('/get-contact-due/{contact_id}', [ContactController::class, 'getContactDue']);
    Route::get('/contacts/payments/{contact_id}', [ContactController::class, 'getContactPayments']);

    Route::get('/contacts/cars/{contact_id}', [ContactController::class, 'getContactCars'])->name('getContactCars');

    Route::get('/contacts/loyalty-requests', [ContactController::class, 'loyaltyRequestsIndex'])->name('contacts.loyalty_requests');
    Route::get('/contacts/loyalty-requests-data', [ContactController::class, 'getLoyaltyRequests']);
    Route::post('/contacts/loyalty-requests/{id}/approve', [ContactController::class, 'approveLoyaltyRequest']);
    Route::post('/contacts/loyalty-requests/{id}/reject', [ContactController::class, 'rejectLoyaltyRequest']);

    Route::get('/contacts/dashboard', [ContactDashboardController::class, 'index'])->name('contacts.dashboard');

    Route::get('/contacts/map', [ContactController::class, 'contactMap']);
    Route::get('/contacts/update-status/{id}', [ContactController::class, 'updateStatus']);
    Route::get('/contacts/stock-report/{supplier_id}', [ContactController::class, 'getSupplierStockReport']);
    Route::get('/contacts/ledger', [ContactController::class, 'getLedger']);
    Route::post('/contacts/send-ledger', [ContactController::class, 'sendLedger']);
    Route::get('/contacts/import', [ContactController::class, 'getImportContacts'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'postImportContacts']);
    Route::post('/contacts/check-contacts-id', [ContactController::class, 'checkContactId']);
    Route::post('/contacts/merge', [ContactController::class, 'mergeContacts'])->name('contacts.merge');
    Route::get('/contacts/similar', [ContactController::class, 'getSimilarContacts'])->name('contacts.similar');
    Route::get('/contacts/customers', [ContactController::class, 'getCustomers']);
    Route::get('/customers/search', [ContactController::class, 'search'])->name('customers.search');
    Route::resource('contacts', ContactController::class);

    Route::get('taxonomies-ajax-index-page', [TaxonomyController::class, 'getTaxonomyIndexPage']);
    Route::resource('taxonomies', TaxonomyController::class);
    Route::post('/categories/import', [TaxonomyController::class, 'importCategories'])->name('categories.import');

    Route::resource('variation-templates', VariationTemplateController::class);

    Route::get('/products/download-excel', [ProductController::class, 'downloadExcel']);
    Route::get('/products/details/{id}', [App\Http\Controllers\ProductController::class, 'getProductDetails'])->name('products.details');
    Route::get('/products/stock-history/{id}', [ProductController::class, 'productStockHistory']);
    Route::get('/delete-media/{media_id}', [ProductController::class, 'deleteMedia']);
    Route::post('/products/mass-deactivate', [ProductController::class, 'massDeactivate']);
    Route::get('/products/activate/{id}', [ProductController::class, 'activate']);
    Route::get('/products/view-product-group-price/{id}', [ProductController::class, 'viewGroupPrice']);
    Route::get('/products/add-selling-prices/{id}', [ProductController::class, 'addSellingPrices']);
    Route::post('/products/save-selling-prices', [ProductController::class, 'saveSellingPrices']);
    Route::post('/products/mass-delete', [ProductController::class, 'massDestroy']);
    Route::get('/products/view/{id}', [ProductController::class, 'view']);
    Route::get('/products/list', [ProductController::class, 'getProducts']);
    Route::get('/products/list-no-variation', [ProductController::class, 'getProductsWithoutVariations']);
    Route::post('/products/bulk-edit', [ProductController::class, 'bulkEdit']);
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('/products/bulk-update-location', [ProductController::class, 'updateProductLocation']);
    Route::get('/products/get-product-to-edit/{product_id}', [ProductController::class, 'getProductToEdit']);

    Route::post('/products/get_sub_categories', [ProductController::class, 'getSubCategories']);
    Route::get('/products/get_car_brands', [ProductController::class, 'getCarBrands']);
    Route::get('/products/get_car_models', [ProductController::class, 'getCarModels']);
    Route::get('/products/get_car_models_by_brand', [ProductController::class, 'getCarModelsByBrand']);
    Route::get('/products/get_sub_units', [ProductController::class, 'getSubUnits']);
    Route::post('/products/product_form_part', [ProductController::class, 'getProductVariationFormPart']);
    Route::post('/products/get_product_variation_row', [ProductController::class, 'getProductVariationRow']);
    Route::post('/products/get_variation_template', [ProductController::class, 'getVariationTemplate']);
    Route::get('/products/get_variation_value_row', [ProductController::class, 'getVariationValueRow']);
    Route::post('/products/check_product_sku', [ProductController::class, 'checkProductSku']);
    Route::post('/products/validate_variation_skus', [ProductController::class, 'validateVaritionSkus']); //validates multiple skus at once
    Route::get('/products/quick_add', [ProductController::class, 'quickAdd']);
    Route::post('/products/save_quick_product', [ProductController::class, 'saveQuickProduct']);
    Route::get('/products/get-combo-product-entry-row', [ProductController::class, 'getComboProductEntryRow']);
    Route::post('/products/toggle-woocommerce-sync', [ProductController::class, 'toggleWooCommerceSync']);

    Route::get('products/exit/permission', [ProductController::class, 'viewexitpermission'])->name('viewexitpermission');
    Route::get('data/products/permission', [ProductController::class, 'exitPermission'])->name('products.permission');
    Route::get('edit/deliver/status/{id}', [ProductController::class, 'editDeliverStatus'])->name('editDeliverStatus');
    Route::post('/products/merge', [ProductController::class, 'mergeProducts'])->name('products.merge');

    Route::resource('products', ProductController::class);

    // Services Management
    Route::get('/services/datatable', [\App\Http\Controllers\ServiceController::class, 'getServicesDataTable'])->name('s.datable');
    Route::get('/services/flat-rate-details/{id}', [\App\Http\Controllers\ServiceController::class, 'getFlatRateDetails'])->name('services.flat-rate-details');
    Route::get('/services/options-by-locations', [\App\Http\Controllers\ServiceController::class, 'optionsByLocations'])->name('services.options-by-locations');
    Route::get('/services/{id}/overview', [\App\Http\Controllers\ServiceController::class, 'getServiceOverview'])->name('services.overview');
    Route::resource('services', \App\Http\Controllers\ServiceController::class);

    // Labour by Vehicle Management
    Route::get('/labour-by-vehicle/datatable', [\App\Http\Controllers\LabourByVehicleController::class, 'getLabourByVehicleDataTable'])->name('labour-by-vehicle.datatable');
    Route::get('/labour-by-vehicle/models-by-brand', [\App\Http\Controllers\LabourByVehicleController::class, 'modelsByBrand'])->name('labour-by-vehicle.models-by-brand');
    Route::get('/labour-by-vehicle/search', [\App\Http\Controllers\LabourByVehicleController::class, 'searchLabourByVehicleForm'])->name('labour-by-vehicle.search.form');
    Route::post('/labour-by-vehicle/search', [\App\Http\Controllers\LabourByVehicleController::class, 'searchLabourProducts'])->name('labour-by-vehicle.search');
    Route::get('/labour-by-vehicle/{id}/manage-labours', [\App\Http\Controllers\LabourByVehicleController::class, 'manageLabours'])->name('labour-by-vehicle.manage-labours');
    Route::get('/labour-by-vehicle/{id}/manage-labours/datatable', [\App\Http\Controllers\LabourByVehicleController::class, 'getManageLaboursDataTable'])->name('labour-by-vehicle.manage-labours.datatable');
    Route::post('/labour-by-vehicle/manage-labours/update-price', [\App\Http\Controllers\LabourByVehicleController::class, 'updateLabourPrice'])->name('labour-by-vehicle.update-labour-price');
    Route::get('/labour-by-vehicle/labour-products', [\App\Http\Controllers\LabourByVehicleController::class, 'labourProducts'])->name('labour-by-vehicle.labour-products');
    Route::get('/labour-by-vehicle/labour-products/datatable', [\App\Http\Controllers\LabourByVehicleController::class, 'getLabourProductsDataTable'])->name('labour-by-vehicle.labour-products.datatable');
    Route::get('/labour-by-vehicle/labour-products/create-modal', [\App\Http\Controllers\LabourByVehicleController::class, 'createLabourProductModal'])->name('labour-by-vehicle.labour-products.create-modal');
    Route::get('/labour-by-vehicle/labour-products/{id}/edit-modal', [\App\Http\Controllers\LabourByVehicleController::class, 'editLabourProductModal'])->name('labour-by-vehicle.labour-products.edit-modal');
    Route::post('/labour-by-vehicle/labour-products/store', [\App\Http\Controllers\LabourByVehicleController::class, 'storeLabourProduct'])->name('labour-by-vehicle.labour-products.store');
    Route::put('/labour-by-vehicle/labour-products/update', [\App\Http\Controllers\LabourByVehicleController::class, 'updateLabourProduct'])->name('labour-by-vehicle.labour-products.update');
    Route::get('/labour-by-vehicle/import', [\App\Http\Controllers\LabourByVehicleController::class, 'importLabourByVehicleForm'])->name('labour-by-vehicle.import');
    Route::post('/labour-by-vehicle/import', [\App\Http\Controllers\LabourByVehicleController::class, 'importLabourByVehicleStore'])->name('labour-by-vehicle.import.store');
    Route::get('/labour-by-vehicle/{id}/available-products', [\App\Http\Controllers\LabourByVehicleController::class, 'getAvailableProducts'])->name('labour-by-vehicle.available-products');
    Route::get('/labour-by-vehicle/{id}/add-labour-product', [\App\Http\Controllers\LabourByVehicleController::class, 'addLabourProduct'])->name('labour-by-vehicle.add-labour-product');
    Route::post('/labour-by-vehicle/add-multiple-products', [\App\Http\Controllers\LabourByVehicleController::class, 'addMultipleProducts'])->name('labour-by-vehicle.add-multiple-products');
    Route::post('/labour-by-vehicle/toggle-labour', [\App\Http\Controllers\LabourByVehicleController::class, 'toggleLabour'])->name('labour-by-vehicle.toggle-labour');
    Route::get('/labour-by-vehicle-product/{id}/edit', [\App\Http\Controllers\LabourByVehicleController::class, 'editLabourProduct'])->name('labour-by-vehicle.edit-labour-product');
    Route::post('/labour-by-vehicle-product/update', [\App\Http\Controllers\LabourByVehicleController::class, 'updateLabourProductMapping'])->name('labour-by-vehicle.update-labour-product');
    Route::resource('labour-by-vehicle', \App\Http\Controllers\LabourByVehicleController::class);
    
    // MaintenanceNote purchase requests
    Route::get('Purchase-Requests', [MaintenanceNoteController::class, 'index'])->name('repair.maintenance_notes');
    Route::get('maintenance-notes/api', [MaintenanceNoteController::class, 'apiIndex'])->name('repair.maintenance_notes.api');
    Route::get('maintenance-notes/{id}/data', [MaintenanceNoteController::class, 'data'])->name('repair.maintenance_notes.data');
    Route::get('maintenance-notes/products/search', [MaintenanceNoteController::class, 'searchProducts'])->name('repair.maintenance_notes.products.search');
    Route::post('maintenance-notes/{id}/add-product', [MaintenanceNoteController::class, 'addProduct'])->name('repair.maintenance_notes.add_product');
    Route::post('maintenance-notes/{id}/batch-save', [MaintenanceNoteController::class, 'batchSaveProducts'])->name('repair.maintenance_notes.batch_save');
    Route::put('maintenance-notes/{id}/line/{lineId}', [MaintenanceNoteController::class, 'updateLine'])->name('repair.maintenance_notes.update_line');
    Route::delete('maintenance-notes/{id}/line/{lineId}', [MaintenanceNoteController::class, 'deleteLine'])->name('repair.maintenance_notes.delete_line');
    Route::get('maintenance-notes/categories/{category}/subcategories', [MaintenanceNoteController::class, 'subCategories'])->name('repair.maintenance_notes.subcategories');
    Route::post('maintenance-notes/quick-product', [MaintenanceNoteController::class, 'quickCreateProduct'])->name('repair.maintenance_notes.quick_product');
    // Route::get('/recalc-stock-all', [ProductController::class, 'test']);
    // Route::post('/products/recalc-all-stocks', [ProductController::class, 'recalcAllProductStocks'])
    //     ->name('products.recalc_all_stocks');


    // Product Management Cards
    Route::get('/product-management', [ProductManagementController::class, 'index'])->name('product-management.index');

    // Bundles (salvaged body parts)
    Route::get('bundles', [BundleController::class, 'index'])->name('bundles.index');
    Route::get('bundles/datatable', [BundleController::class, 'datatable'])->name('bundles.datatable');
    Route::get('bundles/create', [BundleController::class, 'create'])->name('bundles.create');
    Route::get('bundles/quick-sell', [BundleController::class, 'quickSellForm'])->name('bundles.quick_sell.form');
    Route::post('bundles/quick-sell', [BundleController::class, 'quickSellStore'])->name('bundles.quick_sell.store');
    Route::get('bundles/ajax/search', [BundleController::class, 'getBundlesAjax'])->name('bundles.ajax.search');
    Route::get('bundles/ajax/devices', [BundleController::class, 'getDevicesAjax'])->name('bundles.ajax.devices');
    Route::post('bundles', [BundleController::class, 'store'])->name('bundles.store');
    Route::get('bundles/{id}/edit', [BundleController::class, 'edit'])->name('bundles.edit');
    Route::put('bundles/{id}', [BundleController::class, 'update'])->name('bundles.update');
    Route::delete('bundles/{id}', [BundleController::class, 'destroy'])->name('bundles.destroy');
    Route::get('bundles/{id}/quick-sell', [BundleController::class, 'quickSellForm'])->name('bundles.quick_sell.form.id');
    Route::post('bundles/{id}/quick-sell', [BundleController::class, 'quickSellStore'])->name('bundles.quick_sell.store.id');
    Route::get('bundles/sell/{id}/edit', [BundleController::class, 'editBundleSell'])->name('bundles.sell.edit');
    Route::post('bundles/sell/{id}/update', [BundleController::class, 'updateBundleSell'])->name('bundles.sell.update');
    Route::get('bundles/{id}/overview', [BundleController::class, 'overview'])->name('bundles.overview');

    // Generic Spare Parts
    Route::get('generic-spare-parts', [GenericSparePartController::class, 'index'])->name('generic-spare-parts.index');
    Route::get('generic-spare-parts/datatable', [GenericSparePartController::class, 'datatable'])->name('generic-spare-parts.datatable');
    Route::get('generic-spare-parts/create', [GenericSparePartController::class, 'create'])->name('generic-spare-parts.create');
    Route::post('generic-spare-parts', [GenericSparePartController::class, 'store'])->name('generic-spare-parts.store');
    Route::get('generic-spare-parts/{id}/edit', [GenericSparePartController::class, 'edit'])->name('generic-spare-parts.edit');
    Route::put('generic-spare-parts/{id}', [GenericSparePartController::class, 'update'])->name('generic-spare-parts.update');
    Route::delete('generic-spare-parts/{id}', [GenericSparePartController::class, 'destroy'])->name('generic-spare-parts.destroy');

    // Core Product Management
    Route::get('/core-product-management', [CoreProductManagementController::class, 'index'])->name('core-product-management.index');

    // Product Organization Management
    Route::get('/product-organization', [ProductOrganizationController::class, 'index'])->name('product-organization.index');

    Route::get('/inventory-delivery-returns', [InventoryDeliveryReturnController::class, 'index'])->name('inventory-delivery-returns.index');
    Route::get('/inventory-delivery-returns/datatable', [InventoryDeliveryReturnController::class, 'datatable'])->name('inventory-delivery-returns.datatable');
    Route::post('/inventory-delivery-returns/{id}/return', [InventoryDeliveryReturnController::class, 'returnToInventory'])->name('inventory-delivery-returns.return');

    // Product Extensions Management
    Route::get('/product-extensions', [ProductExtensionsController::class, 'index'])->name('product-extensions.index');

    // Rack Options endpoints
    Route::get('/rack-options', [ProductController::class, 'rackOptions'])->name('rack-options.index');
    Route::post('/rack-options', [ProductController::class, 'storeRackOption'])->name('rack-options.store');

    Route::get('/products/search', [ProductController::class, 'searchProducts'])->name('products.search');
    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/convert-to-draft/{id}', 'SellPosController@convertToInvoice');
    Route::get('/sells/convert-to-proforma/{id}', 'SellPosController@convertToProforma');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::resource('sells', 'SellController')->except(['show']);
    Route::get('/sells/copy-quotation/{id}', [SellPosController::class, 'copyQuotation']);

    Route::post('/import-purchase-products', [PurchaseController::class, 'importPurchaseProducts']);
    Route::post('/purchases/update-status', [PurchaseController::class, 'updateStatus']);
    Route::get('/purchases/get_products', [PurchaseController::class, 'getProducts']);
    Route::get('/purchases/get_suppliers', [PurchaseController::class, 'getSuppliers']);
    Route::post('/purchases/get_purchase_entry_row', [PurchaseController::class, 'getPurchaseEntryRow']);
    Route::post('/purchases/check_ref_number', [PurchaseController::class, 'checkRefNumber']);
    Route::get('/purchases/search-job-sheets', [PurchaseController::class, 'searchJobSheets']);
    Route::get('/purchases/dashboard', [PurchaseDashboardController::class, 'index'])->name('purchases.dashboard');
    Route::resource('purchases', PurchaseController::class)->except(['show']);
    
    // Purchase Receiving
    Route::prefix('purchase-receiving')->name('purchase_receiving.')->group(function () {
        Route::get('/', [PurchaseReceivingController::class, 'index'])->name('index');
        Route::get('/data', [PurchaseReceivingController::class, 'getData'])->name('data');
        Route::get('/{id}/lines', [PurchaseReceivingController::class, 'getPurchaseLines'])->name('lines');
        Route::post('/receive', [PurchaseReceivingController::class, 'receiveRemaining'])->name('receive');
    });

    Route::get('/toggle-subscription/{id}', [SellPosController::class, 'toggleRecurringInvoices']);
    Route::post('/sells/pos/get-types-of-service-details', [SellPosController::class, 'getTypesOfServiceDetails']);
    Route::get('/sells/subscriptions', [SellPosController::class, 'listSubscriptions']);
    Route::get('/sells/duplicate/{id}', [SellController::class, 'duplicateSell']);
    Route::get('/sells/drafts', [SellController::class, 'getDrafts']);
    Route::get('/sells/convert-to-draft/{id}', [SellPosController::class, 'convertToInvoice']);
    Route::get('/sells/convert-to-proforma/{id}', [SellPosController::class, 'convertToProforma']);
    Route::get('/sells/quotations', [SellController::class, 'getQuotations']);
    Route::get('/sells/draft-dt', [SellController::class, 'getDraftDatables']);
    Route::get('/sells/bundles', [SellController::class, 'bundleIndex'])->name('sells.bundles');
    Route::get('/sells/recycle-bin', [SellController::class, 'recycleBin'])->name('sells.recycle_bin');
    Route::get('/sells/dashboard', [SellDashboardController::class, 'index'])->name('sells.dashboard');
    Route::resource('sells', SellController::class)->except(['show']);

    Route::get('/import-sales', [ImportSalesController::class, 'index']);
    Route::post('/import-sales/preview', [ImportSalesController::class, 'preview']);
    Route::post('/import-sales', [ImportSalesController::class, 'import']);
    Route::get('/revert-sale-import/{batch}', [ImportSalesController::class, 'revertSaleImport']);

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', [SellPosController::class, 'getProductRow']);
    Route::post('/sells/pos/get_payment_row', [SellPosController::class, 'getPaymentRow']);
    Route::post('/sells/pos/get-reward-details', [SellPosController::class, 'getRewardDetails']);
    Route::get('/sells/pos/get-recent-transactions', [SellPosController::class, 'getRecentTransactions']);
    Route::get('/sells/pos/get-product-suggestion', [SellPosController::class, 'getProductSuggestion']);
    Route::get('/sells/pos/get-featured-products/{location_id}', [SellPosController::class, 'getFeaturedProducts']);
    Route::get('/reset-mapping', [SellController::class, 'resetMapping']);

    //POS module routes
     Route::get('point_of_sale/dashboard', [POSDashboardController::class, 'index'])->name('pos.dashboard');


    Route::resource('pos', SellPosController::class);
  
        Route::get('inventory/dashboard', [ProductsDashboardController::class, 'index'])->name('products.dashboard');
 


    //Booking estimator endpoints
    Route::get('/bookings/estimators/by-contact/{contactId}', [Restaurant\BookingController::class, 'estimatorsByContact'])->name('bookings.estimators.by_contact');
    Route::get('/bookings/estimators/{id}', [Restaurant\BookingController::class, 'estimatorDetails'])->name('bookings.estimators.details');

    Route::get('/bookings/services/{locationId}', [Restaurant\BookingController::class, 'getServicesByLocation'])->name('bookings.services.by_location');

    // Buy & Sell inspection unified contact modal + contact store
    Route::get('/buy-sell/create-contact-modal', [\Modules\CheckCar\Http\Controllers\BuySellBookingController::class, 'createContactModal'])
        ->name('buy_sell.create_contact_modal');
    Route::post('/buy-sell/store-contact', [\Modules\CheckCar\Http\Controllers\BuySellBookingController::class, 'storeContact'])
        ->name('buy_sell.store_contact');

    Route::resource('roles', RoleController::class);

    Route::resource('users', ManageUserController::class);

    Route::resource('group-taxes', GroupTaxController::class);

    Route::get('/barcodes/set_default/{id}', [BarcodeController::class, 'setDefault']);
    Route::resource('barcodes', BarcodeController::class);

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', [InvoiceSchemeController::class, 'setDefault']);
    Route::resource('invoice-schemes', InvoiceSchemeController::class);

    //Print Labels
    Route::get('/labels/show', [LabelsController::class, 'show']);
    Route::get('/labels/add-product-row', [LabelsController::class, 'addProductRow']);
    Route::get('/labels/preview', [LabelsController::class, 'preview']);

    //Reports...
    Route::get('/reports/gst-purchase-report', [ReportController::class, 'gstPurchaseReport']);
    Route::get('/reports/gst-sales-report', [ReportController::class, 'gstSalesReport']);
    Route::get('/reports/get-stock-by-sell-price', [ReportController::class, 'getStockBySellingPrice']);
    Route::get('/reports/purchase-report', [ReportController::class, 'purchaseReport']);
    Route::get('/reports/sale-report', [ReportController::class, 'saleReport']);
    Route::get('/reports/service-staff-report', [ReportController::class, 'getServiceStaffReport']);
    Route::get('/reports/service-staff-line-orders', [ReportController::class, 'serviceStaffLineOrders']);
    Route::get('/reports/table-report', [ReportController::class, 'getTableReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'getProfitLoss']);
    Route::get('/reports/get-opening-stock', [ReportController::class, 'getOpeningStock']);
    Route::get('/reports/purchase-sell', [ReportController::class, 'getPurchaseSell']);
    Route::get('/reports/customer-supplier', [ReportController::class, 'getCustomerSuppliers']);
    Route::get('/reports/stock-report', [ReportController::class, 'getStockReport']);
    Route::get('/reports/stock-details', [ReportController::class, 'getStockDetails']);
    Route::get('/reports/tax-report', [ReportController::class, 'getTaxReport']);
    Route::get('/reports/tax-details', [ReportController::class, 'getTaxDetails']);
    Route::get('/reports/trending-products', [ReportController::class, 'getTrendingProducts']);
    Route::get('/reports/expense-report', [ReportController::class, 'getExpenseReport']);
    Route::get('/reports/stock-adjustment-report', [ReportController::class, 'getStockAdjustmentReport']);
    Route::get('/reports/register-report', [ReportController::class, 'getRegisterReport']);
    Route::get('/reports/sales-representative-report', [ReportController::class, 'getSalesRepresentativeReport']);
    Route::get('/reports/sales-representative-total-expense', [ReportController::class, 'getSalesRepresentativeTotalExpense']);
    Route::get('/reports/sales-representative-total-sell', [ReportController::class, 'getSalesRepresentativeTotalSell']);
    Route::get('/reports/sales-representative-total-commission', [ReportController::class, 'getSalesRepresentativeTotalCommission']);
    Route::get('/reports/comprehensive-sales-report', [ReportController::class, 'getComprehensiveSalesReport']);
    Route::get('/reports/stock-expiry', [ReportController::class, 'getStockExpiryReport']);
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', [ReportController::class, 'getStockExpiryReportEditModal']);
    Route::post('/reports/stock-expiry-update', [ReportController::class, 'updateStockExpiryReport'])->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', [ReportController::class, 'getCustomerGroup']);
    Route::get('/reports/product-purchase-report', [ReportController::class, 'getproductPurchaseReport']);
    Route::get('/reports/product-sell-grouped-by', [ReportController::class, 'productSellReportBy']);
    Route::get('/reports/product-sell-report', [ReportController::class, 'getproductSellReport']);
    Route::get('/reports/product-sell-report-with-purchase', [ReportController::class, 'getproductSellReportWithPurchase']);
    Route::get('/reports/product-sell-grouped-report', [ReportController::class, 'getproductSellGroupedReport']);
    Route::get('/reports/lot-report', [ReportController::class, 'getLotReport']);
    Route::get('/reports/purchase-payment-report', [ReportController::class, 'purchasePaymentReport']);
    Route::get('/reports/sell-payment-report', [ReportController::class, 'sellPaymentReport']);
    Route::get('/reports/product-stock-details', [ReportController::class, 'productStockDetails']);
    Route::get('/reports/adjust-product-stock', [ReportController::class, 'adjustProductStock']);
    Route::get('/reports/get-profit/{by?}', [ReportController::class, 'getProfit']);
    Route::get('/reports/items-report', [ReportController::class, 'itemsReport']);
    Route::get('/reports/get-stock-value', [ReportController::class, 'getStockValue']);

    // Main Reports Dashboard
    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports.index');

    Route::get('business-locations', [BusinessLocationController::class, 'business_locations'])->name('business-locations');
    Route::get('business-location/map', [BusinessLocationController::class, 'locationMap'])->name('business-location.map');
    Route::get('business-location/activate-deactivate/{location_id}', [BusinessLocationController::class, 'activateDeactivateLocation']);

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', [LocationSettingsController::class, 'index'])->name('settings');
        Route::post('settings', [LocationSettingsController::class, 'updateSettings'])->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', [BusinessLocationController::class, 'checkLocationId']);
    Route::resource('business-location', BusinessLocationController::class);

    //Invoice layouts..
    Route::resource('invoice-layouts', InvoiceLayoutController::class);

    Route::post('get-expense-sub-categories', [ExpenseCategoryController::class, 'getSubCategories']);
    Route::get('expense-categories/search-parents', [ExpenseCategoryController::class, 'searchParents']);

    //Expense Categories...
    Route::resource('expense-categories', ExpenseCategoryController::class);

    //Expenses...
    Route::get('expenses/search-related', [ExpenseController::class, 'searchRelatedTransactions'])->name('expenses.search_related');
    Route::resource('expenses', ExpenseController::class);

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', [TransactionPaymentController::class, 'showChildPayments']);
    Route::get('/payments/view-payment/{payment_id}', [TransactionPaymentController::class, 'viewPayment']);
    Route::get('/payments/add_payment/{transaction_id}', [TransactionPaymentController::class, 'addPayment']);
    Route::get('/payments/pay-contact-due/{contact_id}', [TransactionPaymentController::class, 'getPayContactDue']);
    Route::post('/payments/pay-contact-due', [TransactionPaymentController::class, 'postPayContactDue']);
    Route::resource('payments', TransactionPaymentController::class);

    //Printers...
    Route::resource('printers', PrinterController::class);

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', [StockAdjustmentController::class, 'removeExpiredStock']);
    Route::post('/stock-adjustments/get_product_row', [StockAdjustmentController::class, 'getProductRow']);
    Route::resource('stock-adjustments', StockAdjustmentController::class);

    Route::get('/cash-register/register-details', [CashRegisterController::class, 'getRegisterDetails']);
    Route::get('/cash-register/close-register/{id?}', [CashRegisterController::class, 'getCloseRegister']);
    Route::post('/cash-register/close-register', [CashRegisterController::class, 'postCloseRegister']);
    Route::resource('cash-register', CashRegisterController::class);

    //Import products
    Route::get('/import-products', [ImportProductsController::class, 'index']);
    Route::post('/import-products/store', [ImportProductsController::class, 'store']);

    // Simple Import Products (New minimal flow)
    Route::get('/import-products/simple', [ImportProductsController::class, 'simple'])->name('import-products.simple');
    Route::post('/import-products/simple/store', [ImportProductsController::class, 'storeSimple'])->name('import-products.simple.store');

    // Universal Import Products
    Route::get('/import-products/universal', [UniversalProductImportController::class, 'index'])->name('import-products.universal');
    Route::get('/import-products/universal/template', [UniversalProductImportController::class, 'downloadTemplate'])->name('import.products.template');
    Route::post('/import-products/universal/preview', [UniversalProductImportController::class, 'preview'])->name('import-products.universal.preview');
    Route::post('/import-products/universal/store', [UniversalProductImportController::class, 'store'])->name('import-products.universal.store');

  

    // Simple Car Product Import
    Route::get('/simple-car-product-import', [SimpleCarProductImportController::class, 'index'])->name('simple-car-product-import.index');
    Route::post('/simple-car-product-import', [SimpleCarProductImportController::class, 'store'])->name('simple-car-product-import.store');

    //Sales Commission Agent
    Route::resource('sales-commission-agents', SalesCommissionAgentController::class);

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', [StockTransferController::class, 'printInvoice']);
    Route::post('stock-transfers/update-status/{id}', [StockTransferController::class, 'updateStatus']);
    Route::resource('stock-transfers', StockTransferController::class);

    Route::get('/opening-stock/add/{product_id}', [OpeningStockController::class, 'add']);
    Route::post('/opening-stock/save', [OpeningStockController::class, 'save']);

    //Customer Groups
    Route::resource('customer-group', CustomerGroupController::class);

    //Import opening stock
    Route::get('/import-opening-stock', [ImportOpeningStockController::class, 'index']);
    Route::post('/import-opening-stock/store', [ImportOpeningStockController::class, 'store']);

    //Sell return
    Route::get('validate-invoice-to-return/{invoice_no}', [SellReturnController::class, 'validateInvoiceToReturn']);
    // service staff replacement
    Route::get('validate-invoice-to-service-staff-replacement/{invoice_no}', [SellPosController::class, 'validateInvoiceToServiceStaffReplacement']);
    Route::put('change-service-staff/{id}', [SellPosController::class, 'change_service_staff'])->name('change_service_staff');

    // Sales: Share invoice links (AJAX JSON)
    Route::post('sales/{id}/share-links', [SellPosController::class, 'shareInvoiceLinks'])->name('sales.share_links');
    Route::post('sales/{id}/send-sms', [SellPosController::class, 'sendInvoiceSms'])->name('sales.send_sms');

    Route::resource('sell-return', SellReturnController::class);
    Route::get('sell-return/get-product-row', [SellReturnController::class, 'getProductRow']);
    Route::get('/sell-return/print/{id}', [SellReturnController::class, 'printInvoice']);
    Route::get('/sell-return/add/{id}', [SellReturnController::class, 'add']);

    //Backup
    Route::get('backup/download/{file_name}', [BackUpController::class, 'download']);
    Route::get('backup/{id}/delete', [BackUpController::class, 'delete'])->name('delete_backup');
    Route::resource('backup', BackUpController::class)->only('index', 'create', 'store');

    Route::get('selling-price-group/activate-deactivate/{id}', [SellingPriceGroupController::class, 'activateDeactivate']);
    Route::get('update-product-price', [SellingPriceGroupController::class, 'updateProductPrice'])->name('update-product-price');
    Route::get('export-product-price', [SellingPriceGroupController::class, 'export']);
    Route::post('import-product-price', [SellingPriceGroupController::class, 'import']);

    Route::resource('selling-price-group', SellingPriceGroupController::class);

    Route::resource('notification-templates', NotificationTemplateController::class)->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', [NotificationController::class, 'getTemplate']);
    Route::post('notification/send', [NotificationController::class, 'send']);

    Route::post('/purchase-return/update', [CombinedPurchaseReturnController::class, 'update']);
    Route::get('/purchase-return/edit/{id}', [CombinedPurchaseReturnController::class, 'edit']);
    Route::post('/purchase-return/save', [CombinedPurchaseReturnController::class, 'save']);
    Route::post('/purchase-return/get_product_row', [CombinedPurchaseReturnController::class, 'getProductRow']);
    Route::get('/purchase-return/create', [CombinedPurchaseReturnController::class, 'create']);
    Route::get('/purchase-return/add/{id}', [PurchaseReturnController::class, 'add']);
    Route::resource('/purchase-return', PurchaseReturnController::class)->except('create');

    Route::get('/discount/activate/{id}', [DiscountController::class, 'activate']);
    Route::post('/discount/mass-deactivate', [DiscountController::class, 'massDeactivate']);
    Route::resource('discount', DiscountController::class);

    Route::prefix('account')->group(function () {
        Route::get('/dashboard', [AccountsDashboardController::class, 'index'])->name('accounts.dashboard');
        Route::resource('/account', AccountController::class);
        Route::get('/fund-transfer/{id}', [AccountController::class, 'getFundTransfer']);
        Route::post('/fund-transfer', [AccountController::class, 'postFundTransfer']);
        Route::get('/deposit/{id}', [AccountController::class, 'getDeposit']);
        Route::post('/deposit', [AccountController::class, 'postDeposit']);
        Route::get('/close/{id}', [AccountController::class, 'close']);
        Route::get('/activate/{id}', [AccountController::class, 'activate']);
        Route::get('/delete-account-transaction/{id}', [AccountController::class, 'destroyAccountTransaction']);
        Route::get('/edit-account-transaction/{id}', [AccountController::class, 'editAccountTransaction']);
        Route::post('/update-account-transaction/{id}', [AccountController::class, 'updateAccountTransaction']);
        Route::get('/get-account-balance/{id}', [AccountController::class, 'getAccountBalance']);
        Route::get('/balance-sheet', [AccountReportsController::class, 'balanceSheet']);
        Route::get('/trial-balance', [AccountReportsController::class, 'trialBalance']);
        Route::get('/payment-account-report', [AccountReportsController::class, 'paymentAccountReport']);
        Route::get('/link-account/{id}', [AccountReportsController::class, 'getLinkAccount']);
        Route::post('/link-account', [AccountReportsController::class, 'postLinkAccount']);
        Route::get('/cash-flow', [AccountController::class, 'cashFlow']);
    });

    Route::resource('account-types', AccountTypeController::class);



    //Restaurant module
    Route::prefix('modules')->group(function () {
        Route::resource('tables', Restaurant\TableController::class);
        Route::resource('modifiers', Restaurant\ModifierSetsController::class);

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', [Restaurant\ProductModifierSetController::class, 'edit']);
        Route::post('/product-modifiers/{id}/update', [Restaurant\ProductModifierSetController::class, 'update']);
        Route::get('/product-modifiers/product-row/{product_id}', [Restaurant\ProductModifierSetController::class, 'product_row']);

        Route::get('/add-selected-modifiers', [Restaurant\ProductModifierSetController::class, 'add_selected_modifiers']);

        Route::get('/kitchen', [Restaurant\KitchenController::class, 'index']);
        Route::get('/kitchen/mark-as-cooked/{id}', [Restaurant\KitchenController::class, 'markAsCooked']);
        Route::post('/refresh-orders-list', [Restaurant\KitchenController::class, 'refreshOrdersList']);
        Route::post('/refresh-line-orders-list', [Restaurant\KitchenController::class, 'refreshLineOrdersList']);

        Route::get('/orders', [Restaurant\OrderController::class, 'index']);
        Route::get('/orders/mark-as-served/{id}', [Restaurant\OrderController::class, 'markAsServed']);
        Route::get('/data/get-pos-details', [Restaurant\DataController::class, 'getPosDetails']);
        Route::get('/data/check-staff-pin', [Restaurant\DataController::class, 'checkStaffPin']);
        Route::get('/orders/mark-line-order-as-served/{id}', [Restaurant\OrderController::class, 'markLineOrderAsServed']);
        Route::get('/print-line-order', [Restaurant\OrderController::class, 'printLineOrder']);
    });
    // Route::get('bookings/test_func', [Restaurant\BookingController::class, 'test_func']);
    Route::get('bookings/get-brands', [Restaurant\BookingController::class, 'getBrands'])->name('booking.get_brands');
    Route::get('bookings/get-models/{brandId}', [Restaurant\BookingController::class, 'getModelsByBrand']);
    Route::get('bookings/get-brand-origins/{brandId}', [Restaurant\BookingController::class, 'getBrandOrigins'])->name('booking.get_brand_origins');
    Route::get('bookings/get-custumer-vehicles/{customerId}', [Restaurant\BookingController::class, 'getModelsByCustomer'])->name('vehicles.customer');
    Route::get('bookings/get-vehicle-compatibility/{vehicleId}', [Restaurant\BookingController::class, 'getVehicleCompatibility'])->name('vehicles.compatibility');
    Route::get('bookings/get-inspection-services', [Restaurant\BookingController::class, 'getInspectionServices'])->name('bookings.inspection_services');
    Route::get('bookings/get-services-by-location/{locationId}', [Restaurant\BookingController::class, 'getServicesByLocation'])->name('bookings.services_by_location');
    Route::get('bookings/', [Restaurant\BookingController::class, 'index'])->name('booking.index');
    Route::get('/bookings/get-contact', [Restaurant\BookingController::class, 'getCustomerVehicles']);
    Route::get('/bookings/get-booking', [Restaurant\BookingController::class, 'fetch_booking_data']);
    Route::post('bookings/get-vehicles-contact', [Restaurant\BookingController::class, 'store_car_data'])->name('vehicles.store');
    Route::post('bookings/store-car-data', [Restaurant\BookingController::class, 'store_car_data'])->name('vehicles.store_car_data');
    Route::post('bookings/store-booking', [Restaurant\BookingController::class, 'store_new_booking'])->name('store_new_booking.store');
    Route::get('/bookings/search', [Restaurant\BookingController::class, 'search'])->name('bookings.search');
    Route::get('bookings/get-todays-bookings', [Restaurant\BookingController::class, 'getTodaysBookings'])->name('bookings.getTodaysBookings');
    Route::get('bookings/get-job-sheet-references', [Restaurant\BookingController::class, 'getJobSheetReferences'])->name('bookings.getJobSheetReferences');
    // Route::post('/booking/lookup-chassis', [App\Http\Controllers\Restaurant\BookingController::class, 'lookupChassis'])->name('booking.lookup_chassis');

    Route::get('bookings/contact-devices', [Restaurant\BookingController::class, 'contactDevices'])->name('bookings.contact_devices');
    Route::get('bookings/contact-devices/data', [Restaurant\BookingController::class, 'contactDevicesData'])->name('bookings.contact_devices.data');
    Route::get('bookings/contact-device/{id}/edit', [Restaurant\BookingController::class, 'getContactDeviceEditModal'])->name('bookings.contact_device.edit');
    Route::put('bookings/contact-device/{id}', [Restaurant\BookingController::class, 'updateContactDevice'])->name('bookings.contact_device.update');


    Route::get('/booking/get-models-by-brand/{brandId}', [App\Http\Controllers\Restaurant\BookingController::class, 'getModelsByBrand'])->name('booking.get_models_by_brand');

    // Product compatibility routes
    Route::get('/products/get-models-by-brand/{brandId}', [ProductController::class, 'getModelsByBrand'])->name('products.get_models_by_brand');
    Route::get('/products/compatibility/{id}', [ProductController::class, 'getProductCompatibility'])->name('products.compatibility.get');
    Route::post('/products/compatibility', [ProductController::class, 'storeProductCompatibility'])->name('products.compatibility.store');
    Route::delete('/products/compatibility/{id}', [ProductController::class, 'deleteProductCompatibility'])->name('products.compatibility.delete');

    // Edit Booking

    Route::get('/bookings/{booking}', [Restaurant\BookingController::class, 'show'])->name('bookings.show');

    // Update Booking
    Route::put('/bookings/update', [Restaurant\BookingController::class, 'update'])->name('bookings.update');
    Route::put('bookings/{id}/update-status', [Restaurant\BookingController::class, 'update_status'])->name('bookings.update_status');
    // Edit booking
    Route::get('/bookings/{id}/edit', [Restaurant\BookingController::class, 'edit'])->name('bookings.edit');
    // Route for updating booking status
    // Delete booking
    Route::delete('/bookings/{id}', [Restaurant\BookingController::class, 'destroy'])->name('bookings.destroy');

    // Route::resource('bookings', Restaurant\BookingController::class);

    // Job Estimator Routes
    Route::get('job-estimator', [Restaurant\JobEstimatorController::class, 'index'])->name('job_estimator.index');
    Route::post('job-estimator', [Restaurant\JobEstimatorController::class, 'store'])->name('job_estimator.store');
    Route::get('job-estimator/{id}', [Restaurant\JobEstimatorController::class, 'show'])->name('job_estimator.show');
    Route::get('job-estimator/{id}/edit', [Restaurant\JobEstimatorController::class, 'edit'])->name('job_estimator.edit');
    Route::put('job-estimator/{id}', [Restaurant\JobEstimatorController::class, 'update'])->name('job_estimator.update');
    Route::delete('job-estimator/{id}', [Restaurant\JobEstimatorController::class, 'destroy'])->name('job_estimator.destroy');
    // Update approval status for product_joborder line
    Route::put('job-estimator/line/{id}/approval', [Restaurant\JobEstimatorController::class, 'updateLineApproval'])->name('job_estimator.line.update_approval');
    Route::post('job-estimator/send-sms', [Restaurant\JobEstimatorController::class, 'sendSms'])->name('job_estimator.send_sms');
    Route::get('job-estimator/{id}/print', [Restaurant\JobEstimatorController::class, 'printEstimate'])->name('job_estimator.print');

    Route::resource('types-of-service', TypesOfServiceController::class);
    Route::get('sells/edit-shipping/{id}', [SellController::class, 'editShipping']);
    Route::put('sells/update-shipping/{id}', [SellController::class, 'updateShipping']);
    Route::get('shipments', [SellController::class, 'shipments']);

    Route::post('upload-module', [Install\ModulesController::class, 'uploadModule']);
    Route::delete('manage-modules/destroy/{module_name}', [Install\ModulesController::class, 'destroy']);
    Route::resource('manage-modules', Install\ModulesController::class)
        ->only(['index', 'update']);
    Route::get('regenerate', [Install\ModulesController::class, 'regenerate']);

    Route::resource('warranties', WarrantyController::class);

    Route::resource('dashboard-configurator', DashboardConfiguratorController::class)
        ->only(['edit', 'update']);

    // Data Permissions routes
    Route::resource('data-permissions', DataPermissionController::class);

    // API Settings routes
    Route::resource('api-settings', ApiSettingController::class);

    Route::get('view-media/{model_id}', [SellController::class, 'viewMedia']);

    //common controller for document & note
    Route::get('get-document-note-page', [DocumentAndNoteController::class, 'getDocAndNoteIndexPage']);
    Route::post('post-document-upload', [DocumentAndNoteController::class, 'postMedia']);
    Route::resource('note-documents', DocumentAndNoteController::class);
    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::get('get-purchase-orders/{contact_id}', [PurchaseOrderController::class, 'getPurchaseOrders']);
    Route::get('get-purchase-order-lines/{purchase_order_id}', [PurchaseController::class, 'getPurchaseOrderLines']);
    Route::get('edit-purchase-orders/{id}/status', [PurchaseOrderController::class, 'getEditPurchaseOrderStatus']);
    Route::put('update-purchase-orders/{id}/status', [PurchaseOrderController::class, 'postEditPurchaseOrderStatus']);
    Route::resource('sales-order', SalesOrderController::class)->only(['index']);
    Route::get('get-sales-orders/{customer_id}', [SalesOrderController::class, 'getSalesOrders']);
    Route::get('get-sales-order-lines', [SellPosController::class, 'getSalesOrderLines']);
    Route::get('edit-sales-orders/{id}/status', [SalesOrderController::class, 'getEditSalesOrderStatus']);
    Route::put('update-sales-orders/{id}/status', [SalesOrderController::class, 'postEditSalesOrderStatus']);
    Route::get('reports/activity-log', [ReportController::class, 'activityLog'])->name('reports.activity_logs');
    Route::get('user-location/{latlng}', [HomeController::class, 'getUserLocation']);

    // // Treasury Routes
    // Route::get('/treasury/income', [\App\Http\Controllers\SellController::class, 'create'])->name('treasury.income');
    // Route::get('/treasury/expense', [\App\Http\Controllers\ExpenseController::class, 'create'])->name('treasury.expense');
});

// Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
//     Route::get('products/{id?}', [ProductController::class, 'getProductsApi']);
//     Route::get('categories', [CategoryController::class, 'getCategoriesApi']);
//     Route::get('brands', [BrandController::class, 'getBrandsApi']);
//     Route::post('customers', [ContactController::class, 'postCustomersApi']);
//     Route::get('settings', [BusinessController::class, 'getEcomSettings']);
//     Route::get('variations', [ProductController::class, 'getVariationsApi']);
//     Route::post('orders', [SellPosController::class, 'placeOrdersApi']);
// });

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout.get');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/', [HomeController::class, 'indexNew']);
    Route::get('/firebase/test', function() { return view('firebase.test'); });

    Route::get('/load-more-notifications', [HomeController::class, 'loadMoreNotifications']);
    Route::get('/get-total-unread', [HomeController::class, 'getTotalUnreadNotifications']);
    Route::post('/notifications/mark-selected', [HomeController::class, 'markSelectedNotifications']);
    Route::post('/notifications/mark-all', [HomeController::class, 'markAllNotifications']);
    Route::get('/purchases/print/{id}', [PurchaseController::class, 'printInvoice']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
    Route::get('/download-purchase-order/{id}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchaseOrder.downloadPdf');
    Route::get('/sells/{id}', [SellController::class, 'show']);
    Route::get('/sells/{transaction_id}/print', [SellPosController::class, 'printInvoice'])->name('sell.printInvoice');
    Route::get('/download-sells/{transaction_id}/pdf', [SellPosController::class, 'downloadPdf'])->name('sell.downloadPdf');
    Route::get('/download-quotation/{id}/pdf', [SellPosController::class, 'downloadQuotationPdf'])
        ->name('quotation.downloadPdf');
    Route::get('/download-packing-list/{id}/pdf', [SellPosController::class, 'downloadPackingListPdf'])
        ->name('packing.downloadPdf');
    Route::get('/sells/invoice-url/{id}', [SellPosController::class, 'showInvoiceUrl']);
    Route::get('/show-notification/{id}', [HomeController::class, 'showNotification']);
    Route::get('/sells/{transaction_id}/print-clean', [SellPosController::class, 'printCleanInvoice'])->name('sell.printCleanInvoice');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/missing-purchase', [MissingPurchaseController::class, 'index'])->name('missing-purchase.index');
    Route::post('/missing-purchase/create', [MissingPurchaseController::class, 'createPurchase'])->name('missing-purchase.create');
});

Route::get('contact/register', [LoginController::class, 'showRegisterForm'])->name('showRegisterForm');
Route::post('contact/store/account', [LoginController::class, 'storeAccount'])->name('storeAccount');


Route::get('GetNotification', [NotificationController::class, 'GetNotification']);
    Route::get('/notifications/count', [NotificationController::class, 'GetNotificationCount'])
        ->name('notifications.count');
    Route::get('/notifications/by-status', 
        [NotificationController::class, 'getByStatus']
    )->name('notifications.by-status');
    Route::post('/notification/action', [NotificationController::class, 'NotificationAction'])
    ->name('notification.action');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'MarkAllReadNotification'])
        ->name('notifications.markAllRead');
    Route::delete('/delete/{id}', [NotificationController::class, 'deleteNotification'])->name('notifications.delete');
    Route::post('/restore/{id}', [NotificationController::class, 'restoreNotification'])->name('notifications.restore');

    Route::post('/notifications/mark-read', [NotificationController::class, 'MarkSingleReadNotification'])
        ->name('notifications.markRead');

    Route::get('/notifications/list', [NotificationController::class, 'GetNotifications'])
        ->name('notifications.list');
    Route::prefix('admin')->middleware(['env_admin'])->group(function() {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/dashboard/save', [AdminDashboardController::class, 'saveSettings'])->name('admin.dashboard.save');
      Route::post('/qr/save', [AdminDashboardController::class, 'saveQrSettings'])
        ->name('admin.qr.save');
        });
   

Route::post('/about-settings', [AppController::class, 'updateAboutSettings'])
    ->name('about.update');