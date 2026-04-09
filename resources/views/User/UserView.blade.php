@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Danh sách mã cổ phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/pages/user-home.css')
    @vite('resources/css/adminStockManagement.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @auth
        @include('partials.user-nav-primary')
    @else
        @include('partials.guest-nav-actions')
    @endauth
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Danh sách mã cổ phiếu', 'level' => 1])

    <!-- Filter Panel -->
    <div class="filter-panel">
        <div class="filter-header" onclick="toggleFilter()">
            <span>✏️ Bộ lọc dữ liệu</span>
            <span id="filterToggleIcon">▼</span>
        </div>
        <div id="filterBody" class="filter-body" style="display:none;">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Trạng thái:</label>
                    <select id="filterRisk">
                        <option value="">-- Tất cả --</option>
                        <option value="1">An toàn</option>
                        <option value="2">Cảnh báo</option>
                        <option value="3">Hạn chế GD</option>
                        <option value="4">Đình chỉ/Huỷ</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Thuộc VN:</label>
                    <select id="filterStocksVn">
                        <option value="">-- Tất cả --</option>
                        <option value="30">30</option>
                        <option value="100">100</option>
                        <option value="ALL">ALL</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Điểm:</label>
                    <div class="filter-range">
                        <input type="text" inputmode="numeric" id="filterRatingMin" placeholder="1-10">
                        <span>~</span>
                        <input type="text" inputmode="numeric" id="filterRatingMax" placeholder="1-10">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Khối lượng:</label>
                    <div class="filter-range">
                        <input type="text" inputmode="numeric" id="filterVolumeMin" placeholder="Từ">
                        <span>~</span>
                        <input type="text" inputmode="numeric" id="filterVolumeMax" placeholder="Đến">
                    </div>
                </div>
                <div class="filter-group">
                    <label>% Định giá:</label>
                    <div class="filter-range">
                        <input type="number" id="filterValuationMin" placeholder="Từ" step="0.01">
                        <span>~</span>
                        <input type="number" id="filterValuationMax" placeholder="Đến" step="0.01">
                    </div>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn-filter" onclick="applyFilter()">🔍 Lọc</button>
                <button class="btn-filter-reset" onclick="resetFilter()">🔄 Đặt lại</button>
            </div>
        </div>
    </div>

    @auth
    <div class="home-add-follow-bar">
        <button type="button" class="btn-filter btn-add-follow-home" id="btnAddFollow" disabled onclick="submitAddFollowBatch()">➕ Thêm theo dõi</button>
    </div>
    @endauth

    <div class="table-container">
        <table id="stock-table">
            <thead class="sticky-header">
                <tr>
                    @auth
                    <th class="col-select th-select-all" id="thSelectAll" onclick="toggleSelectAll()" title="Theo dõi tất cả">
                        <span class="th-select-all__inner">
                            <span class="th-select-all__label">Theo dõi</span>
                            <span class="th-select-all__state" aria-hidden="true"></span>
                        </span>
                    </th>
                    @endauth
                    <th class="col-code-sticky" data-sort-key="code" onclick="sortByColumn('code')">Mã CK <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="stocks_vn" onclick="sortByColumn('stocks_vn')">Thuộc VN <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="recommended_buy_price" onclick="sortByColumn('recommended_buy_price')">Giá mua tốt <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="current_price" onclick="sortByColumn('current_price')">Giá hiện tại <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="recommended_sell_price" onclick="sortByColumn('recommended_sell_price')">Giá bán tốt <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="risk_level" onclick="sortByColumn('risk_level')">Trạng thái <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="rating_stocks" onclick="sortByColumn('rating_stocks')">Điểm <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="volume" onclick="sortByColumn('volume')">Khối lượng <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="valuation" onclick="sortByColumn('valuation')">% Định giá <span class="sort-icon">▲</span></th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
    </div>

    {{-- Notify modal --}}
    <div id="home-notify-modal" class="home-notify-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="home-notify-modal__backdrop" id="homeNotifyBackdrop"></div>
        <div class="home-notify-modal__box">
            <span class="home-notify-modal__icon" id="homeNotifyIcon"></span>
            <p class="home-notify-modal__msg" id="homeNotifyMsg"></p>
            <button type="button" class="home-notify-modal__close" id="homeNotifyClose">Đóng</button>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            stocks: @json($stocks),
            userFollowedCodes: @json($userFollowedCodes ?? []),
            isLoggedIn: {{ auth()->check() ? 'true' : 'false' }}
        };
    </script>
    @vite('resources/js/pages/user-home.js')
@endsection
