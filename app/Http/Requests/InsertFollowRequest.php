<?php

namespace App\Http\Requests;

class InsertFollowRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'            => 'required|string|max:10',
            'followPriceBuy'  => 'nullable|numeric|gt:0',
            'followPriceSell' => 'nullable|numeric|gt:0',
        ];
    }
}
