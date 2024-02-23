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
    Route::get('accounts/{id}/as_of/{as_of}', 'App\Http\Controllers\WebV1\AccountControllerExt@showAsOf');
    Route::get('accounts/{id}/pdf_as_of/{as_of}', 'App\Http\Controllers\WebV1\AccountControllerExt@showPDFAsOf');
    Route::get('tradePortfolios/{id}/split', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@split')
        ->name('tradePortfolios.split');
    Route::patch('tradePortfolios/{id}/split', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@doSplit')
        ->name('tradePortfolios.split');

    Route::resource('funds', App\Http\Controllers\WebV1\FundControllerExt::class);
    Route::resource('accountBalances', App\Http\Controllers\AccountBalanceController::class);
    Route::resource('accountMatchingRules', App\Http\Controllers\AccountMatchingRuleController::class);
    Route::resource('accounts', App\Http\Controllers\WebV1\AccountControllerExt::class);
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
    Route::resource('accountReports', App\Http\Controllers\WebV1\AccountReportControllerExt::class);
    Route::resource('changeLogs', App\Http\Controllers\ChangeLogController::class);
    Route::resource('tradePortfolios', App\Http\Controllers\WebV1\TradePortfolioControllerExt::class);
    Route::resource('tradePortfolioItems', App\Http\Controllers\TradePortfolioItemController::class);
    Route::resource('fundReportSchedules', App\Http\Controllers\FundReportScheduleController::class);
    Route::resource('reportSchedules', App\Http\Controllers\WebV1\ReportScheduleControllerExt::class);
    Route::resource('fundReportSchedules', App\Http\Controllers\FundReportScheduleController::class);

    Route::get('tradePortfolios/create', 'App\Http\Controllers\WebV1\TradePortfolioControllerExt@createWithParams')
        ->name('tradePortfolios.create');

});
