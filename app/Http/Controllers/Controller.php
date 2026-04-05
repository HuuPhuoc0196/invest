<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * JSON lỗi server: production không lộ chi tiết DB/exception.
     */
    protected function jsonServerError(Throwable $e, int $status = 500): JsonResponse
    {
        report($e);

        $message = config('app.debug')
            ? ('Lỗi hệ thống: ' . $e->getMessage())
            : 'Lỗi hệ thống. Vui lòng thử lại sau.';

        return response()->json(['status' => 'error', 'message' => $message], $status);
    }
}
