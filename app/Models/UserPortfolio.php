<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserPortfolio extends Model
{
    protected $table = 'user_portfolios';
    protected $fillable = ['id', 'user_id', 'stock_id', 'buy_price', 'buy_date', 'quantity'];

    public static function getProfileUser($userId)
    {
        return self::select(
            'stocks.code',
            'stocks.current_price',
            'user_portfolios.stock_id',
            DB::raw('SUM(user_portfolios.quantity) as total_bought'),
            DB::raw('SUM(user_portfolios.quantity * user_portfolios.buy_price) / SUM(user_portfolios.quantity) as avg_buy_price')
        )
            ->join('stocks', 'user_portfolios.stock_id', '=', 'stocks.id')
            ->where('user_portfolios.user_id', $userId)
            ->groupBy('user_portfolios.stock_id', 'stocks.code', 'stocks.current_price')
            ->get()
            ->map(function ($row) use ($userId) {
                // Tính tổng đã bán cho từng stock
                $soldQty = DB::table('user_portfolios_sell')
                    ->where('user_id', $userId)
                    ->where('stock_id', $row->stock_id)
                    ->sum('quantity');

                $remaining = $row->total_bought - $soldQty;

                if ($remaining <= 0) {
                    return null; // Đã bán hết
                }

                return [
                    'code' => $row->code,
                    'total_quantity' => $remaining,
                    'avg_buy_price' => round($row->avg_buy_price, 2),
                    'current_price' => $row->current_price,
                ];
            })
            ->filter() // loại bỏ null
            ->values(); // reset index
    }

    public static function getStockHolding($userId, $stockId)
    {
        // Tổng số lượng đã mua
        $totalBought = self::where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->sum('quantity');

        // Tổng số lượng đã bán
        $totalSold = DB::table('user_portfolios_sell')
            ->where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->sum('quantity');

        // Số lượng còn giữ
        $remaining = $totalBought - $totalSold;

        // Nếu không còn cổ phiếu nào -> trả về false
        if ($remaining <= 0) {
            return false;
        }

        // Lấy mã cổ phiếu từ bảng stocks
        $stock = DB::table('stocks')
            ->where('id', $stockId)
            ->value('code'); // chỉ lấy trường code

        // Trả về kết quả mong muốn
        return [
            'code' => $stock ?? 'N/A',
            'total_quantity' => $remaining
        ];
    }

    public static function getPortfolioWithStockInfo($userId)
    {
        return DB::table('user_portfolios as up')
            ->join('stocks as s', 'up.stock_id', '=', 's.id')
            ->where('up.user_id', $userId)
            ->select(
                's.code',
                'up.buy_price',
                'up.quantity',
                's.current_price',
                's.risk_level',
                'up.buy_date'
            )
            ->get();
    }
}
