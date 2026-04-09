@extends('Layout.LayoutAdmin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thêm Mã cổ phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
    @include('partials.page-title-invest', ['title' => 'Thêm Mã cổ phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT">
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua tốt:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100.000">
            <div class="error" id="errorBuy">Vui lòng nhập Giá mua tốt</div>
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="currentPrice">Giá hiện tại:</label>
            <input type="text" id="currentPrice" placeholder="VD: 120.000">
            <div class="error" id="errorCurrent">Vui lòng nhập Giá hiện tại</div>
            <div class="error" id="errorCurrentType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="risk">Trạng thái:</label>
            <select id="risk">
                <option value="1">An toàn</option>
                <option value="2">Cảnh báo</option>
                <option value="3">Hạn chế GD</option>
                <option value="4">Đình chỉ/Huỷ</option>
            </select>
        </div>
        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Thêm mới</button>
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
    @vite('resources/js/pages/admin-insert.js')
@endsection
