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
}
