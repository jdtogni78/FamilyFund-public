<?php

use Illuminate\Support\Facades\Route;

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


Auth::routes();
Route::get('/', function () {
    return redirect('login');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['middleware' => 'auth'], function () {
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
        ->name('tradePortfolios.split');
    Route::get('tradePortfolios/{id}/show_diff', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@showDiff')
        ->name('tradePortfolios.show_diff');
    Route::get('tradePortfolios/{id}/announce', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@announce')
        ->name('tradePortfolios.announce');
    Route::get('tradePortfolios/{id}/rebalance/{start}/{end}', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@showRebalance')
        ->name('tradePortfolios.showRebalance');
    Route::get('tradePortfoliosItems/createWithParams', 'App\Http\Controllers\WebV1\TradePortfolioItemControllerExt@createWithParams')
        ->name('tradePortfoliosItems.createWithParams');
    Route::get('transactions/preview', 'App\Http\Controllers\WebV1\TransactionControllerExt@preview')
        ->name('transactions.preview');
    Route::get('accountMatchingRules/create_bulk', 'App\Http\Controllers\AccountMatchingRuleController@bulkCreate')
        ->name('accountMatchingRules.create_bulk');
    Route::post('accountMatchingRules/store_bulk', 'App\Http\Controllers\AccountMatchingRuleController@bulkStore')
        ->name('accountMatchingRules.store_bulk');

    Route::resource('funds', App\Http\Controllers\WebV1\FundControllerExt::class);
    Route::resource('accounts', App\Http\Controllers\WebV1\AccountControllerExt::class);
    Route::resource('accountBalances', App\Http\Controllers\AccountBalanceController::class);
    Route::resource('accountMatchingRules', App\Http\Controllers\AccountMatchingRuleController::class);
    Route::resource('accountReports', App\Http\Controllers\WebV1\AccountReportControllerExt::class);
    Route::resource('assetPrices', App\Http\Controllers\AssetPriceController::class);
    Route::resource('assets', App\Http\Controllers\AssetController::class);
    Route::resource('matchingRules', App\Http\Controllers\MatchingRuleController::class);
    Route::resource('portfolioAssets', App\Http\Controllers\PortfolioAssetController::class);
    Route::resource('portfolios', App\Http\Controllers\PortfolioController::class);
    Route::resource('transactions', App\Http\Controllers\WebV1\TransactionControllerExt::class);
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::resource('assetChangeLogs', App\Http\Controllers\AssetChangeLogController::class);
    Route::resource('transactionMatchings', App\Http\Controllers\TransactionMatchingController::class);
    Route::resource('fundReports', App\Http\Controllers\WebV1\FundReportControllerExt::class);
    Route::resource('changeLogs', App\Http\Controllers\ChangeLogController::class);
    Route::resource('tradePortfolios', App\Http\Controllers\WebV1\TradePortfolioControllerExt::class);
    Route::resource('tradePortfolioItems', App\Http\Controllers\WebV1\TradePortfolioItemControllerExt::class);
    Route::resource('schedules', App\Http\Controllers\ScheduleController::class);
    Route::resource('scheduledJobs', App\Http\Controllers\ScheduledJobController::class);
    Route::resource('addresses', App\Http\Controllers\AddressController::class);
    Route::resource('phones', App\Http\Controllers\PhoneController::class);
    Route::resource('persons', App\Http\Controllers\PersonController::class);
    Route::resource('people', App\Http\Controllers\PersonController::class);
    Route::resource('id_documents', App\Http\Controllers\IdDocumentController::class);
    // Route::resource('accountPersons', App\Http\Controllers\AccountPersonController::class);
    Route::resource('depositRequests', App\Http\Controllers\WebV1\DepositRequestControllerExt::class);
    Route::resource('cashDeposits', App\Http\Controllers\CashDepositController::class);

    Route::get('tradePortfolios/create', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@createWithParams')
        ->name('tradePortfolios.create');

    Route::get('/change-password', [App\Http\Controllers\HomeController::class, 'changePassword'])->name('change-password');
    Route::post('/change-password', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('update-password');
});

Route::resource('persons', App\Http\Controllers\PersonController::class);


