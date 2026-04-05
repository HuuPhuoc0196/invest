<?php

namespace App\Http\Requests;

class UpdateInfoProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }
}
