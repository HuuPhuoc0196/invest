@extends('Layout.Layout')

@section('title', 'Hiệu xuất Đầu tư')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/adminStockManagement.css')
    <style>
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .sticky-clone,
        .sticky-clone-total {
            position: fixed;
            top: 0;
            z-index: 1002;
            overflow: hidden;
            pointer-events: none;
        }
        .sticky-clone {
            background-color: #34495e;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }
        .sticky-clone-total {
            background-color: #f8f9fa;
            border-top: 2px solid #34495e;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .sticky-clone-total td {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .total-row td {
            background-color: #f8f9fa;
            border-top: 2px solid #34495e;
        }
        main {
            cursor: grab;
        }
        main.dragging {
            cursor: grabbing;
            user-select: none;
        }

        /* Thanh lọc theo ngày (main — một hàng) */
        .inv-perf-filter-wrap {
            max-width: 1440px;
            margin: 0 auto 1.25rem;
            padding: 0 12px;
            box-sizing: border-box;
        }

        .inv-perf-filter-bar {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 10px 14px;
        }

        @media (max-width: 720px) {
            .inv-perf-filter-bar {
                flex-wrap: wrap;
            }
        }

        .inv-perf-filter-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--inv-muted, #64748b);
            flex-shrink: 0;
        }

        .inv-perf-filter-date {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--inv-border, #334155);
            background: var(--inv-surface2, #1e293b);
            color: var(--inv-text, #e2e8f0);
            font-size: 14px;
            min-height: 42px;
            box-sizing: border-box;
        }

        .inv-perf-filter-date:focus {
            outline: none;
            border-color: rgba(56, 189, 248, 0.55);
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.12);
        }

        .inv-perf-filter-btn {
            margin-left: 4px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            background: linear-gradient(135deg, var(--inv-accent, #38bdf8) 0%, #0ea5e9 100%);
            color: #0b0f1a;
            transition: filter 0.15s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .inv-perf-filter-btn:hover {
            filter: brightness(1.06);
        }

        .inv-perf-filter-btn:active {
            filter: brightness(0.98);
        }
    </style>
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
@endsection   --}}

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Lịch sử giao dịch', 'level' => 1])

    <div class="inv-perf-filter-wrap">
        <div class="inv-perf-filter-bar">
            <label for="startDate" class="inv-perf-filter-label">Từ:</label>
            <input type="date" id="startDate" class="inv-perf-filter-date" autocomplete="off">
            <label for="endDate" class="inv-perf-filter-label">Đến:</label>
            <input type="date" id="endDate" class="inv-perf-filter-date" autocomplete="off">
            <button type="button" class="inv-perf-filter-btn" onclick="handleInvestmentPerformance()">🔍 Lọc dữ liệu</button>
        </div>
    </div>

    <div class="table-container">
        <table id="stock-table">
            <thead>
                <tr>
                    <th class="col-code-sticky">Mã CK</th>
                    <th>Khối lượng đặt</th>
                    <th>Giá</th>
                    <th>Giá trị giao dịch</th>
                    <th>Ngày giao dịch</th>
                    <th>Loại giao dịch</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
    </div>
@endsection

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const stocks = @json($stocks);
        var user = null;

        document.addEventListener("DOMContentLoaded", function() {
            user = new User();
            user.renderTableInvest(stocks);
            sortInitInvest(stocks);

            // === JS-based sticky header + sticky total row ===
            const stickyTable = document.getElementById('stock-table');
            const stickyContainer = document.querySelector('.table-container');
            if (stickyTable && stickyContainer) {
                const thead = stickyTable.querySelector('thead');
                let cloneWrap = null;
                let cloneTable = null;
                let totalCloneWrap = null;
                let totalCloneTable = null;

                function createClones() {
                    if (cloneWrap) cloneWrap.remove();
                    cloneWrap = document.createElement('div');
                    cloneWrap.className = 'sticky-clone';
                    cloneTable = document.createElement('table');
                    cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                    cloneTable.appendChild(thead.cloneNode(true));
                    cloneWrap.appendChild(cloneTable);
                    document.body.appendChild(cloneWrap);

                    if (totalCloneWrap) totalCloneWrap.remove();
                    totalCloneWrap = null;
                    totalCloneTable = null;
                    const totalRow = stickyTable.querySelector('.total-row');
                    if (totalRow) {
                        totalCloneWrap = document.createElement('div');
                        totalCloneWrap.className = 'sticky-clone-total';
                        totalCloneTable = document.createElement('table');
                        totalCloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#f8f9fa;margin:0;table-layout:fixed;';
                        const cloneTbody = document.createElement('tbody');
                        cloneTbody.appendChild(totalRow.cloneNode(true));
                        totalCloneTable.appendChild(cloneTbody);
                        totalCloneWrap.appendChild(totalCloneTable);
                        document.body.appendChild(totalCloneWrap);
                        totalCloneWrap.style.display = 'none';
                    }

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
                    if (totalCloneTable) {
                        const totalRow = stickyTable.querySelector('.total-row');
                        if (totalRow) {
                            totalCloneTable.style.width = tableWidth + 'px';
                            const origTds = totalRow.querySelectorAll('td');
                            const cloneTds = totalCloneTable.querySelectorAll('td');
                            origTds.forEach((cell, i) => {
                                if (cloneTds[i]) {
                                    const w = cell.getBoundingClientRect().width;
                                    cloneTds[i].style.boxSizing = 'border-box';
                                    cloneTds[i].style.width = w + 'px';
                                    cloneTds[i].style.minWidth = w + 'px';
                                    cloneTds[i].style.maxWidth = w + 'px';
                                }
                            });
                        }
                    }
                }

                function headerInset() {
                    return typeof window.getStickyHeaderInset === 'function'
                        ? window.getStickyHeaderInset()
                        : (window.innerWidth <= 768 ? 56 : 0);
                }

                function syncScroll() {
                    if (!cloneWrap) return;
                    const containerRect = stickyContainer.getBoundingClientRect();
                    const offset = -stickyContainer.scrollLeft + 'px';
                    const inset = headerInset();
                    cloneWrap.style.left = containerRect.left + 'px';
                    cloneWrap.style.width = containerRect.width + 'px';
                    cloneWrap.style.top = inset + 'px';
                    cloneTable.style.marginLeft = offset;
                    if (totalCloneWrap && totalCloneTable) {
                        totalCloneWrap.style.left = containerRect.left + 'px';
                        totalCloneWrap.style.width = containerRect.width + 'px';
                        totalCloneTable.style.marginLeft = offset;
                        totalCloneWrap.style.top = (inset + thead.offsetHeight) + 'px';
                    }
                }

                function onScroll() {
                    if (!cloneWrap) return;
                    const tableRect = stickyTable.getBoundingClientRect();
                    const theadHeight = thead.offsetHeight;
                    const inset = headerInset();

                    if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
                        cloneWrap.style.display = 'block';
                        syncScroll();
                    } else {
                        cloneWrap.style.display = 'none';
                    }

                    if (totalCloneWrap) {
                        const totalRow = stickyTable.querySelector('.total-row');
                        if (totalRow) {
                            const totalRect = totalRow.getBoundingClientRect();
                            const totalHeight = totalRow.offsetHeight;
                            if (
                                tableRect.top < inset &&
                                totalRect.top <= (inset + theadHeight) &&
                                tableRect.bottom > (inset + theadHeight + totalHeight)
                            ) {
                                totalCloneWrap.style.display = 'block';
                                totalCloneWrap.style.top = (inset + theadHeight) + 'px';
                                syncScroll();
                            } else {
                                totalCloneWrap.style.display = 'none';
                            }
                        }
                    }
                }

                createClones();
                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', function () { createClones(); onScroll(); });
                stickyContainer.addEventListener('scroll', syncScroll, { passive: true });
                onScroll();

                // Re-create clones after table re-renders
                const observer = new MutationObserver(function () {
                    setTimeout(function () { createClones(); onScroll(); }, 50);
                });
                observer.observe(document.getElementById('stockTableBody'), { childList: true });
            }

            // === Drag-to-scroll on main ===
            const mainEl = document.querySelector('main');
            const dragTarget = document.querySelector('.table-container');
            if (mainEl && dragTarget) {
                let isDown = false;
                let startX;
                let scrollLeft;

                mainEl.addEventListener('mousedown', (e) => {
                    if (dragTarget.scrollWidth > dragTarget.clientWidth) {
                        isDown = true;
                        mainEl.classList.add('dragging');
                        startX = e.pageX;
                        scrollLeft = dragTarget.scrollLeft;
                    }
                });
                mainEl.addEventListener('mouseleave', () => {
                    isDown = false;
                    mainEl.classList.remove('dragging');
                });
                mainEl.addEventListener('mouseup', () => {
                    isDown = false;
                    mainEl.classList.remove('dragging');
                });
                mainEl.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const walk = (e.pageX - startX) * 1.5;
                    dragTarget.scrollLeft = scrollLeft - walk;
                });
            }
        });

        function searchStock() {
            user.searchStockInvest(stocks);
        }

        function sortInitInvest(stocks){
            
            // Gọi hàm render lại bảng  
            user.renderTableInvest(stocks);
        }

        // 👇 Hàm lọc và hiển thị theo ngày
        function handleInvestmentPerformance() {
            const startDateInput = document.getElementById('startDate').value;
            const endDateInput = document.getElementById('endDate').value;

            const startDate = startDateInput ? new Date(startDateInput) : null;
            const endDate = endDateInput ? new Date(endDateInput) : new Date(); // nếu không nhập thì mặc định là hôm nay

            // Đảm bảo endDate là cuối ngày
            endDate.setHours(23, 59, 59, 999);

            const filteredStocks = stocks.filter(stock => {
                const dateStr = stock.buy_date || stock.sell_date;
                if (!dateStr) return false;
                const stockDate = new Date(dateStr);

                if (startDate && endDate) {
                    return stockDate >= startDate && stockDate <= endDate;
                } else if (startDate) {
                    return stockDate >= startDate;
                } else if (endDate) {
                    return stockDate <= endDate;
                }

                return true;
            });

            user.renderTableInvest(filteredStocks);
        }
    </script>
@endsection