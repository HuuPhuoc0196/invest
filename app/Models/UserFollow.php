<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    protected $fillable = ['user_id', 'stock_id', 'follow_price'];

    public static function getUserFollow($userId)
    {
        return DB::table('user_follows')
            ->join('stocks', 'user_follows.stock_id', '=', 'stocks.id')
            ->where('user_follows.user_id', $userId)
            ->select('stocks.code', 'user_follows.follow_price')
            ->get()
            ->map(function ($row) {
                return [
                    'code' => $row->code,
                    'follow_price' => $row->follow_price
                ];
            });
    }

    public static function deleteByCodeAndUser(string $code, int $userId): bool
    {
        $stock = Stock::where('code', $code)->first();

        if (!$stock) {
            return false;
        }

        return self::where('stock_id', $stock->id)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public static function getUserFollowFirst(int $code_id, int $userId)
    {
        // Trả về bản ghi user_follows tương ứng
        return self::where('stock_id', $code_id)
            ->where('user_id', $userId)
            ->first();
    }

    public static function getCodeFromUserFollow(int $code_id, int $userId)
    {
        return DB::table('user_follows')
            ->join('stocks', 'user_follows.stock_id', '=', 'stocks.id')
            ->where('stock_id', $code_id)
            ->where('user_id', $userId)
            ->select('stocks.code', 'user_follows.follow_price')
            ->first();
    }
}
