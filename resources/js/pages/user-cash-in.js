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
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!cashIn || !isNumber(cashIn)) return false;
    if (cashDate === '' || !dateRegex.test(cashDate) || isNaN(new Date(cashDate).getTime())) return false;
    return true;
}

function updateCashInSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitCashInForm();
}

cashInInput.addEventListener('input', () => { formatToVND(cashInInput); updateCashInSubmitButton(); });
cashDateInput.addEventListener('change', updateCashInSubmitButton);
cashDateInput.addEventListener('input', updateCashInSubmitButton);
updateCashInSubmitButton();

function resetForm() { cashInInput.value = ''; setDefaultCashDate(); updateCashInSubmitButton(); }

function submitForm() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const cashDate = cashDateInput.value.trim();
    const cashIn = parseNumber(cashInInput.value);
    let isValid = true;
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (cashIn) { if (!isNumber(cashIn)) { document.getElementById('errorCashInType').style.display = 'block'; isValid = false; } }

    if (cashDate === '') { document.getElementById('errorCashDate').style.display = 'block'; isValid = false; }
    else if (!dateRegex.test(cashDate)) { document.getElementById('errorCashDateType').style.display = 'block'; isValid = false; }
    else if (isValid && isNaN(new Date(cashDate).getTime())) { document.getElementById('errorCashDateType').style.display = 'block'; isValid = false; }

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
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi kết nối.';
                showCashInModal('error', '❌', msg);
            },
            complete: function () {
                updateCashInSubmitButton();
            }
        });
    }
}
window.submitForm = submitForm;
