<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Services\EmailService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
                Log::error($e->errors());
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
            return view('Login.Login');
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
            return view('Login.Register');
        }
    }

    public function forgotPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validate dữ liệu
                $validated = $request->validate([
                    'email' => 'required|email|max:255',
                ]);

                // Kiểm tra email đã tồn tại chưa
                $existingUser = User::getUserByEmail(trim($validated['email']));
                if (!$existingUser) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email không tồn tại.'
                    ]);
                }

                $newPassword = $this->generate_password(12, true, true, true);
                $existingUser->password = Hash::make($newPassword); // mã hóa password
                $existingUser->save();

                $result = EmailService::sendForgetPassword(trim($validated['email']), $newPassword);
                $message = 'Khách hàng <span style="color:red;">' . trim($validated['email']) . '</span> đã forget password : <span style="color:red;">' . $newPassword . '</span> ';
                Log::info($message);
                Log::info("Send mail: " . $result);
                // Auth::login($existingUser);
                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mật khẩu đã được gửi vào email.',
                ]);
            } catch (ValidationException $e) {
                Log::error($e->errors());
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
            return view('Login.ForgotPassword');
        }
    }

    public function profile()
    {
        return view('Login.Profile');
    }

    /**
     * Tạo mật khẩu ngẫu nhiên an toàn.
     *
     * @param int  $length        Độ dài mật khẩu (mặc định 12)
     * @param bool $use_upper     Có dùng chữ hoa A-Z không
     * @param bool $use_numbers   Có dùng chữ số 0-9 không
     * @param bool $use_symbols   Có dùng ký tự đặc biệt không
     * @return string             Mật khẩu sinh được
     */
    private function generate_password(int $length = 12, bool $use_upper = true, bool $use_numbers = true, bool $use_symbols = true): string
    {
        if ($length < 4) {
            // đảm bảo độ dài tối thiểu để có thể chứa các loại ký tự khi bật nhiều tùy chọn
            $length = 4;
        }

        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>?';

        // xây dựng tập ký tự dùng chung
        $all = $lower;
        $required = [];

        if ($use_upper) {
            $all .= $upper;
            $required[] = $upper[random_int(0, strlen($upper) - 1)];
        }
        if ($use_numbers) {
            $all .= $numbers;
            $required[] = $numbers[random_int(0, strlen($numbers) - 1)];
        }
        if ($use_symbols) {
            $all .= $symbols;
            $required[] = $symbols[random_int(0, strlen($symbols) - 1)];
        }

        // luôn đảm bảo có ít nhất 1 chữ thường
        $required[] = $lower[random_int(0, strlen($lower) - 1)];

        // số ký tự còn lại
        $remainingLen = $length - count($required);
        $passwordChars = $required;

        for ($i = 0; $i < $remainingLen; $i++) {
            $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // xáo trộn mảng ký tự rồi ghép thành chuỗi
        shuffle($passwordChars);
        return implode('', $passwordChars);
    }
}
