@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập nhật thông tin cá nhân')

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

    @include('partials.page-title-invest', ['title' => 'Cập nhật thông tin cá nhân'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="name">Tên:</label>
            <input type="text" id="name">
            <div class="error" id="errorName">Vui lòng nhập tên của bạn</div>
            <div class="error" id="errorNameLength">Tên phải có ít nhất 2 ký tự.</div>
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
        window.__pageData = { baseUrl: "{{ url('') }}", user: @json($user) };
    </script>
    @vite('resources/js/pages/user-update-info-profile.js')
@endsection
