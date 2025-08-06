<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Admin;
use App\Http\Controllers\Login\Login;
use App\Http\Controllers\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

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

Route::middleware('guest')->group(function () {
    // ✅ Các route công khai, không cần login
    Route::get('/forgot-password', [Login::class, 'forgotPassword'])->name('forgotPassword');
    Route::match(['get', 'post'], '/login', [Login::class, 'login'])->name('login');
    Route::match(['get', 'post'], '/register', [Login::class, 'register'])->name('register');
});

Route::get('/', function () {
    $user = auth()->user();
    if (!$user) {
        return redirect('/login');
    }

    return $user->role == 1 ? redirect('/admin') : redirect('/home');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    // Admin
    Route::get('/admin', [Admin::class, 'show']);
    Route::get('/admin/delete/{code}', [Admin::class, 'delete'])->name('admin.delete');
    Route::match(['get', 'post'], '/admin/insert', [Admin::class, 'insert'])->name('insert');
    Route::match(['get', 'put'], '/admin/update/{code}', [Admin::class, 'update'])->name('admin.update');
});

// User routes
Route::middleware(['auth', 'user'])->group(function () {
    // User
    Route::get('/home', [User::class, 'show']);
    Route::get('/user', [User::class, 'show']);
    Route::get('/user/profile', [User::class, 'profile']);
    Route::get('/user/follow', [User::class, 'follow']);
    Route::get('/user/investment-performance', [User::class, 'investmentPerformance']);
    Route::get('/user/deleteFollow/{code}', [User::class, 'deleteFollow'])->name('user.deleteFollow');

    // Giao dịch
    Route::match(['get', 'post'], '/user/buy', [User::class, 'buy'])->name('buy');
    Route::match(['get', 'post'], '/user/sell', [User::class, 'sell'])->name('sell');
    Route::match(['get', 'post'], '/user/insertFollow', [User::class, 'insertFollow'])->name('insertFollow');
});

// Route::get('/clear-cache', function () {
//     Artisan::call('optimize:clear');

//     return '✅ Cleared: config, cache, route, view.';
// });
