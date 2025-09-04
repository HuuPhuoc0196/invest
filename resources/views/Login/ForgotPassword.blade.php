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

@section('body-content')
    <h2>Quên mật khẩu</h2>
    <form id="forgotForm">
      <div class="input-group">
        <label for="email">Nhập email để khôi phục:</label>
        <input type="email" id="email" required />
      </div>
      <button type="submit" class="login-btn">Khôi phục tài khoản</button>
      <div class="error-message" id="forgotError">Email không tồn tại!</div>
    </form>
    <div class="link-group">
      <a href="{{ url('/login') }}">← Quay lại đăng nhập</a>
    </div>
@endsection

@section('login-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      document.getElementById('forgotForm').addEventListener('submit', function (e) {
        e.preventDefault();
        alert('Liên kết khôi phục đã được gửi!');
        // Xử lý logic gửi email tại đây
      });
  </script>
@endsection