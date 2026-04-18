// Moved from UserEmailSettings.blade.php <script> block
(function () {
    const { baseUrl, noticesFollow, sessionClosedItems } = window.__pageData || {};
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.addEventListener("DOMContentLoaded", function () {

        // ─── Sticky header helpers ──────────────────────────────────────────────

        function headerInset() {
            return typeof window.getStickyHeaderInset === 'function'
                ? window.getStickyHeaderInset()
                : (window.innerWidth <= 768 ? 56 : 0);
        }

        // ─── FOLLOW TABLE sticky header ─────────────────────────────────────────

        const stickyTableFollow    = document.getElementById('notice-table-follow');
        const stickyContainerFollow = document.querySelector('.table-container-follow');
        let cloneWrapFollow  = null;
        let cloneTableFollow = null;

        function syncCloneCheckboxesFollow() {
            if (!cloneWrapFollow) return;
            const realBuy  = document.getElementById('checkAllFollowBuy');
            const realSell = document.getElementById('checkAllFollowSell');
            const cbs = cloneWrapFollow.querySelectorAll('input[type=checkbox]');
            if (cbs[0] && realBuy)  cbs[0].checked = realBuy.checked;
            if (cbs[1] && realSell) cbs[1].checked = realSell.checked;
        }

        function createCloneFollow() {
            if (!stickyTableFollow || !stickyContainerFollow) return;
            if (cloneWrapFollow) cloneWrapFollow.remove();

            const theadFollow = stickyTableFollow.querySelector('thead');
            cloneWrapFollow = document.createElement('div');
            cloneWrapFollow.className = 'sticky-clone';
            cloneTableFollow = document.createElement('table');
            cloneTableFollow.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';

            const cloneThead = theadFollow.cloneNode(true);

            const cloneCheckAllBuy = cloneThead.querySelector('#checkAllFollowBuy');
            if (cloneCheckAllBuy) {
                cloneCheckAllBuy.removeAttribute('id');
                cloneCheckAllBuy.addEventListener('click', function () {
                    const real = document.getElementById('checkAllFollowBuy');
                    real.checked = this.checked;
                    document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-buy')
                        .forEach(cb => { cb.checked = this.checked; });
                    updateHeaderCheckboxFollowBuy();
                    syncCloneCheckboxesFollow();
                });
            }
            const cloneCheckAllSell = cloneThead.querySelector('#checkAllFollowSell');
            if (cloneCheckAllSell) {
                cloneCheckAllSell.removeAttribute('id');
                cloneCheckAllSell.addEventListener('click', function () {
                    const real = document.getElementById('checkAllFollowSell');
                    real.checked = this.checked;
                    document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-sell')
                        .forEach(cb => { cb.checked = this.checked; });
                    updateHeaderCheckboxFollowSell();
                    syncCloneCheckboxesFollow();
                });
            }

            cloneTableFollow.appendChild(cloneThead);
            cloneWrapFollow.appendChild(cloneTableFollow);
            document.body.appendChild(cloneWrapFollow);
            syncWidthsFollow();
            syncScrollFollow();
            syncCloneCheckboxesFollow();
            cloneWrapFollow.style.display = 'none';
        }

        function syncWidthsFollow() {
            if (!cloneTableFollow || !stickyTableFollow) return;
            const theadFollow = stickyTableFollow.querySelector('thead');
            const origCells  = theadFollow.querySelectorAll('th');
            const cloneCells = cloneTableFollow.querySelectorAll('th');
            cloneTableFollow.style.width = stickyTableFollow.getBoundingClientRect().width + 'px';
            origCells.forEach((cell, i) => {
                if (!cloneCells[i]) return;
                const w = cell.getBoundingClientRect().width;
                cloneCells[i].style.cssText += `;box-sizing:border-box;width:${w}px;min-width:${w}px;max-width:${w}px`;
            });
        }

        function syncScrollFollow() {
            if (!cloneWrapFollow || !stickyContainerFollow) return;
            const rect  = stickyContainerFollow.getBoundingClientRect();
            const inset = headerInset();
            cloneWrapFollow.style.left  = rect.left + 'px';
            cloneWrapFollow.style.width = rect.width + 'px';
            cloneWrapFollow.style.top   = inset + 'px';
            cloneTableFollow.style.marginLeft = -stickyContainerFollow.scrollLeft + 'px';
        }

        function onScrollFollow() {
            if (!cloneWrapFollow || !stickyTableFollow) return;
            const theadFollow  = stickyTableFollow.querySelector('thead');
            const tableRect    = stickyTableFollow.getBoundingClientRect();
            const theadHeight  = theadFollow.offsetHeight;
            const inset        = headerInset();
            if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
                cloneWrapFollow.style.display = 'block';
                syncScrollFollow();
                syncCloneCheckboxesFollow();
            } else {
                cloneWrapFollow.style.display = 'none';
            }
        }

        if (stickyTableFollow && stickyContainerFollow) {
            createCloneFollow();
            window.addEventListener('scroll', onScrollFollow, { passive: true });
            window.addEventListener('resize', function () { createCloneFollow(); onScrollFollow(); });
            stickyContainerFollow.addEventListener('scroll', syncScrollFollow, { passive: true });
            onScrollFollow();

            new MutationObserver(function () {
                setTimeout(function () { createCloneFollow(); onScrollFollow(); }, 50);
            }).observe(document.getElementById('noticeTableBodyFollow'), { childList: true });
        }

        // ─── SESSION TABLE sticky header ────────────────────────────────────────

        const stickyTableSession     = document.getElementById('notice-table-session');
        const stickyContainerSession = document.querySelector('.table-container-session');
        let cloneWrapSession  = null;
        let cloneTableSession = null;

        function syncCheckAllSession() {
            if (!cloneWrapSession) return;
            const real  = document.getElementById('checkAllSession');
            const clone = cloneWrapSession.querySelector('input[type=checkbox]');
            if (clone && real) clone.checked = real.checked;
        }

        function createCloneSession() {
            if (!stickyTableSession || !stickyContainerSession) return;
            if (cloneWrapSession) cloneWrapSession.remove();

            const theadSession = stickyTableSession.querySelector('thead');
            cloneWrapSession = document.createElement('div');
            cloneWrapSession.className = 'sticky-clone';
            cloneTableSession = document.createElement('table');
            cloneTableSession.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';

            const cloneThead = theadSession.cloneNode(true);
            const cloneCheckAll = cloneThead.querySelector('#checkAllSession');
            if (cloneCheckAll) {
                cloneCheckAll.removeAttribute('id');
                cloneCheckAll.addEventListener('click', function () {
                    const real = document.getElementById('checkAllSession');
                    real.checked = this.checked;
                    toggleAllSession();
                    syncCheckAllSession();
                });
            }

            cloneTableSession.appendChild(cloneThead);
            cloneWrapSession.appendChild(cloneTableSession);
            document.body.appendChild(cloneWrapSession);
            syncWidthsSession();
            syncScrollSession();
            syncCheckAllSession();
            cloneWrapSession.style.display = 'none';
        }

        function syncWidthsSession() {
            if (!cloneTableSession || !stickyTableSession) return;
            const theadSession = stickyTableSession.querySelector('thead');
            const origCells  = theadSession.querySelectorAll('th');
            const cloneCells = cloneTableSession.querySelectorAll('th');
            cloneTableSession.style.width = stickyTableSession.getBoundingClientRect().width + 'px';
            origCells.forEach((cell, i) => {
                if (!cloneCells[i]) return;
                const w = cell.getBoundingClientRect().width;
                cloneCells[i].style.cssText += `;box-sizing:border-box;width:${w}px;min-width:${w}px;max-width:${w}px`;
            });
        }

        function syncScrollSession() {
            if (!cloneWrapSession || !stickyContainerSession) return;
            const rect  = stickyContainerSession.getBoundingClientRect();
            const inset = headerInset();
            cloneWrapSession.style.left  = rect.left + 'px';
            cloneWrapSession.style.width = rect.width + 'px';
            cloneWrapSession.style.top   = inset + 'px';
            cloneTableSession.style.marginLeft = -stickyContainerSession.scrollLeft + 'px';
        }

        function onScrollSession() {
            if (!cloneWrapSession || !stickyTableSession) return;
            const theadSession = stickyTableSession.querySelector('thead');
            const tableRect    = stickyTableSession.getBoundingClientRect();
            const theadHeight  = theadSession.offsetHeight;
            const inset        = headerInset();
            if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
                cloneWrapSession.style.display = 'block';
                syncScrollSession();
                syncCheckAllSession();
            } else {
                cloneWrapSession.style.display = 'none';
            }
        }

        if (stickyTableSession && stickyContainerSession) {
            createCloneSession();
            window.addEventListener('scroll', onScrollSession, { passive: true });
            window.addEventListener('resize', function () { createCloneSession(); onScrollSession(); });
            stickyContainerSession.addEventListener('scroll', syncScrollSession, { passive: true });
            onScrollSession();

            new MutationObserver(function () {
                setTimeout(function () { createCloneSession(); onScrollSession(); }, 50);
            }).observe(document.getElementById('noticeTableBodySession'), { childList: true });
        }

        // ─── Row checkbox change — sync both real thead and sticky clone ─────────

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('checkbox-row-buy') ||
                e.target.classList.contains('checkbox-row-sell')) {
                if (e.target.closest('#noticeTableBodyFollow')) {
                    updateHeaderCheckboxFollowBuy();
                    updateHeaderCheckboxFollowSell();
                    syncCloneCheckboxesFollow();
                }
            }
            if (e.target.classList.contains('checkbox-row-session')) {
                if (e.target.closest('#noticeTableBodySession')) {
                    updateHeaderCheckboxSession();
                    syncCheckAllSession();
                }
            }
        });

        // ─── Real thead checkbox click handlers ─────────────────────────────────

        const checkAllFollowBuyEl = document.getElementById('checkAllFollowBuy');
        if (checkAllFollowBuyEl) {
            checkAllFollowBuyEl.addEventListener('click', toggleAllFollowBuy);
        }
        const checkAllFollowSellEl = document.getElementById('checkAllFollowSell');
        if (checkAllFollowSellEl) {
            checkAllFollowSellEl.addEventListener('click', toggleAllFollowSell);
        }
        const checkAllSessionEl = document.getElementById('checkAllSession');
        if (checkAllSessionEl) {
            checkAllSessionEl.addEventListener('click', toggleAllSession);
        }

        // ─── FOLLOW section ─────────────────────────────────────────────────────

        function toggleSectionFollow() {
            const body = document.getElementById('sectionBodyFollow');
            const icon = document.getElementById('sectionToggleIconFollow');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.textContent = '▲';
                requestAnimationFrame(function () {
                    window.dispatchEvent(new Event('resize'));
                });
            } else {
                body.style.display = 'none';
                icon.textContent = '▼';
            }
        }
        window.toggleSectionFollow = toggleSectionFollow;

        function renderTableFollow(data) {
            const tbody = document.getElementById('noticeTableBodyFollow');
            tbody.innerHTML = '';
            data.forEach(item => {
                const row      = document.createElement('tr');
                const priceBuy  = item.follow_price_buy  != null ? Number(item.follow_price_buy).toLocaleString('vi-VN')  : 'N/A';
                const priceSell = item.follow_price_sell != null ? Number(item.follow_price_sell).toLocaleString('vi-VN') : 'N/A';
                const cBuy  = item.notice_buy  == 1 ? 'checked' : '';
                const cSell = item.notice_sell == 1 ? 'checked' : '';
                row.innerHTML = `
                    <td><a href="https://fireant.vn/dashboard/content/symbols/${item.code}" target="_blank" style="color:inherit;text-decoration:none;">${item.code}</a></td>
                    <td>${priceBuy}</td>
                    <td><input type="checkbox" class="checkbox-row-buy"  data-id="${item.id}" ${cBuy}></td>
                    <td>${priceSell}</td>
                    <td><input type="checkbox" class="checkbox-row-sell" data-id="${item.id}" ${cSell}></td>
                `;
                tbody.appendChild(row);
            });
            updateHeaderCheckboxFollowBuy();
            updateHeaderCheckboxFollowSell();
            updateSaveButtonFollow();
        }

        function updateSaveButtonFollow() {
            const btn  = document.getElementById('btnSaveFollow');
            const rows = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-buy, #noticeTableBodyFollow .checkbox-row-sell');
            btn.disabled = rows.length === 0;
        }

        function toggleAllFollowBuy() {
            const checkAll  = document.getElementById('checkAllFollowBuy');
            document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-buy')
                .forEach(cb => { cb.checked = checkAll.checked; });
            updateHeaderCheckboxFollowBuy();
            syncCloneCheckboxesFollow();
        }

        function toggleAllFollowSell() {
            const checkAll = document.getElementById('checkAllFollowSell');
            document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-sell')
                .forEach(cb => { cb.checked = checkAll.checked; });
            updateHeaderCheckboxFollowSell();
            syncCloneCheckboxesFollow();
        }

        function updateHeaderCheckboxFollowBuy() {
            const checkboxes = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-buy');
            const checkAll   = document.getElementById('checkAllFollowBuy');
            if (checkboxes.length === 0) { checkAll.checked = false; return; }
            checkAll.checked = Array.from(checkboxes).every(cb => cb.checked);
        }

        function updateHeaderCheckboxFollowSell() {
            const checkboxes = document.querySelectorAll('#noticeTableBodyFollow .checkbox-row-sell');
            const checkAll   = document.getElementById('checkAllFollowSell');
            if (checkboxes.length === 0) { checkAll.checked = false; return; }
            checkAll.checked = Array.from(checkboxes).every(cb => cb.checked);
        }

        function setBtnLoading(btn, loading) {
            if (loading) {
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '⏳ Đang lưu...';
                btn.disabled  = true;
            } else {
                btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
                btn.disabled  = false;
            }
        }

        function saveFlagsFollow() {
            const btn   = document.getElementById('btnSaveFollow');
            const rows  = document.querySelectorAll('#noticeTableBodyFollow tr');
            const items = [];
            rows.forEach(row => {
                const buyEl  = row.querySelector('.checkbox-row-buy');
                const sellEl = row.querySelector('.checkbox-row-sell');
                items.push({
                    id:          parseInt(buyEl.getAttribute('data-id')),
                    notice_buy:  buyEl.checked  ? 1 : 0,
                    notice_sell: sellEl.checked ? 1 : 0
                });
            });
            setBtnLoading(btn, true);
            $.ajax({
                url: baseUrl + '/user/email-settings-follow/save',
                type: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                data: JSON.stringify({ items }),
                success: function (r) {
                    setBtnLoading(btn, false);
                    showNotifyModal(r.status === 'success' ? 'success' : 'error',
                        (r.status === 'success' ? '✅ ' : '❌ ') + r.message);
                },
                error: function (xhr) {
                    setBtnLoading(btn, false);
                    showNotifyModal('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
        window.saveFlagsFollow = saveFlagsFollow;

        // ─── SESSION section ────────────────────────────────────────────────────

        function toggleSectionSession() {
            const body = document.getElementById('sectionBodySession');
            const icon = document.getElementById('sectionToggleIconSession');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.textContent = '▲';
                requestAnimationFrame(function () {
                    window.dispatchEvent(new Event('resize'));
                });
            } else {
                body.style.display = 'none';
                icon.textContent = '▼';
            }
        }
        window.toggleSectionSession = toggleSectionSession;

        function renderTableSession(data) {
            const tbody = document.getElementById('noticeTableBodySession');
            tbody.innerHTML = '';
            data.forEach(item => {
                const row     = document.createElement('tr');
                const checked = item.session_closed_flag == 1 ? 'checked' : '';
                row.innerHTML = `
                    <td><a href="https://fireant.vn/dashboard/content/symbols/${item.code}" target="_blank" style="color:inherit;text-decoration:none;">${item.code}</a></td>
                    <td><input type="checkbox" class="checkbox-row-session" data-stock-id="${item.stock_id}" ${checked}></td>
                `;
                tbody.appendChild(row);
            });
            updateHeaderCheckboxSession();
            updateSaveButtonSession();
        }

        function updateSaveButtonSession() {
            const btn  = document.getElementById('btnSaveSession');
            const rows = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            btn.disabled = rows.length === 0;
        }

        function toggleAllSession() {
            const checkAll = document.getElementById('checkAllSession');
            document.querySelectorAll('#noticeTableBodySession .checkbox-row-session')
                .forEach(cb => { cb.checked = checkAll.checked; });
            updateHeaderCheckboxSession();
            syncCheckAllSession();
        }
        window.toggleAllSession = toggleAllSession;

        function updateHeaderCheckboxSession() {
            const checkboxes = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            const checkAll   = document.getElementById('checkAllSession');
            if (checkboxes.length === 0) { checkAll.checked = false; return; }
            checkAll.checked = Array.from(checkboxes).every(cb => cb.checked);
        }

        function saveFlagsSession() {
            const btn      = document.getElementById('btnSaveSession');
            const checkboxes = document.querySelectorAll('#noticeTableBodySession .checkbox-row-session');
            const items = [];
            checkboxes.forEach(cb => {
                items.push({
                    stock_id:          parseInt(cb.getAttribute('data-stock-id')),
                    session_closed_flag: cb.checked ? 1 : 0
                });
            });
            setBtnLoading(btn, true);
            $.ajax({
                url: baseUrl + '/user/email-settings/save-session-closed',
                type: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                data: JSON.stringify({ items }),
                success: function (r) {
                    setBtnLoading(btn, false);
                    showNotifyModal(r.status === 'success' ? 'success' : 'error',
                        (r.status === 'success' ? '✅ ' : '❌ ') + r.message);
                },
                error: function (xhr) {
                    setBtnLoading(btn, false);
                    showNotifyModal('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
        window.saveFlagsSession = saveFlagsSession;

        // ─── Init ───────────────────────────────────────────────────────────────

        renderTableFollow(noticesFollow);
        renderTableSession(sessionClosedItems);

    }); // DOMContentLoaded

})();
