<?php

namespace App\Http\Requests;

use App\Models\UserPortfolio;
use Illuminate\Contracts\Validation\Validator;

class SellStockRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'       => 'required|string|max:10',
            'sell_price' => 'required|numeric|gt:0',
            'quantity'   => 'required|numeric|gt:0',
            'sell_date'  => ['required', 'date_format:Y-m-d', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'sell_date.required'          => 'Vui lòng nhập ngày bán.',
            'sell_date.date_format'       => 'Ngày bán không hợp lệ',
            'sell_date.date'              => 'Ngày bán không hợp lệ',
            'sell_date.before_or_equal'   => 'Ngày bán không hợp lệ',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->has('sell_date') || $v->errors()->has('code')) {
                return;
            }
            $code = strtoupper((string) $this->input('code', ''));
            $sellDate = (string) $this->input('sell_date', '');
            if ($code === '' || $sellDate === '') {
                return;
            }
            $user = $this->user();
            if (! $user) {
                return;
            }
            $earliest = UserPortfolio::getEarliestRemainingBuyDateYmdForCode((int) $user->id, $code);
            if ($earliest !== null && strcmp($sellDate, $earliest) < 0) {
                $v->errors()->add('sell_date', 'Ngày bán không hợp lệ');
            }
        });
    }
}
