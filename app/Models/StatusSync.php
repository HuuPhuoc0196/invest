<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusSync extends Model
{
    protected $fillable = ['id', 'status_sync_price', 'status_sync_risk'];
    protected $table = 'status_sync';

    public static function getStatusSync()
    {
        return self::first();
    }
}
