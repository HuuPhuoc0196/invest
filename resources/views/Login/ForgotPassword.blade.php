@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Quên mật khẩu')

@section('header-css')
    @vite('resources/css/loginRegister.css')
    @vite('resources/css/login.css')
    <style>
        .login-btn .btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: btn-spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        .login-btn .btn-spinner.is-loading { display: inline-block; }
        @keyframes btn-spin { to { transform: rotate(360deg); } }
    </style>
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    <div class="login-card">
    <h2>Quên mật khẩu</h2>
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
    <div id="toast" class="toast"></div>
    <div class="link-group">
      <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
    </div>
    </div>
@endsection

@section('login-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
      }

      function toastSuccess() {
        // Xóa class cũ trước khi thêm class mới
        toast.classList.remove("toast-success", "toast-error");
        toast.classList.add("toast-success");
        toast.classList.add("toast", "show");
      }

      function toastError() {
        // Xóa class cũ trước khi thêm class mới
        toast.classList.remove("toast-success", "toast-error");
        toast.classList.add("toast-error");
        toast.classList.add("toast", "show");
      }

      const baseUrl = "{{ url('') }}";
      const forgetForm = document.getElementById('forgotForm');
      const errorMessage = document.getElementById('errorMessage');
      const toast = document.getElementById('toast');

      forgetForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btnSubmit = document.getElementById('btnForgotSubmit');
        const btnSpinner = document.getElementById('btnForgotSpinner');
        const btnText = document.getElementById('btnForgotText');
        const email = document.getElementById('email').value.trim();
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        errorMessage.style.display = "none";
        errorMessage.innerText = "";

        if (!email) {
          errorMessage.innerText = "Vui lòng nhập đầy đủ email";
          errorMessage.style.display = "block";
          return;
        }
        if (!isValidEmail(email)) {
          errorMessage.innerText = "Email không hợp lệ.";
          errorMessage.style.display = "block";
          return;
        }

        btnSubmit.disabled = true;
        btnSpinner.classList.add('is-loading');
        btnText.textContent = 'Đang xử lý...';

        const data = { email: email };

        $.ajax({
          url: baseUrl + '/forgotPassword',
          type: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
          },
          data: JSON.stringify(data),
          success: function(response) {
            btnSubmit.disabled = false;
            btnSpinner.classList.remove('is-loading');
            btnText.textContent = 'Khôi phục tài khoản';
            if (response.status == "success") {
              toast.innerHTML = '✅ Vui lòng kiểm tra email để lấy link đặt lại mật khẩu.';
              toast.className = "toast show";
              toastSuccess();
              setTimeout(function() { toast.className = toast.className.replace("show", ""); }, 3000);
            } else {
              toast.innerHTML = '❌ ' + response.message;
              toast.className = "toast show";
              toastError();
              setTimeout(function() { toast.className = toast.className.replace("show", ""); }, 5000);
            }
          },
          error: function(xhr) {
            btnSubmit.disabled = false;
            btnSpinner.classList.remove('is-loading');
            btnText.textContent = 'Khôi phục tài khoản';
            toast.innerHTML = '❌ Lỗi: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi kết nối.');
            toast.className = "toast show";
            toastError();
            setTimeout(function() { toast.className = toast.className.replace("show", ""); }, 5000);
          }
        });
      });
  </script>
@endsection