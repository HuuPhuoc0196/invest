<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Stock extends Model
{
    protected $fillable = ['code', 'recommended_buy_price', 'current_price', 'recommended_sell_price', 'price_avg', 'percent_buy', 'percent_sell', 'risk_level', 'rating_stocks', 'stocks_vn', 'volume'];
    protected $table = 'stocks';

    // Lấy toàn bộ stocks (tuỳ chỉnh thêm điều kiện nếu muốn)
    public static function getAllStocks()
    {
        return self::orderBy('code')->get();
    }

    public static function getInvestmentPerformance()
    {
        return self::orderBy('code')->get();
    }

    // Lấy stock theo mã code
    public static function getByCode(string $code): ?Stock
    {
        return self::where('code', strtoupper($code))->first();
    }

    // Lấy theo ID
    public static function getById(int $id): ?Stock
    {
        return self::find($id);
    }

    // app/Models/Stock.php
    public static function deleteByCode(string $code): bool
    {
        $stock = self::where('code', $code)->first();

        if ($stock) {
            return $stock->delete();
        }

        return false; // Không tìm thấy
    }

    public static function getDeleteDependencyCounts(string $code): array
    {
        $stock = self::where('code', strtoupper($code))->first();
        if (!$stock) {
            return [
                'user_portfolios' => 0,
                'user_portfolios_sell' => 0,
                'user_follows' => 0,
            ];
        }

        $stockId = (int) $stock->id;

        return [
            'user_portfolios' => DB::table('user_portfolios')->where('stock_id', $stockId)->count(),
            'user_portfolios_sell' => DB::table('user_portfolios_sell')->where('stock_id', $stockId)->count(),
            'user_follows' => DB::table('user_follows')->where('stock_id', $stockId)->count(),
        ];
    }

    // Lấy stock theo mã code
    public static function getRiskLevelFromCode(string $code): ?Stock
    {
        return self::where('code', $code)
            ->select('code', 'risk_level')
            ->first();
    }
}
