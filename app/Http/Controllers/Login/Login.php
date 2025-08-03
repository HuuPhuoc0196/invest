<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\LOG;
use Illuminate\Support\Facades\Auth;

class Login extends Controller
{
    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validate dữ liệu
                $validated = $request->validate([
                    'email' => 'required|email|max:255',
                    'password' => 'required|string|min:6',
                ]);

                // Kiểm tra user đã tồn tại chưa
                $existingUser = User::getUserLogin(trim($validated['email']), $validated['password']);
                if (!$existingUser) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email hoặc mật khẩu không đúng.'
                    ]);
                }
                Auth::login($existingUser);
                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Login thành công.',
                    'data' => $existingUser
                ]);
            } catch (ValidationException $e) {
                LOG::error($e->errors());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], 422);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                ], 500);
            }
        } else {
            return view('Login.login');
        }
    }

    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validate dữ liệu
                $validated = $request->validate([
                    'name' => 'required|string|max:100',
                    'email' => 'required|email|max:255',
                    'password' => 'required|string|min:6',
                ]);

                // Kiểm tra email đã tồn tại chưa
                $existingUser = User::getUserByEmail(trim($validated['email']));
                if ($existingUser) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email đã tồn tại.'
                    ]);
                }

                // Tạo user mới
                $user = new User();
                $user->name = trim($validated['name']);
                $user->email = trim($validated['email']);
                $user->role = 0;
                $user->password = Hash::make($validated['password']); // mã hóa password

                $user->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Đăng ký thành công.',
                    'data' => $user
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], 422);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                ], 500);
            }
        } else {
            return view('Login.register');
        }
    }

    public function forgotPassword()
    {
        return view('Login.forgotPassword');
    }
}
