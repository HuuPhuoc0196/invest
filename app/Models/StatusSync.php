<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class StatusSync extends Model
{
    protected $fillable = ['status_sync_price', 'status_sync_risk'];
    protected $table = 'status_sync';

    public static function getStatusSync()
    {
        return CacheService::remember('status_sync', CacheService::TTL_ONE_DAY, function () {
            return self::first();
        });
    }
}
