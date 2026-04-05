// Moved from ForgotPassword.blade.php <script> block
(function () {
    const { urlForgotPost } = window.__pageData || {};

    const forgetForm = document.getElementById('forgotForm');
    const errorMessage = document.getElementById('errorMessage');
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

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    forgetForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btnSubmit = document.getElementById('btnForgotSubmit');
        const btnSpinner = document.getElementById('btnForgotSpinner');
        const btnText = document.getElementById('btnForgotText');
        const email = document.getElementById('email').value.trim();
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        errorMessage.style.display = "none";
        errorMessage.innerText = "";

        if (!email) {
            errorMessage.innerText = "Vui lòng nhập đầy đủ email";
            errorMessage.style.display = "block";
            return;
        }
        if (!isValidEmail(email)) {
            errorMessage.innerText = "Email không hợp lệ.";
            errorMessage.style.display = "block";
            return;
        }

        btnSubmit.disabled = true;
        btnSpinner.classList.add('is-loading');
        btnText.textContent = 'Đang xử lý...';

        const data = { email: email };

        $.ajax({
            url: urlForgotPost || '',
            type: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            data: JSON.stringify(data),
            success: function (response) {
                btnSubmit.disabled = false;
                btnSpinner.classList.remove('is-loading');
                btnText.textContent = 'Khôi phục tài khoản';
                if (response.status == "success") {
                    showModal('success', 'Vui lòng kiểm tra email để lấy link đặt lại mật khẩu.');
                } else {
                    showModal('error', response.message);
                }
            },
            error: function (xhr) {
                btnSubmit.disabled = false;
                btnSpinner.classList.remove('is-loading');
                btnText.textContent = 'Khôi phục tài khoản';
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi kết nối.';
                showModal('error', msg);
            }
        });
    });
})();
