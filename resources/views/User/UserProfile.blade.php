@extends('Layout.Layout')

@section('title', 'Tài sản cá nhân')

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
@endsection --}}

@section('actions-left')
    <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
    <a href="{{ url('/user/buy') }}" class="button-link">➕ Mua cổ phiếu</a>
    <a href="{{ url('/user/sell') }}" class="button-link">❌ Bán cổ phiếu</a>
    <a href="{{ url('/user/investment-performance') }}" class="button-link">📈 Hiệu quả đầu tư</a>
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    <h1>Danh sách mã cổ phiếu đang giữ</h1>

    <div class="table-container">
        <table id="stock-table" class="table-wide">
            <thead>
                <tr>
                    <th class="col-code-sticky">Mã cổ phiếu</th>
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
            <thead>
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
        const baseUrl = "{{ url('') }}";
        const userPortfolios = @json($userPortfolios);
        const userInvestCash = @json($userInvestCash);
        var user = null;

        document.addEventListener("DOMContentLoaded", function () {
            user = new User();
            user.renderTableProfile(userPortfolios);
            user.renderInvestTableProfile(userInvestCash);

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
                    // --- Header clone ---
                    if (cloneWrap) cloneWrap.remove();
                    cloneWrap = document.createElement('div');
                    cloneWrap.className = 'sticky-clone';
                    cloneTable = document.createElement('table');
                    cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                    cloneTable.appendChild(thead.cloneNode(true));
                    cloneWrap.appendChild(cloneTable);
                    document.body.appendChild(cloneWrap);

                    // --- Total row clone ---
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
                    // Sync total row widths
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

                function syncScroll() {
                    if (!cloneWrap) return;
                    const containerRect = stickyContainer.getBoundingClientRect();
                    const offset = -stickyContainer.scrollLeft + 'px';
                    cloneWrap.style.left = containerRect.left + 'px';
                    cloneWrap.style.width = containerRect.width + 'px';
                    cloneTable.style.marginLeft = offset;
                    if (totalCloneWrap && totalCloneTable) {
                        totalCloneWrap.style.left = containerRect.left + 'px';
                        totalCloneWrap.style.width = containerRect.width + 'px';
                        totalCloneTable.style.marginLeft = offset;
                    }
                }

                function onScroll() {
                    if (!cloneWrap) return;
                    const tableRect = stickyTable.getBoundingClientRect();
                    const theadHeight = thead.offsetHeight;

                    // Show/hide header clone
                    if (tableRect.top < 0 && tableRect.bottom > theadHeight) {
                        cloneWrap.style.display = 'block';
                        syncScroll();
                    } else {
                        cloneWrap.style.display = 'none';
                    }

                    // Show/hide total row clone
                    if (totalCloneWrap) {
                        const totalRow = stickyTable.querySelector('.total-row');
                        if (totalRow) {
                            const totalRect = totalRow.getBoundingClientRect();
                            const totalHeight = totalRow.offsetHeight;
                            if (tableRect.top < 0 && totalRect.top <= theadHeight && tableRect.bottom > (theadHeight + totalHeight)) {
                                totalCloneWrap.style.display = 'block';
                                totalCloneWrap.style.top = theadHeight + 'px';
                                syncScroll();
                            } else {
                                totalCloneWrap.style.display = 'none';
                            }
                        }
                    }
                }

                createClones();
                window.addEventListener('scroll', onScroll);
                window.addEventListener('resize', function () { createClones(); onScroll(); });
                stickyContainer.addEventListener('scroll', syncScroll);
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
            user.searchStockProfile(userPortfolios);
        }
    </script>
@endsection