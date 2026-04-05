@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Mua Cổ Phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
    @vite('resources/css/pages/user-buy.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    <div class="buy-back-bar">
        <a href="{{ url('/user/profile') }}" class="buy-back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Mua Cổ Phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group form-group-cash-row">
            <label class="cash-title">Số dư: <span class="cash"></span></label>
        </div>

        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <div class="code-input-with-check">
                <input type="text" id="code" placeholder="VD: FPT" autocomplete="off">
                <button type="button" id="btnCheckCode" class="btn-check-stock-code" disabled>Kiểm tra</button>
            </div>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100.000">
            <div class="error" id="errorBuy">Vui lòng nhập Giá mua</div>
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
            <div class="error" id="errorBuyRange">Giá mua không hợp lệ!</div>
        </div>

        <div class="form-group">
            <label for="quantity">Khối lượng giao dịch:</label>
            <input type="text" id="quantity" placeholder="VD: 5000" inputmode="numeric" autocomplete="off">
            <div class="error" id="errorQuantity">Vui lòng nhập Khối lượng giao dịch</div>
            <div class="error" id="errorQuantityType">Vui lòng nhập Số</div>
            <div class="error" id="errorQuantityRange">Khối lượng giao dịch không hợp lệ!</div>

            <div id="totalAmount" style="color: red; font-weight: bold; margin-top: 5px;"></div>
            <div class="error" id="errorCashBuyType" style="color: red; font-weight: bold; margin-top: 5px;">Số dư
                không đủ</div>
        </div>

        <div class="form-group">
            <label for="buyDate">Ngày mua:</label>
            <input type="date" id="buyDate">
            <div class="error" id="errorBuyDate">Vui lòng nhập Ngày mua</div>
            <div class="error" id="errorBuyDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Mua</button>
    </div>
        </div>
    </div>

    {{-- Notify modal --}}
    <div id="buy-notify-modal" class="home-notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="home-notify-modal__backdrop" id="buyNotifyBackdrop"></div>
        <div class="home-notify-modal__box">
            <span class="home-notify-modal__icon" id="buyNotifyIcon"></span>
            <p class="home-notify-modal__msg" id="buyNotifyMsg"></p>
            <button type="button" class="home-notify-modal__close" id="buyNotifyClose">Đóng</button>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            cash: @json($cash),
            buyPriceMax: {{ json_encode((float) ($buyPriceMax ?? '9999999999999.99')) }},
            quantityMax: {{ min((int) ($quantityMax ?? PHP_INT_MAX), 9007199254740991) }}
        };
    </script>
    @vite('resources/js/pages/user-buy.js')
@endsection
