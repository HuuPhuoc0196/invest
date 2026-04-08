<?php

namespace App\Http\Requests;

class CashInRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'cashIn'   => 'required|numeric|gt:0',
            'cashDate' => ['required', 'date_format:Y-m-d', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'cashDate.required'          => 'Vui lòng nhập ngày nạp.',
            'cashDate.date_format'       => 'Ngày nạp không hợp lệ',
            'cashDate.date'              => 'Ngày nạp không hợp lệ',
            'cashDate.before_or_equal'   => 'Ngày nạp không hợp lệ',
        ];
    }
}
