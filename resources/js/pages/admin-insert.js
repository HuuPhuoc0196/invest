const { baseUrl } = window.__pageData || {};
const formatter = new Intl.NumberFormat('vi-VN');
const buyPriceInput = document.getElementById('buyPrice');
const currentPriceInput = document.getElementById('currentPrice');

function isNumber(value) { return !isNaN(value) && value.trim() !== ''; }
function parseNumber(str) { return str.replace(/[^\d]/g, ''); }

function formatToVND(input) {
    const raw = parseNumber(input.value);
    if (raw === '') return (input.value = '');
    input.value = formatter.format(raw);
}

function isAdminInsertFormReady() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const buy = parseNumber(buyPriceInput.value);
    const current = parseNumber(currentPriceInput.value);
    if (!code) return false;
    if (!buy || !isNumber(buy)) return false;
    if (!current || !isNumber(current)) return false;
    return true;
}

function updateAdminInsertSubmitButton() {
    const btn = document.getElementById('btnFormSubmit');
    if (btn) btn.disabled = !isAdminInsertFormReady();
}

buyPriceInput.addEventListener('input', () => { formatToVND(buyPriceInput); updateAdminInsertSubmitButton(); });
currentPriceInput.addEventListener('input', () => { formatToVND(currentPriceInput); updateAdminInsertSubmitButton(); });
document.getElementById('code').addEventListener('input', updateAdminInsertSubmitButton);
document.getElementById('risk').addEventListener('change', updateAdminInsertSubmitButton);

function resetForm() {
    document.getElementById('code').value = '';
    buyPriceInput.value = '';
    currentPriceInput.value = '';
    document.getElementById('risk').value = '1';
    updateAdminInsertSubmitButton();
}

function submitForm() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const buy = parseNumber(buyPriceInput.value);
    const current = parseNumber(currentPriceInput.value);
    const risk = document.getElementById('risk').value;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!code) { document.getElementById('errorCode').style.display = 'block'; isValid = false; }
    if (!buy) { document.getElementById('errorBuy').style.display = 'block'; isValid = false; }
    else if (!isNumber(buy)) { document.getElementById('errorBuyType').style.display = 'block'; isValid = false; }
    if (!current) { document.getElementById('errorCurrent').style.display = 'block'; isValid = false; }
    else if (!isNumber(current)) { document.getElementById('errorCurrentType').style.display = 'block'; isValid = false; }

    if (isValid) {
        $.ajax({
            url: baseUrl + '/admin/insert', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ code, buyPrice: buy, currentPrice: current, risk }),
            success: function (response) {
                if (response.status === 'success') {
                    showNotifyModal('success', `Đã thêm mã <b>${code}</b> thành công`, resetForm);
                } else {
                    showNotifyModal('error', response.message || 'Có lỗi xảy ra.');
                }
            },
            error: function (xhr) {
                showNotifyModal('error', 'Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi kết nối'));
            }
        });
    }
}
window.submitForm = submitForm;
updateAdminInsertSubmitButton();
