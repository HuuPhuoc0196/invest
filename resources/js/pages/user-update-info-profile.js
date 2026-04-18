const { baseUrl, user: userInit, apiUrl: updateInfoProfileUrl } = window.__pageData || {};
const btnFormSubmit = document.getElementById('btnFormSubmit');

function canSubmitUpdateNameForm() {
    const name = document.getElementById('name').value.trim();
    return name.length >= 2;
}

function updateUpdateNameSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitUpdateNameForm();
}

document.addEventListener('DOMContentLoaded', function () {
    if (userInit) {
        document.getElementById('name').value = userInit.name || '';
        const emailEl = document.getElementById('email');
        if (emailEl && userInit.email) emailEl.value = userInit.email;
    }
    updateUpdateNameSubmitButton();
});

document.getElementById('name').addEventListener('input', updateUpdateNameSubmitButton);
document.getElementById('name').addEventListener('change', updateUpdateNameSubmitButton);

function submitForm() {
    document.getElementById('errorName').style.display = 'none';
    document.getElementById('errorNameLength').style.display = 'none';
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const name = document.getElementById('name').value.trim();
    let isValid = true;

    if (!name) { document.getElementById('errorName').style.display = 'block'; isValid = false; }
    else if (name.length < 2) { document.getElementById('errorNameLength').style.display = 'block'; isValid = false; }

    if (isValid) {
        if (btnFormSubmit) {
            btnFormSubmit.dataset.originalText = btnFormSubmit.innerHTML;
            btnFormSubmit.innerHTML = '⏳ Đang cập nhật...';
            btnFormSubmit.disabled = true;
        }
        $.ajax({
            url: updateInfoProfileUrl || (baseUrl + '/user/updateInfoProfile/'), type: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ name }),
            success: function (response) {
                if (btnFormSubmit) {
                    btnFormSubmit.innerHTML = btnFormSubmit.dataset.originalText || 'Cập nhật';
                    btnFormSubmit.disabled = false;
                }
                if (response.status === 'success') {
                    showNotifyModal('success', 'Đã cập nhật thành công');
                } else {
                    showNotifyModal('error', response.message || 'Có lỗi xảy ra.');
                }
            },
            error: function (xhr) {
                if (btnFormSubmit) {
                    btnFormSubmit.innerHTML = btnFormSubmit.dataset.originalText || 'Cập nhật';
                    btnFormSubmit.disabled = false;
                }
                showNotifyModal('error', 'Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi kết nối'));
            }
        });
    }
}
window.submitForm = submitForm;
