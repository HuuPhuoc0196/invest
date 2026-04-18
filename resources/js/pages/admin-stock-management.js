const { stocks } = window.__pageData || {};

window.currentSortKey = 'valuation';
window.currentSortDir = 'asc';

function sortByColumn(key) {
    if (window.currentSortKey === key) {
        window.currentSortDir = window.currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        window.currentSortKey = key;
        window.currentSortDir = 'asc';
    }
    updateSortIcons();
    renderStockTable(getFilteredStocks());
}
window.sortByColumn = sortByColumn;

function updateSortIcons() {
    document.querySelectorAll('th[data-sort-key]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = '⇅';
    });
    document.querySelectorAll('th[data-sort-key="' + window.currentSortKey + '"]').forEach(th => {
        th.classList.add(window.currentSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = window.currentSortDir === 'asc' ? '▲' : '▼';
    });
}

var renderReadyAttempts = 0;
var renderReadyMaxAttempts = 200;

function runWhenRenderReady() {
    if (typeof window.renderStockTable === 'function') {
        updateSortIcons();
        renderStockTable(stocks || []);
    } else if (renderReadyAttempts < renderReadyMaxAttempts) {
        renderReadyAttempts++;
        setTimeout(runWhenRenderReady, 30);
    }
}
document.addEventListener('DOMContentLoaded', runWhenRenderReady);

function getFilteredStocks() {
    const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
    const risk = document.getElementById('filterRisk').value;
    const stocksVn = document.getElementById('filterStocksVn').value;
    const ratingMin = document.getElementById('filterRatingMin').value;
    const ratingMax = document.getElementById('filterRatingMax').value;
    const volumeMin = document.getElementById('filterVolumeMin').value.replace(/\./g, '');
    const volumeMax = document.getElementById('filterVolumeMax').value.replace(/\./g, '');
    const volumeRawMin = document.getElementById('filterVolumeRawMin').value.replace(/\./g, '');
    const volumeRawMax = document.getElementById('filterVolumeRawMax').value.replace(/\./g, '');
    const valuationMin = document.getElementById('filterValuationMin').value;
    const valuationMax = document.getElementById('filterValuationMax').value;

    return (stocks || []).filter(stock => {
        if (keyword && !stock.code.includes(keyword)) return false;
        if (risk && Number(stock.risk_level) !== Number(risk)) return false;
        if (stocksVn === '30') { if (Number(stock.stocks_vn) !== 30) return false; }
        else if (stocksVn === '100') { if (![30, 100].includes(Number(stock.stocks_vn))) return false; }

        const rating = parseFloat(stock.rating_stocks);
        if (ratingMin !== '' && (isNaN(rating) || rating < parseInt(ratingMin, 10))) return false;
        if (ratingMax !== '' && (isNaN(rating) || rating > parseInt(ratingMax, 10))) return false;

        const vol = parseFloat(stock.volume_avg) || 0;
        if (volumeMin !== '' && vol < parseFloat(volumeMin)) return false;
        if (volumeMax !== '' && vol > parseFloat(volumeMax)) return false;
        const volRaw = parseFloat(stock.volume) || 0;
        if (volumeRawMin !== '' && volRaw < parseFloat(volumeRawMin)) return false;
        if (volumeRawMax !== '' && volRaw > parseFloat(volumeRawMax)) return false;

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

function searchStock() { window.clearFollowSelection && window.clearFollowSelection(); renderStockTable(getFilteredStocks()); }
window.searchStock = searchStock;

function applyFilter() { window.clearFollowSelection && window.clearFollowSelection(); renderStockTable(getFilteredStocks()); }
window.applyFilter = applyFilter;

function resetFilter() {
    ['filterRisk', 'filterStocksVn', 'filterRatingMin', 'filterRatingMax',
     'filterVolumeMin', 'filterVolumeMax', 'filterVolumeRawMin', 'filterVolumeRawMax',
     'filterValuationMin', 'filterValuationMax', 'searchInput']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    window.clearFollowSelection && window.clearFollowSelection();
    renderStockTable(stocks || []);
}
window.resetFilter = resetFilter;

function dynamicSort(data) {
    data.sort((a, b) => {
        let valA, valB;
        if (window.currentSortKey === 'valuation') {
            const buyA = parseFloat(a.recommended_buy_price) || 1;
            const curA = parseFloat(a.current_price) || 0;
            valA = buyA !== 0 ? ((curA - buyA) / buyA) * 100 : 0;
            const buyB = parseFloat(b.recommended_buy_price) || 1;
            const curB = parseFloat(b.current_price) || 0;
            valB = buyB !== 0 ? ((curB - buyB) / buyB) * 100 : 0;
        } else if (window.currentSortKey === 'code') {
            valA = (a.code || '').toString();
            valB = (b.code || '').toString();
            return window.currentSortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
        } else {
            valA = parseFloat(a[window.currentSortKey]) || 0;
            valB = parseFloat(b[window.currentSortKey]) || 0;
        }
        return window.currentSortDir === 'asc' ? valA - valB : valB - valA;
    });
}
window.dynamicSort = dynamicSort;

function toggleFilter() {
    const body = document.getElementById('filterBody');
    const icon = document.getElementById('filterToggleIcon');
    if (body.style.display === 'none') { body.style.display = 'block'; icon.textContent = '▲'; }
    else { body.style.display = 'none'; icon.textContent = '▼'; }
}
window.toggleFilter = toggleFilter;

// Format KL trung bình: hiển thị dạng 1.000 / 100.000 cho dễ đọc
function formatVolume(val) {
    const raw = String(val).replace(/\./g, '').trim();
    const num = parseInt(raw, 10);
    if (isNaN(num) || raw === '') return '';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

document.addEventListener('DOMContentLoaded', function() {
    // Điểm: chỉ nhập số nguyên 1-10, blur ra ngoài range → reset ''
    ['filterRatingMin', 'filterRatingMax'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;

        el.addEventListener('keydown', function(e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (allowed.includes(e.key)) return;
            if (e.ctrlKey || e.metaKey) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });

        el.addEventListener('blur', function() {
            const raw = this.value.trim();
            if (raw === '') return;
            const num = parseInt(raw, 10);
            if (isNaN(num) || num < 1 || num > 10) {
                this.value = '';
            } else {
                this.value = num;
            }
        });
    });

    ['filterVolumeMin', 'filterVolumeMax', 'filterVolumeRawMin', 'filterVolumeRawMax'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;

        // Chỉ cho nhập số và phím điều hướng
        el.addEventListener('keydown', function(e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (allowed.includes(e.key)) return;
            if (e.ctrlKey || e.metaKey) return; // cho phép Ctrl+A, Ctrl+C...
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });

        // Khi rời field: format hiển thị
        el.addEventListener('blur', function() {
            const formatted = formatVolume(this.value);
            this.value = formatted;
        });

        // Khi vào field: trả về số thô để user gõ tiếp
        el.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    });
});

// ========================================
// ADMIN FOLLOW CHECKBOX LOGIC (PHASE 2)
// ========================================
(function() {
    const { adminFollowedStockIds } = window.__pageData || {};
    let selectedStockIds = new Set();

    // Override renderStockTable to include follow checkbox
    const originalRender = window.renderStockTable;
    const tbody = document.getElementById('stockTableBody');
    
    window.renderStockTable = function(data) {
        if (!tbody) return;
        selectedStockIds.clear();

        window.dynamicSort(data);

        const { baseUrl } = window.__pageData || {};

        tbody.innerHTML = '';

        data.forEach(stock => {
            const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
            const currentPrice = parseFloat(stock.current_price) || 0;
            const sellPrice = stock.recommended_sell_price ? Number(stock.recommended_sell_price).toLocaleString('vi-VN') : 'N/A';
            const volumeRaw = stock.volume ? Number(stock.volume).toLocaleString('vi-VN') : 'N/A';
            const volumeAvg = stock.volume_avg ? Number(stock.volume_avg).toLocaleString('vi-VN') : 'N/A';

            const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100).toFixed(2) : 0;
            let valuationColor = 'yellow';
            let sign = '';
            if (valuation > 0) { valuationColor = 'green'; sign = '+'; }
            else if (valuation < 0) { valuationColor = 'red'; sign = ''; }

            const isFollowed = adminFollowedStockIds && adminFollowedStockIds.includes(stock.id);
            const isSelected = selectedStockIds.has(stock.id);
            const checkboxAttrs = isFollowed ? ' checked disabled' : (isSelected ? ' checked' : '');
            const followCell = `<label class="cell-label-select"><input type="checkbox" class="follow-checkbox" data-stock-id="${stock.id}"${checkboxAttrs}></label>`;

            const row = document.createElement('tr');
            row.setAttribute('data-stock-id', stock.id);
            row.className = getRowClass(buyPrice, currentPrice);
            row.innerHTML = `
                <td class="col-select">${followCell}</td>
                <td class="col-code-sticky td-code-toggle">${stock.code}</td>
                <td>${[30, 100].includes(Number(stock.stocks_vn)) ? Number(stock.stocks_vn) : 'ALL'}</td>
                <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
                <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
                <td>${sellPrice}</td>
                <td>${stock.price_avg != null ? Number(stock.price_avg).toLocaleString('vi-VN') : 'N/A'}</td>
                <td style="color: ${getRisk(stock.risk_level).color}">${getRisk(stock.risk_level).label}</td>
                <td>${stock.percent_buy != null ? parseFloat(stock.percent_buy) + '%' : 'N/A'}</td>
                <td>${stock.percent_sell != null ? parseFloat(stock.percent_sell) + '%' : 'N/A'}</td>
                <td>${getRatingBadge(stock.rating_stocks)}</td>
                <td>${volumeRaw}</td>
                <td>${volumeAvg}</td>
                <td style="color: ${valuationColor}; font-weight: bold;">${sign}${valuation}%</td>
                <td>
                    <button onclick="location.href='${baseUrl}/admin/update/${stock.code}'">Cập nhật</button>
                    <button class="btn-delete" onclick="confirmDelete('${stock.code}')">Xoá</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Re-sync sticky header clone widths after table data changes
        window.dispatchEvent(new Event('resize'));

        // Attach checkbox listeners
        tbody.querySelectorAll('.follow-checkbox:not(:disabled)').forEach(cb => {
            cb.addEventListener('change', function() {
                const stockId = parseInt(this.dataset.stockId);
                if (this.checked) {
                    selectedStockIds.add(stockId);
                } else {
                    selectedStockIds.delete(stockId);
                }
                updateAddFollowButtonState();
                updateSelectAllState();
            });
        });

        // Click on code = toggle checkbox
        tbody.querySelectorAll('.td-code-toggle').forEach(td => {
            td.addEventListener('click', function(e) {
                e.preventDefault();
                const row = td.closest('tr');
                const cb = row.querySelector('.follow-checkbox:not(:disabled)');
                if (cb) {
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change'));
                }
            });
        });

        // Sync button & header state after every render
        updateAddFollowButtonState();
        updateSelectAllState();
    };

    function updateAddFollowButtonState() {
        const btn = document.getElementById('btnAddFollowAdmin');
        if (btn) {
            btn.disabled = selectedStockIds.size === 0;
        }
    }

    function updateSelectAllState() {
        const thSelectAll = document.getElementById('thSelectAll');
        if (!thSelectAll) return;

        const availableCheckboxes = Array.from(document.querySelectorAll('.follow-checkbox:not(:disabled)'));
        const checkedCount = availableCheckboxes.filter(cb => cb.checked).length;
        const allCount = availableCheckboxes.length;
        const allChecked = allCount > 0 && checkedCount === allCount;
        const someChecked = checkedCount > 0 && checkedCount < allCount;
        const state = thSelectAll.querySelector('.th-select-all__state');

        if (state) {
            state.textContent = allChecked ? '✓' : (someChecked ? '−' : '');
        }

        thSelectAll.title = allChecked ? 'Bỏ theo dõi tất cả' : 'Chọn tất cả';
        thSelectAll.classList.toggle('th-select-all--active', allChecked);
        thSelectAll.classList.toggle('th-select-all--partial', someChecked);
        thSelectAll.classList.remove('checked');
    }

    window.clearFollowSelection = function() {
        selectedStockIds.clear();
        updateAddFollowButtonState();
        updateSelectAllState();
    };

    window.toggleSelectAllAdmin = function() {
        const checkboxes = document.querySelectorAll('.follow-checkbox:not(:disabled)');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb => {
            const stockId = parseInt(cb.dataset.stockId);
            cb.checked = !allChecked;
            if (!allChecked) {
                selectedStockIds.add(stockId);
            } else {
                selectedStockIds.delete(stockId);
            }
        });

        updateAddFollowButtonState();
        updateSelectAllState();
    };

    window.submitAddFollowAdmin = async function() {
        const btn = document.getElementById('btnAddFollowAdmin');
        if (!btn || btn.disabled) return;

        const stockIds = Array.from(selectedStockIds);
        if (stockIds.length === 0) return;

        btn.disabled = true;
        btn.textContent = '⏳ Đang thêm...';

        try {
            const { baseUrl } = window.__pageData || {};
            const response = await fetch(`${baseUrl}/admin/stocks/follow/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ stock_ids: stockIds })
            });

            const result = await response.json();

            if (result.success) {
                showAddFollowNotice('success', `✅ ${result.message}`);
            } else {
                showAddFollowNotice('error', '❌ Có lỗi xảy ra. Vui lòng thử lại.');
                btn.disabled = false;
                btn.textContent = '➕ Thêm theo dõi';
            }
        } catch (error) {
            console.error('Error:', error);
            showAddFollowNotice('error', '❌ Không thể kết nối server.');
            btn.disabled = false;
            btn.textContent = '➕ Thêm theo dõi';
        }
    };

    function showAddFollowNotice(type, message) {
        const modal = document.getElementById('addFollowNoticeModal');
        const title = document.getElementById('addFollowNoticeTitle');
        const msg = document.getElementById('addFollowNoticeMessage');
        if (!modal || !title || !msg) return;

        modal.classList.remove('is-success', 'is-error');
        modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
        title.textContent = type === 'success' ? 'Thành công' : 'Lỗi';
        msg.innerHTML = message;
        modal.style.display = 'flex';
    }

    window.closeAddFollowNoticeModal = function() {
        const modal = document.getElementById('addFollowNoticeModal');
        if (!modal) return;
        const isSuccess = modal.classList.contains('is-success');
        modal.style.display = 'none';
        if (isSuccess) {
            window.location.reload();
        }
    };

    function formatNumber(num) {
        if (!num) return '0';
        return parseFloat(num).toLocaleString('vi-VN');
    }

    function getRiskBadge(level) {
        const badges = {
            1: '<span style="color:#27ae60;">An toàn</span>',
            2: '<span style="color:#f39c12;">Cảnh báo</span>',
            3: '<span style="color:#e74c3c;">Hạn chế GD</span>',
            4: '<span style="color:#c0392b;">Đình chỉ/Huỷ</span>'
        };
        return badges[level] || 'Chưa xác định';
    }

    function calculateValuation(stock) {
        const recommended = parseFloat(stock.recommended_buy_price);
        const current = parseFloat(stock.current_price);
        if (!recommended || recommended === 0) return '-';
        const percent = ((current - recommended) / recommended) * 100;
        const color = percent >= 0 ? 'green' : 'red';
        return `<span style="color:${color}">${percent.toFixed(2)}%</span>`;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            updateAddFollowButtonState();
            updateSelectAllState();
        }, 100);

        // Click outside to close addFollowNoticeModal
        const addFollowNoticeModal = document.getElementById('addFollowNoticeModal');
        if (addFollowNoticeModal) {
            addFollowNoticeModal.addEventListener('click', function(e) {
                if (e.target === this) closeAddFollowNoticeModal();
            });
        }
    });
})();
