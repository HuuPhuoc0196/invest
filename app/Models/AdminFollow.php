<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class AdminFollow extends Model
{
    protected $table = 'admin_follow';
    protected $fillable = ['user_id', 'stock_id'];

    /**
     * Quan hệ với Stock
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Quan hệ với User (Admin)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lấy danh sách stock_id đã được theo dõi (tất cả admin)
     */
    public static function getFollowedStockIds()
    {
        return CacheService::remember('admin_follow_stock_ids', CacheService::TTL_ONE_DAY, function () {
            return self::distinct()->pluck('stock_id')->toArray();
        });
    }

    /**
     * Xóa cache khi có thay đổi
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function () {
            CacheService::clearTableCache('admin_follow');
        });

        static::deleted(function () {
            CacheService::clearTableCache('admin_follow');
        });
    }
}
