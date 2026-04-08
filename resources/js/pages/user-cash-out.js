const { baseUrl, cash: cashInit } = window.__pageData || {};
const formatter = new Intl.NumberFormat('vi-VN');
const cashOutInput = document.getElementById('cashOut');
const cashDateInput = document.getElementById('cashDate');
const btnFormSubmit = document.getElementById('btnFormSubmit');

var cash = parseFloat(cashInit) || 0;
let cashMony = formatter.format(cash);
$('.cash').text(cashMony);
$('#cashOut').attr('placeholder', cashMony);

// ── Modal helpers ──────────────────────────────────────────────
const modal      = document.getElementById('cash-out-notify-modal');
const modalIcon  = document.getElementById('cashOutNotifyIcon');
const modalMsg   = document.getElementById('cashOutNotifyMsg');
const modalClose = document.getElementById('cashOutNotifyClose');

function showCashOutModal(type, icon, message) {
    modal.classList.remove('home-notify-modal--success', 'home-notify-modal--error');
    modal.classList.add(type === 'success' ? 'home-notify-modal--success' : 'home-notify-modal--error');
    modalIcon.textContent = icon;
    modalMsg.innerHTML = message;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => modalClose.focus(), 50);
}

function closeCashOutModal() {
    modal.setAttribute('aria-hidden', 'true');
    if (btnFormSubmit) btnFormSubmit.focus();
}

if (modalClose) {
    modalClose.addEventListener('click', closeCashOutModal);
}
modal.querySelector('.home-notify-modal__backdrop').addEventListener('click', closeCashOutModal);
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
        closeCashOutModal();
    }
});

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
updateCashOutSubmitButton();

function isNumber(value) { return !isNaN(value) && value.trim() !== ''; }
function parseNumber(str) { return str.replace(/[^\d]/g, ''); }

function formatToVND(input) {
    const raw = parseNumber(input.value);
    if (raw === '') return (input.value = '');
    input.value = formatter.format(raw);
}

function canSubmitCashOutForm() {
    const cashOut = parseNumber(cashOutInput.value);
    const cashDate = cashDateInput.value.trim();
    if (!isNumber(cashOut)) return false;
    if (Number(cashOut) > Number(cash)) return false;
    if (cashDate === '' || !parseYmdStrict(cashDate)) return false;
    return true;
}

function updateCashOutSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitCashOutForm();
}

cashOutInput.addEventListener('input', () => { formatToVND(cashOutInput); updateCashOutSubmitButton(); });
cashDateInput.addEventListener('change', updateCashOutSubmitButton);
cashDateInput.addEventListener('input', updateCashOutSubmitButton);
if (cashDateInput) {
    cashDateInput.addEventListener('click', function () {
        try { this.showPicker(); } catch (_) {}
    });
}

function resetForm() { cashOutInput.value = ''; setDefaultCashDate(); updateCashOutSubmitButton(); }

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
    const cashOut = parseNumber(cashOutInput.value);
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!isNumber(cashOut)) { document.getElementById('errorCashOutType').style.display = 'block'; isValid = false; }
    if (Number(cashOut) > Number(cash)) { document.getElementById('errorCashOutPriceType').style.display = 'block'; isValid = false; }

    if (cashDate === '') {
        document.getElementById('errorCashDate').style.display = 'block';
        isValid = false;
    } else if (!parseYmdStrict(cashDate)) {
        showCashOutModal('error', '❌', 'Ngày rút không hợp lệ');
        isValid = false;
    } else if (isAfterTodayYmd(cashDate)) {
        showCashOutModal('error', '❌', 'Ngày rút không hợp lệ');
        isValid = false;
    }

    if (isValid) {
        btnFormSubmit.disabled = true;
        $.ajax({
            url: baseUrl + '/user/cashOut', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ cashOut, cashDate }),
            success: function (response) {
                if (response.status === 'success') {
                    const num2 = parseFloat(cashOutInput.value.replace(/\./g, '').replace(/,/g, ''));
                    cash = parseFloat(cash) - num2;
                    cashMony = formatter.format(cash);
                    $('.cash').text(cashMony);
                    $('#cashOut').attr('placeholder', cashMony);
                    resetForm();
                    showCashOutModal('success', '✅', `Đã rút thành công số tiền: <b>${formatter.format(num2)}</b>`);
                } else {
                    showCashOutModal('error', '❌', response.message || 'Có lỗi xảy ra.');
                }
            },
            error: function (xhr) {
                const msg = ajaxErrorMessage(xhr, 'Lỗi kết nối.');
                showCashOutModal('error', '❌', msg);
            },
            complete: function () {
                updateCashOutSubmitButton();
            }
        });
    }
}
window.submitForm = submitForm;
