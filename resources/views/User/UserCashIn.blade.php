@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Nạp tiền vào tài khoản')

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
        <a href="{{ url('/user/profile') }}" class="back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Nạp tiền vào tài khoản'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">

        <div class="form-group form-group-cash-row">
            <label class="cash-title">Số dư: <span class="cash"></span></label>
        </div>

        <div class="form-group">
            <label for="cashIn">Số tiền cần nạp:</label>
            <input type="text" id="cashIn" placeholder="VD: 1.000.000">
            <div class="error" id="errorCashInEmpty">Vui lòng nhập số tiền nạp</div>
            <div class="error" id="errorCashInType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="cashDate">Ngày nạp:</label>
            <input type="date" id="cashDate" max="{{ date('Y-m-d') }}">
            <div class="error" id="errorCashDate">Vui lòng nhập Ngày nạp</div>
            <div class="error" id="errorCashDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Nạp tiền</button>
    </div>
        </div>
    </div>

    {{-- Notify modal --}}
    <div id="cash-in-notify-modal" class="home-notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="home-notify-modal__backdrop"></div>
        <div class="home-notify-modal__box">
            <span class="home-notify-modal__icon" id="cashInNotifyIcon"></span>
            <p class="home-notify-modal__msg" id="cashInNotifyMsg"></p>
            <button type="button" class="home-notify-modal__close" id="cashInNotifyClose">Đóng</button>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = { baseUrl: "{{ url('') }}", cash: @json($cash) };
    </script>
    @vite('resources/js/pages/user-cash-in.js')
@endsection
