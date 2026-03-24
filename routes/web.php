<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Admin;
use App\Http\Controllers\Login\Login;
use App\Http\Controllers\User\User;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Sync\Sync;

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
    Route::match(['get', 'post'], '/login', [Login::class, 'login'])->name('login');
    Route::match(['get', 'post'], '/register', [Login::class, 'register'])->name('register');
    Route::match(['get', 'post'], '/forgotPassword', [Login::class, 'forgotPassword'])->name('forgotPassword');
    Route::get('/reset-password', [Login::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [Login::class, 'resetPassword'])->name('password.update');
});

// Xác thực email (user bấm link trong email, không cần đăng nhập)
Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Http\Request $request, $id, $hash) {
    $user = UserModel::find($id);
    if (!$user || !hash_equals((string) $hash, (string) sha1($user->getEmailForVerification()))) {
        return redirect()->route('login')->with('error', 'Link xác thực không hợp lệ hoặc đã hết hạn.');
    }
    if ($user->hasVerifiedEmail()) {
        // active không nằm trong $fillable → không dùng update([...]); gán trực tiếp
        $user->active = 1;
        $user->save();
        return redirect()->route('login')->with('message', 'Email đã được xác thực trước đó. Bạn có thể đăng nhập.');
    }
    $user->markEmailAsVerified();
    $user->active = 1;
    $user->save();
    return redirect()->route('login')->with('message', 'Email đã được xác thực. Bạn có thể đăng nhập.');
})->middleware(['signed'])->name('verification.verify');

Route::get('/', function () {
    $user = auth()->user();
    if (!$user) {
        return redirect()->route('home');
    }
    return $user->role == 1 ? redirect('/admin') : redirect()->route('home');
});

// Profile: chỉ user đã đăng nhập (nếu cần public thì chuyển vào nhóm guest)
Route::get('/profile', [Login::class, 'profile'])->name('profile')->middleware('auth');

Route::get('/user/get-risk-level/{code}', [User::class, 'getRiskLevel'])->name('user.getRiskLevel');

// Trang chủ: cho phép cả guest và user (không bắt buộc login)
Route::get('/home', [User::class, 'show'])->name('home');
Route::get('/user', [User::class, 'show']);

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
    Route::match(['get', 'post'], '/admin/updateRiskForCode', [Sync::class, 'updateRiskForCode'])->name('updateRiskForCode');
    Route::post('/admin/sync/run-update-stock/{code}', [Sync::class, 'runSyncUpdateStock'])->name('admin.sync.runUpdateStock');

    // Quản lý cổ phiếu
    Route::get('/admin/stocks', [Admin::class, 'stockManagement'])->name('admin.stocks');
    Route::get('/admin/stocks/export-csv', [Admin::class, 'exportStocksCsv'])->name('admin.stocks.exportCsv');
    Route::post('/admin/stocks/import-csv', [Admin::class, 'importStocksCsv'])->name('admin.stocks.importCsv');
    Route::match(['get', 'post'], '/admin/stocks/insert', [Admin::class, 'stockInsert'])->name('admin.stocks.insert');

    // Log có thể chứa thông tin nhạy cảm; chỉ admin mới truy cập được. Production nên cân nhắc giới hạn độ dài hoặc phân quyền chặt hơn.
    Route::get('/admin/logs', function () {
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            return "Không tìm thấy file log!";
        }

        $logs = File::get($logFile);
        return "<pre>" . htmlspecialchars($logs) . "</pre>";
    });
    Route::get('/admin/logsVPS', [Sync::class, 'getLogsVPS']);
     Route::match(['get', 'post'], '/admin/uploadFile', [Sync::class, 'uploadFile'])->name('uploadFile');
});

// User routes (các trang cần đăng nhập, role user)
Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/user/profile', [User::class, 'profile']);
    Route::get('/user/infoProfile', [User::class, 'infoProfile']);
    Route::get('/user/follow', [User::class, 'follow']);
    Route::get('/user/investment-performance', [User::class, 'investmentPerformance']);
    // Route::get('/user/deleteUserProfileCode/{code}', [User::class, 'deleteUserProfileCode']);
    Route::get('/user/deleteFollow/{code}', [User::class, 'deleteFollow'])->name('user.deleteFollow');

    // Giao dịch
    Route::match(['get', 'post'], '/user/buy', [User::class, 'buy'])->name('buy');
    Route::match(['get', 'post'], '/user/sell', [User::class, 'sell'])->name('sell');
    Route::match(['get', 'post'], '/user/insertFollow', [User::class, 'insertFollow'])->name('insertFollow');
    Route::post('/user/addFollowBatch', [User::class, 'addFollowBatch'])->name('user.addFollowBatch');
    Route::get('/user/checkStockCode/{code}', [User::class, 'checkStockCode'])->name('user.checkStockCode');
    Route::match(['get', 'put'], '/user/updateFollow/{code}', [User::class, 'updateFollow'])->name('user.updateFollow');
    Route::match(['get', 'post'],'/user/cashIn', [User::class, 'cashIn'])->name('user.cashIn');
    Route::match(['get', 'post'],'/user/cashOut', [User::class, 'cashOut'])->name('user.cashOut');

    // info profile
    Route::match(['get', 'put'], '/user/updateInfoProfile', [User::class, 'updateInfoProfile'])->name('updateInfoProfile');
    Route::match(['get', 'put'], '/user/changePassword', [User::class, 'changePassword'])->name('changePassword');

    // Email settings
    Route::get('/user/email-settings', [User::class, 'emailSettings'])->name('user.emailSettings');
    Route::post('/user/email-settings/save-session-closed', [User::class, 'saveSessionClosedFlags'])->name('user.saveSessionClosedFlags');

    // Email settings follow
    Route::post('/user/email-settings-follow/save', [User::class, 'saveEmailSettingsFollow'])->name('user.saveEmailSettingsFollow');
});
