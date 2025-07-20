<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPortfolioSell extends Model
{
    protected $table = 'user_portfolios_sell';
    protected $fillable = ['id', 'user_id', 'stock_id', 'sell_price', 'sell_date', 'quantity'];
}
