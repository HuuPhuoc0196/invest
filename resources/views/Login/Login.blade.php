@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Đăng nhập')

@section('header-css')
    @vite('resources/css/login.css')
    <style>
        .login-btn .btn-spinner { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: btn-spin 0.7s linear infinite; vertical-align: middle; margin-right: 8px; }
        .login-btn .btn-spinner.is-loading { display: inline-block; }
        @keyframes btn-spin { to { transform: rotate(360deg); } }
    </style>
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    <div class="login-card">
    <h2>Đăng nhập</h2>
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
            <button type="button" onclick="location.href='{{ url('/register') }}'" class="secondary-btn">Đăng ký tài
                khoản</button>
            <button type="button" onclick="location.href='{{ url('/forgotPassword') }}'" class="secondary-btn">Quên mật
                khẩu?</button>
        </div>
    </form>
    </div>
@endsection

@section('login-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const baseUrl = "{{ url('') }}";
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            errorMessage.style.display = "none";
            errorMessage.innerText = "";

            if (!email || !password) {
                errorMessage.innerText = "Vui lòng nhập đầy đủ email và mật khẩu.";
                errorMessage.style.display = "block";
                return;
            }
            if (!isValidEmail(email)) {
                errorMessage.innerText = "Email không hợp lệ.";
                errorMessage.style.display = "block";
                return;
            }
            if (password.length < 6) {
                errorMessage.innerText = "Mật khẩu phải có ít nhất 6 ký tự.";
                errorMessage.style.display = "block";
                return;
            }
            if (!csrfToken) {
                errorMessage.innerText = "Thiếu CSRF token. Vui lòng tải lại trang.";
                errorMessage.style.display = "block";
                return;
            }

            const btnSubmit = document.getElementById('btnLoginSubmit');
            const btnSpinner = document.getElementById('btnLoginSpinner');
            const btnText = document.getElementById('btnLoginText');
            btnSubmit.disabled = true;
            btnSpinner.classList.add('is-loading');
            btnText.textContent = 'Đang xử lý...';

            const data = { email: email, password: password, _token: csrfToken };

            $.ajax({
                url: baseUrl + '/login',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                data: JSON.stringify(data),
                success: function (response) {
                    btnSubmit.disabled = false;
                    btnSpinner.classList.remove('is-loading');
                    btnText.textContent = 'Đăng nhập';
                    if (response.status === "success") {
                        if (response.data && response.data.role === 1) window.location.href = "{{ url('/admin') }}";
                        else window.location.href = "{{ url('/') }}";
                    } else {
                        errorMessage.innerText = response.message || '';
                        errorMessage.style.display = "block";
                    }
                },
                error: function (xhr) {
                    btnSubmit.disabled = false;
                    btnSpinner.classList.remove('is-loading');
                    btnText.textContent = 'Đăng nhập';
                    let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi không xác định.';
                    errorMessage.innerText = msg;
                    errorMessage.style.display = "block";
                }
            });
        });
    </script>
@endsection