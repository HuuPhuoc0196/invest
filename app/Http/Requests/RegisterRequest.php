<?php

namespace App\Http\Requests;

class RegisterRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Nhập lại mật khẩu không khớp.',
        ];
    }
}
