@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Đăng ký tài khoản')

@section('header-css')
    @vite('resources/css/login.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    <h2>Đăng nhập</h2>
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

        <button type="submit" class="login-btn">Đăng nhập</button>

        <div class="extra-actions">
            <button type="button" onclick="location.href='{{ url('/register') }}'" class="secondary-btn">Đăng ký tài khoản</button>
            <button type="button" onclick="location.href='{{ url('/forgotPassword') }}'" class="secondary-btn">Quên mật khẩu?</button>
        </div>
    </form>
@endsection

@section('login-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const baseUrl = "{{ url('') }}";
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Reset lỗi cũ
            errorMessage.style.display = "none";
            errorMessage.innerText = "";

            // Validation client
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


            const data = {
                email: email,
                password: password
            };

            $.ajax({
                url: baseUrl + '/login',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify(data),
                success: function (response) {
                    if (response.status === "success") {
                        if(response.data.role === 1) window.location.href = "{{ url('/admin') }}";
                        window.location.href = "{{ url('/') }}";
                    } else {
                        errorMessage.innerText = response.message;
                        errorMessage.style.display = "block";
                    }
                },
                error: function (xhr) {
                    let msg = 'Lỗi không xác định.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    errorMessage.innerText = msg;
                    errorMessage.style.display = "block";
                }
            });
        });
    </script>
@endsection