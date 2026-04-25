import './stickyHeaderInset';

function getRisk(rating) {
    switch (Number(rating)) {
        case 1:
            return { label: 'An toàn', color: '#27ae60' };
        case 2:
            return { label: 'Cảnh báo', color: '#f39c12' };
        case 3:
            return { label: 'Hạn chế GD', color: '#e74c3c' };
        case 4:
            return { label: 'Đình chỉ/Huỷ', color: '#c0392b' };
        default:
            return { label: 'Chưa xác định', color: '#95a5a6' };
    }
}

function getRowClass(goodPrice, currentPrice) {
    if (currentPrice > goodPrice) {
        const percentDiff = ((currentPrice - goodPrice) / goodPrice) * 100;
        if (percentDiff <= 10) {
            return 'yellow';
        } else {
            return '';
        }
    } else if (currentPrice <= goodPrice) {
        const percentDiff = ((goodPrice - currentPrice) / goodPrice) * 100;
        if (percentDiff > 20) {
            return 'red';
        } else if (percentDiff > 10) {
            return 'purple';
        } else {
            return 'green';
        }
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
    return `<span class="rating-badge ${cls}">${val.toFixed(2)}</span>`;
}

// Export helpers for override scripts
window.getRisk = getRisk;
window.getRowClass = getRowClass;
window.getRatingBadge = getRatingBadge;

let pendingDeleteCode = '';

window.renderStockTable = function(data) {
    const baseUrl = (window.__pageData && window.__pageData.baseUrl) ? window.__pageData.baseUrl : '';
    const tbody = document.getElementById('stockTableBody');
    tbody.innerHTML = '';

    // Dynamic sort using global sort state
    if (typeof window.currentSortKey !== 'undefined') {
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

    data.forEach(stock => {
        const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
        const currentPrice = parseFloat(stock.current_price) || 0;
        const sellPrice = stock.recommended_sell_price ? Number(stock.recommended_sell_price).toLocaleString('vi-VN') : 'N/A';
        const volumeAvg = stock.volume_avg ? Number(stock.volume_avg).toLocaleString('vi-VN') : 'N/A';

        // % Định giá = (currentPrice / buyPrice) * 100 - 100 (same as AdminView)
        const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100).toFixed(2) : 0;

        let valuationColor = 'yellow';
        let sign = '';
        if (valuation > 0) {
            valuationColor = 'green';
            sign = '+';
        } else if (valuation < 0) {
            valuationColor = 'red';
            sign = '';
        }

        const row = document.createElement('tr');
        row.className = getRowClass(buyPrice, currentPrice);
        row.innerHTML = `
            <td class="col-code-sticky"><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
            <td>${[30, 100].includes(Number(stock.stocks_vn)) ? Number(stock.stocks_vn) : 'ALL'}</td>
            <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
            <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
            <td>${sellPrice}</td>
            <td>${stock.price_avg != null ? Number(stock.price_avg).toLocaleString('vi-VN') : 'N/A'}</td>
            <td style="color: ${getRisk(stock.risk_level).color}">
                ${getRisk(stock.risk_level).label}
            </td>
            <td>${stock.percent_buy != null ? parseFloat(stock.percent_buy) + '%' : 'N/A'}</td>
            <td>${stock.percent_sell != null ? parseFloat(stock.percent_sell) + '%' : 'N/A'}</td>
            <td>${getRatingBadge(stock.rating_stocks)}</td>
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
};

window.confirmDelete = function(code) {
    const modal = document.getElementById('deleteStockModal');
    const codeEl = document.getElementById('deleteStockCode');
    const btn = document.getElementById('btnDeleteStockConfirm');
    if (!modal || !codeEl) return;
    pendingDeleteCode = code;
    codeEl.textContent = code;
    if (btn) {
        btn.disabled = false;
        btn.textContent = 'Xoá';
    }
    modal.style.display = 'flex';
};

// Alias for backward compatibility
window.confirmDeleteStock = window.confirmDelete;

window.closeDeleteStockModal = function() {
    const modal = document.getElementById('deleteStockModal');
    if (!modal) return;
    pendingDeleteCode = '';
    modal.style.display = 'none';
};

window.runDeleteStock = function() {
    const baseUrl = (window.__pageData && window.__pageData.baseUrl) ? window.__pageData.baseUrl : '';
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const code = pendingDeleteCode;
    const btn = document.getElementById('btnDeleteStockConfirm');
    if (!baseUrl || !code) return;
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang xoá...';
    }

    fetch(baseUrl + '/admin/delete/' + encodeURIComponent(code), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(async function(res) {
        let payload = {};
        try {
            payload = await res.json();
        } catch (_) {
            payload = {};
        }
        return { ok: res.ok, payload };
    })
    .then(function(result) {
        window.closeDeleteStockModal();
        if (result.ok && result.payload && result.payload.success) {
            const pageData = window.__pageData || {};
            if (Array.isArray(pageData.stocks)) {
                const idx = pageData.stocks.findIndex(function(stock) {
                    return String(stock.code || '').toUpperCase() === String(code).toUpperCase();
                });
                if (idx !== -1) {
                    pageData.stocks.splice(idx, 1);
                }
            }

            if (typeof window.clearFollowSelection === 'function') {
                window.clearFollowSelection();
            }
            if (typeof window.searchStock === 'function') {
                window.searchStock();
            } else if (typeof window.renderStockTable === 'function' && Array.isArray(pageData.stocks)) {
                window.renderStockTable(pageData.stocks);
            }

            showDeleteStockNoticeModal('success', '✅ ' + (result.payload.message || ('Đã xoá mã cổ phiếu ' + code + '.')));
        } else {
            const msg = (result.payload && result.payload.message) ? result.payload.message : 'Không thể xoá mã cổ phiếu.';
            showDeleteStockNoticeModal('error', '❌ ' + msg);
        }
    })
    .catch(function() {
        window.closeDeleteStockModal();
        showDeleteStockNoticeModal('error', '❌ Lỗi kết nối. Vui lòng thử lại.');
    })
    .finally(function() {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Xoá';
        }
    });
};

function showDeleteStockNoticeModal(type, message) {
    const modal = document.getElementById('deleteStockNoticeModal');
    const title = document.getElementById('deleteStockNoticeTitle');
    const msg = document.getElementById('deleteStockNoticeMessage');
    if (!modal || !title || !msg) return;

    modal.classList.remove('is-success', 'is-error');
    modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
    title.textContent = type === 'success' ? 'Thành công' : 'Lỗi';
    msg.innerHTML = message;
    modal.style.display = 'flex';
}

window.closeDeleteStockNoticeModal = function() {
    const modal = document.getElementById('deleteStockNoticeModal');
    if (!modal) return;
    modal.style.display = 'none';
};

window.confirmExportCsv = function() {
    const modal = document.getElementById('exportCsvModal');
    const btn = document.getElementById('btnExportCsvConfirm');
    if (!modal) return;
    if (btn) {
        btn.disabled = false;
        btn.textContent = 'Đồng ý';
    }
    modal.style.display = 'flex';
};

window.closeExportCsvModal = function() {
    const modal = document.getElementById('exportCsvModal');
    if (!modal) return;
    modal.style.display = 'none';
};

window.runExportCsv = function() {
    const baseUrl = (window.__pageData && window.__pageData.baseUrl) ? window.__pageData.baseUrl : '';
    const btn = document.getElementById('btnExportCsvConfirm');
    if (!baseUrl) return;

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang xuất...';
    }
    window.location.href = baseUrl + '/admin/stocks/export-csv';
    setTimeout(function() {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Đồng ý';
        }
        if (typeof window.closeExportCsvModal === 'function') {
            window.closeExportCsvModal();
        }
    }, 350);
};

// Drag to scroll horizontally within table container
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.table-container');
    if (!container) return;

    let isDown = false;
    let startX;
    let scrollLeft;

    container.addEventListener('mousedown', function(e) {
        if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A') return;
        isDown = true;
        container.style.cursor = 'grabbing';
        container.style.userSelect = 'none';
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
    });

    container.addEventListener('mouseleave', function() {
        isDown = false;
        container.style.cursor = 'grab';
        container.style.removeProperty('user-select');
    });

    container.addEventListener('mouseup', function() {
        isDown = false;
        container.style.cursor = 'grab';
        container.style.removeProperty('user-select');
    });

    container.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2;
        container.scrollLeft = scrollLeft - walk;
    });

    container.style.cursor = 'grab';
});

// JS-based sticky header (works with overflow-x container)
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('stock-table');
    const container = document.querySelector('.table-container');
    if (!table || !container) return;

    function headerInset() {
        return typeof window.getStickyHeaderInset === 'function'
            ? window.getStickyHeaderInset()
            : (window.innerWidth <= 768 ? 56 : 0);
    }

    const thead = table.querySelector('thead');
    let cloneTable = null;
    let cloneWrap = null;

    function createClone() {
        if (cloneWrap) cloneWrap.remove();

        cloneWrap = document.createElement('div');
        cloneWrap.className = 'sticky-clone';

        cloneTable = document.createElement('table');
        cloneTable.id = 'stock-table-clone';
        cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;';

        const cloneThead = thead.cloneNode(true);
        cloneTable.appendChild(cloneThead);
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

        // Calculate total width from sum of individual cell widths
        // to avoid fractional pixel rounding mismatch with table.getBoundingClientRect()
        let totalWidth = 0;
        origCells.forEach((cell, i) => {
            const w = cell.getBoundingClientRect().width;
            totalWidth += w;
            if (cloneCells[i]) {
                cloneCells[i].style.boxSizing = 'border-box';
                cloneCells[i].style.width = w + 'px';
                cloneCells[i].style.minWidth = w + 'px';
                cloneCells[i].style.maxWidth = w + 'px';
            }
        });
        cloneTable.style.width = totalWidth + 'px';
    }

    function syncScroll() {
        if (!cloneWrap) return;
        const containerRect = container.getBoundingClientRect();
        const topOffset = headerInset();
        cloneWrap.style.left = containerRect.left + 'px';
        cloneWrap.style.width = containerRect.width + 'px';
        cloneWrap.style.top = topOffset + 'px';
        cloneTable.style.marginLeft = -container.scrollLeft + 'px';
    }

    function onScroll() {
        if (!cloneWrap) return;
        const tableRect = table.getBoundingClientRect();
        const theadHeight = thead.offsetHeight;
        const inset = headerInset();

        // Show clone when original header scrolls above the sticky offset (below topbar on mobile)
        if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
            cloneWrap.style.display = 'block';
            syncScroll();
        } else {
            cloneWrap.style.display = 'none';
        }
    }

    createClone();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function() {
        createClone();
        onScroll();
    });
    container.addEventListener('scroll', syncScroll, { passive: true });
    onScroll();
});

// ========== Import CSV Modal ==========
function updateImportCsvSubmitButton() {
    const btn = document.getElementById('btnImportCsvSubmit');
    if (!btn || btn.getAttribute('data-busy') === '1') return;
    const fileInput = document.getElementById('csvFileInput');
    const file = fileInput && fileInput.files[0];
    const ok = !!(file && file.name.toLowerCase().endsWith('.csv'));
    btn.disabled = !ok;
}

window.openImportModal = function() {
    const modal = document.getElementById('importCsvModal');
    const fileInput = document.getElementById('csvFileInput');
    const dropZoneText = document.getElementById('dropZoneText');
    const fileInfo = document.getElementById('fileInfo');
    const dropZone = document.getElementById('dropZone');
    if (!modal || !fileInput || !dropZoneText || !fileInfo || !dropZone) return;

    modal.style.display = 'flex';
    fileInput.value = '';
    dropZoneText.style.display = 'block';
    fileInfo.style.display = 'none';
    dropZone.classList.remove('drag-over');
    const result = document.getElementById('importResult');
    if (result) {
        result.style.display = 'none';
        result.className = 'import-result';
        result.innerHTML = '';
    }
    const btn = document.getElementById('btnImportCsvSubmit');
    if (btn) {
        btn.removeAttribute('data-busy');
        btn.textContent = 'Nhập dữ liệu';
        btn.disabled = true;
    }
    
    // Reset modal actions visibility
    const modalActions = document.getElementById('importModalActions');
    const closeAction = document.getElementById('importModalCloseAction');
    if (modalActions) modalActions.style.display = 'flex';
    if (closeAction) closeAction.style.display = 'none';
    window.__shouldReloadAfterImport = false;
};

window.closeImportModal = function() {
    document.getElementById('importCsvModal').style.display = 'none';
    if (window.__shouldReloadAfterImport) {
        window.__shouldReloadAfterImport = false;
        window.location.reload();
    }
};

window.closeImportModalAndReload = function() {
    document.getElementById('importCsvModal').style.display = 'none';
    if (window.__shouldReloadAfterImport) {
        window.__shouldReloadAfterImport = false;
        window.location.reload();
    }
};

// File selection & drag-drop
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('csvFileInput');
    if (!dropZone || !fileInput) return;

    fileInput.addEventListener('change', function() {
        handleFileSelect(this.files[0]);
    });

    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', function() {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) {
            // Set file to input so submitImportCsv can read it
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            handleFileSelect(file);
        }
    });

    // Close modal when clicking overlay background
    document.getElementById('importCsvModal').addEventListener('click', function(e) {
        if (e.target === this) closeImportModal();
    });
    const exportModal = document.getElementById('exportCsvModal');
    if (exportModal) {
        exportModal.addEventListener('click', function(e) {
            if (e.target === this) closeExportCsvModal();
        });
    }
    const deleteModal = document.getElementById('deleteStockModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteStockModal();
        });
    }
    const deleteNoticeModal = document.getElementById('deleteStockNoticeModal');
    if (deleteNoticeModal) {
        deleteNoticeModal.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteStockNoticeModal();
        });
    }

    if (new URLSearchParams(window.location.search).get('import') === '1') {
        if (typeof window.openImportModal === 'function') {
            window.openImportModal();
            window.history.replaceState({}, '', window.location.pathname + window.location.hash);
        }
    }
});

function handleFileSelect(file) {
    const result = document.getElementById('importResult');
    result.style.display = 'none';

    if (!file) {
        updateImportCsvSubmitButton();
        return;
    }
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showImportResult('error', '❌ Chỉ chấp nhận file .csv');
        updateImportCsvSubmitButton();
        return;
    }

    document.getElementById('dropZoneText').style.display = 'none';
    document.getElementById('fileInfo').style.display = 'block';
    document.getElementById('fileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    updateImportCsvSubmitButton();
}

function showImportResult(type, html) {
    const result = document.getElementById('importResult');
    result.className = 'import-result ' + (type === 'success' ? 'result-success' : 'result-error');
    result.innerHTML = html;
    result.style.display = 'block';
}

window.submitImportCsv = function() {
    const fileInput = document.getElementById('csvFileInput');
    const file = fileInput.files[0];

    if (!file) {
        showImportResult('error', '❌ Vui lòng chọn file CSV');
        return;
    }
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showImportResult('error', '❌ Chỉ chấp nhận file .csv');
        return;
    }

    // Client-side: read file and validate header
    const reader = new FileReader();
    reader.onerror = function() {
        showImportResult('error', '❌ Không thể đọc file. Vui lòng thử lại.');
    };
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.trim().split('\n');
        if (lines.length < 2) {
            showImportResult('error', '❌ File CSV phải có ít nhất 2 dòng (header + data)');
            return;
        }

        const expectedHeaders = ['code', 'prive_avg', 'percent_buy', 'percent_sell', 'recommended_buy_price', 'recommended_sell_price', 'ratting_stocks', 'risk_level', 'current_price', 'percent_stock', 'stocks_vn', 'volume', 'volume_avg', 'recommended_date', 'event_date'];
        const oldHeaders = expectedHeaders.slice(0, 8);
        const rawHeader = lines[0].replace(/\uFEFF/g, '');
        const actualHeaders = rawHeader.split(',').map(h => h.trim().replace(/^["']|["']$/g, '').toLowerCase());

        const matchFull = expectedHeaders.every((h, i) => actualHeaders[i] === h);
        const matchOld = !matchFull && oldHeaders.every((h, i) => actualHeaders[i] === h) && actualHeaders.length === 8;
        if (!matchFull && !matchOld) {
            showImportResult('error', '❌ Header không đúng cấu trúc.<br>Yêu cầu: <b>' + expectedHeaders.join(', ') + '</b><br>Nhận được: <b>' + actualHeaders.join(', ') + '</b>');
            return;
        }

        // Validation passed, send to server
        const formData = new FormData();
        formData.append('csv_file', file);
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const token = csrfMeta ? csrfMeta.getAttribute('content') : '';

        const btn = document.getElementById('btnImportCsvSubmit');
        if (btn) {
            btn.setAttribute('data-busy', '1');
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';
        }

        const baseUrl = (window.__pageData && window.__pageData.baseUrl) ? window.__pageData.baseUrl : '';
        fetch(baseUrl + '/admin/stocks/import-csv', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
        .then(function(result) {
            if (btn) {
                btn.removeAttribute('data-busy');
                btn.textContent = 'Nhập dữ liệu';
                updateImportCsvSubmitButton();
            }
            if (result.ok && result.data.status === 'success') {
                let html = '✅ ' + result.data.message;
                if (result.data.details) {
                    html += '<br>' + result.data.details;
                }
                showImportResult('success', html);
                
                // Hide cancel and import buttons, show close button
                const modalActions = document.getElementById('importModalActions');
                const closeAction = document.getElementById('importModalCloseAction');
                if (modalActions) modalActions.style.display = 'none';
                if (closeAction) closeAction.style.display = 'flex';
                
                // Set flag to reload on close
                window.__shouldReloadAfterImport = true;
            } else {
                let msg = result.data.message || 'Lỗi không xác định';
                if (result.data.errors) {
                    const errs = Object.values(result.data.errors).flat();
                    if (errs.length) msg += '<br>' + errs.join('<br>');
                }
                showImportResult('error', '❌ ' + msg);
            }
        })
        .catch(function(err) {
            if (btn) {
                btn.removeAttribute('data-busy');
                btn.textContent = 'Nhập dữ liệu';
                updateImportCsvSubmitButton();
            }
            showImportResult('error', '❌ Lỗi kết nối. Vui lòng thử lại.');
        });
    };
    reader.readAsText(file);
};
