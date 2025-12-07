<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserCashOut extends Model
{
    protected $table = 'cash_out';
    protected $fillable = ['id', 'user_id', 'cash_out', 'cash_date'];

    public static function getCashOut($userId)
    {
        return self::where('user_id', $userId)->sum('cash_out');
    }
}
