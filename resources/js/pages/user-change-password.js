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

function shouldShowLiveErrors() {
    const password = document.getElementById('password').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const reNewPassword = document.getElementById('reNewPassword').value.trim();
    return password.length > 0 || newPassword.length > 0 || reNewPassword.length > 0;
}

/** Khớp nội dung mặc định trong Blade (reset sau lỗi server / nhập lại). */
const DEFAULT_CHANGE_PASSWORD_ERR = {
    errorPassword: 'Vui lòng nhập mật khẩu',
    errorPasswordLength: 'Mật khẩu phải có ít nhất 6 ký tự.',
    errorNewPassword: 'Vui lòng nhập mật khẩu',
    errorNewPasswordLength: 'Mật khẩu phải có ít nhất 6 ký tự.',
    errorReNewPassword: 'Vui lòng nhập mật khẩu',
    errorReNewPasswordRe: 'Nhập lại mật khẩu không đúng'
};

function removeError() {
    Object.keys(DEFAULT_CHANGE_PASSWORD_ERR).forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = DEFAULT_CHANGE_PASSWORD_ERR[id];
            el.style.display = 'none';
        }
    });
}

/**
 * Hiển thị lỗi đỏ khi nút bị disable (khớp rule với canSubmitChangePasswordForm).
 * @param {boolean} force — true: luôn hiện (sau khi bấm Cập nhật); false: chỉ khi user đã gõ ít nhất một ô.
 */
function syncChangePasswordErrors(force) {
    removeError();
    if (!force && !shouldShowLiveErrors()) return;

    const password = document.getElementById('password').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();
    const reNewPassword = document.getElementById('reNewPassword').value.trim();

    if (!password) {
        document.getElementById('errorPassword').style.display = 'block';
    } else if (password.length < 6) {
        document.getElementById('errorPasswordLength').style.display = 'block';
    }

    if (!newPassword) {
        document.getElementById('errorNewPassword').style.display = 'block';
    } else if (newPassword.length < 6) {
        document.getElementById('errorNewPasswordLength').style.display = 'block';
    }

    if (!reNewPassword) {
        document.getElementById('errorReNewPassword').style.display = 'block';
    } else if (newPassword !== reNewPassword) {
        document.getElementById('errorReNewPasswordRe').style.display = 'block';
    }
}

function updateChangePasswordSubmitButton() {
    syncChangePasswordErrors(false);
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

function removeValue() {
    ['password', 'newPassword', 'reNewPassword'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    removeError();
    updateChangePasswordSubmitButton();
}

function applyValidation422(errors) {
    if (!errors || typeof errors !== 'object') return;
    removeError();
    if (errors.password && errors.password[0]) {
        const msg = errors.password[0];
        const isMin = /6|ít nhất|least/i.test(msg);
        const id = isMin ? 'errorPasswordLength' : 'errorPassword';
        const el = document.getElementById(id);
        if (el) {
            el.textContent = msg;
            el.style.display = 'block';
        }
    }
    if (errors.newPassword && errors.newPassword[0]) {
        const msg = errors.newPassword[0];
        const isMin = /6|ít nhất|least/i.test(msg);
        const id = isMin ? 'errorNewPasswordLength' : 'errorNewPassword';
        const el = document.getElementById(id);
        if (el) {
            el.textContent = msg;
            el.style.display = 'block';
        }
    }
}

function submitForm() {
    if (!canSubmitChangePasswordForm()) {
        syncChangePasswordErrors(true);
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const password = document.getElementById('password').value.trim();
    const newPassword = document.getElementById('newPassword').value.trim();

    removeError();
    btnFormSubmit.disabled = true;

    $.ajax({
        url: baseUrl + '/user/changePassword/',
        type: 'PUT',
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
                toast.innerHTML = '❌' + (response.message || 'Có lỗi xảy ra.');
                toast.className = 'toast show';
                toastError();
                setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
                if (response.message && String(response.message).indexOf('Mật khẩu không đúng') !== -1) {
                    const el = document.getElementById('errorPassword');
                    if (el) {
                        el.textContent = 'Mật khẩu hiện tại không đúng.';
                        el.style.display = 'block';
                    }
                    updateChangePasswordSubmitButton();
                }
            }
        },
        error: function (xhr) {
            const toast = document.getElementById('toast');
            let msg = 'Lỗi kết nối, vui lòng thử lại.';
            if (xhr.responseJSON) {
                const j = xhr.responseJSON;
                if (xhr.status === 422 && j.errors) {
                    applyValidation422(j.errors);
                    msg = j.message || msg;
                } else if (j.message) {
                    msg = j.message;
                }
            }
            toast.innerHTML = '❌ ' + msg;
            toast.className = 'toast show';
            toastError();
            setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
        },
        complete: function () {
            btnFormSubmit.disabled = !canSubmitChangePasswordForm();
        }
    });
}
window.submitForm = submitForm;
