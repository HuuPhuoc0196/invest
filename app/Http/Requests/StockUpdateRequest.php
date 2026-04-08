<?php

namespace App\Http\Requests;

class StockUpdateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'currentPrice' => 'required|numeric|gt:0',
            'risk'         => 'required|integer|min:1|max:5',
            'priceAvg'     => 'nullable|numeric|min:0',
            'buyPrice'     => 'nullable|numeric|min:0',
            'sellPrice'    => 'nullable|numeric|min:0',
            'percentBuy'   => 'nullable|numeric|min:0',
            'percentSell'  => 'nullable|numeric|min:0',
            'ratingStocks' => 'nullable|numeric',
            'stocksVn'     => 'nullable|numeric|min:0',
        ];
    }
}
