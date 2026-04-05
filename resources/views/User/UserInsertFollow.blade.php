@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thêm cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
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

    <div class="follow-action-bar">
        <a href="{{ url('/user/follow') }}" class="follow-action-btn follow-action-btn--list">📋 Danh sách theo dõi</a>
        <a href="{{ route('insertFollow') }}" class="follow-action-btn follow-action-btn--add follow-action-btn--active">➕ Thêm theo dõi</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Thêm cổ phiếu theo dõi'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <div style="display: flex; gap: 8px; align-items: center;">
                <input type="text" id="code" placeholder="VD: FPT" style="flex: 1; min-width: 0;">
                <button type="button" id="btnCheckCode" onclick="checkStockCode()" disabled
                    style="width: auto; white-space: nowrap; padding: 6px 12px; border: none; border-radius: 5px; cursor: not-allowed; background: #ccc; color: #666; font-size: 13px; transition: all 0.3s; flex-shrink: 0;">
                    🔍 Kiểm tra
                </button>
            </div>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="followPriceBuy">Giá mua theo dõi:</label>
            <input type="text" id="followPriceBuy" placeholder="VD: 100.000">
            <div class="error" id="errorFollowPriceBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="followPriceSell">Giá bán theo dõi:</label>
            <input type="text" id="followPriceSell" placeholder="VD: 150.000">
            <div class="error" id="errorFollowPriceSellType">Vui lòng nhập Số</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Thêm</button>
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
    @vite('resources/js/pages/user-insert-follow.js')
@endsection
