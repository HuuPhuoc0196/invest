@extends('Layout.LayoutAdmin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập nhật rủi ro cổ phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
    @include('partials.page-title-invest', ['title' => 'Cập nhật rủi ro cổ phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT">
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>
        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Cập nhật</button>
    </div>
        </div>
    </div>
    @include('partials.notify-modal')
@endsection

@section('admin-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = { baseUrl: "{{ url('') }}" };
    </script>
    @vite('resources/js/pages/admin-update-risk.js')
@endsection
