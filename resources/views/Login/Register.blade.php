@extends('Layout.LayoutLogin')
@section('csrf-token')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Đăng ký tài khoản')

@section('header-css')
  @vite('resources/css/loginRegister.css')
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
  <h2>Đăng ký tài khoản</h2>
  <form id="registerForm">
    <div class="input-group">
      <label for="name">Tên:</label>
      <input type="text" id="name" name="name" required />
      <div class="input-error" id="nameError"></div>
    </div>
    <div class="input-group">
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required />
      <div class="input-error" id="emailError"></div>
    </div>
    <div class="input-group">
      <label for="password">Mật khẩu:</label>
      <input type="password" id="password" name="password" required />
      <div class="input-error" id="passwordError"></div>
    </div>
    <div class="input-group">
      <label for="password_confirmation">Nhập lại mật khẩu:</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required />
      <div class="input-error" id="password_confirmationError"></div>
    </div>
    <button type="submit" class="login-btn" id="btnRegister">
      <span class="btn-spinner" id="btnRegisterSpinner" aria-hidden="true"></span>
      <span class="btn-text" id="btnRegisterText">Đăng ký</span>
    </button>
    <div class="error-message" id="registerError">Thông tin không hợp lệ hoặc đã tồn tại!</div>
  </form>
  <div id="toast" class="toast"></div>
  <div class="link-group">
    <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
  </div>
@endsection

@section('login-script')
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const baseUrl = "{{ url('') }}";
    const form = document.getElementById('registerForm');
    const nameField = document.getElementById('name');
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const passwordConfirmationField = document.getElementById('password_confirmation');
    const registerError = document.getElementById('registerError');

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

    function resetForm() {
      nameField.value = '';
      emailField.value = '';
      passwordField.value = '';
      passwordConfirmationField.value = '';
    }

    const showError = (element, message) => {
      document.getElementById(element + 'Error').innerText = message;
    };

    const clearErrors = () => {
      ['name', 'email', 'password', 'password_confirmation'].forEach(field => {
        showError(field, '');
      });
      registerError.style.display = 'none';
    };

    const validateEmail = (email) => {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearErrors();

      const name = nameField.value.trim();
      const email = emailField.value.trim();
      const password = passwordField.value;
      const passwordConfirmation = passwordConfirmationField.value;
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      let valid = true;

      if (name.length < 2) {
        showError('name', 'Tên phải có ít nhất 2 ký tự.');
        valid = false;
      }

      if (!validateEmail(email)) {
        showError('email', 'Email không hợp lệ.');
        valid = false;
      }

      if (password.length < 6) {
        showError('password', 'Mật khẩu phải có ít nhất 6 ký tự.');
        valid = false;
      }

      if (password !== passwordConfirmation) {
        showError('password_confirmation', 'Nhập lại mật khẩu không khớp.');
        valid = false;
      }

      if (valid) {
        const btnRegister = document.getElementById('btnRegister');
        const btnSpinner = document.getElementById('btnRegisterSpinner');
        const btnText = document.getElementById('btnRegisterText');
        btnRegister.disabled = true;
        btnSpinner.classList.add('is-loading');
        btnText.textContent = 'Đang xử lý...';

        const data = {
            name: name,
            email: email,
            password: password,
            password_confirmation: passwordConfirmation
        };
        $.ajax({
          url: baseUrl + '/register',
          type: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': token
          },
          data: JSON.stringify(data),
          success: function(response) {
            btnRegister.disabled = false;
            btnSpinner.classList.remove('is-loading');
            btnText.textContent = 'Đăng ký';
            if (response.status == "success") {
                const toast = document.getElementById("toast");
                toast.innerHTML = `✅ ${response.message}<br><small>Kiểm tra hộp thư <b>${email}</b> để xác thực.</small>`;
                toast.className = "toast show";
                toastSuccess();
                setTimeout(() => {
                    toast.className = toast.className.replace("show", "");
                }, 3000);

                // Reset form
                resetForm();
            } else {
                const toast = document.getElementById("toast");
                toast.innerHTML = `❌` + response.message;
                toast.className = "toast show";
                toastError();
                setTimeout(() => {
                    toast.className = toast.className.replace("show", "");
                }, 5000);
            }
          },
          error: function(xhr) {
            btnRegister.disabled = false;
            btnSpinner.classList.remove('is-loading');
            btnText.textContent = 'Đăng ký';
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi kết nối.';
            const toast = document.getElementById("toast");
            toast.innerHTML = '❌ ' + msg;
            toast.className = "toast show";
            toastError();
            setTimeout(() => {
                toast.className = toast.className.replace("show", "");
            }, 5000);
          }
        });
      }
    });
  </script>
@endsection