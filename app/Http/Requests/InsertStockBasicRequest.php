<?php

namespace App\Http\Requests;

class InsertStockBasicRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:10',
            'buyPrice'     => 'required|numeric|gt:0',
            'currentPrice' => 'required|numeric|gt:0',
            'risk'         => 'required|integer|min:1|max:5',
        ];
    }
}
