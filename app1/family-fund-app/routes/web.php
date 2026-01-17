<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// Dev-only auto-login route for CLI testing
if (app()->environment('local')) {
    Route::get('/dev-login/{redirect?}', function ($redirect = '/') {
        Auth::loginUsingId(\App\Models\User::where('email', 'claude@test.local')->first()->id);
        return redirect('/' . $redirect);
    })->where('redirect', '.*');
}

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::middleware('auth')->group(function () {
    Route::get('funds/{id}/as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@showAsOf');
    Route::get('funds/{id}/pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@showPDFAsOf');
    Route::get('funds/{id}/trade_bands', 'App\Http\Controllers\WebV1\FundControllerExt@tradeBands')
        ->name('funds.show_trade_bands');
        Route::get('funds/{id}/trade_bands_as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@tradeBandsAsOf')
        ->name('funds.show_trade_bands_as_of');
    Route::get('funds/{id}/trade_bands_pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@showTradeBandsPDFAsOf')
        ->name('funds.show_trade_bands_pdf');
    Route::get('accounts/{id}/as_of/{as_of}', 'App\Http\Controllers\WebV1\AccountControllerExt@showAsOf');
    Route::get('accounts/{id}/pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\AccountControllerExt@showPDFAsOf');
    Route::get('tradePortfolios/{id}/split', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@split')
        ->name('tradePortfolios.split');
    Route::patch('tradePortfolios/{id}/split', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@doSplit')
        ->name('tradePortfolios.doSplit');
    Route::get('tradePortfolios/{id}/show_diff', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@showDiff')
        ->name('tradePortfolios.show_diff');
    Route::get('tradePortfolios/{id}/announce', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@announce')
        ->name('tradePortfolios.announce');
    Route::get('tradePortfolios/{id}/rebalance/{start}/{end}', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@showRebalance')
        ->name('tradePortfolios.showRebalance');
    Route::get('portfolios/{id}/rebalance/{start}/{end}', 'App\Http\Controllers\WebV1\PortfolioControllerExt@showRebalance')
        ->name('portfolios.showRebalance');
    Route::get('portfolios/{id}/rebalance_pdf/{start}/{end}', 'App\Http\Controllers\WebV1\PortfolioControllerExt@showRebalancePDF')
        ->name('portfolios.showRebalancePDF');
    Route::get('tradePortfoliosItems/createWithParams', 'App\Http\Controllers\WebV1\TradePortfolioItemControllerExt@createWithParams')
        ->name('tradePortfoliosItems.createWithParams');
    Route::post('transactions/preview', 'App\Http\Controllers\WebV1\TransactionControllerExt@preview')
        ->name('transactions.preview');
    Route::get('transactions/create_bulk', 'App\Http\Controllers\WebV1\TransactionControllerExt@bulkCreate')
        ->name('transactions.create_bulk');
    Route::post('transactions/preview_bulk', 'App\Http\Controllers\WebV1\TransactionControllerExt@bulkPreview')
        ->name('transactions.preview_bulk');
    Route::post('transactions/store_bulk', 'App\Http\Controllers\WebV1\TransactionControllerExt@bulkStore')
        ->name('transactions.store_bulk');
    Route::get('transactions/preview_pending/{id}', 'App\Http\Controllers\WebV1\TransactionControllerExt@previewPending')
        ->name('transactions.preview_pending');
    Route::post('transactions/process_pending/{id}', 'App\Http\Controllers\WebV1\TransactionControllerExt@processPending')
        ->name('transactions.process_pending');
    Route::post('transactions/process_all_pending', 'App\Http\Controllers\WebV1\TransactionControllerExt@processAllPending')
        ->name('transactions.process_all_pending');
    Route::get('transactions/{id}/clone', 'App\Http\Controllers\WebV1\TransactionControllerExt@clone')
        ->name('transactions.clone');
    Route::get('transactions/{id}/resend-email', 'App\Http\Controllers\WebV1\TransactionControllerExt@resendEmail')
        ->name('transactions.resend-email');
    Route::get('accountMatchingRules/create_bulk', 'App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt@bulkCreate')
        ->name('accountMatchingRules.create_bulk');
    Route::post('accountMatchingRules/store_bulk', 'App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt@bulkStore')
        ->name('accountMatchingRules.store_bulk');
    Route::get('accountMatchingRules/{id}/resend-email', 'App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt@resendEmail')
        ->name('accountMatchingRules.resend-email');
    Route::get('cashDeposits/{id}/assign', 'App\Http\Controllers\WebV1\CashDepositControllerExt@assign')
        ->name('cashDeposits.assign');
    Route::post('cashDeposits/{id}/assign', 'App\Http\Controllers\WebV1\CashDepositControllerExt@doAssign')
            ->name('cashDeposits.do_assign');
    Route::get('cashDeposits/{id}/resend-email', 'App\Http\Controllers\WebV1\CashDepositControllerExt@resendEmail')
        ->name('cashDeposits.resend-email');
    Route::get('tradePortfolios/{id}/preview_deposits', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@previewCashDeposits')
        ->name('tradePortfolios.preview_deposits');
    Route::post('tradePortfolios/{id}/do_deposits', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@doCashDeposits')
        ->name('tradePortfolios.do_deposits');
    Route::get('scheduledJobs/{id}/preview/{asOf}', 'App\Http\Controllers\WebV1\ScheduledJobControllerExt@previewScheduledJob')
        ->name('scheduledJobs.preview');
    Route::post('scheduledJobs/{id}/run/{asOf}', 'App\Http\Controllers\WebV1\ScheduledJobControllerExt@runScheduledJob')
        ->name('scheduledJobs.run');
    Route::post('scheduledJobs/{id}/force-run/{asOf}', 'App\Http\Controllers\WebV1\ScheduledJobControllerExt@forceRunScheduledJob')
        ->name('scheduledJobs.force-run');

    // Operations Dashboard (admin only - checked in controller)
    Route::get('operations', 'App\Http\Controllers\WebV1\OperationsController@index')
        ->name('operations.index');
    Route::post('operations/run-due-jobs', 'App\Http\Controllers\WebV1\OperationsController@runDueJobs')
        ->name('operations.run_due_jobs');
    Route::post('operations/process-pending', 'App\Http\Controllers\WebV1\OperationsController@processPending')
        ->name('operations.process_pending');
    Route::post('operations/queue/start', 'App\Http\Controllers\WebV1\OperationsController@startQueue')
        ->name('operations.queue_start');
    Route::post('operations/queue/stop', 'App\Http\Controllers\WebV1\OperationsController@stopQueue')
        ->name('operations.queue_stop');
    Route::post('operations/queue/retry/{uuid}', 'App\Http\Controllers\WebV1\OperationsController@retryFailedJob')
        ->name('operations.queue_retry');
    Route::post('operations/queue/retry-all', 'App\Http\Controllers\WebV1\OperationsController@retryAllFailedJobs')
        ->name('operations.queue_retry_all');
    Route::post('operations/queue/flush', 'App\Http\Controllers\WebV1\OperationsController@flushFailedJobs')
        ->name('operations.queue_flush');
    Route::post('operations/send-test-email', 'App\Http\Controllers\WebV1\OperationsController@sendTestEmail')
        ->name('operations.send_test_email');

    // Email Operations (admin only - checked in controller)
    Route::get('emails', 'App\Http\Controllers\WebV1\EmailController@index')
        ->name('emails.index');
    Route::post('emails/send-test', 'App\Http\Controllers\WebV1\EmailController@sendTest')
        ->name('emails.send_test');
    Route::get('emails/attachment/{hash}/{filename}', 'App\Http\Controllers\WebV1\EmailController@downloadAttachment')
        ->name('emails.attachment')
        ->where('hash', '[a-f0-9]{32}');
    Route::get('emails/{filename}', 'App\Http\Controllers\WebV1\EmailController@show')
        ->name('emails.show');

    Route::resource('accountBalances', App\Http\Controllers\AccountBalanceController::class);
    Route::resource('accountGoals', App\Http\Controllers\AccountGoalController::class);
    Route::resource('accountMatchingRules', App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt::class);
    Route::resource('accountReports', App\Http\Controllers\WebV1\AccountReportControllerExt::class);
    Route::resource('accounts', App\Http\Controllers\WebV1\AccountControllerExt::class);
    Route::resource('addresses', App\Http\Controllers\AddressController::class);
    Route::resource('assetChangeLogs', App\Http\Controllers\AssetChangeLogController::class);
    Route::resource('assetPrices', App\Http\Controllers\WebV1\AssetPriceControllerExt::class);
    Route::resource('assets', App\Http\Controllers\AssetController::class);
    Route::resource('cashDeposits', App\Http\Controllers\WebV1\CashDepositControllerExt::class);
    Route::resource('changeLogs', App\Http\Controllers\ChangeLogController::class);
    Route::resource('depositRequests', App\Http\Controllers\WebV1\DepositRequestControllerExt::class);
    Route::post('fundReports/{id}/resend', 'App\Http\Controllers\WebV1\FundReportControllerExt@resend')
        ->name('fundReports.resend');
    Route::resource('fundReports', App\Http\Controllers\WebV1\FundReportControllerExt::class);
    Route::get('funds/create-with-setup', 'App\Http\Controllers\WebV1\FundControllerExt@createWithSetup')
        ->name('funds.createWithSetup');
    Route::post('funds/store-with-setup', 'App\Http\Controllers\WebV1\FundControllerExt@storeWithSetup')
        ->name('funds.storeWithSetup');
    Route::resource('funds', App\Http\Controllers\WebV1\FundControllerExt::class);
    Route::resource('goals', App\Http\Controllers\WebV1\GoalControllerExt::class);
    Route::resource('id_documents', App\Http\Controllers\IdDocumentController::class);
    Route::get('matchingRules/{id}/clone', 'App\Http\Controllers\WebV1\MatchingRuleControllerExt@clone')
        ->name('matchingRules.clone');
    Route::post('matchingRules/store_clone', 'App\Http\Controllers\WebV1\MatchingRuleControllerExt@storeClone')
        ->name('matchingRules.store_clone');
    Route::get('matchingRules/{id}/send-all-emails', 'App\Http\Controllers\WebV1\MatchingRuleControllerExt@sendAllEmails')
        ->name('matchingRules.send-all-emails');
    Route::resource('matchingRules', App\Http\Controllers\WebV1\MatchingRuleControllerExt::class);
    Route::resource('people', App\Http\Controllers\PersonController::class);
    Route::resource('persons', App\Http\Controllers\PersonController::class);
    Route::resource('phones', App\Http\Controllers\PhoneController::class);
    Route::resource('portfolioAssets', App\Http\Controllers\WebV1\PortfolioAssetControllerExt::class);
    Route::resource('portfolios', App\Http\Controllers\PortfolioController::class);
    Route::resource('scheduledJobs', App\Http\Controllers\WebV1\ScheduledJobControllerExt::class);
    Route::resource('schedules', App\Http\Controllers\ScheduleController::class);
    Route::get('tradeBandReports/{id}/view-pdf', 'App\Http\Controllers\TradeBandReportController@viewPdf')
        ->name('tradeBandReports.viewPdf');
    Route::post('tradeBandReports/{id}/resend', 'App\Http\Controllers\TradeBandReportController@resend')
        ->name('tradeBandReports.resend');
    Route::resource('tradeBandReports', App\Http\Controllers\TradeBandReportController::class);
    Route::resource('tradePortfolioItems', App\Http\Controllers\WebV1\TradePortfolioItemControllerExt::class);
    Route::resource('tradePortfolios', App\Http\Controllers\WebV1\TradePortfolioControllerExt::class);
    Route::resource('transactionMatchings', App\Http\Controllers\TransactionMatchingController::class);
    Route::resource('transactions', App\Http\Controllers\WebV1\TransactionControllerExt::class);
    Route::resource('users', App\Http\Controllers\UserController::class);

    Route::get('tradePortfolios/create', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@createWithParams')
        ->name('tradePortfolios.create');

    Route::get('/change-password', [App\Http\Controllers\HomeController::class, 'changePassword'])->name('change-password');
    Route::post('/change-password', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('update-password');

    // Admin: User Role Management (system-admin only - checked in controller)
    Route::get('admin/user-roles', 'App\Http\Controllers\WebV1\UserRoleController@index')
        ->name('admin.user-roles.index');
    Route::get('admin/user-roles/{id}', 'App\Http\Controllers\WebV1\UserRoleController@show')
        ->name('admin.user-roles.show');
    Route::post('admin/user-roles/{id}/assign', 'App\Http\Controllers\WebV1\UserRoleController@assign')
        ->name('admin.user-roles.assign');
    Route::post('admin/user-roles/{id}/revoke', 'App\Http\Controllers\WebV1\UserRoleController@revoke')
        ->name('admin.user-roles.revoke');
});

require __DIR__.'/auth.php';