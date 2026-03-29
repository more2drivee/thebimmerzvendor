<?php

// use App\Http\Controllers\Modules;
// use Illuminate\Support\Facades\Route;

Route::middleware(
    'web',
    'authh',
    'auth',
    'SetSessionData',
    'language',
    'timezone',
    'AdminSidebarMenu',
)->group(function () {
    Route::prefix('essentials')->group(function () {
        Route::get('/dashboard', [
            Modules\Essentials\Http\Controllers\DashboardController::class,
            'essentialsDashboard',
        ]);
        Route::get('/install', [
            Modules\Essentials\Http\Controllers\InstallController::class,
            'index',
        ]);
        Route::post('/install', [
            Modules\Essentials\Http\Controllers\InstallController::class,
            'install',
        ]);
        Route::get('/install/update', [
            Modules\Essentials\Http\Controllers\InstallController::class,
            'update',
        ]);
        Route::get('/install/uninstall', [
            Modules\Essentials\Http\Controllers\InstallController::class,
            'uninstall',
        ]);

        Route::get('/', [Modules\Essentials\Http\Controllers\EssentialsController::class, 'index']);

        //document controller
        Route::resource('document', 'Modules\Essentials\Http\Controllers\DocumentController')->only(
            ['index', 'store', 'destroy', 'show'],
        );
        Route::get('document/download/{id}', [
            Modules\Essentials\Http\Controllers\DocumentController::class,
            'download',
        ]);

        //document share controller
        Route::resource(
            'document-share',
            'Modules\Essentials\Http\Controllers\DocumentShareController',
        )->only(['edit', 'update']);

        //todo controller
        Route::resource('todo', 'ToDoController');

        Route::post('todo/add-comment', [
            Modules\Essentials\Http\Controllers\ToDoController::class,
            'addComment',
        ]);
        Route::get('todo/delete-comment/{id}', [
            Modules\Essentials\Http\Controllers\ToDoController::class,
            'deleteComment',
        ]);
        Route::get('todo/delete-document/{id}', [
            Modules\Essentials\Http\Controllers\ToDoController::class,
            'deleteDocument',
        ]);
        Route::post('todo/upload-document', [
            Modules\Essentials\Http\Controllers\ToDoController::class,
            'uploadDocument',
        ]);
        Route::get('view-todo-{id}-share-docs', [
            Modules\Essentials\Http\Controllers\ToDoController::class,
            'viewSharedDocs',
        ]);

        //reminder controller
        Route::resource('reminder', 'Modules\Essentials\Http\Controllers\ReminderController')->only(
            ['index', 'store', 'edit', 'update', 'destroy', 'show'],
        );

        //message controller
        Route::get('get-new-messages', [
            Modules\Essentials\Http\Controllers\EssentialsMessageController::class,
            'getNewMessages',
        ]);
        Route::resource(
            'messages',
            'Modules\Essentials\Http\Controllers\EssentialsMessageController',
        )->only(['index', 'store', 'destroy']);

        //Allowance and deduction controller
        Route::resource(
            'allowance-deduction',
            'Modules\Essentials\Http\Controllers\EssentialsAllowanceAndDeductionController',
        );

        Route::resource(
            'knowledge-base',
            'Modules\Essentials\Http\Controllers\KnowledgeBaseController',
        );

        Route::get('user-sales-targets', [
            Modules\Essentials\Http\Controllers\DashboardController::class,
            'getUserSalesTargets',
        ]);
    });

    Route::prefix('hrm')->group(function () {
        Route::get('/dashboard', [
            Modules\Essentials\Http\Controllers\DashboardController::class,
            'hrmDashboard',
        ])->name('hrmDashboard');
        Route::resource(
            '/leave-type',
            'Modules\Essentials\Http\Controllers\EssentialsLeaveTypeController',
        );
        Route::resource('/leave', 'Modules\Essentials\Http\Controllers\EssentialsLeaveController');
        Route::post('/change-status', [
            Modules\Essentials\Http\Controllers\EssentialsLeaveController::class,
            'changeStatus',
        ]);
        Route::get('/leave/activity/{id}', [
            Modules\Essentials\Http\Controllers\EssentialsLeaveController::class,
            'activity',
        ]);
        Route::get('/user-leave-summary', [
            Modules\Essentials\Http\Controllers\EssentialsLeaveController::class,
            'getUserLeaveSummary',
        ]);

        Route::get('/settings', [
            Modules\Essentials\Http\Controllers\EssentialsSettingsController::class,
            'edit',
        ]);
        Route::post('/settings', [
            Modules\Essentials\Http\Controllers\EssentialsSettingsController::class,
            'update',
        ]);

        Route::post('/import-attendance', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'importAttendance',
        ]);
        Route::get('/generate-attendance-template', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'generateAttendanceTemplate',
        ]);
        Route::get('/get-shifts-by-location', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getShiftsByLocation',
        ]);
        Route::get('/get-shift-by-time', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getShiftByTime',
        ]);
        Route::post('/attendance/bulk', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'bulkStore',
        ]);
        Route::post('/attendance/attend-all', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'attendAll',
        ]);
        Route::resource('/attendance', 'Modules\Essentials\Http\Controllers\AttendanceController');
        Route::post('/clock-in-clock-out', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'clockInClockOut',
        ]);

        Route::post('/validate-clock-in-clock-out', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'validateClockInClockOut',
        ]);

        Route::get('/get-attendance-by-shift', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getAttendanceByShift',
        ]);
        Route::get('/get-attendance-by-date', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getAttendanceByDate',
        ]);
        Route::get('/get-attendance-row/{user_id}', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getAttendanceRow',
        ]);
        Route::get('/get-attendance-calendar', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getAttendanceCalendar',
        ]);
        Route::get('/get-shift-calendar', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getShiftCalendar',
        ]);

        Route::get('/user-attendance-summary', [
            Modules\Essentials\Http\Controllers\AttendanceController::class,
            'getUserAttendanceSummary',
        ]);

        Route::get('/location-employees', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'getEmployeesBasedOnLocation',
        ]);
        Route::get('/my-payrolls', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'getMyPayrolls',
        ]);
        Route::get('/employee-salary/{user_id}', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'getEmployeeSalary',
        ]);
        Route::get('/get-allowance-deduction-row', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'getAllowanceAndDeductionRow',
        ]);
        Route::get('/payroll-group-datatable', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'payrollGroupDatatable',
        ]);
        Route::get('/view/{id}/payroll-group', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'viewPayrollGroup',
        ]);
        Route::get('/edit/{id}/payroll-group', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'getEditPayrollGroup',
        ]);
        Route::post('/update-payroll-group', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'getUpdatePayrollGroup',
        ]);
        Route::get('/payroll-group/{id}/add-payment', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'addPayment',
        ]);
        Route::post('/post-payment-payroll-group', [
            Modules\Essentials\Http\Controllers\PayrollController::class,
            'postAddPayment',
        ]);
        Route::resource('/payroll', 'Modules\Essentials\Http\Controllers\PayrollController');
        Route::resource('/holiday', 'EssentialsHolidayController');

        Route::get('/shift/assign-users/{shift_id}', [
            Modules\Essentials\Http\Controllers\ShiftController::class,
            'getAssignUsers',
        ]);
        Route::post('/shift/assign-users', [
            Modules\Essentials\Http\Controllers\ShiftController::class,
            'postAssignUsers',
        ]);
        Route::resource('/shift', 'Modules\Essentials\Http\Controllers\ShiftController');
        Route::get('/sales-target', [
            Modules\Essentials\Http\Controllers\SalesTargetController::class,
            'index',
        ]);
        Route::get('/set-sales-target/{id}', [
            Modules\Essentials\Http\Controllers\SalesTargetController::class,
            'setSalesTarget',
        ]);
        Route::post('/save-sales-target', [
            Modules\Essentials\Http\Controllers\SalesTargetController::class,
            'saveSalesTarget',
        ]);

        // Standalone pages
        Route::get('/warnings', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'warningsIndex',
        ]);
        Route::get('/bonuses', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'bonusesIndex',
        ]);
        Route::get('/deductions', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'deductionsIndex',
        ]);
        Route::get('/payment-history', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'paymentHistoryIndex',
        ]);
        Route::get('/advances', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'advancesIndex',
        ]);

        Route::get('/employees/search', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'searchEmployees',
        ]);
        Route::get('/employees', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'index',
        ]);
        Route::get('/employees/create', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'create',
        ]);
        Route::get('/employees/leaderboard', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'leaderboard',
        ]);
        Route::post('/employees', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'store',
        ]);
        Route::get('/employees/{id}/edit', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'edit',
        ]);
        Route::put('/employees/{id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'update',
        ]);

        // Employee tab data endpoints
        Route::get('/employees/data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeesData',
        ]);
        Route::get('/employees/attendance-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getAttendanceData',
        ]);
        Route::get('/employees/attendance-calendar', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getAttendanceCalendar',
        ]);
        Route::get('/employees/absence-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getAbsenceData',
        ]);
        Route::get('/employees/leave-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getLeaveData',
        ]);
        Route::get('/employees/warnings-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getWarningsData',
        ]);
        Route::post('/employees/warnings', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeWarning',
        ]);
        Route::delete('/employees/warnings/{id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'deleteWarning',
        ]);
        Route::get('/employees/bonuses-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getBonusesData',
        ]);
        Route::post('/employees/bonuses', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeBonus',
        ]);
        Route::post('/employees/bonuses/{id}/cancel', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'cancelBonus',
        ]);
        Route::delete('/employees/bonuses/{id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'deleteBonus',
        ]);
        Route::get('/employees/deductions-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getDeductionsData',
        ]);
        Route::post('/employees/deductions', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeDeduction',
        ]);
        Route::post('/employees/deductions/{id}/cancel', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'cancelDeduction',
        ]);
        Route::delete('/employees/deductions/{id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'deleteDeduction',
        ]);
        Route::get('/employees/payment-history-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getPaymentHistoryData',
        ]);
        Route::post('/employees/attendance', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeAttendance',
        ]);
        Route::post('/employees/leave', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeLeave',
        ]);
        Route::get('/employees/advances-data', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getAdvancesData',
        ]);
        Route::post('/employees/advances', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeAdvance',
        ]);
        Route::post('/employees/advances/{id}/status', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'updateAdvanceStatus',
        ]);
        Route::delete('/employees/advances/{id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'deleteAdvance',
        ]);

        // Employee document endpoints
        Route::post('/employees/{id}/documents', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'storeDocument',
        ]);
        Route::delete('/employees/{employee_id}/documents/{doc_id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'deleteDocument',
        ]);

        // Employee modal endpoints
        Route::get('/employees/{id}', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'show',
        ]);
        Route::get('/employees/{id}/modal/attendance', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeAttendanceModal',
        ]);
        Route::get('/employees/{id}/modal/absence', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeAbsenceModal',
        ]);
        Route::get('/employees/{id}/modal/leave', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeLeaveModal',
        ]);
        Route::get('/employees/{id}/modal/warnings', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeWarningsModal',
        ]);
        Route::get('/employees/{id}/modal/bonuses', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeBonusesModal',
        ]);
        Route::get('/employees/{id}/modal/deductions', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeDeductionsModal',
        ]);
        Route::get('/employees/{id}/modal/payment', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeePaymentModal',
        ]);
        Route::get('/employees/{id}/modal/advances', [
            Modules\Essentials\Http\Controllers\EmployeeController::class,
            'getEmployeeAdvancesModal',
        ]);
    });
});
