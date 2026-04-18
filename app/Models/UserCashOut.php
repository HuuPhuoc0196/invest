<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class UserCashOut extends Model
{
    protected $table = 'cash_out';
    protected $fillable = ['user_id', 'cash_out', 'cash_date'];

    public static function getCashOut($userId)
    {
        return CacheService::remember("user_cashout_{$userId}", CacheService::TTL_ONE_DAY, function () use ($userId) {
            return self::where('user_id', $userId)->sum('cash_out');
        });
    }
}
