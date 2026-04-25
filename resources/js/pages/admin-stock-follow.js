// Admin Stock Follow - Suggest functionality
(function () {
    const { baseUrl, stocks, adminSuggestedStockIds } = window.__pageData || {};

    let selectedStockIds = new Set();
    let allStockIds = [];
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

    function removeStocksByIds(stockIds) {
        const removeSet = new Set((stockIds || []).map(Number));
        if (!removeSet.size || !Array.isArray(stocks)) return;

        for (let i = stocks.length - 1; i >= 0; i--) {
            if (removeSet.has(Number(stocks[i].id))) {
                stocks.splice(i, 1);
            }
        }

        if (Array.isArray(adminSuggestedStockIds)) {
            for (let i = adminSuggestedStockIds.length - 1; i >= 0; i--) {
                if (removeSet.has(Number(adminSuggestedStockIds[i]))) {
                    adminSuggestedStockIds.splice(i, 1);
                }
            }
        }
    }

    function addSuggestedByIds(stockIds) {
        if (!Array.isArray(adminSuggestedStockIds)) return;
        const existing = new Set(adminSuggestedStockIds.map(Number));
        (stockIds || []).forEach((id) => {
            const numId = Number(id);
            if (!existing.has(numId)) {
                adminSuggestedStockIds.push(numId);
                existing.add(numId);
            }
        });
    }

    function refreshCurrentView() {
        clearSelection();
        renderTable(getFilteredStocks());
    }

    function renderTable(data) {
        dynamicSort(data);
        const tbody = document.getElementById('stockTableBody');
        if (!tbody) return;

        selectedStockIds.clear();
        allStockIds = data.map(stock => stock.id);
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

            const isSuggested = adminSuggestedStockIds.includes(stock.id);
            const isSelected = selectedStockIds.has(stock.id);
            const checkboxAttrs = isSuggested ? ' checked disabled' : (isSelected ? ' checked' : '');
            const suggestCell = `<label class="cell-label-select"><input type="checkbox" class="suggest-checkbox" data-stock-id="${stock.id}"${checkboxAttrs}></label>`;

            const row = document.createElement('tr');
            row.setAttribute('data-stock-id', stock.id);
            row.className = getRowClass(buyPrice, currentPrice);
            row.innerHTML = `
                <td class="col-select">${suggestCell}</td>
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
                    <button class="btn-delete" onclick="confirmDeleteFollow(${stock.id}, '${stock.code}')">Xoá</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Re-sync sticky header clone widths after table data changes
        window.dispatchEvent(new Event('resize'));

        // Attach checkbox listeners
        tbody.querySelectorAll('.suggest-checkbox:not(:disabled)').forEach(cb => {
            cb.addEventListener('change', function() {
                const stockId = parseInt(this.dataset.stockId);
                if (this.checked) {
                    selectedStockIds.add(stockId);
                } else {
                    selectedStockIds.delete(stockId);
                }
                updateAddSuggestButtonState();
                updateSelectAllState();
            });
        });

        // Click on code = toggle checkbox
        tbody.querySelectorAll('.td-code-toggle').forEach(td => {
            td.addEventListener('click', function(e) {
                e.preventDefault();
                const row = td.closest('tr');
                const cb = row.querySelector('.suggest-checkbox:not(:disabled)');
                if (cb) {
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change'));
                }
            });
        });

        // Sync button & header state after every render
        updateAddSuggestButtonState();
        updateDeleteBatchButtonState();
        updateSelectAllState();
    }

    function updateAddSuggestButtonState() {
        const btn = document.getElementById('btnAddSuggestAdmin');
        if (btn) {
            btn.disabled = selectedStockIds.size === 0;
        }
    }

function updateDeleteBatchButtonState() {
    const btn = document.getElementById('btnDeleteFollowBatch');
    if (btn) {
        btn.disabled = allStockIds.length === 0;
    }
}

    function updateSelectAllState() {
        const thSelectAll = document.getElementById('thSelectAll');
        if (!thSelectAll) return;

        const availableCheckboxes = Array.from(document.querySelectorAll('.suggest-checkbox:not(:disabled)'));
        const checkedCount = availableCheckboxes.filter(cb => cb.checked).length;
        const allCount = availableCheckboxes.length;
        const allChecked = allCount > 0 && checkedCount === allCount;
        const someChecked = checkedCount > 0 && checkedCount < allCount;
        const state = thSelectAll.querySelector('.th-select-all__state');

        if (state) {
            state.textContent = allChecked ? '✓' : (someChecked ? '−' : '');
        }

        thSelectAll.title = allChecked ? 'Bỏ gợi ý tất cả' : 'Gợi ý tất cả';
        thSelectAll.classList.toggle('th-select-all--active', allChecked);
        thSelectAll.classList.toggle('th-select-all--partial', someChecked);
        thSelectAll.classList.remove('checked');
    }

    window.toggleSelectAllSuggest = function() {
        const availableCheckboxes = Array.from(document.querySelectorAll('.suggest-checkbox:not(:disabled)'));
        const allChecked = availableCheckboxes.every(cb => cb.checked);

        availableCheckboxes.forEach(cb => {
            cb.checked = !allChecked;
            const stockId = parseInt(cb.dataset.stockId);
            if (!allChecked) {
                selectedStockIds.add(stockId);
            } else {
                selectedStockIds.delete(stockId);
            }
        });

        updateAddSuggestButtonState();
        updateDeleteBatchButtonState();
        updateSelectAllState();
    };

    window.submitAddSuggestAdmin = async function() {
        const btn = document.getElementById('btnAddSuggestAdmin');
        if (!btn || btn.disabled) return;

        const stockIds = Array.from(selectedStockIds);
        if (stockIds.length === 0) return;

        btn.disabled = true;
        btn.textContent = '⏳ Đang thêm...';

        try {
            const response = await fetch(`${baseUrl}/admin/stocks/suggest/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stock_ids: stockIds })
            });

            const result = await response.json();

            if (result.success) {
                addSuggestedByIds(stockIds);
                refreshCurrentView();
                btn.textContent = '💡 Thêm gợi ý';
                showNoticeModal('success', `✅ ${result.message}`);
            } else {
                showNoticeModal('error', '❌ Có lỗi xảy ra. Vui lòng thử lại.');
                btn.disabled = false;
                btn.textContent = '💡 Thêm gợi ý';
            }
        } catch (error) {
            console.error('Error:', error);
            showNoticeModal('error', '❌ Không thể kết nối server. Vui lòng thử lại.');
            btn.disabled = false;
            btn.textContent = '💡 Thêm gợi ý';
        }
    };

    // ========== Delete Follow ==========
    window.confirmDeleteFollow = function(stockId, stockCode) {
        const modal = document.getElementById('deleteFollowModal');
        const codeEl = document.getElementById('deleteFollowCode');
        const btn = document.getElementById('btnDeleteFollowConfirm');
        if (!modal || !codeEl) return;

        pendingDeleteStockId = stockId;
        codeEl.textContent = stockCode;
        if (btn) { btn.disabled = false; btn.textContent = 'Đồng ý'; }
        modal.style.display = 'flex';
    };

    window.closeDeleteFollowModal = function() {
        const modal = document.getElementById('deleteFollowModal');
        if (!modal) return;
        pendingDeleteStockId = null;
        modal.style.display = 'none';
    };

    window.runDeleteFollow = async function() {
        const stockId = pendingDeleteStockId;
        const btn = document.getElementById('btnDeleteFollowConfirm');
        if (!stockId) return;

        if (btn) { btn.disabled = true; btn.textContent = 'Đang xoá...'; }

        try {
            const response = await fetch(`${baseUrl}/admin/stocks/follow/${stockId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            closeDeleteFollowModal();

            if (result.success) {
                removeStocksByIds([stockId]);
                refreshCurrentView();
                showNoticeModal('success', '✅ Đã xóa khỏi danh sách theo dõi');
            } else {
                showNoticeModal('error', '❌ Không thể xóa. Vui lòng thử lại.');
            }
        } catch (error) {
            console.error('Error:', error);
            closeDeleteFollowModal();
            showNoticeModal('error', '❌ Không thể kết nối server.');
        }
    };

    // ========== Batch Delete Follow (filtered set) ==========
    window.confirmDeleteFollowBatch = function() {
        if (!allStockIds.length) return;

        const modal = document.getElementById('deleteFollowBatchModal');
        const countEl = document.getElementById('deleteFollowBatchCount');
        const btn = document.getElementById('btnDeleteFollowBatchConfirm');
        if (!modal) return;

        if (countEl) countEl.textContent = allStockIds.length;
        if (btn) { btn.disabled = false; btn.textContent = 'Đồng ý'; }
        modal.style.display = 'flex';
    };

    window.closeDeleteFollowBatchModal = function() {
        const modal = document.getElementById('deleteFollowBatchModal');
        if (modal) modal.style.display = 'none';
    };

    window.runDeleteFollowBatch = async function() {
        const btn = document.getElementById('btnDeleteFollowBatchConfirm');
        const stockIds = [...allStockIds];
        if (stockIds.length === 0) return;

        if (btn) { btn.disabled = true; btn.textContent = 'Đang xoá...'; }

        try {
            const response = await fetch(`${baseUrl}/admin/stocks/follow/batch-delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stock_ids: stockIds })
            });
            const result = await response.json();
            closeDeleteFollowBatchModal();

            if (result.success) {
                removeStocksByIds(stockIds);
                refreshCurrentView();
                showNoticeModal('success', `✅ ${result.message}`);
            } else {
                showNoticeModal('error', '❌ Không thể xoá theo filter hiện tại. Vui lòng thử lại.');
            }
        } catch (error) {
            console.error('Error:', error);
            closeDeleteFollowBatchModal();
            showNoticeModal('error', '❌ Không thể kết nối server.');
        }
    };

    function showNoticeModal(type, message) {
        const modal = document.getElementById('deleteFollowNoticeModal');
        const title = document.getElementById('deleteFollowNoticeTitle');
        const msg = document.getElementById('deleteFollowNoticeMessage');
        if (!modal || !title || !msg) return;

        modal.classList.remove('is-success', 'is-error');
        modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
        title.textContent = type === 'success' ? 'Thành công' : 'Lỗi';
        msg.innerHTML = message;
        modal.style.display = 'flex';
    }

    window.closeDeleteFollowNoticeModal = function() {
        const modal = document.getElementById('deleteFollowNoticeModal');
        if (!modal) return;
        modal.style.display = 'none';
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
        updateAddSuggestButtonState();
        updateDeleteBatchButtonState();
        updateSelectAllState();
    }

    function searchStock() { clearSelection(); renderTable(getFilteredStocks()); }
    window.searchStock = searchStock;

    function applyFilter() { clearSelection(); renderTable(getFilteredStocks()); }
    window.applyFilter = applyFilter;

    function resetFilter() {
        ['filterRisk', 'filterStocksVn', 'filterRatingMin', 'filterRatingMax',
         'filterVolumeMin', 'filterVolumeMax', 'filterValuationMin', 'filterValuationMax', 'searchInput']
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

    // Format KL trung bình
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
        updateAddSuggestButtonState();
        updateDeleteBatchButtonState();
        updateSelectAllState();

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

        ['filterVolumeMin', 'filterVolumeMax'].forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('keydown', function(e) {
                const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
                if (allowed.includes(e.key)) return;
                if (e.ctrlKey || e.metaKey) return;
                if (!/^\d$/.test(e.key)) e.preventDefault();
            });
            el.addEventListener('blur', function() { this.value = formatVolume(this.value); });
            el.addEventListener('focus', function() { this.value = this.value.replace(/\./g, ''); });
        });

        // Click outside modal to close
        const deleteModal = document.getElementById('deleteFollowModal');
        if (deleteModal) {
            deleteModal.addEventListener('click', function(e) {
                if (e.target === this) closeDeleteFollowModal();
            });
        }

        const noticeModal = document.getElementById('deleteFollowNoticeModal');
        if (noticeModal) {
            noticeModal.addEventListener('click', function(e) {
                if (e.target === this) closeDeleteFollowNoticeModal();
            });
        }

        const batchDeleteModal = document.getElementById('deleteFollowBatchModal');
        if (batchDeleteModal) {
            batchDeleteModal.addEventListener('click', function(e) {
                if (e.target === this) closeDeleteFollowBatchModal();
            });
        }
    });
})();
