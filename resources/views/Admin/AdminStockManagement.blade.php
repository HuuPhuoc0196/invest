@extends('Layout.LayoutAdmin')

@section('title', 'Quản lý cổ phiếu')

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
        @include('partials.page-title-invest', ['title' => 'Danh sách mã cổ phiếu', 'level' => 1])
        @if (session('success'))
            <div class="admin-stock-flash admin-stock-flash--success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-stock-flash admin-stock-flash--error">{{ session('error') }}</div>
        @endif
        <div class="admin-stocks-main-actions">
            <a href="{{ url('/admin/stocks/insert') }}" class="button-link btn-admin-main-action btn-admin-main-action--add">➕ Thêm cổ phiếu</a>
            <a href="javascript:void(0)" class="button-link btn-admin-main-action btn-admin-main-action--export" onclick="confirmExportCsv()">📄 Xuất file csv</a>
            <a href="javascript:void(0)" class="button-link btn-admin-main-action btn-admin-main-action--import" onclick="openImportModal()">📥 Nhập file csv</a>
        </div>

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

        <div class="table-container">
        <table id="stock-table">
            <thead class="sticky-header">
                <tr>
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
                    <th data-sort-key="volume_avg" onclick="sortByColumn('volume_avg')">Khối lượng trung bình <span class="sort-icon">⇅</span></th>
                    <th data-sort-key="valuation" onclick="sortByColumn('valuation')">% Định giá <span class="sort-icon">▲</span></th>
                    <th>Cập nhật</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
    </div>

        <!-- Modal Confirm Export CSV -->
        <div id="exportCsvModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeExportCsvModal()">&times;</span>
            <h2>Xác nhận xuất file CSV</h2>
            <div class="export-csv-modal__message">
                Bạn có chắc chắn muốn xuất toàn bộ danh sách cổ phiếu ra file CSV?
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeExportCsvModal()">Huỷ</button>
                <button type="button" class="btn-import" id="btnExportCsvConfirm" onclick="runExportCsv()">Đồng ý</button>
            </div>
        </div>
        </div>

        <!-- Modal Confirm Delete Stock -->
        <div id="deleteStockModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeDeleteStockModal()">&times;</span>
            <h2>Xác nhận xoá mã cổ phiếu</h2>
            <div class="delete-stock-modal__message">
                Bạn có chắc chắn muốn xoá mã <b id="deleteStockCode"></b>?
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeDeleteStockModal()">Huỷ</button>
                <button type="button" class="btn-delete-confirm" id="btnDeleteStockConfirm" onclick="runDeleteStock()">Xoá</button>
            </div>
        </div>
        </div>

        <!-- Modal Import CSV -->
        <div id="importCsvModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeImportModal()">&times;</span>
            <h2>Thêm cổ phiếu bằng file CSV</h2>
            <div id="dropZone" class="drop-zone" onclick="document.getElementById('csvFileInput').click()">
                <input type="file" id="csvFileInput" accept=".csv" style="display:none;">
                <div id="dropZoneText">
                    <span style="font-size:40px;">📁</span>
                    <p>Click để chọn file CSV hoặc kéo thả vào đây</p>
                </div>
                <div id="fileInfo" style="display:none;">
                    <span style="font-size:30px;">✅</span>
                    <p id="fileName"></p>
                </div>
            </div>
            <div id="importResult" class="import-result" style="display:none;"></div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeImportModal()">Huỷ</button>
                <button type="button" class="btn-import" id="btnImportCsvSubmit" onclick="submitImportCsv()" disabled>Nhập dữ liệu</button>
            </div>
        </div>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminStockManagement.js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = { baseUrl: @json(url('')), stocks: @json($stocks) };
    </script>
    @vite('resources/js/pages/admin-stock-management.js')
@endsection
