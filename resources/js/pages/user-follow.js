const { stocks: allStocksRaw, userFollow: userFollowRaw, baseUrl, urlDeleteBatch } = window.__pageData || {};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// ── Filter state persistence ──────────────────────────────────────────────────
const FILTER_STORAGE_KEY = 'userFollow_filterState';

function saveFilterState() {
    const state = {
        filterRisk:         document.getElementById('filterRisk')?.value ?? '',
        filterStocksVn:     document.getElementById('filterStocksVn')?.value ?? '',
        filterRatingMin:    document.getElementById('filterRatingMin')?.value ?? '',
        filterRatingMax:    document.getElementById('filterRatingMax')?.value ?? '',
        filterVolumeMin:    document.getElementById('filterVolumeMin')?.value ?? '',
        filterVolumeMax:    document.getElementById('filterVolumeMax')?.value ?? '',
        filterValuationMin: document.getElementById('filterValuationMin')?.value ?? '',
        filterValuationMax: document.getElementById('filterValuationMax')?.value ?? '',
        searchInput:        document.getElementById('searchInput')?.value ?? '',
    };
    try { sessionStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(state)); } catch (_) {}
}

function loadFilterState() {
    try {
        const raw = sessionStorage.getItem(FILTER_STORAGE_KEY);
        if (!raw) return false;
        const state = JSON.parse(raw);
        let hasAny = false;
        Object.entries(state).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el && val !== '') { el.value = val; hasAny = true; }
        });
        return hasAny;
    } catch (_) { return false; }
}

function clearFilterState() {
    try { sessionStorage.removeItem(FILTER_STORAGE_KEY); } catch (_) {}
}
// ─────────────────────────────────────────────────────────────────────────────

const followMap = {};
(userFollowRaw || []).forEach(f => {
    followMap[f.code] = {
        follow_price_buy: f.follow_price_buy,
        follow_price_sell: f.follow_price_sell
    };
});

const stocks = (allStocksRaw || [])
    .filter(s => Object.prototype.hasOwnProperty.call(followMap, s.code))
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

let currentSortKey = 'valuation';
let currentSortDir = 'asc';

// Mutable working copy so we can remove rows without a full page reload
let workingStocks = stocks.slice();

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
window.sortByColumn = sortByColumn;

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

document.addEventListener('DOMContentLoaded', function () {
    updateSortIcons();

    // Restore filter state saved before a delete-triggered navigation
    const hadFilter = loadFilterState();
    if (hadFilter) {
        const body = document.getElementById('filterBody');
        const icon = document.getElementById('filterToggleIcon');
        if (body) body.style.display = 'block';
        if (icon) icon.textContent = '▲';
        clearFilterState(); // consumed; only needed once after reload
    }

    renderTable(getFilteredStocks());
    syncDeleteAllButtonState();

    const container = document.querySelector('.table-container');
    let isDown = false;
    let startX, scrollLeft;

    container.addEventListener('mousedown', function (e) {
        if (e.target.closest('a, button, input, select')) return;
        isDown = true;
        container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
        e.preventDefault();
    });
    container.addEventListener('mouseleave', function () { isDown = false; container.style.cursor = 'grab'; });
    container.addEventListener('mouseup', function () { isDown = false; container.style.cursor = 'grab'; });
    container.addEventListener('mousemove', function (e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        container.scrollLeft = scrollLeft - (x - startX) * 2;
    });
    container.style.cursor = 'grab';

    document.getElementById('confirmYes').onclick = function () { executeSingleDelete(); };
    document.getElementById('confirmNo').onclick = function () {
        document.getElementById('confirmModal').style.display = 'none';
        pendingDeleteCode = null;
    };
    document.getElementById('confirmDeleteAllYes').onclick = function () { submitDeleteAllFollow(); };
    document.getElementById('confirmDeleteAllNo').onclick = function () { closeDeleteAllModal(); };
    const errorClose = document.getElementById('errorNotifyClose');
    if (errorClose) {
        errorClose.onclick = function () {
            document.getElementById('errorNotifyModal').style.display = 'none';
        };
    }
    const successClose = document.getElementById('successNotifyClose');
    if (successClose) {
        successClose.onclick = function () {
            document.getElementById('successNotifyModal').style.display = 'none';
        };
    }

    const stickyTable = document.getElementById('stock-table');
    const stickyContainer = document.querySelector('.table-container');
    if (stickyTable && stickyContainer) {
        function headerInset() {
            return typeof window.getStickyHeaderInset === 'function'
                ? window.getStickyHeaderInset()
                : (window.innerWidth <= 768 ? 56 : 0);
        }
        const thead = stickyTable.querySelector('thead');
        let cloneWrap = null, cloneTable = null;

        function createClone() {
            if (cloneWrap) cloneWrap.remove();
            cloneWrap = document.createElement('div');
            cloneWrap.className = 'sticky-clone';
            cloneTable = document.createElement('table');
            cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
            cloneTable.appendChild(thead.cloneNode(true));
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
                    cloneCells[i].style.cssText = `box-sizing:border-box;width:${w}px;min-width:${w}px;max-width:${w}px;`;
                }
            });
        }

        function syncScroll() {
            if (!cloneWrap) return;
            const r = stickyContainer.getBoundingClientRect();
            const inset = headerInset();
            cloneWrap.style.left = r.left + 'px';
            cloneWrap.style.width = r.width + 'px';
            cloneWrap.style.top = inset + 'px';
            cloneTable.style.marginLeft = -stickyContainer.scrollLeft + 'px';
        }

        function onScroll() {
            if (!cloneWrap) return;
            const tableRect = stickyTable.getBoundingClientRect();
            const inset = headerInset();
            if (tableRect.top < inset && tableRect.bottom > (inset + thead.offsetHeight)) {
                cloneWrap.style.display = 'block';
                syncScroll();
            } else {
                cloneWrap.style.display = 'none';
            }
        }

        createClone();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', function () { createClone(); onScroll(); });
        stickyContainer.addEventListener('scroll', syncScroll, { passive: true });
        onScroll();
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
        return ((currentPrice - goodPrice) / goodPrice) * 100 <= 10 ? 'yellow' : '';
    } else if (currentPrice <= goodPrice) {
        const p = ((goodPrice - currentPrice) / goodPrice) * 100;
        if (p > 20) return 'red';
        else if (p > 10) return 'purple';
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

// ── Single delete via AJAX (no full page reload) ──────────────────────────────
let pendingDeleteCode = null;

function confirmDelete(code) {
    pendingDeleteCode = code;
    document.getElementById('confirmModal').style.display = 'flex';
}
window.confirmDelete = confirmDelete;

function executeSingleDelete() {
    const code = pendingDeleteCode;
    if (!code) return;
    document.getElementById('confirmModal').style.display = 'none';
    pendingDeleteCode = null;

    const btnYes = document.getElementById('confirmYes');
    btnYes.disabled = true;

    $.ajax({
        url: urlDeleteBatch,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        data: JSON.stringify({ codes: [code] }),
        contentType: 'application/json',
        success: function () {
            workingStocks = workingStocks.filter(s => s.code !== code);
            renderTable(getFilteredStocks());
            syncDeleteAllButtonState();
            showSuccessModal(`Đã xoá mã ${code} khỏi danh sách theo dõi.`);
        },
        error: function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Xoá thất bại.';
            showErrorModal(msg);
        },
        complete: function () { btnYes.disabled = false; }
    });
}
// ─────────────────────────────────────────────────────────────────────────────

function syncDeleteAllButtonState() {
    const btn = document.getElementById('btnDeleteAllFollow');
    if (!btn) return;
    const visible = getFilteredStocks();
    const hasRows = visible.length > 0;
    btn.disabled = !hasRows;
    btn.style.opacity = hasRows ? '1' : '.6';
    btn.style.cursor = hasRows ? 'pointer' : 'not-allowed';
}

function openDeleteAllModal() {
    const btn = document.getElementById('btnDeleteAllFollow');
    if (!btn || btn.disabled) return;
    const filtered = getFilteredStocks();
    const total = workingStocks.length;
    const msgEl = document.getElementById('deleteAllModalMsg');
    if (msgEl) {
        if (filtered.length < total) {
            msgEl.innerHTML = `Bạn có chắc muốn xoá <b>${filtered.length}</b> mã đang hiển thị (trong tổng số ${total} mã theo dõi)?`;
        } else {
            msgEl.innerHTML = `Bạn có chắc muốn xoá tất cả <b>${total}</b> mã đã theo dõi không?`;
        }
    }
    document.getElementById('confirmDeleteAllModal').style.display = 'flex';
}
window.openDeleteAllModal = openDeleteAllModal;

function closeDeleteAllModal() {
    document.getElementById('confirmDeleteAllModal').style.display = 'none';
}

function showErrorModal(message) {
    const modal = document.getElementById('errorNotifyModal');
    const msgEl = document.getElementById('errorNotifyMsg');
    if (!modal || !msgEl) { alert(message); return; }
    msgEl.textContent = message;
    modal.style.display = 'flex';
    const closeBtn = document.getElementById('errorNotifyClose');
    if (closeBtn) closeBtn.focus();
}

function showSuccessModal(message) {
    const modal = document.getElementById('successNotifyModal');
    const msgEl = document.getElementById('successNotifyMsg');
    if (!modal || !msgEl) return;
    msgEl.textContent = message;
    modal.style.display = 'flex';
    const closeBtn = document.getElementById('successNotifyClose');
    if (closeBtn) closeBtn.focus();
}

function submitDeleteAllFollow() {
    const btnConfirm = document.getElementById('confirmDeleteAllYes');
    if (!btnConfirm) return;
    btnConfirm.disabled = true;
    btnConfirm.textContent = 'Đang xử lý...';

    const filteredCodes = getFilteredStocks().map(s => s.code);

    $.ajax({
        url: urlDeleteBatch,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        data: JSON.stringify({ codes: filteredCodes }),
        contentType: 'application/json',
        success: function (res) {
            closeDeleteAllModal();
            workingStocks = workingStocks.filter(s => !filteredCodes.includes(s.code));
            renderTable(getFilteredStocks());
            syncDeleteAllButtonState();
            showSuccessModal(res && res.message ? res.message : 'Đã xoá.');
        },
        error: function (xhr) {
            closeDeleteAllModal();
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Xoá tất cả thất bại.';
            showErrorModal(msg);
        },
        complete: function () {
            btnConfirm.disabled = false;
            btnConfirm.textContent = 'Xác nhận';
        }
    });
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
        let valuationColor = 'yellow', sign = '';
        if (valuation > 0) { valuationColor = 'green'; sign = '+'; }
        else if (valuation < 0) { valuationColor = 'red'; }

        const row = document.createElement('tr');
        row.className = getRowClass(buyPrice, currentPrice);
        row.innerHTML = `
            <td class="col-code-sticky"><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color:inherit;text-decoration:none;">${stock.code}</a></td>
            <td>${[30, 100].includes(Number(stock.stocks_vn)) ? Number(stock.stocks_vn) : 'ALL'}</td>
            <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
            <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
            <td>${sellPrice}</td>
            <td style="color:${getRisk(stock.risk_level).color}">${getRisk(stock.risk_level).label}</td>
            <td>${getRatingBadge(stock.rating_stocks)}</td>
            <td>${volume}</td>
            <td style="color:${valuationColor};font-weight:bold;">${sign}${valuation}%</td>
            <td style="display:flex;gap:4px;">
                <button class="btn-filter" onclick="location.href='${baseUrl}/user/updateFollow/${stock.code}'">✏️ Cập nhật</button>
                <button class="btn-delete" onclick="confirmDelete('${stock.code}')">🗑️ Xoá</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    window.dispatchEvent(new Event('resize'));
}

function getFilteredStocks() {
    const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
    const risk = document.getElementById('filterRisk').value;
    const stocksVn = document.getElementById('filterStocksVn').value;
    const ratingMin = document.getElementById('filterRatingMin').value;
    const ratingMax = document.getElementById('filterRatingMax').value;
    const volumeMin = document.getElementById('filterVolumeMin').value.replace(/\./g, '');
    const volumeMax = document.getElementById('filterVolumeMax').value.replace(/\./g, '');
    const valuationMin = document.getElementById('filterValuationMin').value;
    const valuationMax = document.getElementById('filterValuationMax').value;

    return workingStocks.filter(stock => {
        if (keyword && !stock.code.includes(keyword)) return false;
        if (risk && Number(stock.risk_level) !== Number(risk)) return false;
        if (stocksVn === '30') { if (Number(stock.stocks_vn) !== 30) return false; }
        else if (stocksVn === '100') { if (![30, 100].includes(Number(stock.stocks_vn))) return false; }
        const rating = parseFloat(stock.rating_stocks);
        if (ratingMin !== '' && (isNaN(rating) || rating < parseInt(ratingMin, 10))) return false;
        if (ratingMax !== '' && (isNaN(rating) || rating > parseInt(ratingMax, 10))) return false;
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

function searchStock() { renderTable(getFilteredStocks()); syncDeleteAllButtonState(); }
window.searchStock = searchStock;

function applyFilter() { renderTable(getFilteredStocks()); syncDeleteAllButtonState(); }
window.applyFilter = applyFilter;

function resetFilter() {
    ['filterRisk', 'filterStocksVn', 'filterRatingMin', 'filterRatingMax',
     'filterVolumeMin', 'filterVolumeMax', 'filterValuationMin', 'filterValuationMax', 'searchInput']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    renderTable(workingStocks);
    syncDeleteAllButtonState();
}
window.resetFilter = resetFilter;

function toggleFilter() {
    const body = document.getElementById('filterBody');
    const icon = document.getElementById('filterToggleIcon');
    if (body.style.display === 'none') { body.style.display = 'block'; icon.textContent = '▲'; }
    else { body.style.display = 'none'; icon.textContent = '▼'; }
}
window.toggleFilter = toggleFilter;

// Format KL trung bình + validate Điểm
function formatVolume(val) {
    const raw = String(val).replace(/\./g, '').trim();
    const num = parseInt(raw, 10);
    if (isNaN(num) || raw === '') return '';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

document.addEventListener('DOMContentLoaded', function() {
    ['filterRatingMin', 'filterRatingMax'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', function(e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('blur', function() {
            const raw = this.value.trim();
            if (raw === '') return;
            const num = parseInt(raw, 10);
            this.value = (isNaN(num) || num < 1 || num > 10) ? '' : num;
        });
    });

    ['filterVolumeMin', 'filterVolumeMax'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', function(e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('blur', function() {
            this.value = formatVolume(this.value);
        });
        el.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    });
});
