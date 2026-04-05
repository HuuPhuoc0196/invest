<?php

namespace App\Http\Requests;

class StockInsertRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:10',
            'currentPrice' => 'required|numeric|gt:0',
            'priceAvg'     => 'nullable|numeric|min:0',
            'buyPrice'     => 'nullable|numeric|min:0',
            'sellPrice'    => 'nullable|numeric|min:0',
            'percentBuy'   => 'nullable|numeric|min:0',
            'percentSell'  => 'nullable|numeric|min:0',
            'risk'         => 'required|integer|min:1|max:5',
            'ratingStocks' => 'nullable|numeric|min:0|max:10',
            'stocksVn'     => 'nullable|numeric|min:0',
        ];
    }
}
