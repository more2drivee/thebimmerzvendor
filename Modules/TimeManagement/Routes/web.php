<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])
    ->prefix('time-management')
    ->group(function () {
        Route::get('/', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'index'])
            ->name('timemanagement.dashboard');

        Route::get('/time-sheet', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'index'])
            ->name('timemanagement.index');
        Route::get('/clock-in', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'showClockInForm'])
            ->name('timemanagement.clock_in_form');
        Route::post('/clock-in', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'clockIn'])
            ->name('timemanagement.clock_in');
        Route::get('/clock-out', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'showClockOutForm'])
            ->name('timemanagement.clock_out_form');
        Route::post('/clock-out', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'clockOut'])
            ->name('timemanagement.clock_out');
        Route::get('/history', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'history'])
            ->name('timemanagement.history');

        // Bulk attendance actions
        Route::post('/bulk-clock-in', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'bulkClockIn'])
            ->name('timemanagement.bulk_clock_in');
        Route::post('/bulk-clock-out', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'bulkClockOut'])
            ->name('timemanagement.bulk_clock_out');

        Route::get('/assignments', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'index'])
            ->name('timemanagement.assignments');

        Route::post('/assignments/assign', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'assign'])
            ->name('timemanagement.assignments.assign');

        Route::post('/assignments/unassign', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'unassign'])
            ->name('timemanagement.assignments.unassign');

        Route::get('/assignments/list', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'list'])
            ->name('timemanagement.assignments.list');

        // Technician Workshop assignment (requires today's attendance)
        Route::post('/assignments/assign-workshop', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'assignWorkshop'])
            ->name('timemanagement.assignments.assignWorkshop');
        Route::post('/assignments/unassign-workshop', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'unassignWorkshop'])
            ->name('timemanagement.assignments.unassignWorkshop');
        Route::get('/assignments/technician-assignments', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'technicianWorkshopAssignments'])
            ->name('timemanagement.assignments.tech_assignments');
        Route::get('/assignments/history', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'assignmentHistory'])
            ->name('timemanagement.assignments.assignmentHistory');
        Route::get('/assignments/workshops-by-job/{job_sheet_id}', [\Modules\TimeManagement\Http\Controllers\AssignmentsController::class, 'getWorkshopsByJobSheet'])
            ->name('timemanagement.assignments.workshopsByJob');

        // Attendance management routes
        Route::get('/attendance/status', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'getAttendanceStatus'])
            ->name('timemanagement.attendance.status');
        Route::post('/attendance/clock-in', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'clockIn'])
            ->name('timemanagement.attendance.clockin');
        Route::post('/attendance/clock-out', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'clockOut'])
            ->name('timemanagement.attendance.clockout');
        Route::post('/attendance/create', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'createAttendance'])
            ->name('timemanagement.attendance.create');
        Route::get('/attendance/users', [\Modules\TimeManagement\Http\Controllers\TimeSheetController::class, 'getUsers'])
            ->name('timemanagement.attendance.users');

        Route::get('/time-control', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'index'])
            ->name('timemanagement.timecontrol');

        Route::get('/time-control/list', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'list'])
            ->name('timemanagement.timecontrol.list');

        // Timer control actions
        Route::post('/time-control/start', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'startTimer'])
            ->name('timemanagement.timecontrol.start');
        Route::post('/time-control/pause', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'pauseTimer'])
            ->name('timemanagement.timecontrol.pause');
        Route::post('/time-control/resume', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'resumeTimer'])
            ->name('timemanagement.timecontrol.resume');
        Route::post('/time-control/complete', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'completeTimer'])
            ->name('timemanagement.timecontrol.complete');
        Route::post('/time-control/play-all', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'playAll'])
            ->name('timemanagement.timecontrol.play_all');
        Route::post('/time-control/pause-all', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'pauseAll'])
            ->name('timemanagement.timecontrol.pause_all');
        Route::post('/time-control/complete-all', [\Modules\TimeManagement\Http\Controllers\TimeControlController::class, 'completeAll'])
            ->name('timemanagement.timecontrol.complete_all');

        Route::get('/performance', [\Modules\TimeManagement\Http\Controllers\PerformanceController::class, 'index'])
            ->name('timemanagement.performance');

        // Time reasons & technician statistics
        Route::get('/time-statistics', [\Modules\TimeManagement\Http\Controllers\TimeStatisticsController::class, 'index'])
            ->name('timemanagement.time_statistics');

        Route::get('/workers', [\Modules\TimeManagement\Http\Controllers\WorkersController::class, 'index'])
            ->name('timemanagement.workers');

        Route::get('/workers/{user}/profile', [\Modules\TimeManagement\Http\Controllers\WorkersController::class, 'profile'])
            ->name('timemanagement.workers.profile');
        Route::get('/workers/{user}/jobs', [\Modules\TimeManagement\Http\Controllers\WorkersController::class, 'jobs'])
            ->name('timemanagement.workers.jobs');

        // Timer phrases management
        Route::get('/phrases', [\Modules\TimeManagement\Http\Controllers\TimerPhraseController::class, 'index'])
            ->name('timemanagement.phrases');
        Route::get('/phrases/list', [\Modules\TimeManagement\Http\Controllers\TimerPhraseController::class, 'list'])
            ->name('timemanagement.phrases.list');
        Route::post('/phrases', [\Modules\TimeManagement\Http\Controllers\TimerPhraseController::class, 'store'])
            ->name('timemanagement.phrases.store');
        Route::put('/phrases/{id}', [\Modules\TimeManagement\Http\Controllers\TimerPhraseController::class, 'update'])
            ->name('timemanagement.phrases.update');
        Route::delete('/phrases/{id}', [\Modules\TimeManagement\Http\Controllers\TimerPhraseController::class, 'destroy'])
            ->name('timemanagement.phrases.destroy');

        // Timer stop reasons management
        Route::get('/stop-reasons', [\Modules\TimeManagement\Http\Controllers\TimerStopReasonAdminController::class, 'index'])
            ->name('timemanagement.stop_reasons');
        Route::get('/stop-reasons/list', [\Modules\TimeManagement\Http\Controllers\TimerStopReasonAdminController::class, 'list'])
            ->name('timemanagement.stop_reasons.list');
        Route::get('/stop-reasons/ongoing-job-sheets', [\Modules\TimeManagement\Http\Controllers\TimerStopReasonAdminController::class, 'ongoingJobSheets'])
            ->name('timemanagement.stop_reasons.ongoing_job_sheets');
        Route::post('/stop-reasons', [\Modules\TimeManagement\Http\Controllers\TimerStopReasonAdminController::class, 'store'])
            ->name('timemanagement.stop_reasons.store');
        Route::put('/stop-reasons/{id}', [\Modules\TimeManagement\Http\Controllers\TimerStopReasonAdminController::class, 'update'])
            ->name('timemanagement.stop_reasons.update');
        Route::delete('/stop-reasons/{id}', [\Modules\TimeManagement\Http\Controllers\TimerStopReasonAdminController::class, 'destroy'])
            ->name('timemanagement.stop_reasons.destroy');
    });