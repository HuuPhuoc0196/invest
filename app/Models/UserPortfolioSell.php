<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserPortfolioSell extends Model
{
    protected $table = 'user_portfolios_sell';
    protected $fillable = ['id', 'user_id', 'stock_id', 'sell_price', 'sell_date', 'quantity'];

    public static function getPortfolioSellWithStockInfo($userId)
    {
        return DB::table('user_portfolios_sell as up')
            ->join('stocks as s', 'up.stock_id', '=', 's.id')
            ->where('up.user_id', $userId)
            ->select(
                's.code',
                'up.sell_price',
                'up.quantity',
                's.current_price',
                's.risk_level',
                'up.sell_date'
            )
            ->get();
    }

    public static function deleteUserInfo(int $stock_id, int $userId): bool
    {
        return self::where('stock_id', $stock_id)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public static function getAllPortfolioSellByUserId($userId)
    {
        return self::where('user_id', $userId)
            ->select(
                'stock_id',
                'sell_price',
                'quantity'
            )
            ->get();
    }
}
