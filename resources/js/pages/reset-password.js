document.getElementById('resetPasswordForm')?.addEventListener('submit', function (e) {
    const pwd = document.getElementById('password');
    const pwdConfirm = document.getElementById('password_confirmation');
    const formError = document.getElementById('formError');
    document.getElementById('passwordError').textContent = '';
    document.getElementById('passwordConfirmationError').textContent = '';
    formError.style.display = 'none';

    if (pwd.value.length < 6) {
        e.preventDefault();
        document.getElementById('passwordError').textContent = 'Mật khẩu tối thiểu 6 ký tự.';
        return;
    }
    if (pwd.value !== pwdConfirm.value) {
        e.preventDefault();
        document.getElementById('passwordConfirmationError').textContent = 'Nhập lại mật khẩu không khớp.';
        return;
    }
});
