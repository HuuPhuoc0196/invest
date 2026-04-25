<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\CacheService;
use App\Services\EmailService;
use App\Services\SyncService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateRiskForCodeRequest;

class Sync extends Controller
{
    public function __construct(private SyncService $syncService) {}

    public function updateRiskForCode(UpdateRiskForCodeRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                set_time_limit(0);
                $code = $request->validated()['code'];
                $result = $this->syncService->syncRiskForCode($code);
                
                // Clear cache sau khi update risk cho code
                CacheService::clearStockCache($code);
                CacheService::forget('stocks_all');
                
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminUpdateRiskForCode');
    }

    public function deleteLogs()
    {
        $logPath   = storage_path('logs');
        $cutoff    = time() - (30 * 86400);
        $files     = glob($logPath . '/laravel-*.log') ?: [];
        $deleted   = 0;
        $failed    = 0;

        foreach ($files as $file) {
            if (!is_file($file) || filemtime($file) >= $cutoff) {
                continue;
            }
            File::delete($file) ? $deleted++ : $failed++;
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Đã xóa {$deleted} file log cũ hơn 30 ngày." . ($failed ? " Thất bại: {$failed}." : ''),
            'deleted' => $deleted,
            'failed'  => $failed,
        ]);
    }

    public function sendEmailVnindex(Request $request)
    {
        $index_current = $request->query('index_current');
        $index_suggest = $request->query('index_suggest');
        $result        = EmailService::sendVnindexChangeNotification($index_current, $index_suggest);
        $message       = "Hệ thống ghi nhận Vnindex có mức điều chỉnh về vùng mua tốt. Vnindex hiện tại: {$index_current} Vindex vùng mua tốt: {$index_suggest}";
        Log::info($message);
        Log::info("Send mail: " . $result);
        return response()->json(['status' => 'success', 'message' => 'Send mail thành công.']);
    }

    public function sendEmailError(Request $request)
    {
        $file     = $request->query('file');
        $function = $request->query('function');
        $message  = $request->query('message');
        $result   = EmailService::sendErrorNotification($file, $function, $message);
        $logMessage = "Hệ thống ghi nhận lỗi trong file {$file} tại function {$function}. Thông báo lỗi: {$message}";
        Log::info($logMessage);
        Log::info("Send mail: " . $result);
        return response()->json(['status' => 'success', 'message' => 'Send mail thành công.']);
    }

    public function sendEmailStocksFollow(Request $request)
    {
        $code       = $request->query('code');
        $stock      = Stock::getByCode($code);
        if (!$stock) {
            return response()->json(['status' => 'error', 'message' => 'Mã ' . $code . ' không tồn tại.']);
        }
        $userFollowExist = \App\Models\UserFollow::getUserFollowFirst($stock->id, 1);
        if ($userFollowExist) {
            return response()->json(['status' => 'error', 'message' => 'Mã cổ phiếu ' . $code . ' đã được theo dõi.']);
        }
        $userFollow                    = new \App\Models\UserFollow();
        $userFollow->user_id           = 1;
        $userFollow->stock_id          = $stock->id;
        $userFollow->follow_price_buy  = $stock->recommended_buy_price;
        $userFollow->follow_price_sell = $stock->recommended_sell_price ?? null;
        $userFollow->notice_flag       = 1;
        $userFollow->save();

        CacheService::forgetMany(["user_follow_1", "user_follow_notice_1"]);

        $result  = EmailService::sendSuggestStocksHave10tr($code);
        $message = "Hệ thống ghi nhận cổ phiếu {$code} đã có khối lượng giao dịch trên 10.000.000 và thêm vào table user_follow.";
        Log::info($message);
        Log::info("Send mail: " . $result);
        return response()->json(['status' => 'success', 'message' => 'Send mail thành công.']);
    }

    public function followStocksEveryDay(Request $request)
    {
        try {
            $result = $this->syncService->followStocksEveryDay();
            return response()->json($result);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }

    public function runSyncUpdateStock(Request $request, string $code)
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        if ($code === '' || strlen($code) > 20) {
            return response()->json(['status' => 'error', 'message' => 'Mã cổ phiếu không hợp lệ.'], 422);
        }
        if (!Stock::getByCode($code)) {
            return response()->json(['status' => 'error', 'message' => 'Mã cổ phiếu không tồn tại trong hệ thống.'], 404);
        }

        $baseUrl = rtrim((string) config('services.sync.base_url'), '/');
        $path    = (string) config('services.sync.run_update_stock_path', '/run-sync-update-stocks');
        $path    = '/' . ltrim($path, '/');

        if ($baseUrl === '') {
            return response()->json(['status' => 'error', 'message' => 'Chưa cấu hình SYNC_SERVICE_URL.'], 500);
        }

        $url = $baseUrl . $path . '/' . rawurlencode($code);

        try {
            $response = Http::timeout(120)->acceptJson()->get($url);
        } catch (ConnectionException $e) {
            Log::error('runSyncUpdateStock connection: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Không kết nối được tới service sync. Kiểm tra SYNC_SERVICE_URL và firewall VPS.'], 502);
        } catch (\Throwable $e) {
            Log::error('runSyncUpdateStock: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Lỗi khi gọi service sync.'], 502);
        }

        $payload = $response->json();
        if (!$response->successful()) {
            $msg = is_array($payload) && !empty($payload['message'])
                ? (string) $payload['message']
                : ('Service sync trả lỗi HTTP ' . $response->status());
            return response()->json([
                'status'      => 'error',
                'message'     => $msg,
                'sync_status' => $response->status(),
            ], $response->status() >= 500 ? 502 : $response->status());
        }

        $message = is_array($payload) && isset($payload['message'])
            ? (string) $payload['message']
            : 'Đã gửi yêu cầu cập nhật cho mã ' . $code . '.';

        // Clear cache sau khi sync thành công
        if ($response->successful()) {
            CacheService::clearStockCache($code);
            CacheService::forget('stocks_all');
        }

        return response()->json(['status' => 'success', 'message' => $message, 'sync' => $payload]);
    }

    public function getLogsVPS()
    {
        return view('Admin.AdminLogsVPS');
    }

    public function getLogsVPSData(Request $request)
    {
        $baseUrl = rtrim((string) config('services.sync.base_url'), '/');
        if ($baseUrl === '') {
            return response()->json(['error' => 'Chưa cấu hình SYNC_SERVICE_URL.'], 503);
        }

        $params = $request->only(['date', 'level', 'search', 'page', 'per_page']);

        try {
            $response = Http::timeout(30)->get($baseUrl . '/get-logs', $params);
        } catch (\Exception $e) {
            Log::error('Request error getLogsVPSData: ' . $e->getMessage());
            return response()->json([
                'error' => config('app.debug') ? $e->getMessage() : 'Không lấy được log VPS: lỗi kết nối',
            ], 502);
        }

        if (! $response->successful()) {
            return response()->json([
                'error' => 'Dịch vụ log trả HTTP ' . $response->status(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function uploadFile(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'file' => ['required', 'file', 'max:5120', 'mimes:txt,csv'],
            ]);
            try {
                $result = $this->syncService->addStocksFollowFromFile($validated['file']->get());
                return response()->json($result);
            } catch (\Exception $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminUploadFile');
    }
}
