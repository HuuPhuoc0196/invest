<?php

namespace App\Models;

use App\Mail\VerifyEmailMail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role'              => 'integer',
    ];

    protected $table = 'users';

    // ✅ Hàm dùng để kiểm tra thông tin đăng nhập
    public static function getUserLogin($email, $password)
    {
        $user = self::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    // ✅ Hàm kiểm tra email đã tồn tại
    public static function getUserByEmail($email)
    {
        return self::where('email', $email)->first();
    }

    // Lấy theo ID
    public static function getUserById(int $id): ?User
    {
        return self::find($id);
    }

    /**
     * Gửi email xác thực theo template chuẩn hệ thống đầu tư cá nhân.
     */
    public function sendEmailVerificationNotification(): void
    {
        Mail::to($this->getEmailForVerification())->send(new VerifyEmailMail($this));
    }
}
