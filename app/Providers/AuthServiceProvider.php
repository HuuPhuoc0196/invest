<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Xác thực địa chỉ email - ' . config('app.name'))
                ->line('Vui lòng bấm nút bên dưới để xác thực địa chỉ email của bạn.')
                ->action('Xác thực email', $url)
                ->line('Nếu bạn không đăng ký tài khoản, vui lòng bỏ qua email này.');
        });
    }
}
