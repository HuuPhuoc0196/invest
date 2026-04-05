<?php

namespace App\Http\Requests;

class UpdateFollowRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'            => 'required|string|max:10',
            'followPriceBuy'  => 'required|numeric|gt:0',
            'followPriceSell' => 'nullable|numeric|gt:0',
            'autoSync'        => 'required|in:0,1',
        ];
    }
}
