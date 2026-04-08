const { baseUrl, cash: cashInit } = window.__pageData || {};
const formatter = new Intl.NumberFormat('vi-VN');
const cashInInput = document.getElementById('cashIn');
const cashDateInput = document.getElementById('cashDate');
const btnFormSubmit = document.getElementById('btnFormSubmit');

var cash = parseFloat(cashInit) || 0;
$('.cash').text(formatter.format(cash));

// ── Modal helpers ──────────────────────────────────────────────
const modal      = document.getElementById('cash-in-notify-modal');
const modalIcon  = document.getElementById('cashInNotifyIcon');
const modalMsg   = document.getElementById('cashInNotifyMsg');
const modalClose = document.getElementById('cashInNotifyClose');

function showCashInModal(type, icon, message) {
    modal.classList.remove('home-notify-modal--success', 'home-notify-modal--error');
    modal.classList.add(type === 'success' ? 'home-notify-modal--success' : 'home-notify-modal--error');
    modalIcon.textContent = icon;
    modalMsg.innerHTML = message;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => modalClose.focus(), 50);
}

function closeCashInModal() {
    modal.setAttribute('aria-hidden', 'true');
    if (btnFormSubmit) btnFormSubmit.focus();
}

if (modalClose) {
    modalClose.addEventListener('click', closeCashInModal);
}
modal.querySelector('.home-notify-modal__backdrop').addEventListener('click', closeCashInModal);
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
        closeCashInModal();
    }
});

// ── Ngày YYYY-MM-DD: tồn tại trên lịch (không nhận 2023-02-31) ──
function parseYmdStrict(s) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(s).trim());
    if (!m) return null;
    const y = +m[1];
    const mo = +m[2];
    const d = +m[3];
    const dt = new Date(y, mo - 1, d);
    if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return null;
    return { y, mo, d };
}

function isAfterTodayYmd(ymd) {
    const p = parseYmdStrict(ymd);
    if (!p) return false;
    const t = new Date();
    const ty = t.getFullYear();
    const tm = t.getMonth() + 1;
    const td = t.getDate();
    if (p.y > ty) return true;
    if (p.y < ty) return false;
    if (p.mo > tm) return true;
    if (p.mo < tm) return false;
    return p.d > td;
}

// ── Form logic ─────────────────────────────────────────────────
function getTodayYmd() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function setDefaultCashDate() { cashDateInput.value = getTodayYmd(); }
setDefaultCashDate();

function isNumber(value) { return !isNaN(value) && value.trim() !== ''; }
function parseNumber(str) { return str.replace(/[^\d]/g, ''); }

function formatToVND(input) {
    const raw = parseNumber(input.value);
    if (raw === '') return (input.value = '');
    input.value = formatter.format(raw);
}

function canSubmitCashInForm() {
    const cashIn = parseNumber(cashInInput.value);
    const cashDate = cashDateInput.value.trim();
    if (!cashIn || !isNumber(cashIn)) return false;
    if (cashDate === '' || !parseYmdStrict(cashDate)) return false;
    return true;
}

function updateCashInSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitCashInForm();
}

cashInInput.addEventListener('input', () => { formatToVND(cashInInput); updateCashInSubmitButton(); });
cashDateInput.addEventListener('change', updateCashInSubmitButton);
cashDateInput.addEventListener('input', updateCashInSubmitButton);
if (cashDateInput) {
    cashDateInput.addEventListener('click', function () {
        try { this.showPicker(); } catch (_) {}
    });
}
updateCashInSubmitButton();

function resetForm() { cashInInput.value = ''; setDefaultCashDate(); updateCashInSubmitButton(); }

function ajaxErrorMessage(xhr, fallback) {
    if (xhr.status === 422 && xhr.responseJSON) {
        const j = xhr.responseJSON;
        if (j.errors && j.errors.cashDate && j.errors.cashDate[0]) return j.errors.cashDate[0];
        if (j.message) return j.message;
    }
    return fallback;
}

function submitForm() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const cashDate = cashDateInput.value.trim();
    const cashIn = parseNumber(cashInInput.value);
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (cashIn) { if (!isNumber(cashIn)) { document.getElementById('errorCashInType').style.display = 'block'; isValid = false; } }

    if (cashDate === '') {
        document.getElementById('errorCashDate').style.display = 'block';
        isValid = false;
    } else if (!parseYmdStrict(cashDate)) {
        showCashInModal('error', '❌', 'Ngày nạp không hợp lệ');
        isValid = false;
    } else if (isAfterTodayYmd(cashDate)) {
        showCashInModal('error', '❌', 'Ngày nạp không hợp lệ');
        isValid = false;
    }

    if (isValid) {
        btnFormSubmit.disabled = true;
        $.ajax({
            url: baseUrl + '/user/cashIn', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ cashIn, cashDate }),
            success: function (response) {
                if (response.status === 'success') {
                    const num2 = parseFloat(cashInInput.value.replace(/\./g, '').replace(/,/g, ''));
                    cash = parseFloat(cash) + num2;
                    $('.cash').text(formatter.format(cash));
                    resetForm();
                    showCashInModal('success', '✅', `Đã nạp thành công số tiền: <b>${formatter.format(num2)}</b>`);
                } else {
                    showCashInModal('error', '❌', response.message || 'Có lỗi xảy ra.');
                }
            },
            error: function (xhr) {
                const msg = ajaxErrorMessage(xhr, 'Lỗi kết nối.');
                showCashInModal('error', '❌', msg);
            },
            complete: function () {
                updateCashInSubmitButton();
            }
        });
    }
}
window.submitForm = submitForm;
