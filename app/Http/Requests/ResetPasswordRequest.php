<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dùng cho form submit thông thường (không phải AJAX).
 * Lỗi sẽ redirect back với errors (Laravel default).
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => 'required|string',
            'email'    => 'required|email',
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
