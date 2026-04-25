import '../stickyHeaderInset';

const API_URL = window.LVPS_DATA_URL || '/admin/logsVPS/data';

const elDate    = document.getElementById('lvps-date');
const elLevel   = document.getElementById('lvps-level');
const elSearch  = document.getElementById('lvps-search');
const elRefresh = document.getElementById('lvps-refresh');
const elTbody   = document.getElementById('lvps-tbody');
const elPager   = document.getElementById('lvps-pagination');
const elStatus  = document.getElementById('lvps-status');
const elError   = document.getElementById('lvps-error');

let currentPage = 1;
let perPage     = 250;
let debounceTimer;
let stickyCloneController = null;

function levelBadge(level) {
    const l = (level || '').toLowerCase();
    const labels = { info: 'INFO', warning: 'WARNING', error: 'ERROR', debug: 'DEBUG', critical: 'CRITICAL' };
    return `<span class="lvps-badge lvps-badge--${l}">${labels[l] || level}</span>`;
}

function showError(msg) {
    elError.textContent = msg;
    elError.style.display = 'block';
}

function hideError() {
    elError.style.display = 'none';
}

function renderRows(rows) {
    if (!rows || rows.length === 0) {
        elTbody.innerHTML = '<tr><td colspan="3" class="lvps-empty">Không có log nào.</td></tr>';
        return;
    }
    elTbody.innerHTML = rows.map(r => {
        const l = (r.level || '').toLowerCase();
        const rowClass = (l === 'warning' || l === 'error' || l === 'critical') ? ` lvps-row--${l}` : '';
        return `<tr class="${rowClass}">
            <td class="lvps-td lvps-td--time">${escHtml(r.datetime || '')}</td>
            <td class="lvps-td">${levelBadge(r.level)}</td>
            <td class="lvps-td lvps-td--msg">${escHtml(r.message || '')}</td>
        </tr>`;
    }).join('');

    if (stickyCloneController && typeof stickyCloneController.refresh === 'function') {
        stickyCloneController.refresh();
    }
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function renderPager(total, lastPage, current) {
    if (lastPage <= 1) { elPager.innerHTML = ''; return; }

    const start = Math.max(1, current - 2);
    const end   = Math.min(lastPage, current + 2);
    let html = '';

    html += `<button class="lvps-page-btn" ${current === 1 ? 'disabled' : ''} data-page="${current - 1}">‹</button>`;

    if (start > 1) {
        html += `<button class="lvps-page-btn" data-page="1">1</button>`;
        if (start > 2) html += `<span class="lvps-page-info">…</span>`;
    }
    for (let p = start; p <= end; p++) {
        html += `<button class="lvps-page-btn ${p === current ? 'lvps-page-btn--active' : ''}" data-page="${p}">${p}</button>`;
    }
    if (end < lastPage) {
        if (end < lastPage - 1) html += `<span class="lvps-page-info">…</span>`;
        html += `<button class="lvps-page-btn" data-page="${lastPage}">${lastPage}</button>`;
    }

    html += `<button class="lvps-page-btn" ${current === lastPage ? 'disabled' : ''} data-page="${current + 1}">›</button>`;
    html += `<span class="lvps-page-info">${total.toLocaleString()} bản ghi</span>`;

    elPager.innerHTML = html;
}

function populateDates(dates) {
    const current = elDate.value;
    Array.from(elDate.options).forEach((o, i) => { if (i > 0) o.remove(); });
    (dates || []).forEach(d => {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = d;
        elDate.appendChild(opt);
    });
    if (current && dates && dates.includes(current)) {
        elDate.value = current;
    }
}

async function loadLogs(page = 1) {
    currentPage = page;
    elStatus.textContent = 'Đang tải…';
    elRefresh.disabled = true;
    hideError();

    const params = new URLSearchParams({
        page:     page,
        per_page: perPage,
    });
    if (elDate.value)   params.set('date',   elDate.value);
    if (elLevel.value)  params.set('level',  elLevel.value);
    if (elSearch.value) params.set('search', elSearch.value);

    try {
        const res  = await fetch(`${API_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        if (!res.ok || data.error) {
            showError(data.error || `HTTP ${res.status}`);
            elTbody.innerHTML = '<tr><td colspan="3" class="lvps-empty">Không tải được log.</td></tr>';
            elPager.innerHTML = '';
            elStatus.textContent = 'Lỗi';
            return;
        }

        populateDates(data.available_dates);
        renderRows(data.data);
        renderPager(data.total, data.last_page, data.current_page);

        const from = (data.current_page - 1) * data.per_page + 1;
        const to   = Math.min(data.current_page * data.per_page, data.total);
        elStatus.textContent = data.total > 0
            ? `${from}–${to} / ${data.total.toLocaleString()}`
            : '0 bản ghi';

    } catch (err) {
        showError('Lỗi kết nối: ' + err.message);
        elTbody.innerHTML = '<tr><td colspan="3" class="lvps-empty">Lỗi kết nối.</td></tr>';
        elPager.innerHTML = '';
        elStatus.textContent = 'Lỗi';
    } finally {
        elRefresh.disabled = false;
    }
}

function setupStickyHeaderClone() {
    const table = document.querySelector('.lvps-table');
    const container = document.querySelector('.lvps-table-wrap');
    if (!table || !container) return null;

    const thead = table.querySelector('thead');
    if (!thead) return null;

    function headerInset() {
        return typeof window.getStickyHeaderInset === 'function'
            ? window.getStickyHeaderInset()
            : (window.innerWidth <= 768 ? 56 : 0);
    }

    let cloneWrap = null;
    let cloneTable = null;

    function createClone() {
        if (cloneWrap) cloneWrap.remove();

        cloneWrap = document.createElement('div');
        cloneWrap.className = 'lvps-sticky-clone';

        cloneTable = document.createElement('table');
        cloneTable.className = 'lvps-table';
        cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;margin:0;table-layout:auto;';

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
        if (!cloneWrap || !cloneTable) return;
        const containerRect = container.getBoundingClientRect();
        const inset = headerInset();
        cloneWrap.style.left = containerRect.left + 'px';
        cloneWrap.style.width = containerRect.width + 'px';
        cloneWrap.style.top = inset + 'px';
        cloneTable.style.marginLeft = -container.scrollLeft + 24 + 'px';
    }

    function onScroll() {
        if (!cloneWrap) return;
        const tableRect = table.getBoundingClientRect();
        const theadHeight = thead.offsetHeight;
        const inset = headerInset();

        if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
            cloneWrap.style.display = 'block';
            syncScroll();
        } else {
            cloneWrap.style.display = 'none';
        }
    }

    function refresh() {
        createClone();
        onScroll();
    }

    createClone();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', refresh);
    container.addEventListener('scroll', syncScroll, { passive: true });
    onScroll();

    return { refresh };
}

elPager.addEventListener('click', e => {
    const btn = e.target.closest('[data-page]');
    if (!btn || btn.disabled) return;
    loadLogs(parseInt(btn.dataset.page, 10));
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

elDate.addEventListener('change',  () => loadLogs(1));
elLevel.addEventListener('change', () => loadLogs(1));

elSearch.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadLogs(1), 450);
});

elRefresh.addEventListener('click', () => loadLogs(currentPage));

stickyCloneController = setupStickyHeaderClone();

// Initial load
loadLogs(1);
