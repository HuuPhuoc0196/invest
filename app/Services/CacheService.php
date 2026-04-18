<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Centralized cache management service với lock mechanism để prevent cache stampede.
 * Sử dụng file cache (storage/framework/cache/data/) cho stability trên shared hosting.
 */
class CacheService
{
    /**
     * Cache TTL: 1 ngày (86400 giây)
     */
    public const TTL_ONE_DAY = 86400;

    /**
     * Lock timeout: 10 giây
     */
    private const LOCK_TIMEOUT = 10;

    /**
     * Cache với lock mechanism để prevent stampede.
     * Chỉ 1 request được phép query DB khi cache miss, các request khác chờ và lấy cache.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live trong giây (default: 1 ngày)
     * @param callable $callback Function để lấy data từ DB khi cache miss
     * @return mixed Cached data hoặc fresh data từ callback
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        // Check cache trước
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Cache miss - acquire lock
        $lock = Cache::lock("lock_{$key}", self::LOCK_TIMEOUT);

        try {
            // Block và chờ lock (tối đa LOCK_TIMEOUT giây)
            if ($lock->block(self::LOCK_TIMEOUT)) {
                // Double-check cache sau khi có lock (có thể request khác đã tạo)
                $cached = Cache::get($key);
                if ($cached !== null) {
                    return $cached;
                }

                // Query DB và cache
                $data = $callback();
                Cache::put($key, $data, $ttl);
                
                Log::info("Cache stored", ['key' => $key, 'ttl' => $ttl]);
                
                return $data;
            }

            // Không lấy được lock (timeout) - query DB trực tiếp
            Log::warning("Cache lock timeout", ['key' => $key]);
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * Xóa một cache key.
     *
     * @param string $key Cache key cần xóa
     * @return bool True nếu xóa thành công
     */
    public static function forget(string $key): bool
    {
        try {
            $result = Cache::forget($key);
            Log::info("Cache cleared", ['key' => $key]);
            return $result;
        } catch (\Throwable $e) {
            Log::error("Cache forget error", ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Xóa nhiều cache keys.
     *
     * @param array $keys Mảng các cache keys cần xóa
     * @return int Số lượng keys đã xóa thành công
     */
    public static function forgetMany(array $keys): int
    {
        $count = 0;
        foreach ($keys as $key) {
            if (self::forget($key)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Xóa cache theo pattern (wildcard).
     * Ví dụ: clearByPattern('stock_code_*') xóa tất cả stock_code_VNM, stock_code_HPG, ...
     *
     * @param string $pattern Pattern với wildcard * (ví dụ: 'user_portfolio_*')
     * @return int Số lượng keys đã xóa
     */
    public static function forgetByPattern(string $pattern): int
    {
        $count = 0;
        $cachePath = storage_path('framework/cache/data');

        if (!File::isDirectory($cachePath)) {
            return 0;
        }

        try {
            // Convert pattern to regex
            $regex = '/' . str_replace(['*', '_'], ['.*', '_'], preg_quote($pattern, '/')) . '/';
            
            $files = File::allFiles($cachePath);
            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                
                // Laravel file cache format: s:{length}:"{key}";...
                if (preg_match('/s:\d+:"([^"]+)"/', $content, $matches)) {
                    $key = $matches[1];
                    
                    // Check if key matches pattern
                    if (preg_match($regex, $key)) {
                        if (self::forget($key)) {
                            $count++;
                        }
                    }
                }
            }
            
            Log::info("Cache cleared by pattern", ['pattern' => $pattern, 'count' => $count]);
        } catch (\Throwable $e) {
            Log::error("Cache clear by pattern error", ['pattern' => $pattern, 'error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Xóa tất cả cache liên quan đến một user.
     *
     * @param int $userId User ID
     * @return int Số lượng keys đã xóa
     */
    public static function clearUserCache(int $userId): int
    {
        $keys = [
            "user_{$userId}",
            "user_portfolio_profile_{$userId}",
            "user_portfolio_stock_info_{$userId}",
            "user_portfolio_buy_{$userId}",
            "user_portfolio_session_{$userId}",
            "user_portfolio_sell_{$userId}",
            "user_follow_{$userId}",
            "user_follow_notice_{$userId}",
            "user_cash_{$userId}",
            "user_cashin_{$userId}",
            "user_cashout_{$userId}",
        ];

        $count = self::forgetMany($keys);
        Log::info("User cache cleared", ['user_id' => $userId, 'count' => $count]);
        
        return $count;
    }

    /**
     * Xóa tất cả cache liên quan đến một mã cổ phiếu.
     *
     * @param string $code Stock code (ví dụ: VNM, HPG)
     * @return int Số lượng keys đã xóa
     */
    public static function clearStockCache(string $code): int
    {
        $code = strtoupper($code);
        $keys = [
            "stock_code_{$code}",
            "stock_risk_{$code}",
        ];

        $count = self::forgetMany($keys);
        Log::info("Stock cache cleared", ['code' => $code, 'count' => $count]);
        
        return $count;
    }

    /**
     * Xóa tất cả cache liên quan đến một database table.
     *
     * @param string $table Table name
     * @return int Số lượng keys đã xóa
     */
    public static function clearTableCache(string $table): int
    {
        $count = 0;

        switch ($table) {
            case 'stocks':
                // Clear stocks_all và tất cả stock_code_*, stock_risk_*
                self::forget('stocks_all');
                $count += self::forgetByPattern('stock_code_*');
                $count += self::forgetByPattern('stock_risk_*');
                break;

            case 'user_portfolios':
                // Clear tất cả user_portfolio_*
                $count += self::forgetByPattern('user_portfolio_profile_*');
                $count += self::forgetByPattern('user_portfolio_stock_info_*');
                $count += self::forgetByPattern('user_portfolio_buy_*');
                $count += self::forgetByPattern('user_portfolio_session_*');
                break;

            case 'user_portfolios_sell':
                // Clear tất cả user_portfolio_sell_*
                $count += self::forgetByPattern('user_portfolio_sell_*');
                break;

            case 'user_follows':
                // Clear tất cả user_follow_*
                $count += self::forgetByPattern('user_follow_*');
                $count += self::forgetByPattern('user_follow_notice_*');
                break;

            case 'cash_follow':
                // Clear tất cả user_cash_*
                $count += self::forgetByPattern('user_cash_*');
                break;

            case 'users':
                // Clear tất cả user_{id}
                $count += self::forgetByPattern('user_*');
                break;

            case 'status_sync':
                // Clear status_sync
                $count += self::forget('status_sync') ? 1 : 0;
                break;

            case 'admin_follow':
                // Clear admin_follow_stock_ids và admin_follow_stocks (joined view)
                $count += self::forget('admin_follow_stock_ids') ? 1 : 0;
                $count += self::forget('admin_follow_stocks') ? 1 : 0;
                break;

            case 'admin_suggest':
                // Clear admin_suggest_stock_ids và admin_suggest_stocks (joined view)
                $count += self::forget('admin_suggest_stock_ids') ? 1 : 0;
                $count += self::forget('admin_suggest_stocks') ? 1 : 0;
                break;

            default:
                Log::warning("Unknown table for cache clear", ['table' => $table]);
                return 0;
        }

        Log::info("Table cache cleared", ['table' => $table, 'count' => $count]);
        return $count;
    }

    /**
     * Xóa toàn bộ cache của application.
     *
     * @return bool True nếu xóa thành công
     */
    public static function clearAll(): bool
    {
        try {
            Cache::flush();
            Log::info("All cache cleared");
            return true;
        } catch (\Throwable $e) {
            Log::error("Cache flush error", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Lấy thông tin cache (dùng để debug, monitor).
     *
     * @return array Thông tin về cache
     */
    public static function getCacheInfo(): array
    {
        $cachePath = storage_path('framework/cache/data');
        
        if (!File::isDirectory($cachePath)) {
            return [
                'driver' => config('cache.default'),
                'path' => $cachePath,
                'exists' => false,
                'file_count' => 0,
                'total_size' => 0,
            ];
        }

        try {
            $files = File::allFiles($cachePath);
            $totalSize = 0;
            $keys = [];

            foreach ($files as $file) {
                $totalSize += $file->getSize();
                
                // Parse cache key từ file content
                $content = File::get($file->getPathname());
                if (preg_match('/s:\d+:"([^"]+)"/', $content, $matches)) {
                    $keys[] = $matches[1];
                }
            }

            return [
                'driver' => config('cache.default'),
                'path' => $cachePath,
                'exists' => true,
                'file_count' => count($files),
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'sample_keys' => array_slice($keys, 0, 20), // Lấy 20 keys đầu làm sample
            ];
        } catch (\Throwable $e) {
            Log::error("Get cache info error", ['error' => $e->getMessage()]);
            return [
                'driver' => config('cache.default'),
                'path' => $cachePath,
                'error' => $e->getMessage(),
            ];
        }
    }
}
