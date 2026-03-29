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
use App\Http\Controllers\SellController;
use App\Http\Controllers\ExpenseController;
use Modules\Repair\Http\Controllers\transactionOverviewController;

Route::group(['middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone'], 'prefix' => 'treasury'], function() {
    Route::get('/', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'index'])->name('treasury.index');
    Route::get('/get-treasury-transactions', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getTreasuryTransactions']);
    Route::get('/pending-payments', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getPendingPayments'])->name('treasury.pending.payments');
    Route::post('/payment/update-status', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'updatePaymentStatus'])->name('treasury.payment.update.status');
    Route::get('/payments', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'paymentsIndex'])->name('treasury.payments.index');
    Route::get('/payments/data', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getPaymentsData'])->name('treasury.payments.data');
    Route::get('/payments/report', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'paymentsReport'])->name('treasury.payments.report');
    
    // Optimized Dashboard Routes (Consolidated Requests)
    Route::get('/dashboard/all-data', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getAllDashboardData'])->name('treasury.dashboard.all.data');
    Route::get('/dashboard/unfiltered-totals', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getUnfilteredFinancialTotals'])->name('treasury.dashboard.unfiltered.totals');
    
    // Original Dashboard Routes (Kept for backward compatibility)
    Route::get('/dashboard-cards', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getDashboardCards'])->name('treasury.dashboard.cards');
    Route::get('/chart-data', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getChartData'])->name('treasury.chart.data');
    Route::get('/filtered-totals', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getFilteredTotals'])->name('treasury.filtered.totals');
    Route::get('/payment-methods-chart', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getPaymentMethodsChart'])->name('treasury.payment.methods.chart');
    Route::get('/transaction-type-trend-chart', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getTransactionTypeTrendChart'])->name('treasury.transaction.type.trend.chart');
    Route::get('/sales-payment-dues', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getSalesPaymentDues'])->name('treasury.sales-payment-dues');
    Route::get('/get-payment-method-balances', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getPaymentMethodBalances'])->name('treasury.get.payment.method.balances');
    Route::get('/payment-method-balances', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getPaymentMethodBalances'])->name('treasury.payment.method.balances');
    Route::get('/branch-payment-method-balances', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getBranchPaymentMethodBalances'])->name('treasury.branch.payment.method.balances');
    Route::post('/submit-internal-transfer', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'submitInternalTransfer'])->name('treasury.submit.internal.transfer');
    Route::get('transaction-overview/{transaction_id}', [transactionOverviewController::class, 'index'])->name('treasury.transaction_overview');

    // Internal Transfer Management Routes
    Route::get('/internal-transfers', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'internalTransfersIndex'])->name('treasury.internal.transfers.index');
    Route::get('/internal-transfers/data', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'getInternalTransfersData'])->name('treasury.internal.transfers.data');
    Route::get('/internal-transfers/{id}', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'showInternalTransfer'])->name('treasury.internal.transfers.show');
    Route::get('/internal-transfers/{id}/edit', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'editInternalTransfer'])->name('treasury.internal.transfers.edit');
    Route::put('/internal-transfers/{id}', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'updateInternalTransfer'])->name('treasury.internal.transfers.update');
    Route::delete('/internal-transfers/{id}', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'destroyInternalTransfer'])->name('treasury.internal.transfers.destroy');
    
    // Opening Balance Management Routes
    Route::get('/opening-balance', [Modules\Treasury\Http\Controllers\OpeningBalanceController::class, 'index'])->name('treasury.opening-balance.index');
    Route::get('/opening-balance/data', [Modules\Treasury\Http\Controllers\OpeningBalanceController::class, 'getData'])->name('treasury.opening-balance.data');
    Route::post('/opening-balance', [Modules\Treasury\Http\Controllers\OpeningBalanceController::class, 'store'])->name('treasury.opening-balance.store');
    Route::get('/opening-balance/transactions', [Modules\Treasury\Http\Controllers\OpeningBalanceController::class, 'getTransactions'])->name('treasury.opening-balance.transactions');
    Route::delete('/opening-balance/{id}', [Modules\Treasury\Http\Controllers\OpeningBalanceController::class, 'destroy'])->name('treasury.opening-balance.destroy');
    
    Route::get('/income', [SellController::class, 'create'])->name('treasury.income');
    Route::get('/expense', [ExpenseController::class, 'create'])->name('treasury.expense');

    // Due Transactions Management Routes
    Route::get('/due-transactions', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'index'])->name('treasury.due-transactions.index');
    Route::get('/due-transactions/data', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'getDueTransactionsData'])->name('treasury.due-transactions.data');
    Route::post('/due-transactions/set-due', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'setTransactionAsDue'])->name('treasury.due-transactions.set-due');
    Route::post('/due-transactions/postpone', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'postponeDueTransaction'])->name('treasury.due-transactions.postpone');
    Route::post('/due-transactions/send-sms', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'sendDueTransactionSms'])->name('treasury.due-transactions.send-sms');
    Route::get('/due-transactions/history', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'getDueDateHistory'])->name('treasury.due-transactions.history');
    Route::post('/due-transactions/toggle', [Modules\Treasury\Http\Controllers\DueTransactionController::class, 'toggleDueTransaction'])->name('treasury.due-transactions.toggle');

    Route::get('/create', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'create']);
    Route::post('/', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'store']);
    Route::get('/{id}/edit', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'edit']);
    Route::put('/{id}', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'update']);
    Route::get('/{id}', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'show']);
    Route::get('/{id}/print', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'printTransaction'])->name('treasury.transaction.print');
    Route::delete('/{id}', [Modules\Treasury\Http\Controllers\TreasuryController::class, 'destroy']);
});
 // Treasury Routes