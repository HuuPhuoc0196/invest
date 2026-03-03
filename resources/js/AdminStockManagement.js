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

window.renderStockTable = function(data) {
    const tbody = document.getElementById('stockTableBody');
    tbody.innerHTML = '';

    // Dynamic sort using global sort state
    if (typeof currentSortKey !== 'undefined') {
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
            <td><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
            <td>${[30, 100].includes(Number(stock.stocks_vn)) ? Number(stock.stocks_vn) : 'ALL'}</td>
            <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
            <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
            <td>${sellPrice}</td>
            <td style="color: ${getRisk(stock.risk_level).color}">
                ${getRisk(stock.risk_level).label}
            </td>
            <td>${stock.percent_buy != null ? parseFloat(stock.percent_buy) + '%' : 'N/A'}</td>
            <td>${stock.percent_sell != null ? parseFloat(stock.percent_sell) + '%' : 'N/A'}</td>
            <td>${getRatingBadge(stock.rating_stocks)}</td>
            <td>${volumeAvg}</td>
            <td style="color: ${valuationColor}; font-weight: bold;">${sign}${valuation}%</td>
            <td>
                <button onclick="location.href='${baseUrl}/admin/update/${stock.code}'">Update</button>
                <button class="btn-delete" onclick="confirmDelete('${stock.code}')">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Re-sync sticky header clone widths after table data changes
    window.dispatchEvent(new Event('resize'));
};

window.confirmDelete = function(code) {
    if (confirm('Bạn có chắc chắn muốn xóa mã ' + code + '?')) {
        location.href = baseUrl + '/admin/delete/' + code;
    }
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

    const thead = table.querySelector('thead');
    let cloneTable = null;
    let cloneWrap = null;

    function createClone() {
        if (cloneWrap) cloneWrap.remove();

        cloneWrap = document.createElement('div');
        cloneWrap.className = 'sticky-clone';

        cloneTable = document.createElement('table');
        cloneTable.id = 'stock-table-clone';
        cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';

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
        const tableWidth = table.getBoundingClientRect().width;
        cloneTable.style.width = tableWidth + 'px';

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

    function syncScroll() {
        if (!cloneWrap) return;
        const containerRect = container.getBoundingClientRect();
        cloneWrap.style.left = containerRect.left + 'px';
        cloneWrap.style.width = containerRect.width + 'px';
        cloneTable.style.marginLeft = -container.scrollLeft + 'px';
    }

    function onScroll() {
        if (!cloneWrap) return;
        const tableRect = table.getBoundingClientRect();
        const theadHeight = thead.offsetHeight;

        // Show clone when original header scrolls above viewport
        // Hide when table bottom is above viewport
        if (tableRect.top < 0 && tableRect.bottom > theadHeight) {
            cloneWrap.style.display = 'block';
            syncScroll();
        } else {
            cloneWrap.style.display = 'none';
        }
    }

    createClone();
    window.addEventListener('scroll', onScroll);
    window.addEventListener('resize', function() {
        createClone();
        onScroll();
    });
    container.addEventListener('scroll', syncScroll);
});

// ========== Import CSV Modal ==========
window.openImportModal = function() {
    document.getElementById('importCsvModal').style.display = 'flex';
    document.getElementById('csvFileInput').value = '';
    document.getElementById('dropZoneText').style.display = 'block';
    document.getElementById('fileInfo').style.display = 'none';
    const result = document.getElementById('importResult');
    result.style.display = 'none';
    result.className = 'import-result';
    result.innerHTML = '';
};

window.closeImportModal = function() {
    document.getElementById('importCsvModal').style.display = 'none';
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
});

function handleFileSelect(file) {
    const result = document.getElementById('importResult');
    result.style.display = 'none';

    if (!file) return;
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showImportResult('error', '❌ Chỉ chấp nhận file .csv');
        return;
    }

    document.getElementById('dropZoneText').style.display = 'none';
    document.getElementById('fileInfo').style.display = 'block';
    document.getElementById('fileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
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
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.trim().split('\n');
        if (lines.length < 2) {
            showImportResult('error', '❌ File CSV phải có ít nhất 2 dòng (header + data)');
            return;
        }

        const expectedHeaders = ['code', 'prive_avg', 'percent_buy', 'percent_sell', 'recommended_buy_price', 'recommended_sell_price', 'ratting_stocks', 'risk_level'];
        const actualHeaders = lines[0].replace(/\uFEFF/g, '').split(',').map(h => h.trim().toLowerCase());

        const headersMatch = expectedHeaders.every((h, i) => actualHeaders[i] === h);
        if (!headersMatch) {
            showImportResult('error', '❌ Header không đúng cấu trúc.<br>Yêu cầu: <b>' + expectedHeaders.join(', ') + '</b><br>Nhận được: <b>' + actualHeaders.join(', ') + '</b>');
            return;
        }

        // Validation passed, send to server
        const formData = new FormData();
        formData.append('csv_file', file);
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Disable button while uploading
        const btn = document.querySelector('.btn-import');
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';

        $.ajax({
            url: baseUrl + '/admin/stocks/import-csv',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': token },
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                btn.disabled = false;
                btn.textContent = 'Nhập dữ liệu';
                if (response.status === 'success') {
                    let html = '✅ ' + response.message;
                    if (response.details) {
                        html += '<br>' + response.details;
                    }
                    showImportResult('success', html);
                    // Reload page after 2s to show updated data
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showImportResult('error', '❌ ' + response.message);
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                btn.textContent = 'Nhập dữ liệu';
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi không xác định';
                showImportResult('error', '❌ ' + msg);
            }
        });
    };
    reader.readAsText(file);
};
