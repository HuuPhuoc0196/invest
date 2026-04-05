// Moved from Register.blade.php <script> block
(function () {
    const { urlRegisterPost } = window.__pageData || {};

    const form = document.getElementById('registerForm');
    const nameField = document.getElementById('name');
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const passwordConfirmationField = document.getElementById('password_confirmation');
    const registerError = document.getElementById('registerError');
    const notifyModal = document.getElementById('notify-modal');

    function showModal(type, html) {
        document.getElementById('notifyIcon').textContent = type === 'success' ? '✅' : '❌';
        document.getElementById('notifyMsg').innerHTML = html;
        notifyModal.className = 'notify-modal show ' + (type === 'success' ? 'is-success' : 'is-error');
        notifyModal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        notifyModal.className = 'notify-modal';
        notifyModal.setAttribute('aria-hidden', 'true');
    }

    document.getElementById('notifyClose').addEventListener('click', closeModal);
    document.getElementById('notifyBackdrop').addEventListener('click', closeModal);

    function resetForm() {
        nameField.value = '';
        emailField.value = '';
        passwordField.value = '';
        passwordConfirmationField.value = '';
    }

    const showError = (element, message) => {
        document.getElementById(element + 'Error').innerText = message;
    };

    const clearErrors = () => {
        ['name', 'email', 'password', 'password_confirmation'].forEach(field => {
            showError(field, '');
        });
        registerError.style.display = 'none';
    };

    const validateEmail = (email) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const name = nameField.value.trim();
        const email = emailField.value.trim();
        const password = passwordField.value;
        const passwordConfirmation = passwordConfirmationField.value;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        let valid = true;

        if (name.length < 2) {
            showError('name', 'Tên phải có ít nhất 2 ký tự.');
            valid = false;
        }

        if (!validateEmail(email)) {
            showError('email', 'Email không hợp lệ.');
            valid = false;
        }

        if (password.length < 6) {
            showError('password', 'Mật khẩu phải có ít nhất 6 ký tự.');
            valid = false;
        }

        if (password !== passwordConfirmation) {
            showError('password_confirmation', 'Nhập lại mật khẩu không khớp.');
            valid = false;
        }

        if (valid) {
            const btnRegister = document.getElementById('btnRegister');
            const btnSpinner = document.getElementById('btnRegisterSpinner');
            const btnText = document.getElementById('btnRegisterText');
            btnRegister.disabled = true;
            btnSpinner.classList.add('is-loading');
            btnText.textContent = 'Đang xử lý...';

            const data = {
                name: name,
                email: email,
                password: password,
                password_confirmation: passwordConfirmation
            };
            $.ajax({
                url: urlRegisterPost || '',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify(data),
                success: function (response) {
                    btnRegister.disabled = false;
                    btnSpinner.classList.remove('is-loading');
                    btnText.textContent = 'Đăng ký';
                    if (response.status == "success") {
                        showModal('success', `${response.message}<br><small>Kiểm tra hộp thư <b>${email}</b> để xác thực.</small>`);
                        resetForm();
                    } else {
                        showModal('error', response.message);
                    }
                },
                error: function (xhr) {
                    btnRegister.disabled = false;
                    btnSpinner.classList.remove('is-loading');
                    btnText.textContent = 'Đăng ký';
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi kết nối.';
                    showModal('error', msg);
                }
            });
        }
    });
})();
