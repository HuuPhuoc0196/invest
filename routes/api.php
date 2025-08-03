<?php

use App\Http\Controllers\Admin\Admin;
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


Route::get('/admin/collect', [Admin::class, 'getNewPrice'])->name('admin.getNewPrice');
Route::get('/admin/collectRisk', [Admin::class, 'getNewRisk'])->name('admin.getNewRisk');
