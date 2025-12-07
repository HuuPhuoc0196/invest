<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserCashFollow extends Model
{
    protected $table = 'cash_follow';
    protected $fillable = ['id', 'user_id', 'cash'];

    public static function getCashFollow($userId)
    {
        return self::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->first();
    }
}
