const { baseUrl, cash: cashInit, userPortfolios: userPortfoliosRaw } = window.__pageData || {};
const userPortfolios = userPortfoliosRaw || [];
const formatter = new Intl.NumberFormat('vi-VN');
const sellPriceInput = document.getElementById('sellPrice');
const quantityInput = document.getElementById('quantity');
const sellDateInput = document.getElementById('sellDate');
const btnFormSubmit = document.getElementById('btnFormSubmit');

var cash = parseFloat(cashInit) || 0;
$('.cash').text(formatter.format(cash));

function showSellModal(msg, type, onClose) {
    const modal    = document.getElementById('sell-notify-modal');
    const icon     = document.getElementById('sellNotifyIcon');
    const msgEl    = document.getElementById('sellNotifyMsg');
    const closeBtn = document.getElementById('sellNotifyClose');
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
    setTimeout(() => closeBtn.focus(), 50);
    function closeModal() {
        if (modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        modal.setAttribute('aria-hidden', 'true');
        closeBtn.removeEventListener('click', closeModal);
        document.getElementById('sellNotifyBackdrop').removeEventListener('click', closeModal);
        if (typeof onClose === 'function') onClose();
    }
    closeBtn.addEventListener('click', closeModal);
    document.getElementById('sellNotifyBackdrop').addEventListener('click', closeModal);
}

function getTodayYmd() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function setDefaultSellDate() { sellDateInput.value = getTodayYmd(); }

document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('code');
    setDefaultSellDate();

    userPortfolios.forEach(p => {
        const option = document.createElement('option');
        option.value = p.code;
        option.textContent = p.code;
        select.appendChild(option);
    });

    select.addEventListener('change', function () {
        const selectedPortfolio = userPortfolios.find(p => p.code === this.value);
        if (selectedPortfolio) {
            quantityInput.value = selectedPortfolio.total_quantity;
            formatQuantityInteger(quantityInput);
        } else {
            quantityInput.value = '';
        }
        updateTotalAmount();
        updateSellSubmitButton();
    });
    updateSellSubmitButton();
});

function isNumber(value) { return !isNaN(value) && String(value).trim() !== ''; }
function parseNumber(str) { return str.replace(/[^\d]/g, ''); }

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

function isValidCalendarDateYmd(s) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
    const y = parseInt(s.slice(0, 4), 10), m = parseInt(s.slice(5, 7), 10), d = parseInt(s.slice(8, 10), 10);
    const dt = new Date(y, m - 1, d);
    return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
}

function canSubmitSellForm() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const sell = parseNumber(sellPriceInput.value);
    const quantity = parseNumber(quantityInput.value);
    const sellDate = sellDateInput.value.trim();
    if (!code || !sell || !isNumber(sell) || !quantity || !isNumber(quantity)) return false;
    const sellN = parseFloat(sell), qtyN = parseInt(quantity, 10);
    if (!Number.isFinite(sellN) || sellN <= 0) return false;
    if (!Number.isFinite(qtyN) || qtyN < 1) return false;
    if (sellDate === '' || !isValidCalendarDateYmd(sellDate)) return false;
    return true;
}

function updateTotalAmount() {
    const sell = parseFloat(parseNumber(sellPriceInput.value)) || 0;
    const quantity = parseInt(parseNumber(quantityInput.value), 10) || 0;
    const total = sell > 0 && quantity > 0 ? sell * quantity : 0;
    document.getElementById('totalAmount').textContent = total > 0 ? `Tổng tiền nhận: ${formatter.format(total)} VND` : '';
}

function updateSellSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitSellForm();
}

sellPriceInput.addEventListener('input', () => { formatToVND(sellPriceInput); updateTotalAmount(); updateSellSubmitButton(); });
quantityInput.addEventListener('input', () => { formatQuantityInteger(quantityInput); updateTotalAmount(); updateSellSubmitButton(); });
sellDateInput.addEventListener('change', updateSellSubmitButton);
sellDateInput.addEventListener('input', updateSellSubmitButton);

function resetForm() {
    document.getElementById('code').value = '';
    sellPriceInput.value = '';
    quantityInput.value = '';
    document.getElementById('totalAmount').textContent = '';
    setDefaultSellDate();
    updateSellSubmitButton();
}

function sellStocksOnForm(code, quantity) {
    const stock = userPortfolios.find(item => item.code === code);
    if (!stock) return false;
    if (quantity > stock.total_quantity) return false;
    stock.total_quantity -= quantity;
    refreshStockSelect();
    return true;
}

function refreshStockSelect() {
    const select = document.getElementById('code');
    select.innerHTML = '<option value="">-- Chọn mã cổ phiếu --</option>';
    userPortfolios.forEach(p => {
        if (p.total_quantity > 0) {
            const option = document.createElement('option');
            option.value = p.code;
            option.textContent = p.code;
            select.appendChild(option);
        }
    });
    updateSellSubmitButton();
}

function submitForm() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const sell = parseNumber(sellPriceInput.value);
    const quantity = parseNumber(quantityInput.value);
    const sellDate = sellDateInput.value.trim();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!code) { document.getElementById('errorCode').style.display = 'block'; isValid = false; }
    if (!sell) { document.getElementById('errorSell').style.display = 'block'; isValid = false; }
    else if (!isNumber(sell)) { document.getElementById('errorSellType').style.display = 'block'; isValid = false; }
    if (!quantity) { document.getElementById('errorQuantity').style.display = 'block'; isValid = false; }
    else if (!isNumber(quantity)) { document.getElementById('errorQuantityType').style.display = 'block'; isValid = false; }
    if (sellDate === '') { document.getElementById('errorSellDate').style.display = 'block'; isValid = false; }
    else if (!isValidCalendarDateYmd(sellDate)) { document.getElementById('errorSellDateType').style.display = 'block'; isValid = false; }

    if (isValid) {
        btnFormSubmit.disabled = true;
        const cashSell = parseFloat(sell) * parseInt(quantity, 10);
        const data = { code, sell_price: parseFloat(sell), quantity: parseInt(quantity, 10), sell_date: sellDate };
        $.ajax({
            url: baseUrl + '/user/sell', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify(data),
            success: function (response) {
                if (response.status === 'success') {
                    cash = parseFloat(cash) + cashSell;
                    $('.cash').text(formatter.format(cash));
                    resetForm();
                    sellStocksOnForm(code, parseInt(quantity, 10));
                    showSellModal('Đã bán thành công mã ' + code, 'success');
                } else {
                    showSellModal(response.message || 'Có lỗi xảy ra.', 'error');
                }
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi kết nối, vui lòng thử lại.';
                showSellModal(msg, 'error');
            },
            complete: function () {
                btnFormSubmit.disabled = !canSubmitSellForm();
            }
        });
    }
}
window.submitForm = submitForm;
