<?php

namespace App\Http\Requests;

class CashInRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'cashIn'   => 'required|numeric|gt:0',
            'cashDate' => 'required|date|before_or_equal:today',
        ];
    }
}
