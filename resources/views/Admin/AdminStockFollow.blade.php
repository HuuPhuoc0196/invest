@extends('Layout.LayoutAdmin')

@section('title', 'Admin theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminStockManagement.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('admin-body-content')
    <div class="admin-stocks-page">
        {{-- Toolbar --}}
        <div class="buy-back-bar">
            <a href="{{ route('admin.stocks') }}" class="buy-back-btn">← Quay lại</a>
            <a href="{{ route('admin.stocks.suggest') }}" class="btn-nav">💡 Admin gợi ý</a>
        </div>

        {{-- Title với số lượng --}}
        @include('partials.page-title-invest', [
            'title' => 'Danh sách Admin theo dõi (' . count($stocks) . ')',
            'level' => 1
        ])

        {{-- Wrapper cho button và filter để dùng flexbox order trên mobile --}}
        <div class="admin-stocks-filter-wrapper">
            {{-- Button Thêm gợi ý --}}
            <div class="admin-stocks-add-suggest-bar">
                <button type="button" class="btn-add-suggest-admin" id="btnAddSuggestAdmin" disabled onclick="submitAddSuggestAdmin()">
                    💡 Thêm gợi ý
                </button>
            </div>

            {{-- Filter Panel --}}
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
                                <input type="text" inputmode="numeric" id="filterRatingMin" placeholder="1-10">
                                <span>~</span>
                                <input type="text" inputmode="numeric" id="filterRatingMax" placeholder="1-10">
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>KL trung bình:</label>
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
        </div>{{-- End admin-stocks-filter-wrapper --}}

        {{-- Table --}}
        <div class="table-container">
            <table id="stock-table">
                <thead class="sticky-header">
                    <tr>
                        <th class="col-select th-select-all" id="thSelectAll" onclick="toggleSelectAllSuggest()" title="Gợi ý tất cả">
                        Gợi ý
                        </th>
                        <th class="col-code-sticky" data-sort-key="code" onclick="sortByColumn('code')">Mã CK <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="stocks_vn" onclick="sortByColumn('stocks_vn')">Thuộc VN <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="recommended_buy_price" onclick="sortByColumn('recommended_buy_price')">Giá mua tốt <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="current_price" onclick="sortByColumn('current_price')">Giá hiện tại <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="recommended_sell_price" onclick="sortByColumn('recommended_sell_price')">Giá bán tốt <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="price_avg" onclick="sortByColumn('price_avg')">Giá trung bình <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="risk_level" onclick="sortByColumn('risk_level')">Trạng thái <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="percent_buy" onclick="sortByColumn('percent_buy')">Tỉ lệ mua <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="percent_sell" onclick="sortByColumn('percent_sell')">Tỉ lệ bán <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="rating_stocks" onclick="sortByColumn('rating_stocks')">Điểm <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="volume" onclick="sortByColumn('volume')">Khối lượng <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="volume_avg" onclick="sortByColumn('volume_avg')">KL trung bình <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="valuation" onclick="sortByColumn('valuation')">% Định giá <span class="sort-icon">▲</span></th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody id="stockTableBody">
                </tbody>
            </table>
        </div>

        {{-- Modal Confirm Delete Follow --}}
        <div id="deleteFollowModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <span class="modal-close" onclick="closeDeleteFollowModal()">&times;</span>
                <h2>Xác nhận xoá theo dõi</h2>
                <div class="delete-follow-modal__message">
                    Bạn có chắc chắn muốn xoá mã <b id="deleteFollowCode"></b> khỏi danh sách theo dõi?
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeDeleteFollowModal()">Huỷ</button>
                    <button type="button" class="btn-delete-confirm" id="btnDeleteFollowConfirm" onclick="runDeleteFollow()">Đồng ý</button>
                </div>
            </div>
        </div>

        {{-- Modal Delete Success --}}
        <div id="deleteFollowNoticeModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <span class="modal-close" onclick="closeDeleteFollowNoticeModal()">&times;</span>
                <h2 id="deleteFollowNoticeTitle">Thông báo</h2>
                <div id="deleteFollowNoticeMessage" class="delete-follow-notice-modal__message"></div>
                <div class="modal-actions">
                    <button class="btn-import" id="btnDeleteFollowNoticeOk" onclick="closeDeleteFollowNoticeModal()">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminStockManagement.js')
    <script>
        window.__pageData = {
            baseUrl: @json(url('')),
            stocks: @json($stocks),
            adminSuggestedStockIds: @json($adminSuggestedStockIds)
        };
    </script>
    @vite('resources/js/pages/admin-stock-follow.js')
@endsection
