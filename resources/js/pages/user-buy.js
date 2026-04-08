const { baseUrl, cash: cashInit, buyPriceMax: BUY_PRICE_MAX_RAW, quantityMax: QUANTITY_MAX_RAW } = window.__pageData || {};
const formatter = new Intl.NumberFormat('vi-VN');
const BUY_PRICE_MAX = typeof BUY_PRICE_MAX_RAW !== 'undefined' ? BUY_PRICE_MAX_RAW : 9999999999999.99;
const QUANTITY_MAX = typeof QUANTITY_MAX_RAW !== 'undefined' ? QUANTITY_MAX_RAW : 9007199254740991;

const buyPriceInput = document.getElementById('buyPrice');
const quantityInput = document.getElementById('quantity');
const buyDateInput = document.getElementById('buyDate');
const codeInput = document.getElementById('code');
const btnFormSubmit = document.getElementById('btnFormSubmit');
const btnCheckCode = document.getElementById('btnCheckCode');

var cash = parseFloat(cashInit);
if (!Number.isFinite(cash)) cash = 0;
let cashMony = formatter.format(cash);
$('.cash').text(cashMony);

function showBuyModal(msg, type, onClose) {
    const modal    = document.getElementById('buy-notify-modal');
    const icon     = document.getElementById('buyNotifyIcon');
    const msgEl    = document.getElementById('buyNotifyMsg');
    const closeBtn = document.getElementById('buyNotifyClose');
    if (!modal) return;
    modal.classList.remove('home-notify-modal--success', 'home-notify-modal--error');
    if (type === 'success') {
        modal.classList.add('home-notify-modal--success');
        icon.textContent = '✅';
    } else {
        modal.classList.add('home-notify-modal--error');
        icon.textContent = '❌';
    }
    msgEl.textContent = msg;
    modal.setAttribute('aria-hidden', 'false');
    // Move focus into modal for accessibility
    setTimeout(() => closeBtn.focus(), 50);
    function closeModal() {
        // Blur any focused element inside modal before hiding to avoid aria-hidden warning
        if (modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        modal.setAttribute('aria-hidden', 'true');
        closeBtn.removeEventListener('click', closeModal);
        document.getElementById('buyNotifyBackdrop').removeEventListener('click', closeModal);
        if (typeof onClose === 'function') onClose();
    }
    closeBtn.addEventListener('click', closeModal);
    document.getElementById('buyNotifyBackdrop').addEventListener('click', closeModal);
}

function getTodayYmd() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function setDefaultBuyDate() { buyDateInput.value = getTodayYmd(); }
setDefaultBuyDate();

function updateCheckCodeButton() {
    if (!btnCheckCode) return;
    btnCheckCode.disabled = !codeInput.value.trim().length;
}

function checkStockCode() {
    const code = codeInput.value.trim().toUpperCase();
    if (!code) return;
    const prevLabel = btnCheckCode.textContent;
    btnCheckCode.disabled = true;
    btnCheckCode.textContent = 'Đang kiểm tra...';

    $.ajax({
        url: baseUrl + '/user/validate-stock/' + encodeURIComponent(code),
        type: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                showBuyModal(response.message, 'success');
                const d = response.data;
                if (d && d.current_price != null && String(d.current_price).trim() !== '') {
                    const n = Number(d.current_price);
                    if (!isNaN(n) && n > 0) {
                        buyPriceInput.value = formatter.format(Math.round(n));
                        buyPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
                updateTotalAmount();
                updateBuySubmitButton();
            } else {
                const plain = String(response.message || 'Mã cổ phiếu không tồn tại trong hệ thống.').replace(/<[^>]*>/g, '');
                showBuyModal(plain, 'error');
            }
        },
        error: function (xhr) {
            let msg = 'Lỗi kết nối, vui lòng thử lại.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = String(xhr.responseJSON.message).replace(/<[^>]*>/g, '');
            showBuyModal(msg, 'error');
        },
        complete: function () { btnCheckCode.textContent = prevLabel; updateCheckCodeButton(); }
    });
}

btnCheckCode.addEventListener('click', checkStockCode);
updateBuySubmitButton();
updateCheckCodeButton();

function isNumber(value) { return !isNaN(value) && String(value).trim() !== ''; }
function parseNumber(str) { return str.replace(/[^\d]/g, ''); }

function isValidCalendarDateYmd(s) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
    const y = parseInt(s.slice(0, 4), 10), m = parseInt(s.slice(5, 7), 10), d = parseInt(s.slice(8, 10), 10);
    const dt = new Date(y, m - 1, d);
    return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
}

/** So sánh hai chuỗi Y-m-d (hợp lệ). */
function cmpYmd(a, b) {
    if (a === b) return 0;
    return a < b ? -1 : 1;
}

function formatToVND(input) {
    const raw = parseNumber(input.value);
    if (raw === '') return (input.value = '');
    input.value = formatter.format(raw);
}

function formatQuantityInteger(input) {
    const raw = input.value.replace(/\D/g, '');
    if (raw === '') return (input.value = '');
    const n = parseInt(raw, 10);
    if (!Number.isFinite(n)) return (input.value = '');
    input.value = formatter.format(n);
}

function syncBuyRangeErrors() {
    const erBuy = document.getElementById('errorBuyRange');
    const erQty = document.getElementById('errorQuantityRange');
    const erCash = document.getElementById('errorCashBuyType');
    if (erBuy) erBuy.style.display = 'none';
    if (erQty) erQty.style.display = 'none';
    if (erCash) erCash.style.display = 'none';
    const buyStr = parseNumber(buyPriceInput.value);
    const qtyStr = parseNumber(quantityInput.value);
    if (buyStr !== '') {
        const buyN = parseFloat(buyStr);
        if (Number.isFinite(buyN) && buyN > BUY_PRICE_MAX && erBuy) erBuy.style.display = 'block';
    }
    if (qtyStr !== '') {
        const qtyN = parseInt(qtyStr, 10);
        if (!Number.isFinite(qtyN) || qtyN < 1 || qtyN > QUANTITY_MAX) { if (erQty) erQty.style.display = 'block'; }
    }
    if (erCash && buyStr !== '' && qtyStr !== '') {
        const buyN = parseFloat(buyStr);
        const qtyN = parseInt(qtyStr, 10);
        if (Number.isFinite(buyN) && buyN > 0 && Number.isFinite(qtyN) && qtyN >= 1 && buyN <= BUY_PRICE_MAX && qtyN <= QUANTITY_MAX) {
            const cashBuy = buyN * qtyN;
            if (Number.isFinite(cashBuy) && cashBuy > cash) erCash.style.display = 'block';
        }
    }
}

function canSubmitBuyForm() {
    const code = codeInput.value.trim().toUpperCase();
    const buyStr = parseNumber(buyPriceInput.value);
    const qtyStr = parseNumber(quantityInput.value);
    const buyDate = buyDateInput.value.trim();
    const today = getTodayYmd();
    if (!code || !buyStr || !qtyStr) return false;
    if (!isNumber(buyStr) || !isNumber(qtyStr)) return false;
    const buyN = parseFloat(buyStr), qtyN = parseInt(qtyStr, 10);
    if (!Number.isFinite(buyN) || buyN <= 0) return false;
    if (!Number.isFinite(qtyN) || qtyN < 1) return false;
    if (buyN > BUY_PRICE_MAX || qtyN > QUANTITY_MAX) return false;
    const cashBuy = buyN * qtyN;
    if (!Number.isFinite(cashBuy) || cashBuy > cash) return false;
    if (buyDate === '' || !isValidCalendarDateYmd(buyDate)) return false;
    if (cmpYmd(buyDate, today) > 0) return false;
    return true;
}

function updateBuySubmitButton() {
    syncBuyRangeErrors();
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitBuyForm();
}

buyPriceInput.addEventListener('input', () => { formatToVND(buyPriceInput); updateTotalAmount(); updateBuySubmitButton(); });
quantityInput.addEventListener('input', () => { formatQuantityInteger(quantityInput); updateTotalAmount(); updateBuySubmitButton(); });
codeInput.addEventListener('input', function () { updateBuySubmitButton(); updateCheckCodeButton(); });
buyDateInput.addEventListener('change', function () {
    const v = this.value.trim();
    if (v !== '') {
        if (!isValidCalendarDateYmd(v) || cmpYmd(v, getTodayYmd()) > 0) {
            showBuyModal('Ngày mua không hợp lệ', 'error');
        }
    }
    updateBuySubmitButton();
});
buyDateInput.addEventListener('input', updateBuySubmitButton);
if (buyDateInput) {
    buyDateInput.addEventListener('click', function () {
        try { this.showPicker(); } catch (_) {}
    });
}

function updateTotalAmount() {
    const buy = parseFloat(parseNumber(buyPriceInput.value)) || 0;
    const quantity = parseInt(parseNumber(quantityInput.value), 10) || 0;
    const total = buy > 0 && quantity > 0 ? buy * quantity : 0;
    document.getElementById('totalAmount').textContent = total > 0 ? `Tổng tiền: ${formatter.format(total)} VND` : '';
}

function resetForm() {
    codeInput.value = '';
    buyPriceInput.value = '';
    quantityInput.value = '';
    setDefaultBuyDate();
    document.getElementById('errorBuyRange').style.display = 'none';
    document.getElementById('errorQuantityRange').style.display = 'none';
    const erCash = document.getElementById('errorCashBuyType');
    if (erCash) erCash.style.display = 'none';
    updateBuySubmitButton();
    updateCheckCodeButton();
}

function submitForm() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const buy = parseNumber(buyPriceInput.value);
    const quantity = parseNumber(quantityInput.value);
    const buyDate = buyDateInput.value.trim();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!code) { document.getElementById('errorCode').style.display = 'block'; isValid = false; }
    if (!buy) { document.getElementById('errorBuy').style.display = 'block'; isValid = false; }
    else if (!isNumber(buy)) { document.getElementById('errorBuyType').style.display = 'block'; isValid = false; }
    else if (parseFloat(buy) > BUY_PRICE_MAX) { document.getElementById('errorBuyRange').style.display = 'block'; isValid = false; }

    if (!quantity) { document.getElementById('errorQuantity').style.display = 'block'; isValid = false; }
    else if (!isNumber(quantity)) { document.getElementById('errorQuantityType').style.display = 'block'; isValid = false; }
    else {
        const qn = parseInt(quantity, 10);
        if (!Number.isFinite(qn) || qn < 1 || qn > QUANTITY_MAX) { document.getElementById('errorQuantityRange').style.display = 'block'; isValid = false; }
    }

    let cashBuy = Number(buy) * Number(quantity);
    if (isValid && cashBuy > cash) { document.getElementById('errorCashBuyType').style.display = 'block'; isValid = false; }

    if (buyDate === '') { document.getElementById('errorBuyDate').style.display = 'block'; isValid = false; }
    else if (!dateRegex.test(buyDate) || !isValidCalendarDateYmd(buyDate)) {
        document.getElementById('errorBuyDateType').style.display = 'block';
        isValid = false;
    } else if (cmpYmd(buyDate, getTodayYmd()) > 0) {
        showBuyModal('Ngày mua không hợp lệ', 'error');
        isValid = false;
    }

    if (isValid) {
        btnFormSubmit.disabled = true;
        const data = { code, buy_price: parseFloat(buy), quantity: parseInt(quantity, 10), buy_date: buyDate };
        $.ajax({
            url: baseUrl + '/user/buy', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify(data),
            success: function (response) {
                if (response.status === 'success') {
                    cash = parseFloat(cash) - cashBuy;
                    if (!Number.isFinite(cash)) cash = 0;
                    cashMony = formatter.format(cash);
                    $('.cash').text(cashMony);
                    updateBuySubmitButton();
                    document.getElementById('totalAmount').textContent = '';
                    resetForm();
                    showBuyModal('Đã mua thành công mã ' + code, 'success');
                } else {
                    showBuyModal(response.message || 'Có lỗi xảy ra.', 'error');
                }
            },
            error: function (xhr) {
                let msg = 'Lỗi kết nối, vui lòng thử lại.';
                if (xhr.responseJSON) {
                    const j = xhr.responseJSON;
                    if (xhr.status === 422 && j.errors && j.errors.buy_date && j.errors.buy_date[0]) {
                        msg = j.errors.buy_date[0];
                    } else if (j.message) {
                        msg = j.message;
                    }
                }
                showBuyModal(msg, 'error');
            },
            complete: function () {
                btnFormSubmit.disabled = !canSubmitBuyForm();
            }
        });
    }
}
window.submitForm = submitForm;
