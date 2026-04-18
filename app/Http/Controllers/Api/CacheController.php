<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Cache Management API Controller
 * Các API để server khác có thể clear cache khi update database
 * Bảo vệ bởi middleware 'cron.secret' (yêu cầu X-Cron-Secret header)
 */
class CacheController extends Controller
{
    /**
     * Clear toàn bộ cache của application
     * 
     * @return \Illuminate\Http\JsonResponse
     * 
     * POST /api/cache/clear-all
     * Headers: X-Cron-Secret: {CRON_API_SECRET}
     */
    public function clearAll()
    {
        try {
            $result = CacheService::clearAll();
            
            Log::info('Cache cleared via API', [
                'action' => 'clear_all',
                'ip' => request()->ip(),
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Đã xóa toàn bộ cache.',
                'cleared' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cache clear all error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cache theo table
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * POST /api/cache/clear-table
     * Headers: X-Cron-Secret: {CRON_API_SECRET}
     * Body: { "table": "stocks" }
     * 
     * Supported tables:
     * - stocks
     * - user_portfolios
     * - user_portfolios_sell
     * - user_follows
     * - cash_follow
     * - users
     * - status_sync
     */
    public function clearTable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => [
                'required',
                'string',
                'in:stocks,user_portfolios,user_portfolios_sell,user_follows,cash_follow,users,status_sync',
            ],
        ], [
            'table.required' => 'Tên table không được để trống.',
            'table.in' => 'Table không hợp lệ. Chỉ hỗ trợ: stocks, user_portfolios, user_portfolios_sell, user_follows, cash_follow, users, status_sync.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = $request->input('table');

        try {
            $count = CacheService::clearTableCache($table);
            
            Log::info('Cache cleared via API', [
                'action' => 'clear_table',
                'table' => $table,
                'count' => $count,
                'ip' => request()->ip(),
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => "Đã xóa cache của table '{$table}'.",
                'table' => $table,
                'keys_cleared' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cache clear table error', ['table' => $table, 'error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cache theo user ID
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * POST /api/cache/clear-user
     * Headers: X-Cron-Secret: {CRON_API_SECRET}
     * Body: { "user_id": 1 }
     */
    public function clearUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'min:1'],
        ], [
            'user_id.required' => 'User ID không được để trống.',
            'user_id.integer' => 'User ID phải là số nguyên.',
            'user_id.min' => 'User ID phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = (int) $request->input('user_id');

        try {
            $count = CacheService::clearUserCache($userId);
            
            Log::info('Cache cleared via API', [
                'action' => 'clear_user',
                'user_id' => $userId,
                'count' => $count,
                'ip' => request()->ip(),
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => "Đã xóa cache của user ID {$userId}.",
                'user_id' => $userId,
                'keys_cleared' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cache clear user error', ['user_id' => $userId, 'error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cache theo stock code
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * POST /api/cache/clear-stock
     * Headers: X-Cron-Secret: {CRON_API_SECRET}
     * Body: { "code": "VNM" }
     */
    public function clearStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'Mã cổ phiếu không được để trống.',
            'code.string' => 'Mã cổ phiếu phải là chuỗi.',
            'code.max' => 'Mã cổ phiếu không được vượt quá 20 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = strtoupper(trim($request->input('code')));

        try {
            $count = CacheService::clearStockCache($code);
            
            // Cũng clear stocks_all để đảm bảo consistency
            CacheService::forget('stocks_all');
            $count++;
            
            Log::info('Cache cleared via API', [
                'action' => 'clear_stock',
                'code' => $code,
                'count' => $count,
                'ip' => request()->ip(),
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => "Đã xóa cache của mã {$code}.",
                'code' => $code,
                'keys_cleared' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cache clear stock error', ['code' => $code, 'error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear nhiều cache keys cụ thể
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * POST /api/cache/clear-keys
     * Headers: X-Cron-Secret: {CRON_API_SECRET}
     * Body: { "keys": ["stocks_all", "stock_code_VNM", "user_portfolio_profile_1"] }
     */
    public function clearKeys(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string', 'max:255'],
        ], [
            'keys.required' => 'Danh sách keys không được để trống.',
            'keys.array' => 'Keys phải là một mảng.',
            'keys.min' => 'Phải có ít nhất 1 key.',
            'keys.*.string' => 'Mỗi key phải là chuỗi.',
            'keys.*.max' => 'Mỗi key không được vượt quá 255 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $keys = $request->input('keys');

        try {
            $count = CacheService::forgetMany($keys);
            
            Log::info('Cache cleared via API', [
                'action' => 'clear_keys',
                'keys' => $keys,
                'count' => $count,
                'ip' => request()->ip(),
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => "Đã xóa {$count}/{" . count($keys) . "} cache keys.",
                'keys_requested' => $keys,
                'keys_cleared' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cache clear keys error', ['keys' => $keys, 'error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin cache (dùng để debug, monitor)
     * 
     * @return \Illuminate\Http\JsonResponse
     * 
     * GET /api/cache/info
     * Headers: X-Cron-Secret: {CRON_API_SECRET}
     */
    public function getCacheInfo()
    {
        try {
            $info = CacheService::getCacheInfo();
            
            return response()->json([
                'status' => 'success',
                'data' => $info,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cache get info error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi lấy thông tin cache: ' . $e->getMessage(),
            ], 500);
        }
    }
}
