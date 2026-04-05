@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thay đổi mật khẩu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    <div class="back-bar">
        <a href="{{ url('/user/infoProfile') }}" class="back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Thay đổi mật khẩu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="password">Mật khẩu:</label>
            <input type="password" id="password">
            <div class="error" id="errorPassword">Vui lòng nhập mật khẩu</div>
            <div class="error" id="errorPasswordLength">Mật khẩu phải có ít nhất 6 ký tự.</div>
        </div>

        <div class="form-group">
            <label for="newPassword">Mật khẩu mới:</label>
            <input type="password" id="newPassword">
            <div class="error" id="errorNewPassword">Vui lòng nhập mật khẩu</div>
            <div class="error" id="errorNewPasswordLength">Mật khẩu phải có ít nhất 6 ký tự.</div>
        </div>

        <div class="form-group">
            <label for="reNewPassword">Nhập lại mật khẩu mới:</label>
            <input type="password" id="reNewPassword">
            <div class="error" id="errorReNewPassword">Vui lòng nhập mật khẩu</div>
            <div class="error" id="errorReNewPasswordRe">Nhập lại mật khẩu không đúng</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Cập nhật</button>
    </div>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = { baseUrl: "{{ url('') }}" };
    </script>
    @vite('resources/js/pages/user-change-password.js')
@endsection
