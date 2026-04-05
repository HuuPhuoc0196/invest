<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base FormRequest cho tất cả JSON API endpoints.
 * Trả về format lỗi đồng nhất: {status, message, errors}
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * GET/HEAD requests show the form — skip validation entirely.
     * Only POST/PUT/PATCH/DELETE requests need to be validated.
     */
    protected function getValidatorInstance(): Validator
    {
        if (in_array(strtoupper($this->method()), ['GET', 'HEAD'])) {
            return app(ValidationFactory::class)->make([], []);
        }

        return parent::getValidatorInstance();
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
