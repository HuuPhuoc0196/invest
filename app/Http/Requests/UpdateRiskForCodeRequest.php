<?php

namespace App\Http\Requests;

class UpdateRiskForCodeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:10',
        ];
    }
}
