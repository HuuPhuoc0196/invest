@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Quên mật khẩu')

@section('seo')
    @include('partials.seo-public', [
        'pageTitle' => 'Quên mật khẩu — ' . config('app.name'),
        'description' => 'Nhập email đã đăng ký để nhận link đặt lại mật khẩu an toàn.',
    ])
@endsection

@section('header-css')
    @vite('resources/css/loginRegister.css')
    @vite('resources/css/login.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    <div class="login-card">
    <h1>Quên mật khẩu</h1>
    <p class="login-auth-sub">Nhập email đã đăng ký — chúng tôi sẽ gửi link đặt lại mật khẩu.</p>
    <form id="forgotForm">
      <div class="input-group">
        <label for="email">Nhập email để khôi phục:</label>
        <input type="email" id="email" required />
      </div>
      <div class="error-message" id="errorMessage" style="display: none; color: red;"></div>
      <button type="submit" class="login-btn" id="btnForgotSubmit">
        <span class="btn-spinner" id="btnForgotSpinner" aria-hidden="true"></span>
        <span class="btn-text" id="btnForgotText">Khôi phục tài khoản</span>
      </button>
      <div class="error-message" id="forgotError">Email không tồn tại!</div>
    </form>
    <div class="link-group">
      <a href="{{ route('login') }}">← Quay lại đăng nhập</a>
    </div>
    </div>
@endsection

@section('page-modals')
    <div id="notify-modal" class="notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
      <div class="notify-modal__backdrop" id="notifyBackdrop"></div>
      <div class="notify-modal__box">
        <span class="notify-modal__icon" id="notifyIcon"></span>
        <p class="notify-modal__msg" id="notifyMsg"></p>
        <button type="button" class="notify-modal__close" id="notifyClose">Đóng</button>
      </div>
    </div>
@endsection

@section('login-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            urlForgotPost: @json(route('forgotPassword')),
        };
    </script>
    @vite('resources/js/pages/forgot-password.js')
@endsection