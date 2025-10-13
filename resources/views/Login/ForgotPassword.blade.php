@extends('Layout.LayoutLogin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Quên mật khẩu')

@section('header-css')
    @vite('resources/css/loginRegister.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection
<style>
  .login-btn.hidden {
    display: none !important;
  }
</style>
@section('body-content')
    <h2>Quên mật khẩu</h2>
    <form id="forgotForm">
      <div class="input-group">
        <label for="email">Nhập email để khôi phục:</label>
        <input type="email" id="email" required />
      </div>
      <div class="error-message" id="errorMessage" style="display: none; color: red;"></div>
      <button type="submit" class="login-btn">Khôi phục tài khoản</button>
      <div class="error-message" id="forgotError">Email không tồn tại!</div>
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

      forgetForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const loginBtn = document.querySelector('.login-btn');
        loginBtn.disabled = true;
        loginBtn.classList.add('hidden');
        const email = document.getElementById('email').value.trim();
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Reset lỗi cũ
        errorMessage.style.display = "none";
        errorMessage.innerText = "";

        // Validation client
        if (!email) {
          errorMessage.innerText = "Vui lòng nhập đầy đủ email";
          errorMessage.style.display = "block";
          loginBtn.disabled = false;
          loginBtn.classList.remove('hidden');
          return;
        }

        if (!isValidEmail(email)) {
          errorMessage.innerText = "Email không hợp lệ.";
          errorMessage.style.display = "block";
          loginBtn.disabled = false;
          loginBtn.classList.remove('hidden');
          return;
        }

        const data = {
            email: email,
        };

        $.ajax({
          url: baseUrl + '/forgotPassword',
          type: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
          },
          data: JSON.stringify(data),
          success: function(response) {
            loginBtn.disabled = false;
            loginBtn.classList.remove('hidden');
            if (response.status == "success") {
              const toast = document.getElementById("toast");
              toast.innerHTML = `✅ Mật khẩu mới đã được gửi vào email của bạn<br>`;
              toast.className = "toast show";
              toastSuccess();
              setTimeout(() => {
                  toast.className = toast.className.replace("show", "");
              }, 3000);

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
          loginBtn.disabled = false;
          loginBtn.classList.remove('hidden');
          console.log(xhr);
          const toast = document.getElementById("toast");
          toast.innerHTML = '❌ Lỗi: ' + xhr.responseJSON.message;
          toast.className = "toast show";
          toastError();
          setTimeout(() => {
              toast.className = toast.className.replace("show", "");
          }, 5000);
        }
        });
    });
  </script>
@endsection