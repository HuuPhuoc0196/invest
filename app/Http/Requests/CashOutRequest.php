<?php

namespace App\Http\Requests;

class CashOutRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'cashOut'  => 'required|numeric|gt:0',
            'cashDate' => ['required', 'date_format:Y-m-d', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'cashDate.required'          => 'Vui lòng nhập ngày rút.',
            'cashDate.date_format'       => 'Ngày rút không hợp lệ',
            'cashDate.date'              => 'Ngày rút không hợp lệ',
            'cashDate.before_or_equal'   => 'Ngày rút không hợp lệ',
        ];
    }
}
