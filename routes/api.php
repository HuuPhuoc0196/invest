<?php

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


Route::get('/admin/collect', [Sync::class, 'getNewPrice'])->name('Sync.getNewPrice');
Route::get('/admin/collectRisk', [Sync::class, 'getNewRisk'])->name('Sync.getNewRisk');
Route::get('/admin/getSuggestInvestment', [Sync::class, 'suggestInvestment'])->name('Sync.suggestInvestment');
Route::get('/admin/deleteLogs', [Sync::class, 'deleteLogs'])->name('Sync.deleteLogs');
Route::get('/admin/sendEmailRisk', [Sync::class, 'sendEmailRisk'])->name('Sync.sendEmailRisk');
Route::get('/admin/sendEmailStocks', [Sync::class, 'sendEmailStocks'])->name('Sync.sendEmailStocks');
