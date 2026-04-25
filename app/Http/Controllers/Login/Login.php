<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;

class Login extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(LoginRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->authService->login($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Login.Login');
    }

    public function register(RegisterRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->authService->register($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Login.Register');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->authService->forgotPassword($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Login.ForgotPassword');
    }

    public function showResetPasswordForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');
        $error = null;

        if (!$token || !$email) {
            $error = 'Link không hợp lệ. Thiếu token hoặc email.';
        } else {
            $error = $this->authService->validateResetToken($email, $token);
        }

        return view('Login.ResetPassword', compact('token', 'email', 'error'));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = $request->validated();
        $result    = $this->authService->resetPassword($validated);

        if ($result === 'not_found') {
            return redirect()->route('forgotPassword')->with('error', 'Tài khoản không tồn tại.');
        }
        if ($result !== 'success') {
            return redirect()->route('password.reset', [
                'token' => $validated['token'],
                'email' => $validated['email'],
            ])->with('error', $result);
        }

        return redirect()->route('login')->with('message', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập.');
    }

}
