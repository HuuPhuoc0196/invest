// Moved from AdminUpdate.blade.php <script> block
(function () {
    const { baseUrl, stockData } = window.__pageData || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function openSyncStockModal() {
        document.getElementById('syncStockCode').textContent = stockData.code;
        document.getElementById('syncStockModal').style.display = 'flex';
    }
    window.openSyncStockModal = openSyncStockModal;

    function closeSyncStockModal() {
        document.getElementById('syncStockModal').style.display = 'none';
    }
    window.closeSyncStockModal = closeSyncStockModal;

    function runSyncStock() {
        const btn = document.getElementById('btnSyncStock');
        btn.disabled = true;
        btn.textContent = 'Đang cập nhật...';
        window.$.ajax({
            url: baseUrl + '/admin/sync/run-update-stock/' + encodeURIComponent(stockData.code),
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                btn.disabled = false;
                btn.textContent = 'Đồng ý';
                closeSyncStockModal();
                if (res && res.status === 'success') {
                    if (typeof window.showUpdateNoticeModal === 'function') {
                        window.showUpdateNoticeModal('success', '✅ ' + (res.message || ('Đã gửi yêu cầu cập nhật cho mã ' + stockData.code)));
                    }
                } else {
                    if (typeof window.showUpdateNoticeModal === 'function') {
                        window.showUpdateNoticeModal('error', '❌ ' + (res && res.message ? res.message : 'Lỗi gửi yêu cầu cập nhật!'));
                    }
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                btn.textContent = 'Đồng ý';
                closeSyncStockModal();
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi gửi yêu cầu cập nhật!';
                if (typeof window.showUpdateNoticeModal === 'function') {
                    window.showUpdateNoticeModal('error', '❌ ' + msg);
                }
            }
        });
    }
    window.runSyncStock = runSyncStock;
})();
