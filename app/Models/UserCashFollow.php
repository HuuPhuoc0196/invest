<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserCashFollow extends Model
{
    protected $table = 'cash_follow';
    protected $fillable = ['user_id', 'cash'];

    public static function getCashFollow($userId)
    {
        return CacheService::remember("user_cash_{$userId}", CacheService::TTL_ONE_DAY, function () use ($userId) {
            return self::where('user_id', $userId)
                ->orderBy('id', 'asc')
                ->first();
        });
    }
}
