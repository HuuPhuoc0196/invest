@extends('Layout.Layout')

@section('title', 'Danh sách mã cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/adminStockManagement.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
    <a href="{{ url('/user/insertFollow') }}" class="button-link">➕ Thêm mới</a>
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    <h1>Danh sách mã cổ phiếu theo dõi</h1>

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

    <div class="table-container">
        <table id="stock-table">
            <thead class="sticky-header">
                <tr>
                    <th data-sort-key="code" onclick="sortByColumn('code')">Mã cổ phiếu <span class="sort-icon">⇅</span></th>
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

    <!-- Modal xác nhận xoá -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
            <p>Bạn có chắc chắn muốn xoá?</p>
            <button id="confirmYes">Có</button>
            <button id="confirmNo">Không</button>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const allStocks = @json($stocks);
        const userFollow = @json($userFollow);
        let deleteUrl = "";

        // Merge: chỉ lấy stocks mà user đang follow; Giá mua tốt & Giá bán tốt lấy từ user_follows
        const followMap = {};
        userFollow.forEach(f => {
            followMap[f.code] = {
                follow_price_buy: f.follow_price_buy,
                follow_price_sell: f.follow_price_sell
            };
        });

        const stocks = allStocks
            .filter(s => followMap.hasOwnProperty(s.code))
            .map(s => {
                const merged = Object.assign({}, s);
                const follow = followMap[s.code];
                if (follow.follow_price_buy !== null && follow.follow_price_buy !== undefined) {
                    merged.recommended_buy_price = follow.follow_price_buy;
                }
                if (follow.follow_price_sell !== null && follow.follow_price_sell !== undefined) {
                    merged.recommended_sell_price = follow.follow_price_sell;
                }
                return merged;
            });

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
            renderTable(getFilteredStocks());
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

        function dynamicSort(data) {
            data.sort((a, b) => {
                let valA, valB;
                if (currentSortKey === 'valuation') {
                    const buyA = parseFloat(a.recommended_buy_price) || 1;
                    const curA = parseFloat(a.current_price) || 0;
                    valA = buyA !== 0 ? ((curA - buyA) / buyA) * 100 : 0;
                    const buyB = parseFloat(b.recommended_buy_price) || 1;
                    const curB = parseFloat(b.current_price) || 0;
                    valB = buyB !== 0 ? ((curB - buyB) / buyB) * 100 : 0;
                } else if (currentSortKey === 'code') {
                    valA = (a.code || '').toString();
                    valB = (b.code || '').toString();
                    return currentSortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
                } else {
                    valA = parseFloat(a[currentSortKey]) || 0;
                    valB = parseFloat(b[currentSortKey]) || 0;
                }
                return currentSortDir === 'asc' ? valA - valB : valB - valA;
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateSortIcons();
            renderTable(stocks);

            // Drag-to-scroll
            const container = document.querySelector('.table-container');
            let isDown = false;
            let startX, scrollLeft;

            container.addEventListener('mousedown', function(e) {
                if (e.target.closest('a, button, input, select')) return;
                isDown = true;
                container.style.cursor = 'grabbing';
                startX = e.pageX - container.offsetLeft;
                scrollLeft = container.scrollLeft;
                e.preventDefault();
            });

            container.addEventListener('mouseleave', function() {
                isDown = false;
                container.style.cursor = 'grab';
            });

            container.addEventListener('mouseup', function() {
                isDown = false;
                container.style.cursor = 'grab';
            });

            container.addEventListener('mousemove', function(e) {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - container.offsetLeft;
                const walk = (x - startX) * 2;
                container.scrollLeft = scrollLeft - walk;
            });

            container.style.cursor = 'grab';

            // Modal confirm delete
            document.getElementById("confirmYes").onclick = function () {
                window.location.href = deleteUrl;
            };
            document.getElementById("confirmNo").onclick = function () {
                document.getElementById("confirmModal").style.display = "none";
                deleteUrl = "";
            };

            // JS-based sticky header (works with overflow-x container)
            const stickyTable = document.getElementById('stock-table');
            const stickyContainer = document.querySelector('.table-container');
            if (stickyTable && stickyContainer) {
                const thead = stickyTable.querySelector('thead');
                let cloneTable = null;
                let cloneWrap = null;

                function createClone() {
                    if (cloneWrap) cloneWrap.remove();
                    cloneWrap = document.createElement('div');
                    cloneWrap.className = 'sticky-clone';
                    cloneTable = document.createElement('table');
                    cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                    const cloneThead = thead.cloneNode(true);
                    cloneTable.appendChild(cloneThead);
                    cloneWrap.appendChild(cloneTable);
                    document.body.appendChild(cloneWrap);
                    syncWidths();
                    syncScroll();
                    cloneWrap.style.display = 'none';
                }

                function syncWidths() {
                    if (!cloneTable) return;
                    const origCells = thead.querySelectorAll('th');
                    const cloneCells = cloneTable.querySelectorAll('th');
                    const tableWidth = stickyTable.getBoundingClientRect().width;
                    cloneTable.style.width = tableWidth + 'px';
                    origCells.forEach((cell, i) => {
                        if (cloneCells[i]) {
                            const w = cell.getBoundingClientRect().width;
                            cloneCells[i].style.boxSizing = 'border-box';
                            cloneCells[i].style.width = w + 'px';
                            cloneCells[i].style.minWidth = w + 'px';
                            cloneCells[i].style.maxWidth = w + 'px';
                        }
                    });
                }

                function syncScroll() {
                    if (!cloneWrap) return;
                    const containerRect = stickyContainer.getBoundingClientRect();
                    cloneWrap.style.left = containerRect.left + 'px';
                    cloneWrap.style.width = containerRect.width + 'px';
                    cloneTable.style.marginLeft = -stickyContainer.scrollLeft + 'px';
                }

                function onScroll() {
                    if (!cloneWrap) return;
                    const tableRect = stickyTable.getBoundingClientRect();
                    const theadHeight = thead.offsetHeight;
                    if (tableRect.top < 0 && tableRect.bottom > theadHeight) {
                        cloneWrap.style.display = 'block';
                        syncScroll();
                    } else {
                        cloneWrap.style.display = 'none';
                    }
                }

                createClone();
                window.addEventListener('scroll', onScroll);
                window.addEventListener('resize', function() { createClone(); onScroll(); });
                stickyContainer.addEventListener('scroll', syncScroll);
            }
        });

        function getRisk(rating) {
            switch (Number(rating)) {
                case 1: return { label: 'An toàn', color: '#27ae60' };
                case 2: return { label: 'Cảnh báo', color: '#f39c12' };
                case 3: return { label: 'Hạn chế GD', color: '#e74c3c' };
                case 4: return { label: 'Đình chỉ/Huỷ', color: '#c0392b' };
                default: return { label: 'Chưa xác định', color: '#95a5a6' };
            }
        }

        function getRowClass(goodPrice, currentPrice) {
            if (currentPrice > goodPrice) {
                const percentDiff = ((currentPrice - goodPrice) / goodPrice) * 100;
                return percentDiff <= 10 ? 'yellow' : '';
            } else if (currentPrice <= goodPrice) {
                const percentDiff = ((goodPrice - currentPrice) / goodPrice) * 100;
                if (percentDiff > 20) return 'red';
                else if (percentDiff > 10) return 'purple';
                else return 'green';
            }
        }

        function getRatingBadge(rating) {
            if (rating === null || rating === undefined) {
                return '<span class="rating-badge" style="background-color:#eee;color:#999;">N/A</span>';
            }
            const val = parseFloat(rating);
            let cls = 'rating-medium';
            if (val >= 7) cls = 'rating-high';
            else if (val < 5) cls = 'rating-low';
            return '<span class="rating-badge ' + cls + '">' + val.toFixed(2) + '</span>';
        }

        function confirmDelete(code) {
            deleteUrl = `${baseUrl}/user/deleteFollow/${code}`;
            document.getElementById("confirmModal").style.display = "flex";
        }

        function renderTable(data) {
            const tbody = document.getElementById('stockTableBody');
            tbody.innerHTML = '';

            dynamicSort(data);

            data.forEach(stock => {
                const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
                const currentPrice = parseFloat(stock.current_price) || 0;
                const sellPrice = stock.recommended_sell_price ? Number(stock.recommended_sell_price).toLocaleString('vi-VN') : 'N/A';
                const volume = stock.volume ? Number(stock.volume).toLocaleString('vi-VN') : 'N/A';
                const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100).toFixed(2) : 0;

                let valuationColor = 'yellow';
                let sign = '';
                if (valuation > 0) { valuationColor = 'green'; sign = '+'; }
                else if (valuation < 0) { valuationColor = 'red'; sign = ''; }

                const row = document.createElement('tr');
                row.className = getRowClass(buyPrice, currentPrice);
                row.innerHTML = `
                    <td><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
                    <td>${[30, 100].includes(Number(stock.stocks_vn)) ? Number(stock.stocks_vn) : 'ALL'}</td>
                    <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
                    <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
                    <td>${sellPrice}</td>
                    <td style="color: ${getRisk(stock.risk_level).color}">
                        ${getRisk(stock.risk_level).label}
                    </td>
                    <td>${getRatingBadge(stock.rating_stocks)}</td>
                    <td>${volume}</td>
                    <td style="color: ${valuationColor}; font-weight: bold;">${sign}${valuation}%</td>
                    <td style="display:flex;gap:4px;">
                        <button class="btn-filter" onclick="location.href='${baseUrl}/user/updateFollow/${stock.code}'">✏️ Cập nhật</button>
                        <button class="btn-delete" onclick="confirmDelete('${stock.code}')">🗑️ Xoá</button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Re-sync sticky header clone after table re-render
            window.dispatchEvent(new Event('resize'));
        }

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
                if (keyword && !stock.code.includes(keyword)) return false;
                if (risk && Number(stock.risk_level) !== Number(risk)) return false;

                // Thuộc VN: lọc bao gồm theo tầng (30 ⊆ 100 ⊆ ALL ⊆ tất cả)
                if (stocksVn === '30') {
                    if (Number(stock.stocks_vn) !== 30) return false;
                } else if (stocksVn === '100') {
                    if (![30, 100].includes(Number(stock.stocks_vn))) return false;
                }
                // stocksVn === '' (Tất cả) hoặc 'ALL': không lọc thêm

                const rating = parseFloat(stock.rating_stocks);
                if (ratingMin !== '' && (isNaN(rating) || rating < parseFloat(ratingMin))) return false;
                if (ratingMax !== '' && (isNaN(rating) || rating > parseFloat(ratingMax))) return false;

                const vol = parseFloat(stock.volume) || 0;
                if (volumeMin !== '' && vol < parseFloat(volumeMin)) return false;
                if (volumeMax !== '' && vol > parseFloat(volumeMax)) return false;

                const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
                const currentPrice = parseFloat(stock.current_price) || 0;
                const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100) : 0;
                let minVal = valuationMin !== '' ? parseFloat(valuationMin) : null;
                let maxVal = valuationMax !== '' ? parseFloat(valuationMax) : null;
                if (minVal !== null && maxVal !== null) {
                    if (minVal > maxVal) { const tmp = minVal; minVal = maxVal; maxVal = tmp; }
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
            renderTable(getFilteredStocks());
        }

        function applyFilter() {
            renderTable(getFilteredStocks());
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
            renderTable(stocks);
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
    </script>
@endsection