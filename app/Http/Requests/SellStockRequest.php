<?php

namespace App\Http\Requests;

class SellStockRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'      => 'required|string|max:10',
            'sell_price' => 'required|numeric|gt:0',
            'quantity'  => 'required|numeric|gt:0',
            'sell_date' => 'required|date|before_or_equal:today',
        ];
    }
}
