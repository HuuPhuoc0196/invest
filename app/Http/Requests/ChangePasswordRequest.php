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

    public function messages(): array
    {
        return [
            'password.required'    => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.min'         => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'newPassword.required' => 'Vui lòng nhập mật khẩu mới.',
            'newPassword.min'      => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
        ];
    }
}
