<?php

namespace App\Http\Requests;

use App\Models\UserPortfolio;

class BuyStockRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'     => 'required|string|max:10',
            'buy_price' => 'required|numeric|gt:0|max:' . UserPortfolio::BUY_PRICE_MAX,
            'quantity' => 'required|integer|min:1|max:' . UserPortfolio::QUANTITY_MAX,
            'buy_date' => 'required|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'buy_price.max'      => 'Giá mua không hợp lệ!',
            'buy_price.numeric'  => 'Giá mua không hợp lệ!',
            'quantity.max'       => 'Khối lượng giao dịch không hợp lệ!',
            'quantity.integer'   => 'Khối lượng giao dịch không hợp lệ!',
            'quantity.min'       => 'Khối lượng giao dịch không hợp lệ!',
        ];
    }
}
