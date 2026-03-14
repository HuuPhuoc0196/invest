@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cài đặt thông báo email')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    <style>
        .section-panel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            background: #34495e;
            color: white;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            user-select: none;
        }
        .section-header:hover {
            background: #3d566e;
        }
        .section-body {
            padding: 20px;
        }
        .btn-save {
            padding: 10px 24px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }
        .btn-save:hover:not(:disabled) {
            background-color: #219a52;
        }
        .btn-save:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            min-width: 600px;
        }
        th, td {
            padding: 10px 14px;
            text-align: center;
        }
        .checkbox-all {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        .checkbox-row {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        .checkbox-row-session {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        .toast {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 14px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .toast.show { display: block; }
        .toast-success { background-color: #27ae60; }
        .toast-error { background-color: #e74c3c; }
        .save-bar {
            display: flex;
            justify-content: flex-end;
        }
        .sticky-clone {
            position: fixed;
            top: 0;
            z-index: 1002;
            overflow: hidden;
            background-color: #34495e;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            pointer-events: auto;
        }
    </style>
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/home') }}" class="button-link">🏠 Trang chủ</a>
@endsection

@section('user-body-content')
    <h1>Cài đặt thông báo email</h1>

    <div class="section-panel">
        <div class="section-header" onclick="toggleSectionFollow()">
            <span>🔔 Thông báo email cổ phiếu đã theo dõi</span>
            <span id="sectionToggleIconFollow">▼</span>
        </div>
        <div id="sectionBodyFollow" class="section-body" style="display:none;">
            <div class="save-bar">
                <button class="btn-save" id="btnSaveFollow" onclick="saveFlagsFollow()">💾 Lưu</button>
            </div>

            <div class="table-container table-container-follow">
                <table id="notice-table-follow">
                    <thead>
                        <tr>
                            <th>Mã cổ phiếu</th>
                            <th>Giá mua theo dõi</th>
                            <th>Giá bán theo dõi</th>
                            <th><input type="checkbox" class="checkbox-all" id="checkAllFollow" onclick="toggleAllFollow()"></th>
                        </tr>
                    </thead>
                    <tbody id="noticeTableBodyFollow"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-panel">
        <div class="section-header" onclick="toggleSectionSession()">
            <span>⏰ Thông báo email cuối phiên cho cổ phiếu đã mua</span>
            <span id="sectionToggleIconSession">▼</span>
        </div>
        <div id="sectionBodySession" class="section-body" style="display:none;">
            <div class="save-bar">
                <button class="btn-save" id="btnSaveSession" onclick="saveFlagsSession()">💾 Lưu</button>
            </div>

            <div class="table-container table-container-session">
                <table id="notice-table-session">
                    <thead>
                        <tr>
                            <th>Mã cổ phiếu</th>
                            <th><input type="checkbox" class="checkbox-all" id="checkAllSession" onclick="toggleAllSession()"></th>
                        </tr>
                    </thead>
                    <tbody id="noticeTableBodySession"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>
@endsection

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const noticesFollow = @json($noticesFollow);
        const sessionClosedItems = @json($sessionClosedItems);
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.addEventListener("DOMContentLoaded", function () {
            renderTableFollow(noticesFollow);
            renderTableSession(sessionClosedItems);

            // === JS clone-based sticky header for FOLLOW table ===
            const stickyTableFollow = document.getElementById('notice-table-follow');
            const stickyContainerFollow = document.querySelector('.table-container-follow');
            if (stickyTableFollow && stickyContainerFollow) {
                const theadFollow = stickyTableFollow.querySelector('thead');
                let cloneWrapFollow = null;
                let cloneTableFollow = null;

                function createCloneFollow() {
                    if (cloneWrapFollow) cloneWrapFollow.remove();
                    cloneWrapFollow = document.createElement('div');
                    cloneWrapFollow.className = 'sticky-clone';
                    cloneTableFollow = document.createElement('table');
                    cloneTableFollow.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                    const cloneThead = theadFollow.cloneNode(true);
                    const cloneCheckAll = cloneThead.querySelector('#checkAllFollow');
                    if (cloneCheckAll) {
                        cloneCheckAll.removeAttribute('id');
                        cloneCheckAll.addEventListener('click', function() {
                            const realCheckAll = document.getElementById('checkAllFollow');
                            realCheckAll.checked = this.checked;
                            toggleAllFollow();
                        });
                    }
                    cloneTableFollow.appendChild(cloneThead);
                    cloneWrapFollow.appendChild(cloneTableFollow);
                    document.body.appendChild(cloneWrapFollow);
                    syncWidthsFollow();
                    syncScrollFollow();
                    cloneWrapFollow.style.display = 'none';
                }

                function syncWidthsFollow() {
                    if (!cloneTableFollow) return;
                    const origCells = theadFollow.querySelectorAll('th');
                    const cloneCells = cloneTableFollow.querySelectorAll('th');
                    const tableWidth = stickyTableFollow.getBoundingClientRect().width;
                    cloneTableFollow.style.width = tableWidth + 'px';
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

                function syncScrollFollow() {
                    if (!cloneWrapFollow) return;
                    const containerRect = stickyContainerFollow.getBoundingClientRect();
                    cloneWrapFollow.style.left = containerRect.left + 'px';
                    cloneWrapFollow.style.width = containerRect.width + 'px';
                    cloneTableFollow.style.marginLeft = -stickyContainerFollow.scrollLeft + 'px';
                }

                function syncCheckAllFollow() {
                    if (!cloneWrapFollow) return;
                    const realCheckAll = document.getElementById('checkAllFollow');
                    const cloneCheckbox = cloneWrapFollow.querySelector('input[type=checkbox]');
                    if (cloneCheckbox && realCheckAll) {
                        cloneCheckbox.checked = realCheckAll.checked;
                    }
                }

                function onScrollFollow() {
                    if (!cloneWrapFollow) return;
                    const tableRect = stickyTableFollow.getBoundingClientRect();
                    const theadHeight = theadFollow.offsetHeight;
                    if (tableRect.top < 0 && tableRect.bottom > theadHeight) {
                        cloneWrapFollow.style.display = 'block';
                        syncScrollFollow();
                        syncCheckAllFollow();
                    } else {
                        cloneWrapFollow.style.display = 'none';
                    }
                }

                createCloneFollow();
                window.addEventListener('scroll', onScrollFollow);
                window.addEventListener('resize', function() { createCloneFollow(); onScrollFollow(); });
                stickyContainerFollow.addEventListener('scroll', syncScrollFollow);

                const observerFollow = new MutationObserver(function() {
                    setTimeout(function() { createCloneFollow(); onScrollFollow(); }, 50);
                });
                observerFollow.observe(document.getElementById('noticeTableBodyFollow'), { childList: true });
            }

            // === JS clone-based sticky header for SESSION table ===
            const stickyTableSession = document.getElementById('notice-table-session');
            const stickyContainerSession = document.querySelector('.table-container-session');
            if (stickyTableSession && stickyContainerSession) {
                const theadSession = stickyTableSession.querySelector('thead');
                let cloneWrapSession = null;
                let cloneTableSession = null;

                function createCloneSession() {
                    if (cloneWrapSession) cloneWrapSession.remove();
                    cloneWrapSession = document.createElement('div');
                    cloneWrapSession.className = 'sticky-clone';
                    cloneTableSession = document.createElement('table');
                    cloneTableSession.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                    const cloneThead = theadSession.cloneNode(true);
                    const cloneCheckAll = cloneThead.querySelector('#checkAllSession');
                    if (cloneCheckAll) {
                        cloneCheckAll.removeAttribute('id');
                        cloneCheckAll.addEventListener('click', function() {
                            const realCheckAll = document.getElementById('checkAllSession');
                            realCheckAll.checked = this.checked;
                            toggleAllSession();
                        });
                    }
                    cloneTableSession.appendChild(cloneThead);
                    cloneWrapSession.appendChild(cloneTableSession);
                    document.body.appendChild(cloneWrapSession);
                    syncWidthsSession();
                    syncScrollSession();
                    cloneWrapSession.style.display = 'none';
                }

                function syncWidthsSession() {
                    if (!cloneTableSession) return;
                    const origCells = theadSession.querySelectorAll('th');
                    const cloneCells = cloneTableSession.querySelectorAll('th');
                    const tableWidth = stickyTableSession.getBoundingClientRect().width;
                    cloneTableSession.style.width = tableWidth + 'px';
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

                function syncScrollSession() {
                    if (!cloneWrapSession) return;
                    const containerRect = stickyContainerSession.getBoundingClientRect();
                    cloneWrapSession.style.left = containerRect.left + 'px';
                    cloneWrapSession.style.width = containerRect.width + 'px';
                    cloneTableSession.style.marginLeft = -stickyContainerSession.scrollLeft + 'px';
                }

                function syncCheckAllSession() {
                    if (!cloneWrapSession) return;
                    const realCheckAll = document.getElementById('checkAllSession');
                    const cloneCheckbox = cloneWrapSession.querySelector('input[type=checkbox]');
                    if (cloneCheckbox && realCheckAll) {
                        cloneCheckbox.checked = realCheckAll.checked;
                    }
                }

                function onScrollSession() {
                    if (!cloneWrapSession) return;
                    const tableRect = stickyTableSession.getBoundingClientRect();
                    const theadHeight = theadSession.offsetHeight;
                    if (tableRect.top < 0 && tableRect.bottom > theadHeight) {
                        cloneWrapSession.style.display = 'block';
                        syncScrollSession();
                        syncCheckAllSession();
                    } else {
                        cloneWrapSession.style.display = 'none';
                    }
                }

                createCloneSession();
                window.addEventListener('scroll', onScrollSession);
                window.addEventListener('resize', function() { createCloneSession(); onScrollSession(); });
                stickyContainerSession.addEventListener('scroll', syncScrollSession);

                const observerSession = new MutationObserver(function() {
                    setTimeout(function() { createCloneSession(); onScrollSession(); }, 50);
                });
                observerSession.observe(document.getElementById('noticeTableBodySession'), { childList: true });
            }
        });

        // Update header checkbox khi row checkbox thay đổi
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('checkbox-row')) {
                if (e.target.closest('#noticeTableBodyFollow')) {
                    updateHeaderCheckboxFollow();
                }
            }
            if (e.target.classList.contains('checkbox-row-session')) {
                if (e.target.closest('#noticeTableBodySession')) {
                    updateHeaderCheckboxSession();
                }
            }
        });

        // ========== FOLLOW SECTION ==========
        function toggleSectionFollow() {
            const body = document.getElementById('sectionBodyFollow');
            const icon = document.getElementById('sectionToggleIconFollow');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.textContent = '▲';
            } else {
                body.style.display = 'none';
                icon.textContent = '▼';
            }
        }

        function renderTableFollow(data) {
            const tbody = document.getElementById('noticeTableBodyFollow');
            tbody.innerHTML = '';

            data.forEach(item => {
                const row = document.createElement('tr');
                const priceBuy = item.follow_price_buy != null ? Number(item.follow_price_buy).toLocaleString('vi-VN') : 'N/A';
                const priceSell = item.follow_price_sell != null ? Number(item.follow_price_sell).toLocaleString('vi-VN') : 'N/A';
                const checked = item.notice_flag == 1 ? 'checked' : '';

                row.innerHTML = `
                    <td><a href="https://fireant.vn/dashboard/content/symbols/${item.code}" target="_blank" style="color: inherit; text-decoration: none;">${item.code}</a></td>
                    <td>${priceBuy}</td>
                    <td>${priceSell}</td>
                    <td><input type="checkbox" class="checkbox-row" data-id="${item.id}" ${checked}></td>
                `;
                tbody.appendChild(row);
            });

            updateHeaderCheckboxFollow();
            updateSaveButtonFollow();
        }

        function updateSaveButtonFollow() {
            const btn = document.getElementById('btnSaveFollow');
            const rows = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row');
            btn.disabled = rows.length === 0;
        }

        function toggleAllFollow() {
            const checkAll = document.getElementById('checkAllFollow');
            const checkboxes = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row');
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
        }

        function updateHeaderCheckboxFollow() {
            const checkboxes = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row');
            const checkAll = document.getElementById('checkAllFollow');
            if (checkboxes.length === 0) {
                checkAll.checked = false;
                return;
            }
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkAll.checked = allChecked;
        }

        function saveFlagsFollow() {
            const checkboxes = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row');
            const items = [];
            checkboxes.forEach(cb => {
                items.push({
                    id: parseInt(cb.getAttribute('data-id')),
                    notice_flag: cb.checked ? 1 : 0
                });
            });

            $.ajax({
                url: baseUrl + '/user/email-settings-follow/save',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify({ items: items }),
                success: function (response) {
                    if (response.status === 'success') {
                        toastShow('success', '✅ ' + response.message);
                    } else {
                        toastShow('error', '❌ ' + response.message);
                    }
                },
                error: function (xhr) {
                    toastShow('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }

        // ========== SESSION CLOSED SECTION ==========
        function toggleSectionSession() {
            const body = document.getElementById('sectionBodySession');
            const icon = document.getElementById('sectionToggleIconSession');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.textContent = '▲';
            } else {
                body.style.display = 'none';
                icon.textContent = '▼';
            }
        }

        function renderTableSession(data) {
            const tbody = document.getElementById('noticeTableBodySession');
            tbody.innerHTML = '';

            data.forEach(item => {
                const row = document.createElement('tr');
                const checked = item.session_closed_flag == 1 ? 'checked' : '';

                row.innerHTML = `
                    <td><a href="https://fireant.vn/dashboard/content/symbols/${item.code}" target="_blank" style="color: inherit; text-decoration: none;">${item.code}</a></td>
                    <td><input type="checkbox" class="checkbox-row-session" data-stock-id="${item.stock_id}" ${checked}></td>
                `;
                tbody.appendChild(row);
            });

            updateHeaderCheckboxSession();
            updateSaveButtonSession();
        }

        function updateSaveButtonSession() {
            const btn = document.getElementById('btnSaveSession');
            const rows = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            btn.disabled = rows.length === 0;
        }

        function toggleAllSession() {
            const checkAll = document.getElementById('checkAllSession');
            const checkboxes = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
        }

        function updateHeaderCheckboxSession() {
            const checkboxes = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            const checkAll = document.getElementById('checkAllSession');
            if (checkboxes.length === 0) {
                checkAll.checked = false;
                return;
            }
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkAll.checked = allChecked;
        }

        function saveFlagsSession() {
            const checkboxes = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            const items = [];
            checkboxes.forEach(cb => {
                items.push({
                    stock_id: parseInt(cb.getAttribute('data-stock-id')),
                    session_closed_flag: cb.checked ? 1 : 0
                });
            });

            $.ajax({
                url: baseUrl + '/user/email-settings/save-session-closed',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify({ items: items }),
                success: function (response) {
                    if (response.status === 'success') {
                        toastShow('success', '✅ ' + response.message);
                    } else {
                        toastShow('error', '❌ ' + response.message);
                    }
                },
                error: function (xhr) {
                    toastShow('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }

        function toastShow(type, message) {
            const toast = document.getElementById('toast');
            toast.classList.remove('toast-success', 'toast-error', 'show');
            toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
            toast.innerHTML = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
@endsection
