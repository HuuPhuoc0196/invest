@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Đặt lại mật khẩu')

@section('header-css')
    @vite('resources/css/loginRegister.css')
    @vite('resources/css/login.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    <div class="login-card">
    <p class="login-auth-lead">Bảo mật tài khoản</p>
    <h2>Đặt lại mật khẩu</h2>
    <p class="login-auth-sub">Chọn mật khẩu mới đủ mạnh và dễ nhớ với bạn.</p>

    @if(session('error'))
        <div class="session-error" style="padding: 10px; margin-bottom: 12px; border-radius: 8px;">{{ session('error') }}</div>
    @endif
    @if(session('message'))
        <div class="session-message" style="padding: 10px; margin-bottom: 12px; border-radius: 8px;">{{ session('message') }}</div>
    @endif

    @if($error ?? null)
        <div class="session-error" style="padding: 10px; margin-bottom: 12px; border-radius: 8px;">{{ $error }}</div>
        <div class="link-group" style="margin-top: 16px;">
            <a href="{{ url('/forgotPassword') }}">Gửi lại link đặt lại mật khẩu</a><br>
            <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
        </div>
    @else
        <form id="resetPasswordForm" action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <div class="input-group">
                <label for="password">Mật khẩu mới:</label>
                <input type="password" id="password" name="password" required minlength="6" />
                <div class="input-error" id="passwordError"></div>
            </div>
            <div class="input-group">
                <label for="password_confirmation">Nhập lại mật khẩu:</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" />
                <div class="input-error" id="passwordConfirmationError"></div>
            </div>
            <div class="error-message" id="formError" style="display: none;"></div>
            <button type="submit" class="login-btn">Đặt lại mật khẩu</button>
        </form>
        <div class="link-group">
            <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
        </div>
    @endif
    </div>
@endsection

@section('login-script')
    <script>
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            var pwd = document.getElementById('password');
            var pwdConfirm = document.getElementById('password_confirmation');
            var formError = document.getElementById('formError');
            document.getElementById('passwordError').textContent = '';
            document.getElementById('passwordConfirmationError').textContent = '';
            formError.style.display = 'none';

            if (pwd.value.length < 6) {
                e.preventDefault();
                document.getElementById('passwordError').textContent = 'Mật khẩu tối thiểu 6 ký tự.';
                return;
            }
            if (pwd.value !== pwdConfirm.value) {
                e.preventDefault();
                document.getElementById('passwordConfirmationError').textContent = 'Nhập lại mật khẩu không khớp.';
                return;
            }
        });
    </script>
@endsection
