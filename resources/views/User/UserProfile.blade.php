@extends('Layout.Layout')

@section('title', 'Tài sản cá nhân')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/adminStockManagement.css')
    @vite('resources/css/pages/user-profile.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

{{-- @section('user-info')
<div class="user-info">
    <img src="{{ asset('images/default-avatar.png') }}" alt="User Avatar" class="avatar">
    <div class="user-details">
        <p class="user-name">👤 {{ Auth::user()->name }}</p>
        <p class="user-email">📧 {{ Auth::user()->email }}</p>
    </div>
</div>
@endsection --}}

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    <div class="asset-action-bar">
        <a href="{{ route('buy') }}" class="asset-action-btn asset-action-btn--buy">➕ Mua cổ phiếu</a>
        <a href="{{ route('sell') }}" class="asset-action-btn asset-action-btn--sell">❌ Bán cổ phiếu</a>
        <a href="{{ route('user.cashIn') }}" class="asset-action-btn asset-action-btn--cash-in">💰 Nạp tiền</a>
        <a href="{{ route('user.cashOut') }}" class="asset-action-btn asset-action-btn--cash-out">💵 Rút tiền</a>
        <a href="{{ route('user.portfolio.exportPdf') }}" class="asset-action-btn" style="background:rgba(129,140,248,0.15);color:#818cf8;border:1px solid rgba(129,140,248,0.3);" target="_blank">📄 Xuất PDF</a>
    </div>

    @php
        $balanceCash = $userCash?->cash ?? 0;
    @endphp
    <div class="profile-balance-strip">
        <div class="profile-balance-left">
            <span class="profile-balance-icon">💰</span>
            <span class="profile-balance-label">Số dư khả dụng</span>
        </div>
        <span class="profile-balance-amount {{ $balanceCash > 0 ? 'is-positive' : 'is-zero' }}">
            {{ number_format($balanceCash, 0, ',', '.') }}&nbsp;VNĐ
        </span>
    </div>

    @include('partials.page-title-invest', ['title' => 'Danh sách mã cổ phiếu đang giữ', 'level' => 1])

    <div class="table-container">
        <table id="stock-table" class="table-wide">
            <thead class="sticky-header">
                <tr>
                    <th class="col-code-sticky">Mã CK</th>
                    <th>Khối lượng nắm giữ</th>
                    <th>Giá vốn</th>
                    <th>Giá hiện tại</th>
                    <th>Giá trị vốn</th>
                    <th>Giá trị thị trường</th>
                    <th>Tiền lãi</th>
                    <th>% lãi</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
        <table id="invest-table" class="table-wide">
            <thead class="sticky-header">
                <tr>
                    <th class="col-code-sticky">Danh mục</th>
                    <th>Vốn đầu tư</th>
                    <th>Giá trị hiện tại</th>
                    <th>Tiền lãi</th>
                    <th>% lãi</th>
                </tr>
            </thead>
            <tbody id="investTableBody">
            </tbody>
        </table>
    </div>
@endsection


@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            userPortfolios: @json($userPortfolios),
            userInvestCash: @json($userInvestCash)
        };
    </script>
    @vite('resources/js/pages/user-profile.js')
@endsection
