@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Danh sách mã cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/adminStockManagement.css')
    @vite('resources/css/pages/user-follow.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Danh sách mã cổ phiếu theo dõi', 'level' => 1])

    <!-- Filter Panel -->
    <div class="filter-panel">
        <div class="filter-header" onclick="toggleFilter()">
            <span>🔧 Bộ lọc dữ liệu</span>
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
                        <input type="number" id="filterRatingMin" placeholder="Từ" step="0.1">
                        <span>~</span>
                        <input type="number" id="filterRatingMax" placeholder="Đến" step="0.1">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Khối lượng:</label>
                    <div class="filter-range">
                        <input type="number" id="filterVolumeMin" placeholder="Từ">
                        <span>~</span>
                        <input type="number" id="filterVolumeMax" placeholder="Đến">
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

    <div class="table-top-bar">
        <button
            id="btnDeleteAllFollow"
            class="btn-delete"
            type="button"
            disabled
            style="opacity:.6;cursor:not-allowed;"
            onclick="openDeleteAllModal()"
        >
            🗑️ Xoá tất cả
        </button>
    </div>

    <div class="table-container">
        <table id="stock-table">
            <thead class="sticky-header">
                <tr>
                    <th class="col-code-sticky" data-sort-key="code" onclick="sortByColumn('code')">Mã CK <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="stocks_vn" onclick="sortByColumn('stocks_vn')">Thuộc VN <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="recommended_buy_price" onclick="sortByColumn('recommended_buy_price')">Giá mua tốt <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="current_price" onclick="sortByColumn('current_price')">Giá hiện tại <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="recommended_sell_price" onclick="sortByColumn('recommended_sell_price')">Giá bán tốt <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="risk_level" onclick="sortByColumn('risk_level')">Trạng thái <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="rating_stocks" onclick="sortByColumn('rating_stocks')">Điểm <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="volume" onclick="sortByColumn('volume')">Khối lượng <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="valuation" onclick="sortByColumn('valuation')">% Định giá <span class="sort-icon">▲</span></th>
                    <th>Cập nhật</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
    </div>

    {{-- Modal xác nhận xoá từng mã --}}
    <div id="confirmModal" style="display: none; position: fixed; inset: 0; background: rgba(10, 14, 26, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border: 1px solid rgba(248, 113, 113, 0.25); padding: 28px 24px 20px; border-radius: 16px; width: min(90vw, 320px); text-align: center; box-shadow: 0 24px 56px rgba(0, 0, 0, 0.55); display: flex; flex-direction: column; align-items: center; gap: 14px;">
            <span style="font-size: 2rem; line-height: 1;">🗑️</span>
            <p style="margin: 0; color: #e2e8f0; font-size: 15px; font-weight: 500; line-height: 1.5;">Bạn có chắc chắn muốn xoá?</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="confirmYes" style="padding: 9px 24px; border-radius: 10px; border: none; font-size: 14px; font-weight: 700; cursor: pointer; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; font-family: inherit; transition: filter .15s;">Xoá</button>
                <button id="confirmNo" style="padding: 9px 24px; border-radius: 10px; border: 1px solid rgba(99, 179, 237, 0.2); font-size: 14px; font-weight: 600; cursor: pointer; background: #2a3a52; color: #e2e8f0; font-family: inherit; transition: filter .15s;">Không</button>
            </div>
        </div>
    </div>

    {{-- Modal xác nhận xoá tất cả --}}
    <div id="confirmDeleteAllModal" style="display: none; position: fixed; inset: 0; background: rgba(10, 14, 26, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border: 1px solid rgba(248, 113, 113, 0.25); padding: 28px 24px 20px; border-radius: 16px; width: min(90vw, 360px); text-align: center; box-shadow: 0 24px 56px rgba(0, 0, 0, 0.55); display: flex; flex-direction: column; align-items: center; gap: 14px;">
            <span style="font-size: 2rem; line-height: 1;">🗑️</span>
            <p id="deleteAllModalMsg" style="margin: 0; color: #e2e8f0; font-size: 15px; font-weight: 500; line-height: 1.5;">Bạn có chắc muốn xoá tất cả mã đã theo dõi không?</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="confirmDeleteAllYes" style="padding: 9px 24px; border-radius: 10px; border: none; font-size: 14px; font-weight: 700; cursor: pointer; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; font-family: inherit; transition: filter .15s;">Xác nhận</button>
                <button id="confirmDeleteAllNo" style="padding: 9px 24px; border-radius: 10px; border: 1px solid rgba(99, 179, 237, 0.2); font-size: 14px; font-weight: 600; cursor: pointer; background: #2a3a52; color: #e2e8f0; font-family: inherit; transition: filter .15s;">Huỷ bỏ</button>
            </div>
        </div>
    </div>
    {{-- Modal thông báo lỗi --}}
    <div id="errorNotifyModal" style="display: none; position: fixed; inset: 0; background: rgba(10, 14, 26, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border: 1px solid rgba(248, 113, 113, 0.25); padding: 28px 24px 20px; border-radius: 16px; width: min(90vw, 340px); text-align: center; box-shadow: 0 24px 56px rgba(0, 0, 0, 0.55); display: flex; flex-direction: column; align-items: center; gap: 14px;">
            <span style="font-size: 2rem; line-height: 1;">❌</span>
            <p id="errorNotifyMsg" style="margin: 0; color: #fca5a5; font-size: 15px; font-weight: 500; line-height: 1.5;"></p>
            <button id="errorNotifyClose" style="padding: 9px 28px; border-radius: 10px; border: 1px solid rgba(99, 179, 237, 0.2); font-size: 14px; font-weight: 600; cursor: pointer; background: #2a3a52; color: #e2e8f0; font-family: inherit; transition: filter .15s;">Đóng</button>
        </div>
    </div>

    {{-- Modal thông báo thành công --}}
    <div id="successNotifyModal" style="display: none; position: fixed; inset: 0; background: rgba(10, 14, 26, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border: 1px solid rgba(74, 222, 128, 0.25); padding: 28px 24px 20px; border-radius: 16px; width: min(90vw, 340px); text-align: center; box-shadow: 0 24px 56px rgba(0, 0, 0, 0.55); display: flex; flex-direction: column; align-items: center; gap: 14px;">
            <span style="font-size: 2rem; line-height: 1;">✅</span>
            <p id="successNotifyMsg" style="margin: 0; color: #86efac; font-size: 15px; font-weight: 500; line-height: 1.5;"></p>
            <button id="successNotifyClose" style="padding: 9px 28px; border-radius: 10px; border: 1px solid rgba(99, 179, 237, 0.2); font-size: 14px; font-weight: 600; cursor: pointer; background: #2a3a52; color: #e2e8f0; font-family: inherit; transition: filter .15s;">Đóng</button>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            stocks: @json($stocks),
            userFollow: @json($userFollow),
            urlDeleteBatch: "{{ route('user.deleteFollowBatch') }}"
        };
    </script>
    @vite('resources/js/pages/user-follow.js')
@endsection
