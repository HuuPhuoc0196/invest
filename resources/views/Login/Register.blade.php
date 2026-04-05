@extends('Layout.LayoutLogin')
@section('csrf-token')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Đăng ký tài khoản')

@section('seo')
  @include('partials.seo-public', [
      'pageTitle' => 'Đăng ký tài khoản — ' . config('app.name'),
      'description' => 'Tạo tài khoản để theo dõi cổ phiếu, danh mục và cài đặt email — xác thực qua hộp thư đăng ký.',
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
  <h1>Đăng ký</h1>
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
        urlRegisterPost: @json(route('register')),
    };
  </script>
  @vite('resources/js/pages/register.js')
@endsection