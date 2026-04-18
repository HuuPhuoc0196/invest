<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class AdminSuggest extends Model
{
    protected $table = 'admin_suggest';
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
     * Lấy danh sách stock_id đã được gợi ý (tất cả admin)
     */
    public static function getSuggestedStockIds()
    {
        return CacheService::remember('admin_suggest_stock_ids', CacheService::TTL_ONE_DAY, function () {
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
            CacheService::clearTableCache('admin_suggest');
        });

        static::deleted(function () {
            CacheService::clearTableCache('admin_suggest');
        });
    }
}
