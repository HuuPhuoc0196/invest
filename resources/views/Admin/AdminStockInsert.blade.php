@extends('Layout.LayoutAdmin')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thêm cổ phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminStockInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
    <div class="buy-back-bar">
        <a href="{{ url('/admin/stocks') }}" class="buy-back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Thêm cổ phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu: <span class="required">*</span></label>
            <input type="text" id="code" placeholder="VD: FPT">
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="currentPrice">Giá hiện tại: <span class="required">*</span></label>
            <input type="text" id="currentPrice" placeholder="VD: 120.000" value="10000">
            <div class="error" id="errorCurrent">Vui lòng nhập Giá hiện tại</div>
            <div class="error" id="errorCurrentType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="priceAvg">Giá trung bình:</label>
            <input type="text" id="priceAvg" placeholder="VD: 110,000">
            <div class="error" id="errorPriceAvgType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua tốt:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100.000" value="10000">
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="sellPrice">Giá bán tốt:</label>
            <input type="text" id="sellPrice" placeholder="VD: 150.000" value="10000">
            <div class="error" id="errorSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="percentBuy">Tỉ lệ mua (%):</label>
            <input type="text" id="percentBuy" placeholder="VD: 80" value="100">
            <div class="error" id="errorPercentBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="percentSell">Tỉ lệ bán (%):</label>
            <input type="text" id="percentSell" placeholder="VD: 120" value="100">
            <div class="error" id="errorPercentSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="risk">Trạng thái: <span class="required">*</span></label>
            <select id="risk">
                <option value="1">An toàn</option>
                <option value="2">Cảnh báo</option>
                <option value="3">Hạn chế GD</option>
                <option value="4" selected>Đình chỉ/Huỷ</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ratingStocks">Điểm:</label>
            <input type="text" id="ratingStocks" placeholder="VD: 8.5">
            <div class="error" id="errorRatingType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="stocksVn">Thuộc VN:</label>
            <input type="text" id="stocksVn" placeholder="VD: 1000" value="1000">
            <div class="error" id="errorStocksVnType">Vui lòng nhập Số</div>
        </div>

        <button type="button" id="btnFormSubmit" onclick="submitStockForm()" disabled>Thêm mới</button>
    </div>
        </div>
    </div>

    <div id="admin-insert-notify-modal" class="home-notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="home-notify-modal__backdrop" id="adminInsertNotifyBackdrop"></div>
        <div class="home-notify-modal__box">
            <span class="home-notify-modal__icon" id="adminInsertNotifyIcon"></span>
            <p class="home-notify-modal__msg" id="adminInsertNotifyMsg"></p>
            <button type="button" class="home-notify-modal__close" id="adminInsertNotifyClose">Đóng</button>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminStockInsert.js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = { baseUrl: "{{ url('') }}" };
    </script>
@endsection
