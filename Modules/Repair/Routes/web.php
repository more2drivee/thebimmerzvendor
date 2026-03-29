<?php

use Illuminate\Support\Facades\Route;
use Modules\Repair\Http\Controllers\InfoJobOrderController;
use Modules\Connector\Http\Controllers\Api\OpenAIController;
use Modules\Repair\Http\Controllers\DashboardController;
use Modules\Repair\Http\Controllers\DiagnoseMessageController;
use Modules\Repair\Http\Controllers\RepairController;
use Modules\Repair\Http\Controllers\MaintenanceNoteController;
use Modules\Repair\Http\Controllers\TransactionTechnicianEfficiencyController;


// Route::get('/status/test/{id}', [RepairController::class, 'show']);


Route::get('/repair-status', [Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index'])->name('repair-status');
Route::post('/post-repair-status', [Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'postRepairStatus'])->name('post-repair-status');
Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->prefix('repair')->group(function () {
    Route::get('edit-repair/{id}/status', [Modules\Repair\Http\Controllers\RepairController::class, 'editRepairStatus']);
    Route::post('update-repair-status', [Modules\Repair\Http\Controllers\RepairController::class, 'updateRepairStatus']);
    Route::get('delete-media/{id}', [Modules\Repair\Http\Controllers\RepairController::class, 'deleteMedia']);
    Route::get('print-label/{id}', [Modules\Repair\Http\Controllers\RepairController::class, 'printLabel']);
    Route::get('print-repair/{transaction_id}/customer-copy', [Modules\Repair\Http\Controllers\RepairController::class, 'printCustomerCopy'])->name('repair.customerCopy');
    // Unified Recycle Bin
    Route::get('recycle-bin', 'Modules\Repair\Http\Controllers\RecycleBinController@index')->name('recycle-bin.index');
    Route::get('recycle-bin/preview/{id}', 'Modules\Repair\Http\Controllers\RecycleBinController@getRestorePreview')->name('recycle-bin.preview');
    Route::post('recycle-bin/restore-job-sheet/{id}', 'Modules\Repair\Http\Controllers\RecycleBinController@restoreJobSheet')->name('recycle-bin.restore-job-sheet');
    Route::post('recycle-bin/restore-transaction/{id}', 'Modules\Repair\Http\Controllers\RecycleBinController@restoreTransaction')->name('recycle-bin.restore-transaction');
    Route::post('recycle-bin/restore-transaction-with-options/{id}', 'Modules\Repair\Http\Controllers\RecycleBinController@restoreTransactionWithOptions')->name('recycle-bin.restore-transaction-with-options');
    Route::delete('recycle-bin/permanent-delete-job-sheet/{id}', 'Modules\Repair\Http\Controllers\RecycleBinController@permanentDeleteJobSheet')->name('recycle-bin.permanent-delete-job-sheet');
    Route::delete('recycle-bin/permanent-delete-transaction/{id}', 'Modules\Repair\Http\Controllers\RecycleBinController@permanentDeleteTransaction')->name('recycle-bin.permanent-delete-transaction');

    // Legacy routes (kept for backward compatibility)
    Route::get('repair/recycle-bin', 'Modules\Repair\Http\Controllers\RepairController@recycleBin')->name('repair.recycle_bin');
    Route::post('repair/restore/{id}', 'Modules\Repair\Http\Controllers\RepairController@restore')->name('repair.restore');
    Route::delete('repair/permanent-delete/{id}', 'Modules\Repair\Http\Controllers\RepairController@permanentDelete')->name('repair.permanent_delete');

    Route::get('job-sheet/recycle-bin', 'Modules\Repair\Http\Controllers\JobSheetController@recycleBin')->name('job-sheet.recycle_bin');
    Route::post('job-sheet/restore/{id}', 'Modules\Repair\Http\Controllers\JobSheetController@restore')->name('job-sheet.restore');
    Route::delete('job-sheet/permanent-delete/{id}', 'Modules\Repair\Http\Controllers\JobSheetController@permanentDelete')->name('job-sheet.permanent_delete');

    Route::resource('/repair', 'Modules\Repair\Http\Controllers\RepairController')->except(['create', 'edit']);
    Route::resource('/status', 'Modules\Repair\Http\Controllers\RepairStatusController')->except('show');

    Route::resource('/repair-settings', 'Modules\Repair\Http\Controllers\RepairSettingsController')->only('index', 'store');

    // Flat Rate Services
    Route::get('flat-rate', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'flatRate'])->name('repair.flat_rate');
    Route::post('flat-rate', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'storeFlatRate'])->name('repair.flat_rate.store');

    // Active flat rate per location
    Route::get('flat-rate/active', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'activeFlatRate'])->name('repair.flat_rate.active');

    Route::get('flat-rate/{id}', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'showFlatRate'])->name('repair.flat_rate.show');
    Route::put('flat-rate/{id}', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'updateFlatRate'])->name('repair.flat_rate.update');
    Route::delete('flat-rate/{id}', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'deleteFlatRate'])->name('repair.flat_rate.destroy');

    Route::get('send/status/{id}', [DashboardController::class, 'editjobStatus'])->name('editjobStatus');

    Route::get('exit/permission/car/{id}', [RepairController::class, 'editPermission'])->name('editPermission');
    Route::get('transactions/{transaction}/technician-efficiency', [TransactionTechnicianEfficiencyController::class, 'show'])->name('repair.transaction.technician_efficiency');
    Route::post('transactions/{transaction}/technician-efficiency/timer', [TransactionTechnicianEfficiencyController::class, 'updateTimer'])->name('repair.transaction.technician_efficiency.update_timer');
    // Transaction overview page for a repair transaction
    Route::get('/install', [Modules\Repair\Http\Controllers\InstallController::class, 'index']);
    Route::post('/install', [Modules\Repair\Http\Controllers\InstallController::class, 'install']);
    Route::get('/install/uninstall', [Modules\Repair\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('/install/update', [Modules\Repair\Http\Controllers\InstallController::class, 'update']);

    Route::get('get-device-models', [Modules\Repair\Http\Controllers\DeviceModelController::class, 'getDeviceModels']);
    Route::get('models-repair-checklist', [Modules\Repair\Http\Controllers\DeviceModelController::class, 'getRepairChecklists']);
    Route::resource('device-models', 'Modules\Repair\Http\Controllers\DeviceModelController')->except(['show']);
    Route::resource('dashboard', 'Modules\Repair\Http\Controllers\DashboardController');
Route::post('/device_models/import', [Modules\Repair\Http\Controllers\DeviceModelController::class, 'importDeviceModels'])->name('device_models.import');

    Route::post('job-sheet-post-upload-docs', [Modules\Repair\Http\Controllers\JobSheetController::class, 'postUploadDocs']);
    Route::post('job-sheet-upload-media', [Modules\Repair\Http\Controllers\JobSheetController::class, 'postUploadMedia']);
    Route::get('job-sheet/{id}/upload-docs', [Modules\Repair\Http\Controllers\JobSheetController::class, 'getUploadDocs']);


    Route::get('/jobsheets/{id}/media', [Modules\Repair\Http\Controllers\JobSheetController::class, 'showMedia'])->name('jobsheets.media');

    Route::get('job-sheet/print/{id}', [Modules\Repair\Http\Controllers\JobSheetController::class, 'print']);
    Route::get('job-sheet/delete/{id}/image', [Modules\Repair\Http\Controllers\JobSheetController::class, 'deleteJobSheetImage']);
    Route::get('job-sheet/{id}/status', [Modules\Repair\Http\Controllers\JobSheetController::class, 'editStatus']);
    Route::put('job-sheet-update/{id}/status', [Modules\Repair\Http\Controllers\JobSheetController::class, 'updateStatus']);
    Route::get('job-sheet/add-parts/{id}', [Modules\Repair\Http\Controllers\JobSheetController::class, 'addParts']);
    Route::post('job-sheet/save-parts/{id}', [Modules\Repair\Http\Controllers\JobSheetController::class, 'saveParts']);
    Route::post('job-sheet/get-part-row', [Modules\Repair\Http\Controllers\JobSheetController::class, 'jobsheetPartRow']);
    Route::resource('job-sheet', 'Modules\Repair\Http\Controllers\JobSheetController');
    Route::post('update-repair-jobsheet-settings', [Modules\Repair\Http\Controllers\RepairSettingsController::class, 'updateJobsheetSettings']);
    Route::get('job-sheet/print-label/{id}', [Modules\Repair\Http\Controllers\JobSheetController::class, 'printLabel']);

    Route::get('workshops', [Modules\Repair\Http\Controllers\WorkshopController::class, 'index'])->name('workshops.index');
    Route::get('workshops/{id}', [Modules\Repair\Http\Controllers\WorkshopController::class, 'show'])->name('workshops.show');
    Route::get('workshops/create', [Modules\Repair\Http\Controllers\WorkshopController::class, 'create'])->name('workshops.create');
    Route::post('workshops', [Modules\Repair\Http\Controllers\WorkshopController::class, 'store'])->name('workshops.store');

    Route::get('workshops/{id}/edit', [Modules\Repair\Http\Controllers\WorkshopController::class, 'edit'])->name('workshops.edit');
    Route::put('workshops/{id}', [Modules\Repair\Http\Controllers\WorkshopController::class, 'update'])->name('workshops.update');

    Route::delete('workshops/{id}', [Modules\Repair\Http\Controllers\WorkshopController::class, 'destroy'])->name('workshops.destroy');

    // Maintenance Notes - Purchase Orders
    Route::get('maintenance-notes/{id}/purchase-orders', [MaintenanceNoteController::class, 'getPurchaseOrders'])->name('repair.maintenance_notes.purchase_orders');
    Route::put('maintenance-notes/{id}/purchase-orders/{poId}/status', [MaintenanceNoteController::class, 'updatePurchaseOrderStatus'])->name('repair.maintenance_notes.purchase_orders.update_status');
    Route::post('maintenance-notes/{id}/quick-supplier', [MaintenanceNoteController::class, 'quickAddSupplier'])->name('repair.maintenance_notes.quick_supplier');
    Route::get('maintenance-notes/suppliers/search', [MaintenanceNoteController::class, 'suppliersSearch'])->name('repair.maintenance_notes.suppliers.search');

    // Route::get('/diagnose-message/edit', [DiagnoseMessageController::class, 'edit'])->name('diagnose-message.edit');
    // Route::post('/diagnose-message/update', [DiagnoseMessageController::class, 'update'])->name('diagnose-message.update');

    // Lightweight contact edit for Repair module (first/middle/last name + mobile only)
    Route::get('contacts/{id}/edit-basic', [Modules\Repair\Http\Controllers\ContactController::class, 'editBasic'])->name('repair.contacts.edit_basic');
    Route::post('contacts/{id}/update-basic', [Modules\Repair\Http\Controllers\ContactController::class, 'updateBasic'])->name('repair.contacts.update_basic');
    Route::post('contacts/merge', [Modules\Repair\Http\Controllers\ContactController::class, 'mergeContacts'])->name('repair.contacts.merge');
    Route::post('contacts/check-mobile', [Modules\Repair\Http\Controllers\ContactController::class, 'checkMobile'])->name('repair.contacts.check_mobile');

    Route::get('survey/categories', [RepairController::class, 'getSurveyCategories'])->name('repair.survey.categories');
    Route::get('survey/categories/{category}/surveys', [RepairController::class, 'getSurveysByCategory'])->name('repair.survey.category.surveys');
    Route::post('survey/send', [RepairController::class, 'sendSurvey'])->name('repair.survey.send');

    // CRM Follow-up integration
    Route::get('crm-followup/modal', [RepairController::class, 'getCrmFollowupModal'])->name('repair.add_crm_followup_modal');
    Route::post('crm-followup/store', [RepairController::class, 'storeCrmFollowup'])->name('repair.store_crm_followup');

});

Route::get('check/phone/{id}', [InfoJobOrderController::class, 'check'])->name('cheeck.phone');
Route::get('info/job/order/{id}', [InfoJobOrderController::class, 'checkphone'])->name('check.info.job.order');

Route::post('test/info/job/order/{id}', [InfoJobOrderController::class, 'testcheckphone'])->name('test.check.info.job.order');

Route::post('save/job/order/{id}', [InfoJobOrderController::class, 'saveData'])->name('save.job.order');


// Route::get('/progress-tracker/{jobOrderId}', [InfoJobOrderController::class, 'getStatus']);


//Route::get('show/job/order/{id}', [InfoJobOrderController::class, 'saveDataShow'])->name('show.job.order');




// Route::post('check/phone', [InfoJobOrderController::class, 'ansCheck'])->name('cheeck.phone.status');
