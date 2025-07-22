<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Admin;
use App\Http\Controllers\User\User;

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
// GET
// User
Route::get('/', [User::class, 'show']);
Route::get('/user', [User::class, 'show']);
Route::get('/user/profile', [User::class, 'profile']);
Route::get('/user/buy', [User::class, 'buy']);
Route::get('/user/sell', [User::class, 'sell']);
Route::get('/user/investment-performance', [User::class, 'investmentPerformance']);

// GET
// Admin
Route::get('/admin', [Admin::class, 'show']);
Route::get('/admin/insert', [Admin::class, 'insert']);
Route::get('/admin/delete/{code}', [Admin::class, 'delete'])->name('admin.delete');


// POST
// User
Route::post('/user/buy', [User::class, 'buy']);
Route::post('/user/sell', [User::class, 'sell']);

// POST
// Admin
Route::post('/admin/insert', [Admin::class, 'insert']);


// All GET and PUT
// Admin
Route::match(['get', 'put'], '/admin/update/{code}', [Admin::class, 'update'])->name('admin.update');
