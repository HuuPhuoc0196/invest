<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Models\StatusSync;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Database\QueryException;
use App\Http\Requests\InsertStockBasicRequest;
use App\Http\Requests\StockInsertRequest;
use App\Http\Requests\StockUpdateRequest;
use App\Http\Requests\ImportStocksCsvRequest;
use App\Http\Requests\UpdateInfoProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User as UserModel;
use App\Services\AuthService;
use App\Models\AdminFollow;
use App\Models\AdminSuggest;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;

class Admin extends Controller
{
    public function __construct(
        private StockService $stockService,
        private AuthService  $authService,
    ) {}

    public function show()
    {
        $stocks     = Stock::getAllStocks();
        $statusSync = StatusSync::getStatusSync();
        return view('Admin.AdminView', compact('stocks', 'statusSync'));
    }

    public function logs()
    {
        return view('Admin.AdminLogs');
    }

    public function insert(InsertStockBasicRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->stockService->insertStockBasic($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminInsert');
    }

    public function update(StockUpdateRequest $request, $code)
    {
        if ($request->isMethod('PUT')) {
            try {
                $result = $this->stockService->updateStock($code, $request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return redirect()->route('admin.stocks')->with('error', 'Mã cổ phiếu không tồn tại.');
        }
        return view('Admin.AdminUpdate', compact('stock'));
    }

    public function delete(Request $request, string $code)
    {
        $code = strtoupper($code);
        $stock = Stock::getByCode($code);
        if (!$stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Không tìm thấy mã cổ phiếu {$code}.",
                ], 404);
            }
            return redirect('/admin/stocks')->with('error', "Không tìm thấy mã cổ phiếu {$code}.");
        }

        $deps = Stock::getDeleteDependencyCounts($code);
        $totalRefs = array_sum($deps);
        if ($totalRefs > 0) {
            $message = "Không thể xoá mã {$code} vì đang có dữ liệu liên quan. "
                . "Danh mục mua: {$deps['user_portfolios']}, "
                . "Lịch sử bán: {$deps['user_portfolios_sell']}, "
                . "Danh sách theo dõi: {$deps['user_follows']}.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            return redirect('/admin/stocks')->with('error', $message);
        }

        try {
            $deleted = Stock::deleteByCode($code);
            if ($deleted) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Đã xoá mã cổ phiếu {$code}.",
                    ]);
                }
                return redirect('/admin/stocks')->with('success', "Đã xoá mã cổ phiếu {$code}.");
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xoá mã cổ phiếu {$code}.",
                ], 500);
            }
            return redirect('/admin/stocks')->with('error', "Không thể xoá mã cổ phiếu {$code}.");
        } catch (QueryException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xoá mã {$code}: dữ liệu đang được sử dụng.",
                ], 422);
            }
            return redirect('/admin/stocks')->with('error', "Không thể xoá mã {$code}: dữ liệu đang được sử dụng.");
        }
    }

    public function infoProfile()
    {
        $userId = auth()->id();
        $user   = UserModel::getUserById($userId);
        if (!$user) {
            abort(404);
        }
        return view('Admin.AdminInfoProfile', compact('user'));
    }

    public function updateInfoProfile(UpdateInfoProfileRequest $request)
    {
        if ($request->isMethod('PUT')) {
            try {
                $result     = $this->authService->updateUserName(auth()->id(), $request->validated()['name']);
                $httpStatus = ($result['code'] ?? 200);
                unset($result['code']);
                return response()->json($result, $httpStatus);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        $user = UserModel::getUserById(auth()->id());
        if (!$user) {
            return redirect()->route('login')->with('error', 'Tài khoản không tồn tại.');
        }
        return view('Admin.AdminUpdateInfoProfile', compact('user'));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        if ($request->isMethod('PUT')) {
            try {
                $validated  = $request->validated();
                $result     = $this->authService->changePassword(auth()->id(), $validated['password'], $validated['newPassword']);
                $httpStatus = ($result['code'] ?? 200);
                unset($result['code']);
                return response()->json($result, $httpStatus);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminChangePassword');
    }

    public function stockManagement()
    {
        $stocks = Stock::getAllStocks();
        
        // Lấy danh sách stock_id đã được theo dõi (DISTINCT, tất cả admin) - sử dụng cache
        $adminFollowedStockIds = AdminFollow::getFollowedStockIds();
        
        return view('Admin.AdminStockManagement', compact('stocks', 'adminFollowedStockIds'));
    }

    public function exportStocksCsv()
    {
        $stocks   = Stock::getAllStocks();
        $date     = now()->format('Y-m-d');
        $fileName = "csv_{$date}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $columns = [
            'code', 'prive_avg', 'percent_buy', 'percent_sell',
            'recommended_buy_price', 'recommended_sell_price', 'ratting_stocks', 'risk_level',
            'current_price', 'percent_stock', 'stocks_vn', 'volume', 'volume_avg',
            'recommended_date', 'event_date',
        ];

        $callback = function () use ($stocks, $columns) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);
            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->code,
                    $stock->price_avg,
                    $stock->percent_buy,
                    $stock->percent_sell,
                    $stock->recommended_buy_price,
                    $stock->recommended_sell_price,
                    $stock->rating_stocks,
                    $stock->risk_level,
                    $stock->current_price,
                    $stock->percent_stock,
                    $stock->stocks_vn,
                    $stock->volume,
                    $stock->volume_avg,
                    $stock->recommended_date,
                    $stock->event_date,
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    public function importStocksCsv(ImportStocksCsvRequest $request)
    {
        $result = $this->stockService->importStocksCsv($request->file('csv_file'));
        return response()->json($result);
    }

    public function stockInsert(StockInsertRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->stockService->insertStock($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminStockInsert');
    }

    public function userManagement()
    {
        $users = UserModel::query()
            ->select(['id', 'email', 'name', 'role', 'active', 'email_verified_at', 'created_at'])
            ->orderByDesc('id')
            ->get();

        return view('Admin.AdminUserManagement', compact('users'));
    }

    public function updateUser(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return redirect()->route('admin.users')->with('error', 'Không tìm thấy user.');
        }

        if ($request->isMethod('put')) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'min:2', 'max:100'],
                'role' => ['required', 'in:0,1'],
                'active' => ['required', 'in:0,1'],
                'email_verified' => ['required', 'in:0,1'],
            ], [
                'name.required' => 'Tên không được để trống.',
                'name.min' => 'Tên phải có ít nhất 2 ký tự.',
                'role.in' => 'Vai trò không hợp lệ.',
                'active.in' => 'Trạng thái không hợp lệ.',
                'email_verified.in' => 'Trạng thái xác thực email không hợp lệ.',
            ]);

            $user->name = trim((string) $validated['name']);
            $user->role = (int) $validated['role'];
            $user->active = (int) $validated['active'];
            $user->email_verified_at = ((int) $validated['email_verified'] === 1) ? now() : null;
            $user->save();

            CacheService::forget("user_{$user->id}");

            return redirect()->route('admin.users.update', ['id' => $user->id])
                ->with('success', 'Đã cập nhật thông tin user thành công.');
        }

        return view('Admin.AdminUserUpdate', compact('user'));
    }

    public function deleteUser(int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return redirect()->route('admin.users')->with('error', 'Không tìm thấy user để xoá.');
        }

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users')->with('error', 'Không thể tự xoá tài khoản đang đăng nhập.');
        }

        try {
            $userId = $user->id;
            $user->delete();
            CacheService::clearUserCache($userId);
            return redirect()->route('admin.users')->with('success', 'Đã xoá user thành công.');
        } catch (QueryException $e) {
            return redirect()->route('admin.users')->with('error', 'Không thể xoá user vì đang có dữ liệu liên quan.');
        }
    }

    /**
     * Màn hình danh sách Admin theo dõi
     */
    public function adminFollow()
    {
        // Lấy tất cả stocks được theo dõi bởi bất kỳ admin nào (DISTINCT stock_id) - sử dụng cache
        $stocks = CacheService::remember('admin_follow_stocks', CacheService::TTL_ONE_DAY, function () {
            return DB::table('admin_follow')
                ->join('stocks', 'admin_follow.stock_id', '=', 'stocks.id')
                ->select('stocks.*')
                ->distinct()
                ->get();
        });

        // Lấy danh sách stock_id đã được gợi ý (DISTINCT) - sử dụng cache
        $adminSuggestedStockIds = AdminSuggest::getSuggestedStockIds();

        return view('Admin.AdminStockFollow', compact('stocks', 'adminSuggestedStockIds'));
    }

    /**
     * Thêm nhiều mã vào danh sách theo dõi
     */
    public function addFollowBatch(Request $request)
    {
        $validated = $request->validate([
            'stock_ids'   => ['required', 'array', 'max:500'],
            'stock_ids.*' => ['required', 'integer', 'min:1'],
        ]);
        $stockIds = $validated['stock_ids'];
        $userId = auth()->id();

        $added = 0;
        foreach ($stockIds as $stockId) {
            $created = AdminFollow::firstOrCreate([
                'user_id' => $userId,
                'stock_id' => $stockId,
            ]);
            if ($created->wasRecentlyCreated) {
                $added++;
            }
        }

        CacheService::clearTableCache('admin_follow'); // clears admin_follow_stock_ids + admin_follow_stocks

        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$added} mã vào danh sách theo dõi",
            'added' => $added
        ]);
    }

    /**
     * Xóa 1 mã khỏi danh sách theo dõi
     */
    public function deleteFollow($stockId)
    {
        $deleted = AdminFollow::where('stock_id', $stockId)
            ->where('user_id', auth()->id())
            ->delete();

        // Khi xoá theo dõi 1 mã thì xoá luôn gợi ý tương ứng của mã đó.
        $deletedSuggest = AdminSuggest::where('stock_id', $stockId)->delete();

        CacheService::clearTableCache('admin_follow'); // clears admin_follow_stock_ids + admin_follow_stocks
        CacheService::clearTableCache('admin_suggest');

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'Đã xóa khỏi danh sách theo dõi' : 'Không tìm thấy',
            'deleted' => $deleted,
            'deleted_suggest' => $deletedSuggest,
        ]);
    }

    /**
     * Xóa nhiều mã khỏi danh sách theo dõi (theo filter hiện tại)
     * Đồng thời xóa gợi ý tương ứng cho các mã này.
     */
    public function deleteFollowBatch(Request $request)
    {
        $validated = $request->validate([
            'stock_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'stock_ids.*' => ['required', 'integer', 'min:1'],
        ]);
        $stockIds = $validated['stock_ids'];

        $deletedFollow = AdminFollow::whereIn('stock_id', $stockIds)->delete();
        $deletedSuggest = AdminSuggest::whereIn('stock_id', $stockIds)->delete();

        CacheService::clearTableCache('admin_follow');
        CacheService::clearTableCache('admin_suggest');

        return response()->json([
            'success' => true,
            'message' => "Đã xoá {$deletedFollow} bản ghi theo dõi và {$deletedSuggest} bản ghi gợi ý tương ứng",
            'deleted_follow' => $deletedFollow,
            'deleted_suggest' => $deletedSuggest,
        ]);
    }

    /**
     * Màn hình danh sách Admin gợi ý
     */
    public function adminSuggest()
    {
        // Lấy tất cả stocks được gợi ý bởi bất kỳ admin nào (DISTINCT stock_id) - sử dụng cache
        $stocks = CacheService::remember('admin_suggest_stocks', CacheService::TTL_ONE_DAY, function () {
            return DB::table('admin_suggest')
                ->join('stocks', 'admin_suggest.stock_id', '=', 'stocks.id')
                ->select('stocks.*')
                ->distinct()
                ->get();
        });

        return view('Admin.AdminStockSuggest', compact('stocks'));
    }

    /**
     * Thêm nhiều mã vào danh sách gợi ý
     */
    public function addSuggestBatch(Request $request)
    {
        $validated = $request->validate([
            'stock_ids'   => ['required', 'array', 'max:500'],
            'stock_ids.*' => ['required', 'integer', 'min:1'],
        ]);
        $stockIds = $validated['stock_ids'];
        $userId = auth()->id();

        $added = 0;
        foreach ($stockIds as $stockId) {
            $created = AdminSuggest::firstOrCreate([
                'user_id' => $userId,
                'stock_id' => $stockId,
            ]);
            if ($created->wasRecentlyCreated) {
                $added++;
            }
        }

        CacheService::clearTableCache('admin_suggest');

        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$added} mã vào danh sách gợi ý",
            'added' => $added
        ]);
    }

    /**
     * Xóa 1 mã khỏi danh sách gợi ý
     */
    public function deleteSuggest($stockId)
    {
        $deleted = AdminSuggest::where('stock_id', $stockId)
            ->where('user_id', auth()->id())
            ->delete();

        CacheService::clearTableCache('admin_suggest');

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'Đã xóa khỏi danh sách gợi ý' : 'Không tìm thấy',
            'deleted' => $deleted
        ]);
    }

    /**
     * Xóa nhiều mã khỏi danh sách gợi ý
     */
    public function deleteSuggestBatch(Request $request)
    {
        $validated = $request->validate([
            'stock_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'stock_ids.*' => ['required', 'integer', 'min:1'],
        ]);
        $stockIds = $validated['stock_ids'];

        $deleted = AdminSuggest::whereIn('stock_id', $stockIds)
            ->where('user_id', auth()->id())
            ->delete();

        CacheService::clearTableCache('admin_suggest');

        return response()->json([
            'success' => true,
            'message' => "Đã xóa {$deleted} mã khỏi danh sách gợi ý",
            'deleted' => $deleted
        ]);
    }
}
