@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Bán Cổ Phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
    @vite('resources/css/pages/user-sell.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    <div class="sell-back-bar">
        <a href="{{ url('/user/profile') }}" class="sell-back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Bán Cổ Phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group form-group-cash-row">
            <label class="cash-title">Số dư: <span class="cash"></span></label>
        </div>

        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <select id="code">
                <option value="">-- Chọn mã cổ phiếu --</option>
            </select>
            <div class="error" id="errorCode">Vui lòng chọn Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="sellPrice">Giá bán:</label>
            <input type="text" id="sellPrice" placeholder="VD: 100.000">
            <div class="error" id="errorSell">Vui lòng nhập Giá bán</div>
            <div class="error" id="errorSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="quantity">Khối lượng bán:</label>
            <input type="text" id="quantity" placeholder="VD: 5000" inputmode="numeric" autocomplete="off">
            <div class="error" id="errorQuantity">Vui lòng nhập Khối lượng bán</div>
            <div class="error" id="errorQuantityType">Vui lòng nhập Số</div>
            <div id="totalAmount" style="font-weight: bold; margin-top: 5px;"></div>
        </div>

        <div class="form-group">
            <label for="sellDate">Ngày bán:</label>
            <input type="date" id="sellDate">
            <div class="error" id="errorSellDate">Vui lòng nhập Ngày bán</div>
            <div class="error" id="errorSellDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Bán</button>
    </div>
        </div>
    </div>

    {{-- Notify modal --}}
    <div id="sell-notify-modal" class="home-notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="home-notify-modal__backdrop" id="sellNotifyBackdrop"></div>
        <div class="home-notify-modal__box">
            <span class="home-notify-modal__icon" id="sellNotifyIcon"></span>
            <p class="home-notify-modal__msg" id="sellNotifyMsg"></p>
            <button type="button" class="home-notify-modal__close" id="sellNotifyClose">Đóng</button>
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
            userPortfolios: @json($userPortfolios)
        };
    </script>
    @vite('resources/js/pages/user-sell.js')
@endsection
