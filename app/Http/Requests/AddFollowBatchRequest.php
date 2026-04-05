<?php

namespace App\Http\Requests;

class AddFollowBatchRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'codes'   => 'required|array',
            'codes.*' => 'required|string|max:10',
        ];
    }
}
