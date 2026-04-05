<?php

namespace App\Http\Requests;

class ChangePasswordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'password'    => 'required|string|min:6',
            'newPassword' => 'required|string|min:6',
        ];
    }
}
