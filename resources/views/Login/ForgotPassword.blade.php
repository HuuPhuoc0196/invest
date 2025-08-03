<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Invest</title>
    @vite('resources/js/app.js')
    @vite('resources/css/loginRegister.css')
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

</head>

<body>
  <div class="login-container">
    <h2>Quên mật khẩu</h2>
    <form id="forgotForm">
      <div class="input-group">
        <label for="email">Nhập email để khôi phục:</label>
        <input type="email" id="email" required />
      </div>
      <button type="submit" class="login-btn">Gửi liên kết</button>
      <div class="error-message" id="forgotError">Email không tồn tại!</div>
    </form>
    <div class="link-group">
      <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
    </div>
  </div>

  <script>
    document.getElementById('forgotForm').addEventListener('submit', function (e) {
      e.preventDefault();
      alert('Liên kết khôi phục đã được gửi!');
      // Xử lý logic gửi email tại đây
    });
  </script>
</body>

</html>