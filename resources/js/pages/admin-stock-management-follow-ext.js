// Admin Stock Management - Follow Checkbox Extension
// Append this to admin-stock-management.js hoặc load riêng
(function() {
    const { adminFollowedStockIds } = window.__pageData || {};
    if (!adminFollowedStockIds) return; // Not on stocks management page

    let selectedStockIds = new Set();

    // Wait for renderStockTable to be available
    function initFollowLogic() {
        if (typeof window.renderStockTable !== 'function') {
            setTimeout(initFollowLogic, 100);
            return;
        }

        const originalRender = window.renderStockTable;
        
        window.renderStockTable = function(data) {
            const tbody = document.getElementById('stockTableBody');
            if (!tbody) {
                originalRender(data);
                return;
            }

            window.dynamicSort(data);

            const { baseUrl } = window.__pageData || {};

            tbody.innerHTML = data.map(stock => {
                const isFollowed = adminFollowedStockIds.includes(stock.id);
                const isSelected = selectedStockIds.has(stock.id);

                const followCell = isFollowed ? 
                    '<span class="status-icon status-icon--checked" title="Đã theo dõi">✅</span>' :
                    `<input type="checkbox" class="follow-checkbox" data-stock-id="${stock.id}" ${isSelected ? 'checked' : ''}>`;

                const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
                const currentPrice = parseFloat(stock.current_price) || 0;
                const rowClass = getRowClass(buyPrice, currentPrice);

                return `
                    <tr data-stock-id="${stock.id}" class="${rowClass}">
                        <td class="col-select">${followCell}</td>
                        <td class="col-code-sticky td-code-toggle">${stock.code}</td>
                        <td>${stock.stocks_vn || ''}</td>
                        <td>${formatNumber(stock.recommended_buy_price)}</td>
                        <td>${formatNumber(stock.current_price)}</td>
                        <td>${formatNumber(stock.recommended_sell_price)}</td>
                        <td>${formatNumber(stock.price_avg)}</td>
                        <td>${getRiskBadge(stock.risk_level)}</td>
                        <td>${stock.percent_buy || '0'}%</td>
                        <td>${stock.percent_sell || '0'}%</td>
                        <td>${stock.rating_stocks || '-'}</td>
                        <td>${formatNumber(stock.volume_avg)}</td>
                        <td>${calculateValuation(stock)}</td>
                        <td>
                            <button onclick="location.href='${baseUrl}/admin/update/${stock.code}'">Cập nhật</button>
                            <button class="btn-delete" onclick="confirmDeleteStock('${stock.code}')">Xoá</button>
                        </td>
                    </tr>
                `;
            }).join('');

            // Attach checkbox listeners
            tbody.querySelectorAll('.follow-checkbox').forEach(cb => {
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
                    const cb = row.querySelector('.follow-checkbox');
                    if (cb) {
                        cb.checked = !cb.checked;
                        cb.dispatchEvent(new Event('change'));
                    }
                });
            });

            // Click on col-select td = toggle checkbox
            tbody.querySelectorAll('.col-select').forEach(td => {
                td.addEventListener('click', function(e) {
                    // Don't toggle if clicking directly on checkbox
                    if (e.target.classList.contains('follow-checkbox')) return;
                    
                    const cb = td.querySelector('.follow-checkbox');
                    if (cb) {
                        cb.checked = !cb.checked;
                        cb.dispatchEvent(new Event('change'));
                    }
                });
            });
        };

        // Trigger re-render
        if (window.__pageData && window.__pageData.stocks) {
            window.renderStockTable(window.getFilteredStocks ? window.getFilteredStocks() : window.__pageData.stocks);
        }
    }

    function updateAddFollowButtonState() {
        const btn = document.getElementById('btnAddFollowAdmin');
        if (btn) {
            btn.disabled = selectedStockIds.size === 0;
        }
    }

    function updateSelectAllState() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const cloneCheckbox = document.querySelector('#stock-table-clone #selectAllCheckbox');
        if (!selectAllCheckbox) return;

        const availableCheckboxes = Array.from(document.querySelectorAll('.follow-checkbox'));
        const checkedCount = availableCheckboxes.filter(cb => cb.checked).length;

        let checked = false;
        let indeterminate = false;

        if (checkedCount === 0) {
            checked = false;
            indeterminate = false;
        } else if (checkedCount === availableCheckboxes.length && availableCheckboxes.length > 0) {
            checked = true;
            indeterminate = false;
        } else {
            checked = false;
            indeterminate = true;
        }

        // Update both original and clone
        selectAllCheckbox.checked = checked;
        selectAllCheckbox.indeterminate = indeterminate;
        
        if (cloneCheckbox) {
            cloneCheckbox.checked = checked;
            cloneCheckbox.indeterminate = indeterminate;
        }
    }

    window.toggleSelectAllAdmin = function() {
        const checkboxes = document.querySelectorAll('.follow-checkbox');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        
        // If indeterminate or some checked but not all, check all
        // If all checked, uncheck all
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        const shouldCheckAll = !allChecked || (selectAllCheckbox && selectAllCheckbox.indeterminate);

        checkboxes.forEach(cb => {
            cb.checked = shouldCheckAll;
            const stockId = parseInt(cb.dataset.stockId);
            if (shouldCheckAll) {
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
                // Set flag to reload when modal closes
                window.__shouldReloadAfterModal = true;
                openAddFollowNoticeModal('success', `✅ ${result.message}`);
            } else {
                openAddFollowNoticeModal('error', '❌ Có lỗi xảy ra. Vui lòng thử lại.');
                btn.disabled = false;
                btn.textContent = '➕ Thêm theo dõi';
            }
        } catch (error) {
            console.error('Error:', error);
            openAddFollowNoticeModal('error', '❌ Không thể kết nối server.');
            btn.disabled = false;
            btn.textContent = '➕ Thêm theo dõi';
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
        return '';
    }

    function calculateValuation(stock) {
        const recommended = parseFloat(stock.recommended_buy_price);
        const current = parseFloat(stock.current_price);
        if (!recommended || recommended === 0) return '-';
        const percent = ((current - recommended) / recommended) * 100;
        const color = percent >= 0 ? 'green' : 'red';
        return `<span style="color:${color}; font-weight: bold;">${percent >= 0 ? '+' : ''}${percent.toFixed(2)}%</span>`;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initFollowLogic();
        setTimeout(() => {
            updateAddFollowButtonState();
            updateSelectAllState();
        }, 200);

        // Click outside modal to close
        const modal = document.getElementById('addFollowNoticeModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeAddFollowNoticeModal();
            });
        }

        // Handle checkbox change
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function(e) {
                // Only process if this is a real user interaction or programmatic change
                window.toggleSelectAllAdmin();
                
                // Sync with clone if exists
                const cloneCheckbox = document.querySelector('#stock-table-clone #selectAllCheckbox');
                if (cloneCheckbox && cloneCheckbox !== e.target) {
                    cloneCheckbox.checked = this.checked;
                }
            });
        }

        // Handle click on th header using event delegation
        // This works even when thead is sticky (including cloned header)
        document.addEventListener('click', function(e) {
            // Check if click is on th.col-select
            const thColSelect = e.target.closest('th.col-select');
            if (!thColSelect) return;

            // Find checkbox within this specific th (works for both original and clone)
            const checkbox = thColSelect.querySelector('#selectAllCheckbox');
            if (!checkbox) return;

            // If clicking checkbox directly, browser will handle it naturally
            if (e.target === checkbox) {
                return;
            }
            
            // For any other click in th (including label), toggle programmatically
            e.preventDefault();
            
            // Toggle BOTH checkboxes (original and clone if exists)
            const originalCheckbox = document.querySelector('#stock-table #selectAllCheckbox');
            const cloneCheckbox = document.querySelector('#stock-table-clone #selectAllCheckbox');
            
            const newState = !checkbox.checked;
            
            if (originalCheckbox) {
                originalCheckbox.checked = newState;
            }
            if (cloneCheckbox) {
                cloneCheckbox.checked = newState;
            }
            
            // Trigger change event on original checkbox to update state
            if (originalCheckbox) {
                originalCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
})();

// ========================================
// ADD FOLLOW NOTICE MODAL
// ========================================
window.openAddFollowNoticeModal = function(type, message) {
    const modal = document.getElementById('addFollowNoticeModal');
    const title = document.getElementById('addFollowNoticeTitle');
    const msg = document.getElementById('addFollowNoticeMessage');
    if (!modal || !title || !msg) return;
    modal.classList.remove('is-success', 'is-error');
    modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
    title.textContent = type === 'success' ? 'Thành công' : 'Thông báo';
    msg.innerHTML = message || '';
    modal.style.display = 'flex';
};

window.closeAddFollowNoticeModal = function() {
    const modal = document.getElementById('addFollowNoticeModal');
    if (!modal) return;
    modal.style.display = 'none';
    
    // Reload page if flag is set
    if (window.__shouldReloadAfterModal) {
        window.__shouldReloadAfterModal = false;
        window.location.reload();
    }
};
