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

@section('actions-left')
    <div style="display: flex; gap: 5px;">
        <a href="{{ url('/admin') }}" class="button-link">🏠 Trang chủ</a>
        <a href="{{ url('/admin/stocks/insert') }}" class="button-link admin-stocks-action-hide-iphone">➕ Thêm cổ phiếu</a>
        <a href="javascript:void(0)" class="button-link admin-stocks-action-hide-iphone" onclick="confirmExportCsv()">📄 Xuất file csv</a>
        <a href="javascript:void(0)" class="button-link admin-stocks-action-hide-iphone" onclick="openImportModal()">📥 Nhập file csv</a>
    </div>
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('admin-body-content')
    <div class="admin-stocks-page">
        <div class="iphone-admin-stocks-actions">
            <a href="{{ url('/admin/stocks/insert') }}" class="button-link">➕ Thêm cổ phiếu</a>
            <a href="javascript:void(0)" class="button-link" onclick="confirmExportCsv()">📄 Xuất file csv</a>
            <a href="javascript:void(0)" class="button-link" onclick="openImportModal()">📥 Nhập file csv</a>
        </div>

        <h1>Danh sách mã cổ phiếu</h1>

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
                    <label>KL trung bình:</label>
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
                <button class="btn-import" onclick="submitImportCsv()">Nhập dữ liệu</button>
            </div>
        </div>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminStockManagement.js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const stocks = @json($stocks);

        // Sort state
        let currentSortKey = 'valuation';
        let currentSortDir = 'asc';

        function sortByColumn(key) {
            if (currentSortKey === key) {
                currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortKey = key;
                currentSortDir = 'asc';
            }
            updateSortIcons();
            renderStockTable(getFilteredStocks());
        }

        function updateSortIcons() {
            document.querySelectorAll('th[data-sort-key]').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
                const icon = th.querySelector('.sort-icon');
                if (icon) icon.textContent = '⇅';
            });
            document.querySelectorAll('th[data-sort-key="' + currentSortKey + '"]').forEach(th => {
                th.classList.add(currentSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
                const icon = th.querySelector('.sort-icon');
                if (icon) icon.textContent = currentSortDir === 'asc' ? '▲' : '▼';
            });
        }

        // Đợi AdminStockManagement.js (Vite module) load xong rồi mới gọi renderStockTable (tránh lỗi khi script defer chạy sau DOMContentLoaded)
        var renderReadyAttempts = 0;
        var renderReadyMaxAttempts = 200; // ~6 giây
        function runWhenRenderReady() {
            if (typeof window.renderStockTable === 'function') {
                updateSortIcons();
                renderStockTable(stocks);
            } else if (renderReadyAttempts < renderReadyMaxAttempts) {
                renderReadyAttempts++;
                setTimeout(runWhenRenderReady, 30);
            }
        }
        document.addEventListener("DOMContentLoaded", runWhenRenderReady);

        function getFilteredStocks() {
            const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
            const risk = document.getElementById('filterRisk').value;
            const stocksVn = document.getElementById('filterStocksVn').value;
            const ratingMin = document.getElementById('filterRatingMin').value;
            const ratingMax = document.getElementById('filterRatingMax').value;
            const volumeMin = document.getElementById('filterVolumeMin').value;
            const volumeMax = document.getElementById('filterVolumeMax').value;
            const valuationMin = document.getElementById('filterValuationMin').value;
            const valuationMax = document.getElementById('filterValuationMax').value;

            return stocks.filter(stock => {
                // Search by code
                if (keyword && !stock.code.includes(keyword)) return false;

                // Filter: Trạng thái
                if (risk && Number(stock.risk_level) !== Number(risk)) return false;

                // Filter: Thuộc VN - lọc bao gồm theo tầng (30 ⊆ 100 ⊆ ALL ⊆ tất cả)
                if (stocksVn === '30') {
                    if (Number(stock.stocks_vn) !== 30) return false;
                } else if (stocksVn === '100') {
                    if (![30, 100].includes(Number(stock.stocks_vn))) return false;
                }
                // stocksVn === '' (Tất cả) hoặc 'ALL': không lọc thêm

                // Filter: Điểm

                const rating = parseFloat(stock.rating_stocks);
                if (ratingMin !== '' && (isNaN(rating) || rating < parseFloat(ratingMin))) return false;
                if (ratingMax !== '' && (isNaN(rating) || rating > parseFloat(ratingMax))) return false;

                // Filter: Khối lượng trung bình (dùng volume_avg)
                const vol = parseFloat(stock.volume_avg) || 0;
                if (volumeMin !== '' && vol < parseFloat(volumeMin)) return false;
                if (volumeMax !== '' && vol > parseFloat(volumeMax)) return false;

                // Filter: % Định giá (lọc giữa 2 giá trị bất kể nhập x > y hay y > x)
                const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
                const currentPrice = parseFloat(stock.current_price) || 0;
                const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100) : 0;
                let minVal = valuationMin !== '' ? parseFloat(valuationMin) : null;
                let maxVal = valuationMax !== '' ? parseFloat(valuationMax) : null;
                if (minVal !== null && maxVal !== null) {
                    // Swap if min > max
                    if (minVal > maxVal) {
                        const tmp = minVal; minVal = maxVal; maxVal = tmp;
                    }
                    if (valuation < minVal || valuation > maxVal) return false;
                } else if (minVal !== null) {
                    if (valuation < minVal) return false;
                } else if (maxVal !== null) {
                    if (valuation > maxVal) return false;
                }

                return true;
            });
        }

        function searchStock() {
            renderStockTable(getFilteredStocks());
        }

        function applyFilter() {
            renderStockTable(getFilteredStocks());
        }

        function resetFilter() {
            document.getElementById('filterRisk').value = '';
            document.getElementById('filterStocksVn').value = '';
            document.getElementById('filterRatingMin').value = '';
            document.getElementById('filterRatingMax').value = '';
            document.getElementById('filterVolumeMin').value = '';
            document.getElementById('filterVolumeMax').value = '';
            document.getElementById('filterValuationMin').value = '';
            document.getElementById('filterValuationMax').value = '';
            document.getElementById('searchInput').value = '';
            renderStockTable(stocks);
        }

        function toggleFilter() {
            const body = document.getElementById('filterBody');
            const icon = document.getElementById('filterToggleIcon');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.textContent = '▲';
            } else {
                body.style.display = 'none';
                icon.textContent = '▼';
            }
        }

        function confirmExportCsv() {
            if (confirm('Bạn có muốn xuất file CSV không?')) {
                window.location.href = baseUrl + '/admin/stocks/export-csv';
            }
        }
    </script>
@endsection
