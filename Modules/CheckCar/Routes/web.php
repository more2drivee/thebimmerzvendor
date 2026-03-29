<?php

use Illuminate\Support\Facades\Route;
use Modules\CheckCar\Http\Controllers\CarInspectionController;
use Modules\CheckCar\Http\Controllers\CheckCarSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Authenticated routes for the CheckCar inspection wizard.
*/

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])
    ->prefix('checkcar')
    ->group(function () {
        // List / landing page (optional for now)
        Route::get('/', [CarInspectionController::class, 'index'])
            ->name('checkcar.inspections.index');

        // Inspections list route (alias for main route)
        Route::get('/inspections', [CarInspectionController::class, 'index'])
            ->name('checkcar.inspections.list');

        // DataTables endpoint for inspections
        Route::get('/inspections/datatables', [CarInspectionController::class, 'getInspectionsDatatables'])
            ->name('checkcar.inspections.datatables');

        // Create new inspection wizard
        Route::get('/inspections/create', [CarInspectionController::class, 'create'])
            ->name('checkcar.inspections.create');

        // Store inspection
        Route::post('/inspections', [CarInspectionController::class, 'store'])
            ->name('checkcar.inspections.store');

        // Buy & Sell Car Inspection booking + job sheet + transaction
        Route::post('/buy-sell-booking', [\Modules\CheckCar\Http\Controllers\BuySellBookingController::class, 'store'])
            ->name('checkcar.buy_sell_booking.store');

        // View single inspection report
        Route::get('/inspections/{inspection}', [CarInspectionController::class, 'show'])
            ->name('checkcar.inspections.show');

        // Get inspection documents
        Route::get('/inspections/{inspection}/documents', [CarInspectionController::class, 'getDocuments'])
            ->name('checkcar.inspections.documents');

        // Send SMS notifications (buyer & seller)
        Route::post('/inspections/{inspection}/send-sms', [CarInspectionController::class, 'sendSms'])
            ->name('checkcar.inspections.send_sms');

        // Change car owner (switch contact between buyer and seller)
        Route::get('/inspections/{inspection}/change-car-owner-modal', [CarInspectionController::class, 'changeCarOwnerModal'])
            ->name('checkcar.inspections.change_car_owner_modal');
        Route::post('/inspections/{inspection}/change-car-owner', [CarInspectionController::class, 'updateCarOwner'])
            ->name('checkcar.inspections.change_car_owner');

        // Settings (question categories & phrase templates)
        Route::get('/settings', [CheckCarSettingsController::class, 'index'])
            ->name('checkcar.settings.index');

        // Privacy Policy update
        Route::post('/settings/privacy', [CheckCarSettingsController::class, 'updatePrivacyPolicy'])
            ->name('checkcar.settings.privacy.update');

        Route::post('/settings/categories', [CheckCarSettingsController::class, 'storeCategory'])
            ->name('checkcar.settings.categories.store');
        Route::put('/settings/categories/{category}', [CheckCarSettingsController::class, 'updateCategory'])
            ->name('checkcar.settings.categories.update');
        Route::delete('/settings/categories/{category}', [CheckCarSettingsController::class, 'destroyCategory'])
            ->name('checkcar.settings.categories.destroy');

        Route::post('/settings/templates', [CheckCarSettingsController::class, 'storeTemplate'])
            ->name('checkcar.settings.templates.store');
        Route::put('/settings/templates/{template}', [CheckCarSettingsController::class, 'updateTemplate'])
            ->name('checkcar.settings.templates.update');
        Route::delete('/settings/templates/{template}', [CheckCarSettingsController::class, 'destroyTemplate'])
            ->name('checkcar.settings.templates.destroy');

        Route::post('/settings/subcategories', [CheckCarSettingsController::class, 'storeSubcategory'])
            ->name('checkcar.settings.subcategories.store');
        Route::put('/settings/subcategories/{subcategory}', [CheckCarSettingsController::class, 'updateSubcategory'])
            ->name('checkcar.settings.subcategories.update');
        Route::delete('/settings/subcategories/{subcategory}', [CheckCarSettingsController::class, 'destroySubcategory'])
            ->name('checkcar.settings.subcategories.destroy');

        // Elements CRUD
        Route::post('/settings/elements', [CheckCarSettingsController::class, 'storeElement'])
            ->name('checkcar.settings.elements.store');
        Route::get('/settings/elements/{element}/data', [CheckCarSettingsController::class, 'getElementData'])
            ->name('checkcar.settings.elements.data');
        Route::put('/settings/elements/{element}', [CheckCarSettingsController::class, 'updateElement'])
            ->name('checkcar.settings.elements.update');
        Route::delete('/settings/elements/{element}', [CheckCarSettingsController::class, 'destroyElement'])
            ->name('checkcar.settings.elements.destroy');

        // Element Options CRUD
        Route::post('/settings/element-options', [CheckCarSettingsController::class, 'storeElementOption'])
            ->name('checkcar.settings.element-options.store');
        Route::put('/settings/element-options/{option}', [CheckCarSettingsController::class, 'updateElementOption'])
            ->name('checkcar.settings.element-options.update');
        Route::delete('/settings/element-options/{option}', [CheckCarSettingsController::class, 'destroyElementOption'])
            ->name('checkcar.settings.element-options.destroy');

        // API endpoints for structure
        Route::get('/api/structure', [CheckCarSettingsController::class, 'getFullStructure'])
            ->name('checkcar.api.structure');
        Route::get('/api/elements/{element}', [CheckCarSettingsController::class, 'getElement'])
            ->name('checkcar.api.elements.show');
        Route::get('/api/subcategories/{subcategory}/elements', [CheckCarSettingsController::class, 'getElementsBySubcategory'])
            ->name('checkcar.api.subcategories.elements');
        Route::get('/api/categories/{category}/subcategories', [CheckCarSettingsController::class, 'getSubcategoriesByCategory'])
            ->name('checkcar.api.categories.subcategories');

        // Service Settings Routes
        Route::get('/settings/services', [CheckCarSettingsController::class, 'getServicesSidebar'])
            ->name('checkcar.settings.services');
        Route::post('/settings/services/select', [CheckCarSettingsController::class, 'storeSelectedService'])
            ->name('checkcar.settings.services.select');
        Route::put('/settings/services/{service}', [CheckCarSettingsController::class, 'updateServiceSetting'])
            ->name('checkcar.settings.services.update');
        Route::get('/settings/services/selected', [CheckCarSettingsController::class, 'getSelectedService'])
            ->name('checkcar.settings.services.selected');

        // Element Presets (Phrase Templates)
        Route::get('/api/elements/{element}/presets', [CheckCarSettingsController::class, 'getElementPresets'])
            ->name('checkcar.api.elements.presets');
        Route::post('/settings/elements/{element}/presets', [CheckCarSettingsController::class, 'storeElementPreset'])
            ->name('checkcar.settings.elements.presets.store');
        Route::put('/settings/elements/{element}/presets/{preset}', [CheckCarSettingsController::class, 'updateElementPreset'])
            ->name('checkcar.settings.elements.presets.update');
        Route::delete('/settings/elements/{element}/presets/{preset}', [CheckCarSettingsController::class, 'destroyElementPreset'])
            ->name('checkcar.settings.elements.presets.destroy');
    });

// Public customer-facing inspection report (no auth, standalone layout)
Route::get('checkcar/report/{inspection}/{token}', [CarInspectionController::class, 'publicShow'])
    ->name('checkcar.inspections.public.show');
