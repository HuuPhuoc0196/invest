<?php

use App\Http\Controllers\Api\CacheController;
use App\Http\Controllers\Sync\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('cron.secret')->group(function () {
    Route::get('/admin/collect', [Sync::class, 'getNewPrice'])->name('Sync.getNewPrice');
    Route::get('/admin/collectRisk', [Sync::class, 'getNewRisk'])->name('Sync.getNewRisk');
    Route::get('/admin/getSuggestInvestment', [Sync::class, 'suggestInvestment'])->name('Sync.suggestInvestment');
    Route::get('/admin/deleteLogs', [Sync::class, 'deleteLogs'])->name('Sync.deleteLogs');
    Route::get('/admin/sendEmailRisk', [Sync::class, 'sendEmailRisk'])->name('Sync.sendEmailRisk');
    Route::get('/admin/sendEmailStocks', [Sync::class, 'sendEmailStocks'])->name('Sync.sendEmailStocks');
    Route::post('/admin/sendEmailError', [Sync::class, 'sendEmailError'])->name('Sync.sendEmailError');
    Route::get('/admin/sendEmailStocksFollow', [Sync::class, 'sendEmailStocksFollow'])->name('Sync.sendEmailStocksFollow');
    Route::get('/admin/followStocksEveryDay', [Sync::class, 'followStocksEveryDay'])->name('Sync.followStocksEveryDay');
    Route::get('/admin/sendEmailVnindex', [Sync::class, 'sendEmailVnindex'])->name('Sync.sendEmailVnindex');
    
    // Cache management APIs
    Route::post('/cache/clear-all', [CacheController::class, 'clearAll'])->name('api.cache.clearAll');
    Route::post('/cache/clear-table', [CacheController::class, 'clearTable'])->name('api.cache.clearTable');
    Route::post('/cache/clear-user', [CacheController::class, 'clearUser'])->name('api.cache.clearUser');
    Route::post('/cache/clear-stock', [CacheController::class, 'clearStock'])->name('api.cache.clearStock');
    Route::post('/cache/clear-keys', [CacheController::class, 'clearKeys'])->name('api.cache.clearKeys');
    Route::get('/cache/info', [CacheController::class, 'getCacheInfo'])->name('api.cache.info');
});