const { baseUrl } = window.__pageData || {};

function isUpdateRiskFormReady() {
    return document.getElementById('code').value.trim().length > 0;
}

function updateUpdateRiskSubmitButton() {
    const btn = document.getElementById('btnFormSubmit');
    if (btn) btn.disabled = !isUpdateRiskFormReady();
}

document.getElementById('code').addEventListener('input', updateUpdateRiskSubmitButton);

function resetForm() { document.getElementById('code').value = ''; updateUpdateRiskSubmitButton(); }

function submitForm() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!code) { document.getElementById('errorCode').style.display = 'block'; isValid = false; }

    if (isValid) {
        $.ajax({
            url: baseUrl + '/admin/updateRiskForCode', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ code }),
            success: function (response) {
                if (response.status === 'success') {
                    showNotifyModal('success', `Đã cập nhật mức độ rủi ro cho cổ phiếu: <b>${code}</b>`, resetForm);
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
