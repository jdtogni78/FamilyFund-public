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

// Dev-only auto-login route for CLI/browser ACL testing.
// Default user is claude@test.local; override with ?as=<email>, a role alias,
// user:<id>, acct<id>, account:<id>, or with explicit ?user_id= / ?account_id=.
// Role aliases map to canonical qa-* users seeded by QaTestUsersSeeder.
if (app()->environment('local', 'dev', 'testing')) {
    Route::get('/dev-login/{redirect?}', function (\Illuminate\Http\Request $request, $redirect = '') {
        // Each alias maps to an ordered list of candidate emails; the first that
        // resolves to a real user wins. The admin is renamed to a non-PII dev
        // address by prod_to_dev.sql, so we fall back to the pre-anonymization
        // admin@dev.familyfund.local (still present on a fresh prod dump / test baseline).
        $aliases = [
            'admin' => ['admin@dev.familyfund.local', 'admin@dev.familyfund.local'],
            'system-admin' => ['admin@dev.familyfund.local', 'admin@dev.familyfund.local'],
            'fund-admin' => ['qa-fund-admin@test.local'],
            'financial-manager' => ['qa-financial-manager@test.local'],
            'beneficiary' => ['qa-beneficiary@test.local'],
        ];
        // An explicit ?as= wins over the ?account_id= / ?user_id= helpers, so the
        // RedirectStrayImpersonation middleware can forward a stray ?as= even onto
        // a URL that also carries account_id/fund_id as legit filters.
        $as = (string) $request->query('as', '');

        if ($as !== '') {
            if (preg_match('/^user:(\d+)$/', $as, $matches)) {
                $user = \App\Models\User::find((int) $matches[1]);
                abort_unless($user, 404, "dev-login: no user with id '{$matches[1]}'");
            } elseif (preg_match('/^(?:acct|account)[:#-]?(\d+)$/', $as, $matches)) {
                $account = \App\Models\AccountExt::with('user')->find((int) $matches[1]);
                abort_unless($account, 404, "dev-login: no account with id '{$matches[1]}'");
                abort_unless($account->user, 404, "dev-login: account {$account->id} has no user");
                $user = $account->user;
            } else {
                // Aliases map to an ordered candidate list (#79); first match wins.
                $candidates = $aliases[$as] ?? [$as];
                $user = \App\Models\User::whereIn('email', $candidates)->first();
                abort_unless($user, 404, "dev-login: no user for '{$as}' (tried: " . implode(', ', $candidates) . ')');
            }
        } elseif ($request->filled('account_id')) {
            $account = \App\Models\AccountExt::with('user')->find($request->integer('account_id'));
            abort_unless($account, 404, "dev-login: no account with id '{$request->query('account_id')}'");
            abort_unless($account->user, 404, "dev-login: account {$account->id} has no user");
            $user = $account->user;
        } elseif ($request->filled('user_id')) {
            $user = \App\Models\User::find($request->integer('user_id'));
            abort_unless($user, 404, "dev-login: no user with id '{$request->query('user_id')}'");
        } else {
            // No ?as= / account_id / user_id → documented default dev user.
            $user = \App\Models\User::where('email', 'claude@test.local')->first();
            abort_unless($user, 404, "dev-login: no user with email 'claude@test.local'");
        }

        Auth::loginUsingId($user->id);

        // Preserve any non-impersonation query params (e.g. ?fund_id=&account_id=
        // filters carried in by the stray-?as= redirect) onto the destination.
        $passthrough = collect($request->query())
            ->except(['as', 'user_id', 'account_id'])
            ->all();
        // ltrim guards against '//' protocol-relative redirects when $redirect is empty
        $target = '/' . ltrim($redirect, '/');
        if (! empty($passthrough)) {
            $target .= '?' . http_build_query($passthrough);
        }

        return redirect($target);
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
    // Constrain as_of route params to a valid YYYY-MM-DD in the year range
    // [1970, 2100] that Utils::decreaseYearMonth supports. Out-of-range or
    // malformed inputs now 404 at routing instead of 500-ing on downstream
    // substring math (Utils::asOfAddYear) or year-range checks.
    $asOfRegex = '(19[7-9]\d|20\d\d|2100)-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])';

    Route::get('funds/{id}/overview', 'App\Http\Controllers\WebV1\FundControllerExt@overview')
        ->name('funds.overview');
    Route::get('api/funds/{id}/overview-data', 'App\Http\Controllers\WebV1\FundControllerExt@overviewData')
        ->name('api.funds.overview_data');
    Route::get('funds/{id}/as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@showAsOf')
        ->where('as_of', $asOfRegex);
    Route::get('funds/{id}/pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@showPDFAsOf')
        ->where('as_of', $asOfRegex);
    Route::get('funds/{id}/trade_bands', 'App\Http\Controllers\WebV1\FundControllerExt@tradeBands')
        ->name('funds.show_trade_bands');
        Route::get('funds/{id}/trade_bands_as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@tradeBandsAsOf')
        ->where('as_of', $asOfRegex)
        ->name('funds.show_trade_bands_as_of');
    Route::get('funds/{id}/trade_bands_pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\FundControllerExt@showTradeBandsPDFAsOf')
        ->where('as_of', $asOfRegex)
        ->name('funds.show_trade_bands_pdf');
    Route::get('funds/{id}/portfolios', 'App\Http\Controllers\WebV1\FundControllerExt@portfolios')
        ->name('funds.portfolios');
    Route::get('funds/{id}/withdrawal_goal/edit', 'App\Http\Controllers\WebV1\FundControllerExt@editFourPctGoal')
        ->name('funds.withdrawal_goal.edit');
    Route::put('funds/{id}/withdrawal_goal', 'App\Http\Controllers\WebV1\FundControllerExt@updateFourPctGoal')
        ->name('funds.withdrawal_goal.update');
    Route::get('accounts/{id}/as_of/{as_of}', 'App\Http\Controllers\WebV1\AccountControllerExt@showAsOf')
        ->where('as_of', $asOfRegex);
    Route::get('accounts/{id}/pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\AccountControllerExt@showPDFAsOf')
        ->where('as_of', $asOfRegex);
    Route::get('tradePortfolios/{id}/rebalance', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@rebalance')
        ->middleware('fund.full')
        ->name('tradePortfolios.rebalance');
    Route::post('tradePortfolios/{id}/rebalance', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@doRebalance')
        ->middleware('fund.full')
        ->name('tradePortfolios.doRebalance');
    Route::get('tradePortfolios/{id}/show_diff', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@showDiff')
        ->middleware('fund.full')
        ->name('tradePortfolios.show_diff');
    Route::post('tradePortfolios/{id}/announce', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@announce')
        ->middleware('fund.full')
        ->name('tradePortfolios.announce');
    Route::get('tradePortfolios/{id}/rebalance/{start}/{end}', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@showRebalance')
        ->middleware('fund.full')
        ->name('tradePortfolios.showRebalance');
    Route::get('portfolios/{id}/rebalance/{start}/{end}', 'App\Http\Controllers\WebV1\PortfolioControllerExt@showRebalance')
        ->middleware('fund.full')
        ->name('portfolios.showRebalance');
    Route::get('portfolios/{id}/rebalance_pdf/{start}/{end}', 'App\Http\Controllers\WebV1\PortfolioControllerExt@showRebalancePDF')
        ->middleware('fund.full')
        ->name('portfolios.showRebalancePDF');
    Route::get('tradePortfoliosItems/createWithParams', 'App\Http\Controllers\WebV1\TradePortfolioItemControllerExt@createWithParams')
        ->middleware('fund.full')
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
    Route::post('transactions/{id}/resend-email', 'App\Http\Controllers\WebV1\TransactionControllerExt@resendEmail')
        ->name('transactions.resend-email');
    Route::get('accountMatchingRules/create_bulk', 'App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt@bulkCreate')
        ->middleware('fund.full')
        ->name('accountMatchingRules.create_bulk');
    Route::post('accountMatchingRules/store_bulk', 'App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt@bulkStore')
        ->middleware('fund.full')
        ->name('accountMatchingRules.store_bulk');
    Route::post('accountMatchingRules/{id}/resend-email', 'App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt@resendEmail')
        ->middleware('fund.full')
        ->name('accountMatchingRules.resend-email');
    Route::get('cashDeposits/{id}/assign', 'App\Http\Controllers\WebV1\CashDepositControllerExt@assign')
        ->middleware('fund.full')
        ->name('cashDeposits.assign');
    Route::post('cashDeposits/{id}/assign', 'App\Http\Controllers\WebV1\CashDepositControllerExt@doAssign')
            ->middleware('fund.full')
            ->name('cashDeposits.do_assign');
    Route::post('cashDeposits/{id}/resend-email', 'App\Http\Controllers\WebV1\CashDepositControllerExt@resendEmail')
        ->middleware('fund.full')
        ->name('cashDeposits.resend-email');
    Route::get('tradePortfolios/{id}/preview_deposits', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@previewCashDeposits')
        ->middleware('fund.full')
        ->name('tradePortfolios.preview_deposits');
    Route::post('tradePortfolios/{id}/do_deposits', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@doCashDeposits')
        ->middleware('fund.full')
        ->name('tradePortfolios.do_deposits');
    Route::get('scheduledJobs/{id}/preview/{asOf}', 'App\Http\Controllers\WebV1\ScheduledJobControllerExt@previewScheduledJob')
        ->middleware('fund.full')
        ->name('scheduledJobs.preview');
    Route::post('scheduledJobs/{id}/run/{asOf}', 'App\Http\Controllers\WebV1\ScheduledJobControllerExt@runScheduledJob')
        ->middleware('fund.full')
        ->name('scheduledJobs.run');
    Route::post('scheduledJobs/{id}/force-run/{asOf}', 'App\Http\Controllers\WebV1\ScheduledJobControllerExt@forceRunScheduledJob')
        ->middleware('fund.full')
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
    Route::get('operations/validate-portfolio-balances', 'App\Http\Controllers\WebV1\OperationsController@validatePortfolioBalances')
        ->name('operations.validate_portfolio_balances');

    // Exchange Holidays (admin only - checked in controller)
    Route::get('exchange-holidays', 'App\Http\Controllers\WebV1\ExchangeHolidayController@index')
        ->name('exchange-holidays.index');
    Route::post('exchange-holidays/sync', 'App\Http\Controllers\WebV1\ExchangeHolidayController@sync')
        ->name('exchange-holidays.sync');

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

    Route::resource('accountBalances', App\Http\Controllers\WebV1\AccountBalanceControllerExt::class)
        ->middleware('fund.full');
    Route::resource('accountGoals', App\Http\Controllers\WebV1\AccountGoalControllerExt::class)
        ->middleware('fund.full');
    Route::resource('accountMatchingRules', App\Http\Controllers\WebV1\AccountMatchingRuleControllerExt::class)
        ->middleware('fund.full');
    Route::resource('accountReports', App\Http\Controllers\WebV1\AccountReportControllerExt::class)
        ->middleware('fund.full');
    Route::resource('accounts', App\Http\Controllers\WebV1\AccountControllerExt::class);
    Route::resource('addresses', App\Http\Controllers\Web\AddressController::class)
        ->middleware('fund.full');
    Route::resource('assetChangeLogs', App\Http\Controllers\Web\AssetChangeLogController::class)
        ->middleware('fund.full');
    Route::resource('assetPrices', App\Http\Controllers\WebV1\AssetPriceControllerExt::class)
        ->middleware('fund.full');
    Route::resource('assets', App\Http\Controllers\Web\AssetController::class)
        ->middleware('fund.full');
    Route::resource('cashDeposits', App\Http\Controllers\WebV1\CashDepositControllerExt::class)
        ->middleware('fund.full');
    Route::resource('changeLogs', App\Http\Controllers\Web\ChangeLogController::class)
        ->middleware('fund.full');
    Route::resource('depositRequests', App\Http\Controllers\WebV1\DepositRequestControllerExt::class)
        ->middleware('fund.full');
    Route::post('fundReports/{id}/resend', 'App\Http\Controllers\WebV1\FundReportControllerExt@resend')
        ->middleware('fund.full')
        ->name('fundReports.resend');
    Route::resource('fundReports', App\Http\Controllers\WebV1\FundReportControllerExt::class)
        ->middleware('fund.full');
    Route::get('funds/create-with-setup', 'App\Http\Controllers\WebV1\FundControllerExt@createWithSetup')
        ->name('funds.createWithSetup');
    Route::post('funds/store-with-setup', 'App\Http\Controllers\WebV1\FundControllerExt@storeWithSetup')
        ->name('funds.storeWithSetup');
    Route::resource('funds', App\Http\Controllers\WebV1\FundControllerExt::class);
    Route::resource('goals', App\Http\Controllers\WebV1\GoalControllerExt::class)
        ->middleware('fund.full');
    Route::resource('id_documents', App\Http\Controllers\Web\IdDocumentController::class)
        ->middleware('fund.full');
    Route::get('matchingRules/{id}/clone', 'App\Http\Controllers\WebV1\MatchingRuleControllerExt@clone')
        ->middleware('fund.full')
        ->name('matchingRules.clone');
    Route::post('matchingRules/store_clone', 'App\Http\Controllers\WebV1\MatchingRuleControllerExt@storeClone')
        ->middleware('fund.full')
        ->name('matchingRules.store_clone');
    Route::post('matchingRules/{id}/send-all-emails', 'App\Http\Controllers\WebV1\MatchingRuleControllerExt@sendAllEmails')
        ->middleware('fund.full')
        ->name('matchingRules.send-all-emails');
    Route::resource('matchingRules', App\Http\Controllers\WebV1\MatchingRuleControllerExt::class)
        ->middleware('fund.full');
    Route::resource('people', App\Http\Controllers\Web\PersonController::class)
        ->middleware('fund.full');
    Route::resource('persons', App\Http\Controllers\Web\PersonController::class)
        ->middleware('fund.full');
    Route::resource('phones', App\Http\Controllers\Web\PhoneController::class)
        ->middleware('fund.full');
    Route::resource('portfolioAssets', App\Http\Controllers\WebV1\PortfolioAssetControllerExt::class)
        ->middleware('fund.full');
    Route::resource('portfolios', App\Http\Controllers\Web\PortfolioController::class)
        ->middleware('fund.full');
    Route::resource('scheduledJobs', App\Http\Controllers\WebV1\ScheduledJobControllerExt::class)
        ->middleware('fund.full');
    Route::resource('schedules', App\Http\Controllers\Web\ScheduleController::class)
        ->middleware('fund.full');
    Route::get('tradeBandReports/{id}/view-pdf', 'App\Http\Controllers\Web\TradeBandReportController@viewPdf')
        ->middleware('fund.full')
        ->name('tradeBandReports.viewPdf');
    Route::post('tradeBandReports/{id}/resend', 'App\Http\Controllers\Web\TradeBandReportController@resend')
        ->middleware('fund.full')
        ->name('tradeBandReports.resend');
    Route::resource('tradeBandReports', App\Http\Controllers\Web\TradeBandReportController::class)
        ->middleware('fund.full');
    Route::resource('tradePortfolioItems', App\Http\Controllers\WebV1\TradePortfolioItemControllerExt::class)
        ->middleware('fund.full');
    Route::resource('tradePortfolios', App\Http\Controllers\WebV1\TradePortfolioControllerExt::class)
        ->middleware('fund.full');
    Route::resource('transactionMatchings', App\Http\Controllers\Web\TransactionMatchingController::class)
        ->middleware('fund.full');
    Route::resource('transactions', App\Http\Controllers\WebV1\TransactionControllerExt::class);
    Route::resource('users', App\Http\Controllers\Web\UserController::class)
        ->middleware('fund.full');

    Route::get('tradePortfolios/create', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@createWithParams')
        ->middleware('fund.full')
        ->name('tradePortfolios.create');

    Route::get('/change-password', [App\Http\Controllers\HomeController::class, 'changePassword'])->name('change-password');
    Route::post('/change-password', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('update-password');

    // Credit Lines (Phase 2 wiring)
    Route::get('accounts/{account}/credit-lines',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'index'])
        ->name('credit_lines.index');
    Route::get('accounts/{account}/credit-lines/create',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'create'])
        ->name('credit_lines.create');
    Route::get('accounts/{account}/credit-lines/available-shares',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'availableShares'])
        ->name('credit_lines.available_shares');
    Route::post('accounts/{account}/credit-lines',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'store'])
        ->name('credit_lines.store');
    Route::get('credit-lines/resolve',
        [\App\Http\Controllers\WebV1\CreditLineMatchResolutionController::class, 'index'])
        ->name('credit_lines.resolve_index');
    Route::post('credit-lines/resolve/{transaction}',
        [\App\Http\Controllers\WebV1\CreditLineMatchResolutionController::class, 'resolve'])
        ->name('credit_lines.resolve');
    // Global (cross-account) listings — must precede credit-lines/{line}
    // so the static segments aren't captured as a {line} id.
    Route::get('credit-lines',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'globalIndex'])
        ->name('credit_lines.global_index');
    Route::get('credit-lines/payments',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'globalPayments'])
        ->name('credit_lines.global_payments');
    // Global create flow (no pre-selected account) — static segments must
    // precede credit-lines/{line} so they aren't captured as a {line} id.
    Route::get('credit-lines/create',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'globalCreate'])
        ->name('credit_lines.global_create');
    Route::get('credit-lines/available-shares',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'globalAvailableShares'])
        ->name('credit_lines.global_available_shares');
    Route::post('credit-lines',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'store'])
        ->name('credit_lines.global_store');
    Route::get('credit-lines/{line}',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'show'])
        ->name('credit_lines.show');
    Route::get('credit-lines/{line}/actions',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'actions'])
        ->name('credit_lines.actions');
    Route::get('credit-lines/{line}/edit',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'edit'])
        ->name('credit_lines.edit');
    Route::put('credit-lines/{line}',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'update'])
        ->name('credit_lines.update');
    Route::post('credit-lines/{line}/repay',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'repay'])
        ->name('credit_lines.repay');
    Route::get('credit-lines/{line}/payments/{payment}/register',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'registerPaymentForm'])
        ->name('credit_lines.payments.register_form');
    Route::post('credit-lines/{line}/payments/{payment}/register',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'registerPayment'])
        ->name('credit_lines.payments.register');
    Route::get('credit-lines/{line}/payments/{payment}/edit',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'editPaymentForm'])
        ->name('credit_lines.payments.edit_form');
    Route::post('credit-lines/{line}/payments/{payment}/edit',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'updatePayment'])
        ->name('credit_lines.payments.update');
    Route::post('credit-lines/{line}/payments/{payment}/reverse',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'reversePayment'])
        ->name('credit_lines.payments.reverse');
    Route::get('credit-lines/{line}/transactions/{transaction}/allocate',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'allocateForm'])
        ->name('credit_lines.payments.allocate_form');
    Route::post('credit-lines/{line}/transactions/{transaction}/allocate',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'allocate'])
        ->name('credit_lines.payments.allocate');
    Route::post('credit-lines/{line}/readjust',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'readjust'])
        ->name('credit_lines.readjust');
    Route::post('credit-lines/{line}/cancel',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'cancel'])
        ->name('credit_lines.cancel');
    Route::post('transactions/{transaction}/reverse',
        [\App\Http\Controllers\WebV1\TransactionReversalController::class, 'store'])
        ->name('credit_lines.reverse');
    // Phase 9: credit-line payment simulator (admin-gated in controller)
    Route::get('credit-lines/{line}/simulator',
        [\App\Http\Controllers\WebV1\AccountCreditLineController::class, 'simulator'])
        ->name('credit_lines.simulator');

    // Admin: backdated transaction creation (UC-46)
    Route::get('admin/transactions/create',
        [\App\Http\Controllers\WebV1\AdminTransactionController::class, 'create'])
        ->name('admin.transactions.create');
    Route::post('admin/transactions',
        [\App\Http\Controllers\WebV1\AdminTransactionController::class, 'store'])
        ->name('admin.transactions.store');

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
