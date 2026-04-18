// Admin Stock Suggest - with checkbox selection and bulk delete
(function () {
    const { baseUrl, stocks } = window.__pageData || {};

    let selectedStockIds = new Set();
    let pendingDeleteStockId = null;

    // Sort state
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
        renderTable(getFilteredStocks());
    }
    window.sortByColumn = sortByColumn;

    function updateSortIcons() {
        document.querySelectorAll('th[data-sort-key]').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
            const icon = th.querySelector('.sort-icon');
            if (icon) icon.textContent = '⇅';
        });
        document.querySelectorAll(`th[data-sort-key="${window.currentSortKey}"]`).forEach(th => {
            th.classList.add(window.currentSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
            const icon = th.querySelector('.sort-icon');
            if (icon) icon.textContent = window.currentSortDir === 'asc' ? '▲' : '▼';
        });
    }

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

    // Use helper functions from AdminStockManagement.js
    const getRowClass = window.getRowClass || function() { return ''; };
    const getRisk = window.getRisk || function(l) { return { label: '', color: '' }; };
    const getRatingBadge = window.getRatingBadge || function() { return '-'; };

    function renderTable(data) {
        dynamicSort(data);
        const tbody = document.getElementById('stockTableBody');
        if (!tbody) return;

        selectedStockIds.clear();
        tbody.innerHTML = '';

        data.forEach(stock => {
            const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
            const currentPrice = parseFloat(stock.current_price) || 0;
            const sellPrice = stock.recommended_sell_price ? Number(stock.recommended_sell_price).toLocaleString('vi-VN') : 'N/A';
            const volumeAvg = stock.volume_avg ? Number(stock.volume_avg).toLocaleString('vi-VN') : 'N/A';

            const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100).toFixed(2) : 0;
            let valuationColor = 'yellow';
            let sign = '';
            if (valuation > 0) { valuationColor = 'green'; sign = '+'; }
            else if (valuation < 0) { valuationColor = 'red'; sign = ''; }

            const isSelected = selectedStockIds.has(stock.id);
            const selectCell = `<label class="cell-label-select"><input type="checkbox" class="select-checkbox" data-stock-id="${stock.id}"${isSelected ? ' checked' : ''}></label>`;

            const row = document.createElement('tr');
            row.setAttribute('data-stock-id', stock.id);
            row.className = getRowClass(buyPrice, currentPrice);
            row.innerHTML = `
                <td class="col-select">${selectCell}</td>
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
                <td>${formatNumber(stock.volume)}</td>
                <td>${volumeAvg}</td>
                <td style="color: ${valuationColor}; font-weight: bold;">${sign}${valuation}%</td>
                <td>
                    <button class="btn-delete" onclick="confirmDeleteSuggest(${stock.id}, '${stock.code}')">Xoá</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Re-sync sticky header clone widths after table data changes
        window.dispatchEvent(new Event('resize'));

        // Attach checkbox listeners
        tbody.querySelectorAll('.select-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const stockId = parseInt(this.dataset.stockId);
                if (this.checked) {
                    selectedStockIds.add(stockId);
                } else {
                    selectedStockIds.delete(stockId);
                }
                updateDeleteBatchButtonState();
                updateSelectAllState();
            });
        });

        // Click on code = toggle checkbox
        tbody.querySelectorAll('.td-code-toggle').forEach(td => {
            td.addEventListener('click', function(e) {
                e.preventDefault();
                const row = td.closest('tr');
                const cb = row.querySelector('.select-checkbox');
                if (cb) {
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change'));
                }
            });
        });

        updateDeleteBatchButtonState();
        updateSelectAllState();
    }

    function updateDeleteBatchButtonState() {
        const btn = document.getElementById('btnDeleteSuggestBatch');
        if (btn) {
            btn.disabled = selectedStockIds.size === 0;
        }
    }

    function updateSelectAllState() {
        const thSelectAll = document.getElementById('thSelectAll');
        if (!thSelectAll) return;

        const allCheckboxes = Array.from(document.querySelectorAll('.select-checkbox'));
        const checkedCount = allCheckboxes.filter(cb => cb.checked).length;
        const allCount = allCheckboxes.length;
        const allChecked = allCount > 0 && checkedCount === allCount;
        const someChecked = checkedCount > 0 && checkedCount < allCount;
        const state = thSelectAll.querySelector('.th-select-all__state');

        if (state) {
            state.textContent = allChecked ? '✓' : (someChecked ? '−' : '');
        }

        thSelectAll.title = allChecked ? 'Bỏ chọn tất cả' : 'Chọn tất cả';
        thSelectAll.classList.toggle('th-select-all--active', allChecked);
        thSelectAll.classList.toggle('th-select-all--partial', someChecked);
        thSelectAll.classList.remove('checked');
    }

    window.toggleSelectAllSuggest = function() {
        const allCheckboxes = Array.from(document.querySelectorAll('.select-checkbox'));
        const allChecked = allCheckboxes.every(cb => cb.checked);

        allCheckboxes.forEach(cb => {
            cb.checked = !allChecked;
            const stockId = parseInt(cb.dataset.stockId);
            if (!allChecked) {
                selectedStockIds.add(stockId);
            } else {
                selectedStockIds.delete(stockId);
            }
        });

        updateDeleteBatchButtonState();
        updateSelectAllState();
    };

    // ========== Batch Delete ==========
    window.confirmDeleteSuggestBatch = function() {
        if (selectedStockIds.size === 0) return;

        const modal = document.getElementById('deleteSuggestBatchModal');
        const countEl = document.getElementById('deleteSuggestBatchCount');
        const btn = document.getElementById('btnDeleteSuggestBatchConfirm');
        if (!modal) return;

        if (countEl) countEl.textContent = selectedStockIds.size;
        if (btn) { btn.disabled = false; btn.textContent = 'Đồng ý'; }
        modal.style.display = 'flex';
    };

    window.closeDeleteSuggestBatchModal = function() {
        const modal = document.getElementById('deleteSuggestBatchModal');
        if (modal) modal.style.display = 'none';
    };

    window.runDeleteSuggestBatch = async function() {
        const btn = document.getElementById('btnDeleteSuggestBatchConfirm');
        const stockIds = Array.from(selectedStockIds);
        if (stockIds.length === 0) return;

        if (btn) { btn.disabled = true; btn.textContent = 'Đang xoá...'; }

        try {
            const response = await fetch(`${baseUrl}/admin/stocks/suggest/batch-delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stock_ids: stockIds })
            });
            const result = await response.json();
            closeDeleteSuggestBatchModal();

            if (result.success) {
                selectedStockIds.clear();
                showNoticeModal('success', `✅ ${result.message}`);
            } else {
                showNoticeModal('error', '❌ Không thể xóa. Vui lòng thử lại.');
            }
        } catch (error) {
            console.error('Error:', error);
            closeDeleteSuggestBatchModal();
            showNoticeModal('error', '❌ Không thể kết nối server.');
        }
    };

    // ========== Single Delete ==========
    window.confirmDeleteSuggest = function(stockId, stockCode) {
        const modal = document.getElementById('deleteSuggestModal');
        const codeEl = document.getElementById('deleteSuggestCode');
        const btn = document.getElementById('btnDeleteSuggestConfirm');
        if (!modal || !codeEl) return;

        pendingDeleteStockId = stockId;
        codeEl.textContent = stockCode;
        if (btn) { btn.disabled = false; btn.textContent = 'Đồng ý'; }
        modal.style.display = 'flex';
    };

    window.closeDeleteSuggestModal = function() {
        const modal = document.getElementById('deleteSuggestModal');
        if (!modal) return;
        pendingDeleteStockId = null;
        modal.style.display = 'none';
    };

    window.runDeleteSuggest = async function() {
        const stockId = pendingDeleteStockId;
        const btn = document.getElementById('btnDeleteSuggestConfirm');
        if (!stockId) return;

        if (btn) { btn.disabled = true; btn.textContent = 'Đang xoá...'; }

        try {
            const response = await fetch(`${baseUrl}/admin/stocks/suggest/${stockId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            closeDeleteSuggestModal();

            if (result.success) {
                showNoticeModal('success', '✅ Đã xóa khỏi danh sách gợi ý');
            } else {
                showNoticeModal('error', '❌ Không thể xóa. Vui lòng thử lại.');
            }
        } catch (error) {
            console.error('Error:', error);
            closeDeleteSuggestModal();
            showNoticeModal('error', '❌ Không thể kết nối server.');
        }
    };

    function showNoticeModal(type, message) {
        const modal = document.getElementById('deleteSuggestNoticeModal');
        const title = document.getElementById('deleteSuggestNoticeTitle');
        const msg = document.getElementById('deleteSuggestNoticeMessage');
        if (!modal || !title || !msg) return;

        modal.classList.remove('is-success', 'is-error');
        modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
        title.textContent = type === 'success' ? 'Thành công' : 'Lỗi';
        msg.innerHTML = message;
        modal.style.display = 'flex';
    }

    window.closeDeleteSuggestNoticeModal = function() {
        const modal = document.getElementById('deleteSuggestNoticeModal');
        if (!modal) return;
        modal.style.display = 'none';
        if (modal.classList.contains('is-success')) {
            window.location.reload();
        }
    };

    // ========== Filter Logic (matching admin/stocks) ==========
    function getFilteredStocks() {
        const keyword = (document.getElementById('searchInput')?.value || '').trim().toUpperCase();
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

    function clearSelection() {
        selectedStockIds.clear();
        updateDeleteBatchButtonState();
        updateSelectAllState();
    }

    function searchStock() { clearSelection(); renderTable(getFilteredStocks()); }
    window.searchStock = searchStock;

    function applyFilter() { clearSelection(); renderTable(getFilteredStocks()); }
    window.applyFilter = applyFilter;

    function resetFilter() {
        ['filterRisk', 'filterStocksVn', 'filterRatingMin', 'filterRatingMax',
         'filterVolumeMin', 'filterVolumeMax', 'filterVolumeRawMin', 'filterVolumeRawMax',
         'filterValuationMin', 'filterValuationMax', 'searchInput']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        clearSelection();
        renderTable(stocks || []);
    }
    window.resetFilter = resetFilter;

    function toggleFilter() {
        const body = document.getElementById('filterBody');
        const icon = document.getElementById('filterToggleIcon');
        if (body.style.display === 'none') { body.style.display = 'block'; icon.textContent = '▲'; }
        else { body.style.display = 'none'; icon.textContent = '▼'; }
    }
    window.toggleFilter = toggleFilter;

    function formatVolume(val) {
        const raw = String(val).replace(/\./g, '').trim();
        const num = parseInt(raw, 10);
        if (isNaN(num) || raw === '') return '';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatNumber(num) {
        if (!num) return '0';
        return parseFloat(num).toLocaleString('vi-VN');
    }

    // ========== Initialize ==========
    document.addEventListener('DOMContentLoaded', function() {
        updateSortIcons();
        renderTable(stocks || []);
        updateDeleteBatchButtonState();

        // Filter input validation (matching admin/stocks)
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
                if (isNaN(num) || num < 1 || num > 10) { this.value = ''; }
                else { this.value = num; }
            });
        });

        ['filterVolumeMin', 'filterVolumeMax', 'filterVolumeRawMin', 'filterVolumeRawMax'].forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('keydown', function(e) {
                const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
                if (allowed.includes(e.key)) return;
                if (e.ctrlKey || e.metaKey) return;
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
})();
