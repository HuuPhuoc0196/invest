<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class UserCashIn extends Model
{
    protected $table = 'cash_in';
    protected $fillable = ['user_id', 'cash_in', 'cash_date'];

    public static function getCashIn($userId)
    {
        return CacheService::remember("user_cashin_{$userId}", CacheService::TTL_ONE_DAY, function () use ($userId) {
            return self::where('user_id', $userId)->sum('cash_in');
        });
    }
}
