@extends('Layout.LayoutAdmin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập Nhật Mã cổ phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminStockInsert.css')
    @vite('resources/css/pages/admin-stock-update.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-right')
    <button type="button" class="button-link admin-update-sync-btn" onclick="openSyncStockModal()">🔄 Cập nhật cổ phiếu</button>
@endsection

@section('admin-body-content')
    @include('partials.page-title-invest', ['title' => 'Cập Nhật Mã cổ phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu: <span class="required">*</span></label>
            <input type="text" id="code" placeholder="VD: FPT" disabled>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="currentPrice">Giá hiện tại: <span class="required">*</span></label>
            <input type="text" id="currentPrice" placeholder="VD: 120,000">
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
            <input type="text" id="buyPrice" placeholder="VD: 100,000">
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="sellPrice">Giá bán tốt:</label>
            <input type="text" id="sellPrice" placeholder="VD: 150,000">
            <div class="error" id="errorSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="percentBuy">Tỉ lệ mua (%):</label>
            <input type="text" id="percentBuy" placeholder="VD: 80">
            <div class="error" id="errorPercentBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="percentSell">Tỉ lệ bán (%):</label>
            <input type="text" id="percentSell" placeholder="VD: 120">
            <div class="error" id="errorPercentSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="risk">Trạng thái: <span class="required">*</span></label>
            <select id="risk">
                <option value="1">An toàn</option>
                <option value="2">Cảnh báo</option>
                <option value="3">Hạn chế GD</option>
                <option value="4">Đình chỉ/Huỷ</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ratingStocks">Điểm:</label>
            <input type="text" id="ratingStocks" placeholder="VD: 8.5">
            <div class="error" id="errorRatingType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="stocksVn">Thuộc VN:</label>
            <input type="text" id="stocksVn" placeholder="VD: 1000">
            <div class="error" id="errorStocksVnType">Vui lòng nhập Số</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitUpdateForm()" disabled>Cập nhật</button>
    </div>
        </div>
    </div>
    <!-- Modal confirm sync update stock -->
    <div id="syncStockModal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width:350px;">
            <span class="modal-close" onclick="closeSyncStockModal()">&times;</span>
            <h2 style="font-size:20px;margin-bottom:18px;">Xác nhận cập nhật cổ phiếu</h2>
            <div style="margin-bottom:18px;">Bạn có chắc chắn muốn cập nhật dữ liệu cho mã <b id="syncStockCode"></b>?</div>
            <div style="display:flex;justify-content:center;gap:12px;">
                <button class="btn-cancel" onclick="closeSyncStockModal()">Huỷ</button>
                <button class="btn-import" id="btnSyncStock" onclick="runSyncStock()">Đồng ý</button>
            </div>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminStockUpdate.js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            stockData: @json($stock)
        };
    </script>
    @vite('resources/js/pages/admin-stock-update.js')
@endsection