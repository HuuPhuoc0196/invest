/**
 * Admin Crontab Management
 * Proxies to VPS via Laravel routes defined in window.__crontabRoutes
 */

const R = window.__crontabRoutes;
const csrf = R.csrf;

// ── DOM refs ──────────────────────────────────────────────────────────────────
const tbody      = document.getElementById('crontab-tbody');
const errorBox   = document.getElementById('crontab-error');
const flash      = document.getElementById('crontab-flash');
const statusEl   = document.getElementById('crontab-status');
const formWrap   = document.getElementById('crontab-form-wrap');
const formTitle  = document.getElementById('crontab-form-title');
const cfMinute   = document.getElementById('cf-minute');
const cfHour     = document.getElementById('cf-hour');
const cfDay      = document.getElementById('cf-day');
const cfMonth    = document.getElementById('cf-month');
const cfWeekday  = document.getElementById('cf-weekday');
const cfEndpoint = document.getElementById('cf-endpoint');
const cfDesc     = document.getElementById('cf-desc');
const btnSave    = document.getElementById('btn-form-save');
const btnCancel  = document.getElementById('btn-form-cancel');
const btnAddOpen = document.getElementById('btn-add-open');
const btnRefresh = document.getElementById('btn-refresh');

// ── State ─────────────────────────────────────────────────────────────────────
let editingIdx = null; // null = add mode, number = edit mode

// ── Helpers ───────────────────────────────────────────────────────────────────

function setStatus(msg) { statusEl.textContent = msg; }

function showFlash(msg, type = 'success') {
    flash.textContent = msg;
    flash.className = 'admin-crontab-flash admin-crontab-flash--' + type;
    flash.hidden = false;
    clearTimeout(flash._t);
    flash._t = setTimeout(() => { flash.hidden = true; }, 5000);
}

function showError(msg) {
    errorBox.textContent = msg;
    errorBox.hidden = false;
}
function hideError() { errorBox.hidden = true; }

function scheduleDesc(parts) {
    if (!parts) return '—';
    const m = parts.minute   ?? '*';
    const h = parts.hour     ?? '*';
    const d = parts.day      ?? '*';
    const mo= parts.month    ?? '*';
    const wd= parts.day_of_week ?? '*';

    let desc = '';
    if (h !== '*' && m !== '*') {
        desc += `${h.padStart ? h.padStart(2,'0') : h}:${(m+'').padStart(2,'0')}`;
    } else if (h !== '*') {
        desc += `giờ ${h}`;
    } else {
        desc += 'mỗi phút';
    }
    if (wd !== '*') {
        const dayMap = {'0':'CN','1':'T2','2':'T3','3':'T4','4':'T5','5':'T6','6':'T7'};
        const wdDesc = wd.split(/[-,]/).length > 1
            ? 'T' + wd.replace('-','–')
            : (dayMap[wd] || 'Thứ ' + wd);
        desc += ', ' + wdDesc;
    } else if (d !== '*') {
        desc += ', ngày ' + d;
    } else {
        desc += ' hàng ngày';
    }
    if (mo !== '*') desc += ', tháng ' + mo;
    return desc;
}

function parseScheduleParts(schedule) {
    const parts = (schedule || '').trim().split(/\s+/);
    if (parts.length < 5) return null;
    return { minute: parts[0], hour: parts[1], day: parts[2], month: parts[3], day_of_week: parts[4] };
}

function buildScheduleFromForm() {
    return [cfMinute.value.trim(), cfHour.value.trim(), cfDay.value.trim(), cfMonth.value.trim(), cfWeekday.value.trim()].join(' ');
}

function updateFormDesc() {
    const parts = parseScheduleParts(buildScheduleFromForm());
    cfDesc.textContent = parts ? scheduleDesc(parts) : '—';
}

// ── Fetch list ────────────────────────────────────────────────────────────────

async function fetchList() {
    setStatus('Đang tải…');
    hideError();
    tbody.innerHTML = '<tr><td colspan="6" class="ct-empty">Đang tải…</td></tr>';
    try {
        const res = await fetch(R.list, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        if (!res.ok) { showError(data.error || 'Lỗi tải danh sách.'); tbody.innerHTML = ''; setStatus(''); return; }
        const entries = Array.isArray(data) ? data : (data.entries || []);
        renderTable(entries);
        setStatus(entries.length + ' entries');
    } catch (e) {
        showError('Không kết nối được tới server.');
        tbody.innerHTML = '';
        setStatus('');
    }
}

// ── Render table ──────────────────────────────────────────────────────────────

function renderTable(entries) {
    if (!entries.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="ct-empty">Không có cron job nào.</td></tr>';
        return;
    }
    tbody.innerHTML = entries.map((e, i) => {
        const isApi = e.is_stocks_api === true || e.is_stocks_api === 1;
        const enabled = e.enabled === true || e.enabled === 1;
        const schedule = e.schedule || '';
        const scheduleParts = e.schedule_parts || parseScheduleParts(schedule);
        const desc = scheduleDesc(scheduleParts);
        const name = e.name || e.endpoint || schedule;
        const lineIdx = e.line_idx ?? i;

        const typeBadge = isApi
            ? '<span class="ct-badge ct-badge--api">API</span>'
            : '<span class="ct-badge ct-badge--system">System</span>';

        const statusDot = enabled
            ? '<span class="ct-status ct-status--on">Bật</span>'
            : '<span class="ct-status ct-status--off">Tắt</span>';

        const toggleBtn = enabled
            ? `<button class="ct-btn ct-btn--toggle-on" data-action="toggle" data-idx="${lineIdx}">⏸ Tắt</button>`
            : `<button class="ct-btn ct-btn--toggle-off" data-action="toggle" data-idx="${lineIdx}">▶ Bật</button>`;

        let actionBtns = toggleBtn;
        if (isApi) {
            const editData = encodeURIComponent(JSON.stringify({ schedule, endpoint: e.endpoint || '' }));
            actionBtns += `<button class="ct-btn ct-btn--edit" data-action="edit" data-idx="${lineIdx}" data-entry="${editData}">✏️ Sửa</button>`;
            actionBtns += `<button class="ct-btn ct-btn--run" data-action="run" data-idx="${lineIdx}" data-name="${name}"${!enabled ? ' disabled' : ''}>▶️ Chạy</button>`;
            actionBtns += `<button class="ct-btn ct-btn--delete" data-action="delete" data-idx="${lineIdx}" data-name="${name}">🗑️ Xóa</button>`;
        }

        return `<tr>
            <td class="ct-td--idx" data-label="#">${lineIdx}</td>
            <td class="ct-td--name" data-label="Endpoint">${escHtml(name)}</td>
            <td class="ct-td--schedule" data-label="Schedule">
                <div>${escHtml(schedule)}</div>
                <div class="ct-schedule-desc">${escHtml(desc)}</div>
            </td>
            <td data-label="Loại">${typeBadge}</td>
            <td data-label="Trạng thái">${statusDot}</td>
            <td class="ct-td--actions">${actionBtns}</td>
        </tr>`;
    }).join('');
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Form helpers ──────────────────────────────────────────────────────────────

function openAddForm() {
    editingIdx = null;
    formTitle.textContent = 'Thêm cron job';
    cfMinute.value = '0'; cfHour.value = '15'; cfDay.value = '*'; cfMonth.value = '*'; cfWeekday.value = '1-5';
    cfEndpoint.value = '';
    updateFormDesc();
    formWrap.hidden = false;
    cfEndpoint.focus();
}

function openEditForm(lineIdx, entry) {
    editingIdx = lineIdx;
    formTitle.textContent = 'Sửa cron job #' + lineIdx;
    const parts = parseScheduleParts(entry.schedule || '') || { minute:'*', hour:'*', day:'*', month:'*', day_of_week:'*' };
    cfMinute.value  = parts.minute   ?? '*';
    cfHour.value    = parts.hour     ?? '*';
    cfDay.value     = parts.day      ?? '*';
    cfMonth.value   = parts.month    ?? '*';
    cfWeekday.value = parts.day_of_week ?? '*';
    cfEndpoint.value = entry.endpoint || '';
    updateFormDesc();
    formWrap.hidden = false;
    formWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeForm() {
    formWrap.hidden = true;
    editingIdx = null;
}

// ── CRUD actions ──────────────────────────────────────────────────────────────

async function handleSave() {
    const schedule = buildScheduleFromForm();
    const endpoint = cfEndpoint.value.trim();
    if (!endpoint.startsWith('/')) { showFlash('Endpoint phải bắt đầu bằng /', 'error'); return; }
    const parts = schedule.trim().split(/\s+/);
    if (parts.length !== 5) { showFlash('Schedule phải có đúng 5 phần (phút giờ ngày tháng thứ)', 'error'); return; }

    btnSave.disabled = true;
    btnSave.textContent = editingIdx === null ? 'Đang thêm…' : 'Đang lưu…';
    try {
        let res, data;
        if (editingIdx === null) {
            res = await fetch(R.add, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ schedule, endpoint }),
            });
        } else {
            res = await fetch(R.update + '/' + editingIdx, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ schedule, endpoint }),
            });
        }
        data = await res.json();
        if (!res.ok) { showFlash(data.error || 'Lỗi lưu crontab.', 'error'); return; }
        showFlash(data.message || (editingIdx === null ? 'Đã thêm cron job.' : 'Đã cập nhật cron job.'));
        closeForm();
        await fetchList();
    } catch (e) {
        showFlash('Lỗi kết nối server.', 'error');
    } finally {
        btnSave.disabled = false;
        btnSave.textContent = '✅ Lưu';
    }
}

async function handleDelete(lineIdx, name) {
    if (!confirm(`Xóa cron job "${name}" (#${lineIdx})?\n\nHành động này không thể hoàn tác.`)) return;
    try {
        const res = await fetch(R.delete + '/' + lineIdx, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (!res.ok) { showFlash(data.error || 'Lỗi xóa crontab.', 'error'); return; }
        showFlash(data.message || 'Đã xóa cron job.');
        await fetchList();
    } catch (e) {
        showFlash('Lỗi kết nối server.', 'error');
    }
}

async function handleToggle(lineIdx, btn) {
    btn.disabled = true;
    try {
        const res = await fetch(R.toggle + '/' + lineIdx, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (!res.ok) { showFlash(data.error || 'Lỗi toggle crontab.', 'error'); return; }
        showFlash(data.message || 'Đã cập nhật trạng thái.');
        await fetchList();
    } catch (e) {
        showFlash('Lỗi kết nối server.', 'error');
        btn.disabled = false;
    }
}

async function handleRun(lineIdx, name, btn) {
    if (!confirm(`Chạy ngay cron job "${name}" (#${lineIdx})?`)) return;
    btn.disabled = true;
    const origText = btn.textContent;
    btn.textContent = 'Đang chạy…';
    try {
        const res = await fetch(R.run + '/' + lineIdx, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (!res.ok) { showFlash(data.error || 'Lỗi chạy crontab.', 'error'); return; }
        showFlash(data.message || 'Đã gửi lệnh chạy.');
    } catch (e) {
        showFlash('Lỗi kết nối server.', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = origText;
    }
}

// ── Clear cache modal ─────────────────────────────────────────────────────────

const clearCacheModal   = document.getElementById('clearCacheModal');
const btnClearCacheOpen = document.getElementById('btn-clear-cache');
const btnClearCacheClose   = document.getElementById('btnClearCacheClose');
const btnClearCacheCancel  = document.getElementById('btnClearCacheCancel');
const btnClearCacheConfirm = document.getElementById('btnClearCacheConfirm');

function openClearCacheModal()  { clearCacheModal.hidden = false; btnClearCacheConfirm.focus(); }
function closeClearCacheModal() { clearCacheModal.hidden = true; }

async function handleClearAllCache() {
    btnClearCacheConfirm.disabled = true;
    const origText = btnClearCacheConfirm.textContent;
    btnClearCacheConfirm.textContent = 'Đang xóa…';
    try {
        const res = await fetch(R.clearCache, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        closeClearCacheModal();
        if (!res.ok) { showFlash(data.message || 'Lỗi xóa cache.', 'error'); return; }
        showFlash(data.message || 'Đã xóa toàn bộ cache hệ thống.', 'success');
    } catch (e) {
        closeClearCacheModal();
        showFlash('Lỗi kết nối server.', 'error');
    } finally {
        btnClearCacheConfirm.disabled = false;
        btnClearCacheConfirm.textContent = origText;
    }
}

btnClearCacheOpen.addEventListener('click', openClearCacheModal);
btnClearCacheClose.addEventListener('click', closeClearCacheModal);
btnClearCacheCancel.addEventListener('click', closeClearCacheModal);
btnClearCacheConfirm.addEventListener('click', handleClearAllCache);

// Đóng modal khi click vào overlay ngoài
clearCacheModal.addEventListener('click', e => {
    if (e.target === clearCacheModal) closeClearCacheModal();
});

// Đóng modal khi nhấn Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !clearCacheModal.hidden) closeClearCacheModal();
});

// ── Event delegation ──────────────────────────────────────────────────────────

tbody.addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;
    const idx = parseInt(btn.dataset.idx, 10);

    if (action === 'toggle') { handleToggle(idx, btn); return; }
    if (action === 'delete') { handleDelete(idx, btn.dataset.name); return; }
    if (action === 'run')    { handleRun(idx, btn.dataset.name, btn); return; }
    if (action === 'edit') {
        try { openEditForm(idx, JSON.parse(decodeURIComponent(btn.dataset.entry))); }
        catch { showFlash('Không đọc được dữ liệu entry.', 'error'); }
    }
});

btnAddOpen.addEventListener('click', openAddForm);
btnCancel.addEventListener('click', closeForm);
btnSave.addEventListener('click', handleSave);
btnRefresh.addEventListener('click', fetchList);

[cfMinute, cfHour, cfDay, cfMonth, cfWeekday].forEach(el => el.addEventListener('input', updateFormDesc));

// ── Sticky header clone (PC) ──────────────────────────────────────────────────

function setupStickyHeader() {
    const table     = document.getElementById('crontab-table');
    const container = table && table.closest('.table-container');
    if (!table || !container) return;

    const thead = table.querySelector('thead');
    if (!thead) return;

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
        cloneWrap.className = 'sticky-clone ct-sticky-clone';

        cloneTable = document.createElement('table');
        cloneTable.id = 'crontab-table-clone';
        cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;margin:0;table-layout:fixed;';

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
        const origCells  = thead.querySelectorAll('th');
        const cloneCells = cloneTable.querySelectorAll('th');
        cloneTable.style.width = table.getBoundingClientRect().width + 'px';
        origCells.forEach((cell, i) => {
            if (!cloneCells[i]) return;
            const w = cell.getBoundingClientRect().width;
            cloneCells[i].style.boxSizing = 'border-box';
            cloneCells[i].style.width     = w + 'px';
            cloneCells[i].style.minWidth  = w + 'px';
            cloneCells[i].style.maxWidth  = w + 'px';
        });
    }

    function syncScroll() {
        if (!cloneWrap) return;
        const r = container.getBoundingClientRect();
        cloneWrap.style.left  = r.left + 'px';
        cloneWrap.style.width = r.width + 'px';
        cloneWrap.style.top   = headerInset() + 'px';
        cloneTable.style.marginLeft = -container.scrollLeft + 'px';
    }

    function onScroll() {
        if (!cloneWrap) return;
        if (window.innerWidth <= 768) { cloneWrap.style.display = 'none'; return; }
        const tableRect   = table.getBoundingClientRect();
        const theadHeight = thead.offsetHeight;
        const inset       = headerInset();
        if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
            syncWidths();
            cloneWrap.style.display = 'block';
            syncScroll();
        } else {
            cloneWrap.style.display = 'none';
        }
    }

    createClone();
    window.addEventListener('scroll',  onScroll,   { passive: true });
    window.addEventListener('resize',  () => { createClone(); onScroll(); });
    container.addEventListener('scroll', syncScroll, { passive: true });
}

// ── Init ──────────────────────────────────────────────────────────────────────
fetchList();
setupStickyHeader();
