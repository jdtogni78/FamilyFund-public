<?php

use App\Http\Controllers\API\AssetAPIController;
use App\Http\Controllers\APIv1\PortfolioAssetAPIControllerExt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIv1\AssetPriceAPIControllerExt;
use App\Http\Controllers\API\PortfolioAssetAPIController;
use App\Http\Controllers\APIv1\AccountAPIControllerExt;
use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;

use Illuminate\Support\Facades\Artisan;
Route::get('/clear', function () {
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    return 'clear done';
});
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('account_matching/{account_id}/as_of/{as_of}', [AccountAPIControllerExt::class, 'accountMatching']);

Route::get('portfolios/{id}/as_of/{as_of}', 'App\Http\Controllers\APIv1\PortfolioAPIControllerExt@showAsOf');
//Route::post('portfolios/{code}/assets_update', [PortfolioAPIControllerExt::class, 'assetsUpdate']);
Route::post('asset_prices_bulk_update', [AssetPriceAPIControllerExt::class, 'bulkStore']);
Route::get('asset_prices/gaps', [AssetPriceAPIControllerExt::class, 'gaps']);
Route::post('portfolio_assets_bulk_update', [PortfolioAssetAPIControllerExt::class, 'bulkStore']);
Route::post('portfolio_balances_bulk_update', [App\Http\Controllers\API\PortfolioBalanceAPIController::class, 'bulkUpdate']);

Route::get('funds/{id}/as_of/{as_of}', 'App\Http\Controllers\APIv1\FundAPIControllerExt@showAsOf');
Route::get('funds/{id}/performance_as_of/{as_of}', 'App\Http\Controllers\APIv1\FundAPIControllerExt@showPerformanceAsOf');
Route::get('funds/{id}/account_balances_as_of/{as_of}', 'App\Http\Controllers\APIv1\FundAPIControllerExt@showAccountBalancesAsOf');
Route::get('funds/{id}/report_as_of/{as_of}', 'App\Http\Controllers\APIv1\FundAPIControllerExt@showReportAsOf');

Route::get('accounts/{id}/as_of/{as_of}', 'App\Http\Controllers\APIv1\AccountAPIControllerExt@showAsOf');
Route::get('accounts/{id}/transactions_as_of/{as_of}', 'App\Http\Controllers\APIv1\AccountAPIControllerExt@showTransactionsAsOf');
Route::get('accounts/{id}/performance_as_of/{as_of}', 'App\Http\Controllers\APIv1\AccountAPIControllerExt@showPerformanceAsOf');
Route::get('accounts/{id}/report_as_of/{as_of}', 'App\Http\Controllers\APIv1\AccountAPIControllerExt@showReportAsOf');
Route::get('accounts/{id}/share_value_as_of/{as_of}', 'App\Http\Controllers\APIv1\AccountAPIControllerExt@shareValueAsOf')
    ->name('accounts.share_value');

Route::post('schedule_jobs', 'App\Http\Controllers\APIv1\ScheduledJobAPIControllerExt@scheduleJobs');

Route::post('funds/setup', 'App\Http\Controllers\APIv1\FundAPIControllerExt@storeWithSetup')->name('api.funds.setup');
Route::resource('funds', App\Http\Controllers\API\FundAPIController::class)->names('api.funds');
Route::resource('accounts', App\Http\Controllers\API\AccountAPIController::class)->names('api.accounts');
Route::resource('portfolios', App\Http\Controllers\API\PortfolioAPIController::class)->names('api.portfolios');
Route::resource('transactions', App\Http\Controllers\APIv1\TransactionAPIControllerExt::class)->names('api.transactions');

Route::resource('asset_prices', AssetPriceAPIControllerExt::class);
Route::resource('assets', AssetAPIController::class)->names('api.assets');
Route::resource('account_balances', App\Http\Controllers\API\AccountBalanceAPIController::class);
Route::resource('account_matching_rules', App\Http\Controllers\API\AccountMatchingRuleAPIController::class);
Route::resource('matching_rules', App\Http\Controllers\API\MatchingRuleAPIController::class);
Route::resource('portfolio_assets', PortfolioAssetAPIController::class);
Route::resource('users', App\Http\Controllers\API\UserAPIController::class)->names('api.users');
Route::resource('asset_change_logs', App\Http\Controllers\API\AssetChangeLogAPIController::class);
Route::resource('transaction_matchings', App\Http\Controllers\API\TransactionMatchingAPIController::class);

Route::resource('fund_reports', App\Http\Controllers\APIv1\FundReportAPIControllerExt::class);
Route::resource('account_reports', App\Http\Controllers\APIv1\AccountReportAPIControllerExt::class);

Route::resource('change_logs', App\Http\Controllers\API\ChangeLogAPIController::class);
Route::resource('trade_portfolios', App\Http\Controllers\APIv1\TradePortfolioAPIControllerExt::class);
Route::resource('trade_portfolio_items', App\Http\Controllers\API\TradePortfolioItemAPIController::class);

Route::resource('schedules', App\Http\Controllers\API\ScheduleAPIController::class)->names('api.schedules');
Route::resource('scheduled_jobs', App\Http\Controllers\APIv1\ScheduledJobAPIControllerExt::class);


Route::resource('addresses', App\Http\Controllers\API\AddressAPIController::class)->names('api.addresses');


Route::resource('people', App\Http\Controllers\API\PersonAPIController::class)->names('api.people');


Route::resource('id_documents', App\Http\Controllers\API\IdDocumentAPIController::class)->names('api.id_documents');


Route::resource('phones', App\Http\Controllers\API\PhoneAPIController::class)->names('api.phones');

// Exchange Holiday API
Route::get('/exchange_holidays/{exchange}/{year}',
    [App\Http\Controllers\APIv1\ExchangeHolidayAPIController::class, 'index']);
Route::post('/exchange_holidays/sync',
    [App\Http\Controllers\APIv1\ExchangeHolidayAPIController::class, 'sync']);
Route::get('/exchange_holidays/status',
    [App\Http\Controllers\APIv1\ExchangeHolidayAPIController::class, 'status']);
