@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Đăng nhập')

@section('seo')
    @include('partials.seo-public', [
        'pageTitle' => 'Đăng nhập — ' . config('app.name'),
        'description' => 'Đăng nhập để quản lý danh mục, theo dõi mã cổ phiếu và cài đặt tài khoản trên nền tảng quản lý đầu tư cá nhân.',
    ])
@endsection

@section('header-css')
    @vite('resources/css/login.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    <div class="login-card">
    <h1>Đăng nhập</h1>
    @if (session('message'))
        <div class="session-message" style="padding: 10px; margin-bottom: 12px; background: #d4edda; color: #155724; border-radius: 6px;">{{ session('message') }}</div>
    @endif
    @if (session('error'))
        <div class="session-error" style="padding: 10px; margin-bottom: 12px; background: #f8d7da; color: #721c24; border-radius: 6px;">{{ session('error') }}</div>
    @endif
    <form id="loginForm">
        <div class="input-group">
            <label for="email">Email:</label>
            <input type="email" id="email" required />
        </div>
        <div class="input-group">
            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" required />
        </div>

        <div class="error-message" id="errorMessage" style="display: none; color: red;"></div>

        <button type="submit" class="login-btn" id="btnLoginSubmit">
            <span class="btn-spinner" id="btnLoginSpinner" aria-hidden="true"></span>
            <span class="btn-text" id="btnLoginText">Đăng nhập</span>
        </button>

        <div class="extra-actions">
            <button type="button" onclick="location.href='{{ route('register') }}'" class="secondary-btn">Đăng ký tài
                khoản</button>
            <button type="button" onclick="location.href='{{ route('forgotPassword') }}'" class="secondary-btn">Quên mật
                khẩu?</button>
        </div>
    </form>
    </div>
@endsection

@section('login-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            urlAdmin: "{{ url('/admin') }}",
            urlHome: "{{ route('home') }}",
            urlLoginPost: @json(route('login')),
        };
    </script>
    @vite('resources/js/pages/login.js')
@endsection