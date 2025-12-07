<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserCashIn extends Model
{
    protected $table = 'cash_in';
    protected $fillable = ['id', 'user_id', 'cash_in', 'cash_date'];

    public static function getCashIn($userId)
    {
        return self::where('user_id', $userId)->sum('cash_in');
    }
}
