<?php

namespace App\Http\Requests;

class CashOutRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'cashOut'  => 'required|numeric|gt:0',
            'cashDate' => 'required|date|before_or_equal:today',
        ];
    }
}
