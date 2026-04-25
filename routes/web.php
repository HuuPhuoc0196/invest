<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Admin;
use App\Http\Controllers\Login\Login;
use App\Http\Controllers\User\User;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Sync\Sync;
use App\Services\CacheService;

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

Route::get('/robots.txt', function () {
    $base = rtrim((string) config('app.url'), '/');
    $body = implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin/',
        'Disallow: /user/',
        'Disallow: /profile',
        '',
        'Sitemap: ' . $base . '/sitemap.xml',
        '',
    ]);

    return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('site.robots');

Route::get('/sitemap.xml', function () {
    $xml = Cache::remember('sitemap_xml', 86400, function () {
        $today = now()->toDateString();
        $urls = [
            ['loc' => route('home'),          'lastmod' => $today, 'changefreq' => 'daily',   'priority' => '1.0'],
            ['loc' => route('login'),         'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('register'),      'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('forgotPassword'),'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.4'],
        ];
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $lines[] = '<url>';
            $lines[] = '<loc>' . $loc . '</loc>';
            $lines[] = '<lastmod>' . e($u['lastmod']) . '</lastmod>';
            $lines[] = '<changefreq>' . e($u['changefreq']) . '</changefreq>';
            $lines[] = '<priority>' . e($u['priority']) . '</priority>';
            $lines[] = '</url>';
        }
        $lines[] = '</urlset>';
        return implode("\n", $lines);
    });

    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('site.sitemap');

Route::middleware('guest')->group(function () {
    Route::match(['get', 'post'], '/dang-nhap', [Login::class, 'login'])->name('login');
    Route::match(['get', 'post'], '/dang-ky', [Login::class, 'register'])->name('register');
    Route::match(['get', 'post'], '/quen-mat-khau', [Login::class, 'forgotPassword'])->name('forgotPassword');
    Route::get('/dat-lai-mat-khau', [Login::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [Login::class, 'resetPassword'])->name('password.update');

    Route::post('/login', [Login::class, 'login']);
    Route::post('/register', [Login::class, 'register']);
    Route::post('/forgotPassword', [Login::class, 'forgotPassword']);
    Route::post('/reset-password', [Login::class, 'resetPassword']);
});

Route::get('/login', fn () => redirect('/dang-nhap', 301));
Route::get('/register', fn () => redirect('/dang-ky', 301));
Route::get('/forgotPassword', fn () => redirect('/quen-mat-khau', 301));
Route::get('/reset-password', function () {
    $q = request()->getQueryString();

    return redirect()->to('/dat-lai-mat-khau' . ($q ? '?' . $q : ''), 301);
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
        CacheService::forget("user_{$user->id}");
        return redirect()->route('login')->with('message', 'Email đã được xác thực trước đó. Bạn có thể đăng nhập.');
    }
    $user->markEmailAsVerified();
    $user->active = 1;
    $user->save();
    CacheService::forget("user_{$user->id}");
    return redirect()->route('login')->with('message', 'Email đã được xác thực. Bạn có thể đăng nhập.');
})->middleware(['signed'])->name('verification.verify');

Route::get('/', function () {
    $user = auth()->user();
    if (!$user) {
        return redirect()->route('home');
    }
    return $user->role == 1 ? redirect('/admin') : redirect()->route('home');
});


// Logo: không dùng file tĩnh public/logo.svg (tránh <?xml bị PHP short_open_tag hiểu nhầm trên XAMPP).
// Xóa public/logo.svg nếu còn — request /logo.svg luôn vào Laravel và trả đúng image/svg+xml.
Route::get('/logo.svg', function () {
    $path = public_path('icon/investment_logo.svg');
    abort_unless(is_readable($path), 404);
    return response()->file($path, [
        'Content-Type' => 'image/svg+xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=604800',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('site.logo');

// Debug logo (chỉ khi APP_DEBUG=true): mở /invest/public/__debug/logo trên trình duyệt
Route::get('/__debug/logo', function () {
    abort_unless(config('app.debug'), 404);
    $logoStatic = public_path('logo.svg');
    $icon = public_path('icon/investment_logo.svg');
    $check = static function (string $path): array {
        return [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => is_readable($path),
            'size' => file_exists($path) ? filesize($path) : null,
            'starts_with_svg' => is_readable($path) ? str_starts_with(trim((string) file_get_contents($path, false, null, 0, 200)), '<') : null,
        ];
    };
    return response()->json([
        'app_url' => config('app.url'),
        'route_site_logo' => route('site.logo'),
        'static_public_logo_svg_should_not_exist' => $check($logoStatic),
        'icon_investment_logo_svg' => $check($icon),
        'hint' => 'Logo được phục vụ bởi route site.logo (Laravel). File nguồn: public/icon/investment_logo.svg. Không đặt public/logo.svg (file tĩnh) để tránh xung đột và lỗi PHP short_open_tag với <?xml.',
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->name('debug.logo');

// Trang chủ: cho phép cả guest và user (không bắt buộc login)
Route::get('/trang-chu', [User::class, 'show'])->name('home');
Route::get('/home', fn () => redirect('/trang-chu', 301));
Route::get('/user', [User::class, 'show']);

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    // Admin
    Route::get('/admin', [Admin::class, 'show'])->name('admin.home');
    Route::get('/admin/logs', [Admin::class, 'logs'])->name('admin.logs');
    Route::post('/admin/delete/{code}', [Admin::class, 'delete'])->name('admin.delete');
    Route::match(['get', 'post'], '/admin/insert', [Admin::class, 'insert'])->name('insert');
    Route::match(['get', 'put'], '/admin/update/{code}', [Admin::class, 'update'])->name('admin.update');
    Route::match(['get', 'post'], '/admin/updateRiskForCode', [Sync::class, 'updateRiskForCode'])->name('updateRiskForCode');
    Route::post('/admin/sync/run-update-stock/{code}', [Sync::class, 'runSyncUpdateStock'])->name('admin.sync.runUpdateStock');

    // Quản lý cổ phiếu
    Route::get('/admin/stocks', [Admin::class, 'stockManagement'])->name('admin.stocks');
    Route::get('/admin/stocks/export-csv', [Admin::class, 'exportStocksCsv'])->name('admin.stocks.exportCsv');
    Route::post('/admin/stocks/import-csv', [Admin::class, 'importStocksCsv'])->name('admin.stocks.importCsv');
    Route::match(['get', 'post'], '/admin/stocks/insert', [Admin::class, 'stockInsert'])->name('admin.stocks.insert');
    
    // Admin theo dõi & gợi ý
    Route::get('/admin/stocks/follow', [Admin::class, 'adminFollow'])->name('admin.stocks.follow');
    Route::post('/admin/stocks/follow/batch', [Admin::class, 'addFollowBatch'])->name('admin.stocks.follow.batch');
    Route::delete('/admin/stocks/follow/{stockId}', [Admin::class, 'deleteFollow'])->name('admin.stocks.follow.delete');
    
    Route::get('/admin/stocks/suggest', [Admin::class, 'adminSuggest'])->name('admin.stocks.suggest');
    Route::post('/admin/stocks/suggest/batch', [Admin::class, 'addSuggestBatch'])->name('admin.stocks.suggest.batch');
    Route::delete('/admin/stocks/suggest/{stockId}', [Admin::class, 'deleteSuggest'])->name('admin.stocks.suggest.delete');
    Route::post('/admin/stocks/suggest/batch-delete', [Admin::class, 'deleteSuggestBatch'])->name('admin.stocks.suggest.batch-delete');

    // Quản lý user
    Route::get('/admin/users', [Admin::class, 'userManagement'])->name('admin.users');
    Route::match(['get', 'put'], '/admin/users/update/{id}', [Admin::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/users/delete/{id}', [Admin::class, 'deleteUser'])->name('admin.users.delete');

    // Thông tin cá nhân admin
    Route::get('/admin/infoProfile', [Admin::class, 'infoProfile'])->name('admin.infoProfile');
    Route::match(['get', 'put'], '/admin/updateInfoProfile', [Admin::class, 'updateInfoProfile'])->name('admin.updateInfoProfile');
    Route::match(['get', 'put'], '/admin/changePassword', [Admin::class, 'changePassword'])->name('admin.changePassword');

    Route::get('/admin/logsVPS', [Sync::class, 'getLogsVPS'])->name('admin.logsVPS');
    Route::get('/admin/logsVPS/data', [Sync::class, 'getLogsVPSData'])->name('admin.logsVPS.data');
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
    Route::post('/user/deleteFollowAll', [User::class, 'deleteAllFollow'])->name('user.deleteFollowAll');
    Route::post('/user/deleteFollowBatch', [User::class, 'deleteFollowBatch'])->name('user.deleteFollowBatch');

    // Giao dịch
    Route::match(['get', 'post'], '/user/buy', [User::class, 'buy'])->name('buy');
    Route::match(['get', 'post'], '/user/sell', [User::class, 'sell'])->name('sell');
    Route::match(['get', 'post'], '/user/insertFollow', [User::class, 'insertFollow'])->name('insertFollow');
    Route::post('/user/addFollowBatch', [User::class, 'addFollowBatch'])->name('user.addFollowBatch');
    Route::get('/user/checkStockCode/{code}', [User::class, 'checkStockCode'])->name('user.checkStockCode');
    Route::get('/user/validate-stock/{code}', [User::class, 'validateStockCode'])->name('user.validateStock');
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
