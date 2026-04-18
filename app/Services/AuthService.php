<?php

namespace App\Services;

use App\Models\User;
use App\Services\CacheService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    public function login(array $data): array
    {
        $existingUser = User::getUserLogin(trim($data['email']), $data['password']);
        if (!$existingUser) {
            return ['status' => 'error', 'message' => 'Email hoặc mật khẩu không đúng.'];
        }
        if (!$existingUser->hasVerifiedEmail()) {
            return ['status' => 'error', 'message' => 'Vui lòng xác thực email trước khi đăng nhập.'];
        }
        Auth::login($existingUser);
        request()->session()->regenerate();

        return [
            'status'  => 'success',
            'message' => 'Login thành công.',
            'data'    => ['id' => $existingUser->id, 'name' => $existingUser->name, 'role' => $existingUser->role],
        ];
    }

    public function register(array $data): array
    {
        $existingUser = User::getUserByEmail(trim($data['email']));
        if ($existingUser) {
            return ['status' => 'error', 'message' => 'Email đã tồn tại.'];
        }

        $user           = new User();
        $user->name     = trim($data['name']);
        $user->email    = trim($data['email']);
        $user->role     = 0;
        $user->active   = 0;
        $user->password = Hash::make($data['password']);
        $user->save();

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Gửi email xác thực thất bại: ' . $e->getMessage(), [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'exception' => $e,
            ]);
        }

        return [
            'status'  => 'success',
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.',
            'data'    => ['id' => $user->id, 'name' => $user->name],
        ];
    }

    public function forgotPassword(array $data): array
    {
        $email = trim($data['email']);
        $existingUser = User::getUserByEmail($email);
        if (!$existingUser) {
            return ['status' => 'error', 'message' => 'Email không tồn tại.'];
        }

        $token         = Str::random(64);
        $expiryMinutes = 60;

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = url('/dat-lai-mat-khau?' . http_build_query([
            'token' => $token,
            'email' => $email,
        ]));
        $result   = EmailService::sendPasswordResetLink($email, $resetUrl, $expiryMinutes);
        Log::info("Password reset link sent to {$email}: " . $result);

        return [
            'status'  => 'success',
            'message' => 'Vui lòng kiểm tra email để lấy link đặt lại mật khẩu.',
        ];
    }

    /**
     * Kiểm tra token reset password, trả về chuỗi lỗi hoặc null nếu hợp lệ.
     */
    public function validateResetToken(string $email, string $token): ?string
    {
        $row = DB::table('password_resets')->where('email', $email)->first();
        if (!$row || !Hash::check($token, $row->token)) {
            return 'Link không hợp lệ hoặc đã được sử dụng.';
        }
        $createdAt = Carbon::parse($row->created_at)->copy();
        if ($createdAt->addMinutes(60)->isPast()) {
            return 'Link đã hết hạn. Vui lòng gửi lại yêu cầu từ trang Quên mật khẩu.';
        }
        return null;
    }

    public function resetPassword(array $data): array|string
    {
        $row = DB::table('password_resets')->where('email', $data['email'])->first();
        if (!$row || !Hash::check($data['token'], $row->token)) {
            return 'Link không hợp lệ hoặc đã được sử dụng.';
        }
        $createdAt = Carbon::parse($row->created_at)->copy();
        if ($createdAt->addMinutes(60)->isPast()) {
            return 'Link đã hết hạn. Vui lòng gửi lại yêu cầu từ trang Quên mật khẩu.';
        }

        $user = User::getUserByEmail($data['email']);
        if (!$user) {
            return 'not_found';
        }

        $user->password = Hash::make($data['password']);
        $user->save();
        DB::table('password_resets')->where('email', $data['email'])->delete();

        CacheService::forget("user_{$user->id}");

        return 'success';
    }

    public function updateUserName(int $userId, string $name): array
    {
        $user = User::getUserById($userId);
        if (!$user) {
            return ['status' => 'error', 'message' => 'Tài khoản không tồn tại.', 'code' => 404];
        }
        $user->name = trim($name);
        $user->save();
        
        // Clear cache sau khi update
        CacheService::forget("user_{$userId}");
        
        return ['status' => 'success', 'message' => 'Cập nhật thông tin thành công.', 'data' => $user];
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = User::getUserById($userId);
        if (!$user) {
            return ['status' => 'error', 'message' => 'Tài khoản không tồn tại.', 'code' => 404];
        }
        $existingUser = User::getUserLogin($user->email, $oldPassword);
        if (!$existingUser) {
            return ['status' => 'error', 'message' => 'Mật khẩu không đúng.'];
        }
        $user->password = Hash::make($newPassword);
        $user->save();

        CacheService::forget("user_{$userId}");

        return ['status' => 'success', 'message' => 'Đổi mật khẩu thành công.', 'data' => $user];
    }

    public function generatePassword(int $length = 12, bool $use_upper = true, bool $use_numbers = true, bool $use_symbols = true): string
    {
        if ($length < 4) {
            $length = 4;
        }

        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>?';

        $all      = $lower;
        $required = [];

        if ($use_upper) {
            $all       .= $upper;
            $required[] = $upper[random_int(0, strlen($upper) - 1)];
        }
        if ($use_numbers) {
            $all       .= $numbers;
            $required[] = $numbers[random_int(0, strlen($numbers) - 1)];
        }
        if ($use_symbols) {
            $all       .= $symbols;
            $required[] = $symbols[random_int(0, strlen($symbols) - 1)];
        }

        $required[]   = $lower[random_int(0, strlen($lower) - 1)];
        $remainingLen = $length - count($required);
        $passwordChars = $required;

        for ($i = 0; $i < $remainingLen; $i++) {
            $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($passwordChars);
        return implode('', $passwordChars);
    }
}
