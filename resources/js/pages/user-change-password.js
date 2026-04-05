const { baseUrl } = window.__pageData || {};
const btnFormSubmit = document.getElementById('btnFormSubmit');

function canSubmitChangePasswordForm() {
    const password = document.getElementById('password').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const reNewPassword = document.getElementById('reNewPassword').value.trim();
    if (!password || password.length < 6) return false;
    if (!newPassword || newPassword.length < 6) return false;
    if (!reNewPassword || reNewPassword !== newPassword) return false;
    return true;
}

function updateChangePasswordSubmitButton() {
    if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitChangePasswordForm();
}

['password', 'newPassword', 'reNewPassword'].forEach(function (id) {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', updateChangePasswordSubmitButton);
        el.addEventListener('change', updateChangePasswordSubmitButton);
    }
});
updateChangePasswordSubmitButton();

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

function removeError() {
    ['errorPassword', 'errorPasswordLength', 'errorNewPassword', 'errorNewPasswordLength',
     'errorReNewPassword', 'errorReNewPasswordRe'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function removeValue() {
    ['password', 'newPassword', 'reNewPassword'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    updateChangePasswordSubmitButton();
}

function submitForm() {
    removeError();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const password = document.getElementById('password').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const reNewPassword = document.getElementById('reNewPassword').value.trim();
    let isValid = true;

    if (!password) { document.getElementById('errorPassword').style.display = 'block'; isValid = false; }
    else if (password.length < 6) { document.getElementById('errorPasswordLength').style.display = 'block'; isValid = false; }

    if (!newPassword) { document.getElementById('errorNewPassword').style.display = 'block'; isValid = false; }
    else if (newPassword.length < 6) { document.getElementById('errorNewPasswordLength').style.display = 'block'; isValid = false; }

    if (!reNewPassword) { document.getElementById('errorReNewPassword').style.display = 'block'; isValid = false; }
    else if (reNewPassword !== newPassword) { document.getElementById('errorReNewPasswordRe').style.display = 'block'; isValid = false; }

    if (isValid) {
        $.ajax({
            url: baseUrl + '/user/changePassword/', type: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ password, newPassword }),
            success: function (response) {
                const toast = document.getElementById('toast');
                if (response.status === 'success') {
                    removeValue();
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
