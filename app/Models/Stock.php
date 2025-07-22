<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['id', 'code', 'recommended_buy_price', 'current_price', 'risk_level'];
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
}
