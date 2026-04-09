const { baseUrl } = window.__pageData || {};
const formatter = new Intl.NumberFormat('vi-VN');
const followPriceBuyInput = document.getElementById('followPriceBuy');
const followPriceSellInput = document.getElementById('followPriceSell');
const codeInput = document.getElementById('code');
const btnCheckCode = document.getElementById('btnCheckCode');
const btnFormSubmit = document.getElementById('btnFormSubmit');

function isNumber(value) { return !isNaN(value) && value.trim() !== ''; }
function parseNumber(str) { return str.replace(/[^\d]/g, ''); }

function formatToVND(input) {
    const raw = parseNumber(input.value);
    if (raw === '') return (input.value = '');
    input.value = formatter.format(raw);
}

function canSubmitInsertFollowForm() {
    const code = codeInput.value.trim();
    const fpb = parseNumber(followPriceBuyInput.value);
    const fps = parseNumber(followPriceSellInput.value);
    if (!code) return false;
    if (fpb && !isNumber(fpb)) return false;
    if (fps && !isNumber(fps)) return false;
    return true;
}

function updateInsertFollowSubmitButton() {
    const hasCode = codeInput.value.trim().length > 0;
    btnCheckCode.disabled = !hasCode;
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitInsertFollowForm();
    if (hasCode) { btnCheckCode.style.background = '#3498db'; btnCheckCode.style.color = '#fff'; btnCheckCode.style.cursor = 'pointer'; }
    else { btnCheckCode.style.background = '#ccc'; btnCheckCode.style.color = '#666'; btnCheckCode.style.cursor = 'not-allowed'; }
}

followPriceBuyInput.addEventListener('input', () => { formatToVND(followPriceBuyInput); updateInsertFollowSubmitButton(); });
followPriceSellInput.addEventListener('input', () => { formatToVND(followPriceSellInput); updateInsertFollowSubmitButton(); });
codeInput.addEventListener('input', updateInsertFollowSubmitButton);
updateInsertFollowSubmitButton();

function checkStockCode() {
    const code = codeInput.value.trim().toUpperCase();
    if (!code) return;
    btnCheckCode.disabled = true;
    btnCheckCode.innerHTML = '⏳ Đang kiểm tra...';

    $.ajax({
        url: baseUrl + '/user/checkStockCode/' + code,
        type: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                showNotifyModal('success', `✅ ${response.message}`);
                if (response.data && response.data.recommended_buy_price) followPriceBuyInput.value = formatter.format(response.data.recommended_buy_price);
                if (response.data && response.data.recommended_sell_price) followPriceSellInput.value = formatter.format(response.data.recommended_sell_price);
                updateInsertFollowSubmitButton();
            } else if (response.status === 'warning') {
                showNotifyModal('warning', `⚠️ ${response.message}`);
                if (response.data && response.data.recommended_buy_price) followPriceBuyInput.value = formatter.format(response.data.recommended_buy_price);
                if (response.data && response.data.recommended_sell_price) followPriceSellInput.value = formatter.format(response.data.recommended_sell_price);
            } else {
                const plain = String(response.message || 'Mã cổ phiếu không tồn tại trong hệ thống.').replace(/<[^>]*>/g, '');
                followPriceBuyInput.value = '';
                followPriceSellInput.value = '';
                updateInsertFollowSubmitButton();
                showNotifyModal('error', '❌ ' + plain);
            }
        },
        error: function (xhr) {
            let msg = 'Lỗi kết nối, vui lòng thử lại.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = String(xhr.responseJSON.message).replace(/<[^>]*>/g, '');
            showNotifyModal('error', '❌ ' + msg);
        },
        complete: function () {
            btnCheckCode.disabled = false;
            btnCheckCode.innerHTML = '🔍 Kiểm tra';
            if (codeInput.value.trim().length > 0) { btnCheckCode.style.background = '#3498db'; btnCheckCode.style.color = '#fff'; btnCheckCode.style.cursor = 'pointer'; }
        }
    });
}
window.checkStockCode = checkStockCode;

function resetForm() {
    codeInput.value = '';
    followPriceBuyInput.value = '';
    followPriceSellInput.value = '';
    updateInsertFollowSubmitButton();
}

function submitForm() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const code = codeInput.value.trim().toUpperCase();
    const followPriceBuy = parseNumber(followPriceBuyInput.value);
    const followPriceSell = parseNumber(followPriceSellInput.value);
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!code) { document.getElementById('errorCode').style.display = 'block'; isValid = false; }
    if (followPriceBuy && !isNumber(followPriceBuy)) { document.getElementById('errorFollowPriceBuyType').style.display = 'block'; isValid = false; }
    if (followPriceSell && !isNumber(followPriceSell)) { document.getElementById('errorFollowPriceSellType').style.display = 'block'; isValid = false; }

    if (isValid) {
        $.ajax({
            url: baseUrl + '/user/insertFollow', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ code, followPriceBuy, followPriceSell }),
            success: function (response) {
                if (response.status === 'success') {
                    showNotifyModal('success', `✅ Đã thêm thành công mã <b>${code}</b>`);
                    resetForm();
                } else {
                    showNotifyModal('error', '❌ ' + (response.message || 'Có lỗi xảy ra.'));
                }
            },
            error: function (xhr) {
                showNotifyModal('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi'));
            }
        });
    }
}
window.submitForm = submitForm;
