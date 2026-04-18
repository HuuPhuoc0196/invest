const { stocks: allStocksRaw, userFollow: userFollowRaw, baseUrl, urlDeleteBatch } = window.__pageData || {};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// ── Filter state persistence ───────────────────────────────────────────────────
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

// Build merged stock list (only followed stocks, with follow prices)
const followMap = {};
(userFollowRaw || []).forEach(f => {
    followMap[f.code] = {
        follow_price_buy:  f.follow_price_buy,
        follow_price_sell: f.follow_price_sell
    };
});

const stocks = (allStocksRaw || [])
    .filter(s => Object.prototype.hasOwnProperty.call(followMap, s.code))
    .map(s => {
        const merged = Object.assign({}, s);
        const follow = followMap[s.code];
        if (follow.follow_price_buy  !== null && follow.follow_price_buy  !== undefined) merged.recommended_buy_price  = follow.follow_price_buy;
        if (follow.follow_price_sell !== null && follow.follow_price_sell !== undefined) merged.recommended_sell_price = follow.follow_price_sell;
        return merged;
    });

// Mutable working copy — rows removed after delete without full reload
let workingStocks = stocks.slice();

// Selected codes (using code as key, same as batch delete API)
let selectedCodes = new Set();
let pendingDeleteCode = null;

// ── Sort ──────────────────────────────────────────────────────────────────────
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
// ─────────────────────────────────────────────────────────────────────────────

function getRisk(rating) {
    switch (Number(rating)) {
        case 1: return { label: 'An toàn',      color: '#27ae60' };
        case 2: return { label: 'Cảnh báo',     color: '#f39c12' };
        case 3: return { label: 'Hạn chế GD',   color: '#e74c3c' };
        case 4: return { label: 'Đình chỉ/Huỷ', color: '#c0392b' };
        default: return { label: 'Chưa xác định', color: '#95a5a6' };
    }
}

function getRowClass(goodPrice, currentPrice) {
    if (currentPrice > goodPrice) {
        return ((currentPrice - goodPrice) / goodPrice) * 100 <= 10 ? 'yellow' : '';
    }
    const p = ((goodPrice - currentPrice) / goodPrice) * 100;
    if (p > 20) return 'red';
    if (p > 10) return 'purple';
    return 'green';
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

// ── Checkbox state ────────────────────────────────────────────────────────────
function updateDeleteBatchButtonState() {
    const btn = document.getElementById('btnDeleteFollowBatch');
    if (!btn) return;
    btn.disabled = selectedCodes.size === 0;
}

function updateSelectAllState() {
    const allCheckboxes = Array.from(document.querySelectorAll('.select-checkbox'));
    const checkedCount  = allCheckboxes.filter(cb => cb.checked).length;
    const n = allCheckboxes.length;
    const allChecked  = n > 0 && checkedCount === n;
    const someChecked = checkedCount > 0 && checkedCount < n;

    // Update all .th-select-all elements — real thead AND sticky clone
    document.querySelectorAll('.th-select-all').forEach(th => {
        const state = th.querySelector('.th-select-all__state');
        if (state) {
            state.textContent = allChecked ? '☑' : (someChecked ? '−' : '');
        }
        th.title = allChecked ? 'Bỏ chọn tất cả' : 'Chọn tất cả';
        th.classList.toggle('th-select-all--active',  allChecked);
        th.classList.toggle('th-select-all--partial', someChecked);
    });
}

window.toggleSelectAllFollow = function () {
    const allCheckboxes = Array.from(document.querySelectorAll('.select-checkbox'));
    const allChecked    = allCheckboxes.every(cb => cb.checked);
    allCheckboxes.forEach(cb => {
        cb.checked = !allChecked;
        const code = cb.dataset.code;
        if (!allChecked) selectedCodes.add(code);
        else             selectedCodes.delete(code);
    });
    updateDeleteBatchButtonState();
    updateSelectAllState();
};
// ─────────────────────────────────────────────────────────────────────────────

// ── Render ────────────────────────────────────────────────────────────────────
function renderTable(data) {
    selectedCodes.clear();
    const tbody = document.getElementById('stockTableBody');
    tbody.innerHTML = '';
    dynamicSort(data);

    data.forEach(stock => {
        const buyPrice    = parseFloat(stock.recommended_buy_price) || 0;
        const currentPrice = parseFloat(stock.current_price) || 0;
        const sellPrice   = stock.recommended_sell_price ? Number(stock.recommended_sell_price).toLocaleString('vi-VN') : 'N/A';
        const volume      = stock.volume ? Number(stock.volume).toLocaleString('vi-VN') : 'N/A';
        const valuation   = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100).toFixed(2) : 0;
        let valuationColor = 'yellow', sign = '';
        if (valuation > 0)      { valuationColor = 'green'; sign = '+'; }
        else if (valuation < 0) { valuationColor = 'red'; }

        const isSelected  = selectedCodes.has(stock.code);
        const selectCell  = `<label class="cell-label-select"><input type="checkbox" class="select-checkbox" data-code="${stock.code}"${isSelected ? ' checked' : ''}></label>`;

        const row = document.createElement('tr');
        row.setAttribute('data-code', stock.code);
        row.className = getRowClass(buyPrice, currentPrice);
        row.innerHTML = `
            <td class="col-select">${selectCell}</td>
            <td class="col-code-sticky td-code-toggle"><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color:inherit;text-decoration:none;">${stock.code}</a></td>
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
                <button class="btn-delete" onclick="openDeleteFollowSingleModal('${stock.code}')">🗑️ Xoá</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Sync sticky header clone widths
    window.dispatchEvent(new Event('resize'));

    // Attach checkbox listeners
    tbody.querySelectorAll('.select-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            const code = this.dataset.code;
            if (this.checked) selectedCodes.add(code);
            else              selectedCodes.delete(code);
            updateDeleteBatchButtonState();
            updateSelectAllState();
        });
    });

    updateDeleteBatchButtonState();
    updateSelectAllState();
}
// ─────────────────────────────────────────────────────────────────────────────

// ── Single delete ─────────────────────────────────────────────────────────────
function openDeleteFollowSingleModal(code) {
    pendingDeleteCode = code;
    const codeEl = document.getElementById('deleteFollowSingleCode');
    const btn    = document.getElementById('btnDeleteFollowSingleConfirm');
    if (codeEl) codeEl.textContent = code;
    if (btn)    { btn.disabled = false; btn.textContent = 'Đồng ý'; }
    document.getElementById('deleteFollowSingleModal').style.display = 'flex';
}
window.openDeleteFollowSingleModal = openDeleteFollowSingleModal;

window.closeDeleteFollowSingleModal = function () {
    pendingDeleteCode = null;
    document.getElementById('deleteFollowSingleModal').style.display = 'none';
};

window.runDeleteFollowSingle = function () {
    const code = pendingDeleteCode;
    const btn  = document.getElementById('btnDeleteFollowSingleConfirm');
    if (!code) return;
    if (btn) { btn.disabled = true; btn.textContent = 'Đang xoá...'; }

    $.ajax({
        url: urlDeleteBatch,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        data: JSON.stringify({ codes: [code] }),
        contentType: 'application/json',
        success: function () {
            window.closeDeleteFollowSingleModal();
            workingStocks = workingStocks.filter(s => s.code !== code);
            selectedCodes.delete(code);
            renderTable(getFilteredStocks());
            updateDeleteBatchButtonState();
            showNoticeModal('success', `✅ Đã xoá mã ${code} khỏi danh sách theo dõi.`);
        },
        error: function (xhr) {
            window.closeDeleteFollowSingleModal();
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Xoá thất bại.';
            showNoticeModal('error', '❌ ' + msg);
        },
        complete: function () {
            if (btn) { btn.disabled = false; btn.textContent = 'Đồng ý'; }
        }
    });
};
// ─────────────────────────────────────────────────────────────────────────────

// ── Batch delete ──────────────────────────────────────────────────────────────
window.confirmDeleteFollowBatch = function () {
    if (selectedCodes.size === 0) return;
    const countEl = document.getElementById('deleteFollowBatchCount');
    const btn     = document.getElementById('btnDeleteFollowBatchConfirm');
    if (countEl) countEl.textContent = selectedCodes.size;
    if (btn)     { btn.disabled = false; btn.textContent = 'Đồng ý'; }
    document.getElementById('deleteFollowBatchModal').style.display = 'flex';
};

window.closeDeleteFollowBatchModal = function () {
    document.getElementById('deleteFollowBatchModal').style.display = 'none';
};

window.runDeleteFollowBatch = async function () {
    const btn   = document.getElementById('btnDeleteFollowBatchConfirm');
    const codes = Array.from(selectedCodes);
    if (codes.length === 0) return;
    if (btn) { btn.disabled = true; btn.textContent = 'Đang xoá...'; }

    try {
        const response = await fetch(urlDeleteBatch, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ codes })
        });
        const result = await response.json();
        window.closeDeleteFollowBatchModal();

        if (result.status === 'success') {
            workingStocks = workingStocks.filter(s => !codes.includes(s.code));
            selectedCodes.clear();
            renderTable(getFilteredStocks());
            updateDeleteBatchButtonState();
            showNoticeModal('success', '✅ ' + (result.message || 'Đã xoá.'));
        } else {
            showNoticeModal('error', '❌ ' + (result.message || 'Xoá thất bại.'));
        }
    } catch (err) {
        window.closeDeleteFollowBatchModal();
        showNoticeModal('error', '❌ Không thể kết nối server.');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Đồng ý'; }
    }
};
// ─────────────────────────────────────────────────────────────────────────────

// ── Notice modal ──────────────────────────────────────────────────────────────
function showNoticeModal(type, message) {
    const modal = document.getElementById('deleteFollowNoticeModal');
    const title = document.getElementById('deleteFollowNoticeTitle');
    const msg   = document.getElementById('deleteFollowNoticeMessage');
    if (!modal || !title || !msg) return;
    modal.classList.remove('is-success', 'is-error');
    modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
    title.textContent = type === 'success' ? 'Thành công' : 'Lỗi';
    msg.innerHTML     = message;
    modal.style.display = 'flex';
}

window.closeDeleteFollowNoticeModal = function () {
    const modal = document.getElementById('deleteFollowNoticeModal');
    if (!modal) return;
    modal.style.display = 'none';
    // không reload — bảng đã được cập nhật ngay lập tức qua workingStocks
};
// ─────────────────────────────────────────────────────────────────────────────

// ── Filter ────────────────────────────────────────────────────────────────────
function getFilteredStocks() {
    const keyword      = document.getElementById('searchInput').value.trim().toUpperCase();
    const risk         = document.getElementById('filterRisk').value;
    const stocksVn     = document.getElementById('filterStocksVn').value;
    const ratingMin    = document.getElementById('filterRatingMin').value;
    const ratingMax    = document.getElementById('filterRatingMax').value;
    const volumeMin    = document.getElementById('filterVolumeMin').value.replace(/\./g, '');
    const volumeMax    = document.getElementById('filterVolumeMax').value.replace(/\./g, '');
    const valuationMin = document.getElementById('filterValuationMin').value;
    const valuationMax = document.getElementById('filterValuationMax').value;

    return workingStocks.filter(stock => {
        if (keyword && !stock.code.includes(keyword)) return false;
        if (risk && Number(stock.risk_level) !== Number(risk)) return false;
        if (stocksVn === '30')  { if (Number(stock.stocks_vn) !== 30) return false; }
        else if (stocksVn === '100') { if (![30, 100].includes(Number(stock.stocks_vn))) return false; }
        const rating = parseFloat(stock.rating_stocks);
        if (ratingMin !== '' && (isNaN(rating) || rating < parseInt(ratingMin, 10))) return false;
        if (ratingMax !== '' && (isNaN(rating) || rating > parseInt(ratingMax, 10))) return false;
        const vol = parseFloat(stock.volume) || 0;
        if (volumeMin !== '' && vol < parseFloat(volumeMin)) return false;
        if (volumeMax !== '' && vol > parseFloat(volumeMax)) return false;
        const buyPrice     = parseFloat(stock.recommended_buy_price) || 0;
        const currentPrice = parseFloat(stock.current_price) || 0;
        const valuation    = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100) : 0;
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

function clearSelection() {
    selectedCodes.clear();
    updateDeleteBatchButtonState();
}

function searchStock() { clearSelection(); renderTable(getFilteredStocks()); }
window.searchStock = searchStock;

function applyFilter() { clearSelection(); renderTable(getFilteredStocks()); }
window.applyFilter = applyFilter;

function resetFilter() {
    ['filterRisk','filterStocksVn','filterRatingMin','filterRatingMax',
     'filterVolumeMin','filterVolumeMax','filterValuationMin','filterValuationMax','searchInput']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    clearSelection();
    renderTable(workingStocks);
}
window.resetFilter = resetFilter;

function toggleFilter() {
    const body = document.getElementById('filterBody');
    const icon = document.getElementById('filterToggleIcon');
    if (body.style.display === 'none') { body.style.display = 'block'; icon.textContent = '▲'; }
    else { body.style.display = 'none'; icon.textContent = '▼'; }
}
window.toggleFilter = toggleFilter;
// ─────────────────────────────────────────────────────────────────────────────

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    updateSortIcons();

    const hadFilter = loadFilterState();
    if (hadFilter) {
        const body = document.getElementById('filterBody');
        const icon = document.getElementById('filterToggleIcon');
        if (body) body.style.display = 'block';
        if (icon) icon.textContent = '▲';
        clearFilterState();
    }

    renderTable(getFilteredStocks());
    updateDeleteBatchButtonState();

    // Drag-to-scroll
    const container = document.querySelector('.table-container');
    let isDown = false, startX, scrollLeft;
    container.addEventListener('mousedown', function (e) {
        if (e.target.closest('a, button, input, select, label')) return;
        isDown = true; container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft; scrollLeft = container.scrollLeft;
        e.preventDefault();
    });
    container.addEventListener('mouseleave', function () { isDown = false; container.style.cursor = 'grab'; });
    container.addEventListener('mouseup',    function () { isDown = false; container.style.cursor = 'grab'; });
    container.addEventListener('mousemove',  function (e) {
        if (!isDown) return;
        e.preventDefault();
        container.scrollLeft = scrollLeft - (e.pageX - container.offsetLeft - startX) * 2;
    });
    container.style.cursor = 'grab';

    // Sticky header clone
    const stickyTable     = document.getElementById('stock-table');
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
            cloneWrap  = document.createElement('div');
            cloneWrap.className = 'sticky-clone';
            cloneTable = document.createElement('table');
            cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;';
            cloneTable.appendChild(thead.cloneNode(true));
            cloneWrap.appendChild(cloneTable);
            document.body.appendChild(cloneWrap);
            syncWidths(); syncScroll();
            // Sync header state into freshly created clone
            updateSelectAllState();
            cloneWrap.style.display = 'none';
        }
        function syncWidths() {
            if (!cloneTable) return;
            const origCells  = thead.querySelectorAll('th');
            const cloneCells = cloneTable.querySelectorAll('th');
            let totalWidth = 0;
            origCells.forEach((cell, i) => {
                const w = cell.getBoundingClientRect().width;
                totalWidth += w;
                if (cloneCells[i]) cloneCells[i].style.cssText = `box-sizing:border-box;width:${w}px;min-width:${w}px;max-width:${w}px;`;
            });
            cloneTable.style.width = totalWidth + 'px';
        }
        function syncScroll() {
            if (!cloneWrap) return;
            const r = stickyContainer.getBoundingClientRect();
            const inset = headerInset();
            cloneWrap.style.left  = r.left + 'px';
            cloneWrap.style.width = r.width + 'px';
            cloneWrap.style.top   = inset + 'px';
            cloneTable.style.marginLeft = -stickyContainer.scrollLeft + 'px';
        }
        function onScroll() {
            if (!cloneWrap) return;
            const tableRect = stickyTable.getBoundingClientRect();
            const inset     = headerInset();
            if (tableRect.top < inset && tableRect.bottom > (inset + thead.offsetHeight)) {
                cloneWrap.style.display = 'block';
                syncScroll();
                updateSelectAllState();
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

    // Filter input validation
    ['filterRatingMin', 'filterRatingMax'].forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', function (e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('blur', function () {
            const raw = this.value.trim();
            if (raw === '') return;
            const num = parseInt(raw, 10);
            this.value = (isNaN(num) || num < 1 || num > 10) ? '' : num;
        });
    });

    ['filterVolumeMin', 'filterVolumeMax'].forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', function (e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('blur',  function () {
            const raw = String(this.value).replace(/\./g, '').trim();
            const num = parseInt(raw, 10);
            this.value = (!isNaN(num) && raw !== '') ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        });
        el.addEventListener('focus', function () { this.value = this.value.replace(/\./g, ''); });
    });
});
