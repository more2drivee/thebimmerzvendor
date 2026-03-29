<?php

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


use Illuminate\Support\Facades\Route;
use Modules\Survey\Http\Controllers\CreateGroupController;
use Modules\Survey\Http\Controllers\DashboardController;
use Modules\Survey\Http\Controllers\GeneralGroupController;
use Modules\Survey\Http\Controllers\GroupSurveyController;
use Modules\Survey\Http\Controllers\KnowSurveyController;
use Modules\Survey\Http\Controllers\SurveyController;
use Modules\Survey\Http\Controllers\SurveySentController;
use Modules\Survey\Http\Controllers\SurveyAnalyticsController;
use Modules\Survey\Http\Controllers\SurveyCategoryController;
use Modules\Survey\Http\Controllers\SurveySettingsController;

Route::prefix('survey')->group(function () {
    Route::get('/', 'SurveyController@index');
});


Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    Route::prefix('survey')->group(function () {
        Route::get('/', [SurveyController::class, 'index'])->name('survey.index');
        Route::get('/surveys', [SurveyController::class, 'getSurveyData'])->name('survey.data');
        Route::get('/add', [SurveyController::class, 'create'])->name('survey.create');
        Route::post('/store', [SurveyController::class, 'store'])->name('survey.store');
        Route::get('/{id}/edit', [SurveyController::class, 'edit'])->name('survey.edit');
        Route::post('/update', [SurveyController::class, 'update'])->name('survey.update');
        Route::get('/{id}/delete', [SurveyController::class, 'destroy'])->name('survey.destroy');
        Route::get('/send/{id}', [KnowSurveyController::class, 'send'])->name('know.send');
        Route::get('/group/{id}', [GroupSurveyController::class, 'send'])->name('group.send');
        Route::post('/data', [KnowSurveyController::class, 'store'])->name('know.store');
        Route::post('/show', [GroupSurveyController::class, 'store'])->name('group.store');

        Route::get('/sent', [SurveySentController::class, 'index'])->name('survey.index.sent');
        Route::get('/sent/data', [SurveySentController::class, 'getSurveyDataSent'])->name('survey.data.sent');
        Route::get('/show/{user}/{surveyy}', [SurveySentController::class, 'showAnswer'])->name('show.answer');
        Route::get('/sent/{first_name}/{survey_id}/{action_id}', [SurveySentController::class, 'showAnswer'])->name('survey.sent.show');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/analytics', [SurveyAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/data', [SurveyAnalyticsController::class, 'getSurveyAnalytics'])->name('analytics.data');
        Route::get('/analytics/{id}/details', [SurveyAnalyticsController::class, 'getSurveyDetails'])->name('analytics.details');
        Route::get('/analytics/{id}/export', [SurveyAnalyticsController::class, 'exportResponses'])->name('analytics.export');
        Route::get('/analytics/{id}/trends', [SurveyAnalyticsController::class, 'getResponseTrends'])->name('analytics.trends');
        Route::get('/search-customers', [SurveyAnalyticsController::class, 'searchCustomers'])->name('survey.search-customers');

        // Survey categories management
        Route::get('/categories', [SurveyCategoryController::class, 'index'])->name('survey.categories.index');
        Route::get('/categories/data', [SurveyCategoryController::class, 'data'])->name('survey.categories.data');
        Route::get('/categories/active', [SurveyCategoryController::class, 'getActiveCategories'])->name('survey.categories.active');
        Route::post('/categories', [SurveyCategoryController::class, 'store'])->name('survey.categories.store');
        Route::put('/categories/{id}', [SurveyCategoryController::class, 'update'])->name('survey.categories.update');
        Route::delete('/categories/{id}', [SurveyCategoryController::class, 'destroy'])->name('survey.categories.destroy');

        // Conditional survey sending
        Route::get('/conditional-send', [SurveyController::class, 'showConditionalSend'])->name('survey.conditional-send');
        Route::get('/active-surveys', [SurveyController::class, 'getActiveSurveys'])->name('survey.active-surveys');
        Route::post('/conditional-contacts', [SurveyController::class, 'getConditionalContacts'])->name('survey.conditional-contacts');
        Route::post('/conditional-send', [SurveyController::class, 'sendConditionalSurvey'])->name('survey.send-conditional');
        Route::post('/search-contact-by-mobile', [SurveyController::class, 'searchContactByMobile'])->name('survey.search-contact-by-mobile');

        // Survey settings
        Route::get('/settings', [SurveySettingsController::class, 'index'])->name('survey.settings.index');
        Route::post('/settings', [SurveySettingsController::class, 'store'])->name('survey.settings.store');
    });

    Route::get('create/group', [CreateGroupController::class,'index'])->name('create.group');
    Route::post('create/group', [CreateGroupController::class,'store'])->name('store.group');

    Route::get('create/group/service', [CreateGroupController::class,'indexService'])->name('create.group.service');
    Route::post('create/group/service', [CreateGroupController::class,'storeService'])->name('store.group.service');
    
    Route::prefix('group')->group(function () {

        Route::get('/', [CreateGroupController::class, 'showGroups'])->name('show.groups');
        Route::get('/data', [CreateGroupController::class, 'getGroupsData'])->name('group.data');

        Route::get('delete/{id}', [CreateGroupController::class, 'delete'])->name('group.delete');

        Route::get('edit/{id}', [CreateGroupController::class, 'edit'])->name('group.edit');
        Route::post('update', [CreateGroupController::class, 'update'])->name('group.update');

        Route::get('show/{id}', [CreateGroupController::class, 'show'])->name('group.show');

    });

    Route::get('data/general/groups', [GeneralGroupController::class, 'show'])->name('data.general.groups');
    Route::get('data/of/general', [GeneralGroupController::class, 'getData'])->name('general.data');
    Route::get('show/answer/{id}', [GeneralGroupController::class, 'showAnswer'])->name('general.show.answer');
});
Route::get('/show/{id}', [SurveyController::class, 'show'])->name('survey.show');
Route::get('/survey/{first_name}/{action_id}', [KnowSurveyController::class, 'seen'])
   
    ->name('check.seen');
Route::post('/thanks', [KnowSurveyController::class, 'fill'])

    ->name('surveys.store.fill');

Route::get('general/group/{id}/{title}', [GeneralGroupController::class,'index'])
    ->name('general.group');
Route::post('general/group', [GeneralGroupController::class, 'store'])

    ->name('general.store');
