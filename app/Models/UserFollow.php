<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    protected $fillable = ['user_id', 'stock_id', 'follow_price_buy', 'follow_price_sell', 'notice_flag', 'notice_buy', 'notice_sell', 'auto_sync'];

    public static function getUserFollow($userId)
    {
        return CacheService::remember("user_follow_{$userId}", CacheService::TTL_ONE_DAY, function () use ($userId) {
            return DB::table('user_follows')
                ->join('stocks', 'user_follows.stock_id', '=', 'stocks.id')
                ->where('user_follows.user_id', $userId)
                ->select('stocks.code', 'user_follows.follow_price_buy', 'user_follows.follow_price_sell')
                ->get()
                ->map(function ($row) {
                    return [
                        'code' => $row->code,
                        'follow_price_buy' => $row->follow_price_buy,
                        'follow_price_sell' => $row->follow_price_sell
                    ];
                });
        });
    }

    public static function deleteByCodeAndUser(string $code, int $userId): bool
    {
        $stock = Stock::where('code', $code)->first();

        if (!$stock) {
            return false;
        }

        $deleted = self::where('stock_id', $stock->id)
            ->where('user_id', $userId)
            ->delete() > 0;
        
        // Clear cache sau khi delete
        if ($deleted) {
            CacheService::forgetMany([
                "user_follow_{$userId}",
                "user_follow_notice_{$userId}"
            ]);
        }
        
        return $deleted;
    }

    public static function deleteByCodesAndUser(array $codes, int $userId): int
    {
        if (empty($codes)) {
            return 0;
        }
        $upperCodes = array_map('strtoupper', $codes);
        $stockIds = Stock::whereIn('code', $upperCodes)->pluck('id')->toArray();
        if (empty($stockIds)) {
            return 0;
        }
        $deleted = self::whereIn('stock_id', $stockIds)
            ->where('user_id', $userId)
            ->delete();
        
        // Clear cache sau khi delete
        if ($deleted > 0) {
            CacheService::forgetMany([
                "user_follow_{$userId}",
                "user_follow_notice_{$userId}"
            ]);
        }
        
        return $deleted;
    }

    public static function deleteAllByUserId(int $userId): int
    {
        $deleted = self::where('user_id', $userId)->delete();
        
        // Clear cache sau khi delete
        if ($deleted > 0) {
            CacheService::forgetMany([
                "user_follow_{$userId}",
                "user_follow_notice_{$userId}"
            ]);
        }
        
        return $deleted;
    }

    public static function getUserFollowFirst(int $code_id, int $userId)
    {
        // Trả về bản ghi user_follows tương ứng
        return self::where('stock_id', $code_id)
            ->where('user_id', $userId)
            ->first();
    }

    public static function getCodeFromUserFollow(int $code_id, int $userId)
    {
        return DB::table('user_follows')
            ->join('stocks', 'user_follows.stock_id', '=', 'stocks.id')
            ->where('stock_id', $code_id)
            ->where('user_id', $userId)
            ->select('stocks.code', 'user_follows.follow_price_buy', 'user_follows.follow_price_sell', 'user_follows.auto_sync')
            ->first();
    }

    /**
     * Cập nhật notice_flag cho một record follow cụ thể của user.
     */
    public static function updateNoticeFlag(int $id, int $userId, int $flag): int
    {
        $updated = self::where('id', $id)
            ->where('user_id', $userId)
            ->update(['notice_flag' => $flag]);
        
        // Clear cache sau khi update
        if ($updated > 0) {
            CacheService::forget("user_follow_notice_{$userId}");
        }
        
        return $updated;
    }

    /**
     * Lấy danh sách follow của user cho email settings (thay thế NoticeStockFollow::getByUser)
     */
    public static function getFollowNoticeByUser(int $userId)
    {
        return CacheService::remember("user_follow_notice_{$userId}", CacheService::TTL_ONE_DAY, function () use ($userId) {
            return DB::table('user_follows')
                ->join('stocks', 'user_follows.stock_id', '=', 'stocks.id')
                ->where('user_follows.user_id', $userId)
                ->select(
                    'user_follows.id',
                    'user_follows.stock_id',
                    'stocks.code',
                    'user_follows.follow_price_buy',
                    'user_follows.follow_price_sell',
                    'user_follows.notice_buy',
                    'user_follows.notice_sell'
                )
                ->orderBy('stocks.code', 'asc')
                ->get();
        });
    }

    /**
     * Cập nhật notice_buy và notice_sell cho một record follow cụ thể của user.
     */
    public static function updateNoticeBuySell(int $id, int $userId, int $noticeBuy, int $noticeSell): int
    {
        $updated = self::where('id', $id)
            ->where('user_id', $userId)
            ->update(['notice_buy' => $noticeBuy, 'notice_sell' => $noticeSell]);
        if ($updated > 0) {
            CacheService::forget("user_follow_notice_{$userId}");
        }
        return $updated;
    }

}
