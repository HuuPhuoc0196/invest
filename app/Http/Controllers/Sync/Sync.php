<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\Stock;
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

    public function getNewPrice()
    {
        try {
            set_time_limit(0);
            $result = $this->syncService->syncNewPrice();
            return response()->json($result);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }

    public function getNewRisk()
    {
        try {
            set_time_limit(0);
            $result = $this->syncService->syncNewRisk();
            return response()->json($result);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }

    public function suggestInvestment()
    {
        try {
            $result = $this->syncService->suggestInvestment();
            return response()->json($result);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }

    public function updateRiskForCode(UpdateRiskForCodeRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                set_time_limit(0);
                $result = $this->syncService->syncRiskForCode($request->validated()['code']);
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminUpdateRiskForCode');
    }

    public function deleteLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            file_put_contents($logPath, '');
            return response()->json(['status' => 'success', 'message' => 'Logs đã được xóa thành công!']);
        }
        return response()->json(['status' => 'error', 'message' => 'Không tìm thấy file log!'], 404);
    }

    public function sendEmailRisk(Request $request)
    {
        $code      = $request->query('code');
        $risk_level = $request->query('risk_level');
        $date      = $request->query('date');
        $newRisk   = $request->query('newRisk');
        $result    = EmailService::sendRiskChangeNotification($code, $risk_level, $newRisk, $date);
        $message   = "Hệ thống ghi nhận cổ phiếu {$code} có thay đổi mức độ rủi ro. Chuyển từ {$risk_level} Thành {$newRisk} Ngày thực hiện {$date}";
        Log::info($message);
        Log::info("Send mail: " . $result);
        return response()->json(['status' => 'success', 'message' => 'Send mail thành công.']);
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

    public function sendEmailStocks(Request $request)
    {
        $code    = $request->query('code');
        $result  = EmailService::sendSuggestStocksHave1tr($code);
        $message = "Hệ thống ghi nhận cổ phiếu {$code} đã có khối lượng giao dịch trên 1.000.000 và chưa được thêm vào hệ thống.";
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

        return response()->json(['status' => 'success', 'message' => $message, 'sync' => $payload]);
    }

    public function getLogsVPS()
    {
        $baseUrl = rtrim((string) config('services.sync.base_url'), '/');
        if ($baseUrl === '') {
            return response('<pre>Chưa cấu hình SYNC_SERVICE_URL.</pre>', 503);
        }

        try {
            $response = Http::timeout(120)->get($baseUrl . '/get-logs');
        } catch (\Exception $e) {
            Log::error('Request error getLogsVPS: ' . $e->getMessage());

            return response('<pre>' . e('Không lấy được log VPS: ' . (config('app.debug') ? $e->getMessage() : 'lỗi kết nối')) . '</pre>', 502);
        }

        if (! $response->successful()) {
            return response('<pre>' . e('Dịch vụ log trả HTTP ' . $response->status()) . '</pre>', 502);
        }

        return response('<pre>' . e($response->body()) . '</pre>');
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
