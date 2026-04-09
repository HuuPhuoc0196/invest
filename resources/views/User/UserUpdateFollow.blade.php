@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập nhật cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
    @vite('resources/css/pages/user-follow-form.css')
    @vite('resources/css/pages/user-follow.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    <div class="back-bar">
        <a href="{{ url('/user/follow') }}" class="back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Cập nhật cổ phiếu theo dõi'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT" disabled>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="followPriceBuy">Giá mua theo dõi:</label>
            <input type="text" id="followPriceBuy" placeholder="VD: 100.000">
             <div class="error" id="errorFollowPriceBuy">Vui lòng nhập Giá mua</div>
            <div class="error" id="errorFollowPriceBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="followPriceSell">Giá bán theo dõi:</label>
            <input type="text" id="followPriceSell" placeholder="VD: 150.000">
            <div class="error" id="errorFollowPriceSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group form-group-auto-sync">
            <label>Tự động đồng bộ:</label>
            <input type="hidden" id="autoSync" name="autoSync" value="{{ (int) data_get($userFollow, 'auto_sync', 1) }}">
            <button type="button" id="autoSyncToggle" class="auto-sync-toggle auto-sync-on" aria-pressed="true">Bật</button>
        </div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Cập nhật</button>
    </div>
        </div>
    </div>

    {{-- Notify modal --}}
    <div id="update-follow-notify-modal" class="home-notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="home-notify-modal__backdrop"></div>
        <div class="home-notify-modal__box">
            <span class="home-notify-modal__icon" id="updateFollowNotifyIcon"></span>
            <p class="home-notify-modal__msg" id="updateFollowNotifyMsg"></p>
            <button type="button" class="home-notify-modal__close" id="updateFollowNotifyClose">Đóng</button>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            userFollow: @json($userFollow)
        };
    </script>
    @vite('resources/js/pages/user-follow-form.js')
@endsection
