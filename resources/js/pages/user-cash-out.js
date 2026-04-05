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
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!isNumber(cashOut)) return false;
    if (Number(cashOut) > Number(cash)) return false;
    if (cashDate === '' || !dateRegex.test(cashDate) || isNaN(new Date(cashDate).getTime())) return false;
    return true;
}

function updateCashOutSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitCashOutForm();
}

cashOutInput.addEventListener('input', () => { formatToVND(cashOutInput); updateCashOutSubmitButton(); });
cashDateInput.addEventListener('change', updateCashOutSubmitButton);
cashDateInput.addEventListener('input', updateCashOutSubmitButton);

function resetForm() { cashOutInput.value = ''; setDefaultCashDate(); updateCashOutSubmitButton(); }

function submitForm() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const cashDate = cashDateInput.value.trim();
    const cashOut = parseNumber(cashOutInput.value);
    let isValid = true;
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!isNumber(cashOut)) { document.getElementById('errorCashOutType').style.display = 'block'; isValid = false; }
    if (Number(cashOut) > Number(cash)) { document.getElementById('errorCashOutPriceType').style.display = 'block'; isValid = false; }

    if (cashDate === '') { document.getElementById('errorCashDate').style.display = 'block'; isValid = false; }
    else if (!dateRegex.test(cashDate)) { document.getElementById('errorCashDateType').style.display = 'block'; isValid = false; }
    else if (isValid && isNaN(new Date(cashDate).getTime())) { document.getElementById('errorCashDateType').style.display = 'block'; isValid = false; }

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
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi kết nối.';
                showCashOutModal('error', '❌', msg);
            },
            complete: function () {
                updateCashOutSubmitButton();
            }
        });
    }
}
window.submitForm = submitForm;
