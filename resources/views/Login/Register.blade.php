<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Invest</title>
    @vite('resources/js/app.js')
    @vite('resources/css/loginRegister.css')
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

</head>

<body>
  <div class="login-container">
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
      <button type="submit" class="login-btn">Đăng ký</button>
      <div class="error-message" id="registerError">Thông tin không hợp lệ hoặc đã tồn tại!</div>
    </form>
    <div id="toast" class="toast"></div>
    <div class="link-group">
      <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
    </div>
  </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  const baseUrl = "{{ url('') }}";
  const form = document.getElementById('registerForm');
  const nameField = document.getElementById('name');
  const emailField = document.getElementById('email');
  const passwordField = document.getElementById('password');
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
  }

  const showError = (element, message) => {
    document.getElementById(element + 'Error').innerText = message;
  };

  const clearErrors = () => {
    ['name', 'email', 'password'].forEach(field => {
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
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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

    // Nếu hợp lệ
    if (valid) {
      // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
      const data = {
          name: name,
          email: email,
          password: password
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
          if (response.status == "success") {
              const toast = document.getElementById("toast");
              toast.innerHTML = `✅ Đã đăng ký thành công: <b>${email}</b><br>`;
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
    }
  });
</script>
</html>