const { baseUrl, user: userInit } = window.__pageData || {};
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

function toastSuccess() {
    const toast = document.getElementById('toast');
    toast.classList.remove('toast-success', 'toast-error');
    toast.classList.add('toast-success', 'toast', 'show');
}

function toastError() {
    const toast = document.getElementById('toast');
    toast.classList.remove('toast-success', 'toast-error');
    toast.classList.add('toast-error', 'toast', 'show');
}

function submitForm() {
    document.getElementById('errorName').style.display = 'none';
    document.getElementById('errorNameLength').style.display = 'none';
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const name = document.getElementById('name').value.trim();
    let isValid = true;

    if (!name) { document.getElementById('errorName').style.display = 'block'; isValid = false; }
    else if (name.length < 2) { document.getElementById('errorNameLength').style.display = 'block'; isValid = false; }

    if (isValid) {
        $.ajax({
            url: baseUrl + '/user/updateInfoProfile/', type: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ name }),
            success: function (response) {
                const toast = document.getElementById('toast');
                if (response.status === 'success') {
                    toast.innerHTML = '✅ Đã cập nhật thành công <br>';
                    toast.className = 'toast show';
                    toastSuccess();
                    setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000);
                } else {
                    toast.innerHTML = `❌` + response.message;
                    toast.className = 'toast show';
                    toastError();
                    setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
                }
            },
            error: function (xhr) {
                const toast = document.getElementById('toast');
                toast.innerHTML = '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi');
                toast.className = 'toast show';
                toastError();
                setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
            }
        });
    }
}
window.submitForm = submitForm;
