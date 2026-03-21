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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                if (!$existingUser->hasVerifiedEmail()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng xác thực email trước khi đăng nhập.'
                    ]);
                }
                Auth::login($existingUser);
                // Trả kết quả JSON (chỉ trả field cần thiết, không lộ role/active/email_verified_at)
                return response()->json([
                    'status' => 'success',
                    'message' => 'Login thành công.',
                    'data' => ['id' => $existingUser->id, 'name' => $existingUser->name, 'role' => $existingUser->role]
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
                    'password' => 'required|string|min:6|confirmed',
                ], [
                    'password.confirmed' => 'Nhập lại mật khẩu không khớp.',
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
                $user->active = 0; // chỉ bật 1 sau khi verify email (xem routes verification.verify)
                $user->password = Hash::make($validated['password']);

                $user->save();

                // Gửi email xác thực (bắt lỗi để đăng ký vẫn thành công, lỗi ghi log)
                try {
                    $user->sendEmailVerificationNotification();
                } catch (\Throwable $e) {
                    Log::error('Gửi email xác thực thất bại: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'exception' => $e,
                    ]);
                }

                // Trả kết quả JSON (không đăng nhập, chỉ trả field cần thiết)
                return response()->json([
                    'status' => 'success',
                    'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.',
                    'data' => ['id' => $user->id, 'name' => $user->name]
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

                $email = trim($validated['email']);
                $token = Str::random(64);
                $expiryMinutes = 60;

                DB::table('password_resets')->updateOrInsert(
                    ['email' => $email],
                    ['token' => Hash::make($token), 'created_at' => now()]
                );

                // APP_URL trong .env phải đúng để link reset trỏ đúng domain
                $resetUrl = url('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
                $result = EmailService::sendPasswordResetLink($email, $resetUrl, $expiryMinutes);
                Log::info("Password reset link sent to {$email}: " . $result);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Vui lòng kiểm tra email để lấy link đặt lại mật khẩu.',
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

    /**
     * Hiển thị form đặt lại mật khẩu (khi user click link trong email).
     */
    public function showResetPasswordForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');
        $error = null;

        if (!$token || !$email) {
            $error = 'Link không hợp lệ. Thiếu token hoặc email.';
            return view('Login.ResetPassword', compact('token', 'email', 'error'));
        }

        $row = DB::table('password_resets')->where('email', $email)->first();
        if (!$row || !Hash::check($token, $row->token)) {
            $error = 'Link không hợp lệ hoặc đã được sử dụng.';
            return view('Login.ResetPassword', compact('token', 'email', 'error'));
        }

        $createdAt = \Carbon\Carbon::parse($row->created_at)->copy();
        if ($createdAt->addMinutes(60)->isPast()) {
            $error = 'Link đã hết hạn. Vui lòng gửi lại yêu cầu từ trang Quên mật khẩu.';
            return view('Login.ResetPassword', compact('token', 'email', 'error'));
        }

        return view('Login.ResetPassword', compact('token', 'email', 'error'));
    }

    /**
     * Xử lý submit đặt lại mật khẩu.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Nhập lại mật khẩu không khớp.',
        ]);

        $row = DB::table('password_resets')->where('email', $validated['email'])->first();
        if (!$row || !Hash::check($validated['token'], $row->token)) {
            return redirect()->route('password.reset', [
                'token' => $validated['token'],
                'email' => $validated['email'],
            ])->with('error', 'Link không hợp lệ hoặc đã được sử dụng.');
        }

        $createdAt = \Carbon\Carbon::parse($row->created_at)->copy();
        if ($createdAt->addMinutes(60)->isPast()) {
            return redirect()->route('password.reset', [
                'token' => $validated['token'],
                'email' => $validated['email'],
            ])->with('error', 'Link đã hết hạn. Vui lòng gửi lại yêu cầu từ trang Quên mật khẩu.');
        }

        $user = User::getUserByEmail($validated['email']);
        if (!$user) {
            return redirect()->route('forgotPassword')->with('error', 'Tài khoản không tồn tại.');
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_resets')->where('email', $validated['email'])->delete();

        return redirect()->route('login')->with('message', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập.');
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
