// Moved from Login.blade.php <script> block
(function () {
    const { urlAdmin, urlHome, urlLoginPost } = window.__pageData || {};

    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');

    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        errorMessage.style.display = "none";
        errorMessage.innerText = "";

        if (!email || !password) {
            errorMessage.innerText = "Vui lòng nhập đầy đủ email và mật khẩu.";
            errorMessage.style.display = "block";
            return;
        }
        if (!isValidEmail(email)) {
            errorMessage.innerText = "Email không hợp lệ.";
            errorMessage.style.display = "block";
            return;
        }
        if (password.length < 6) {
            errorMessage.innerText = "Mật khẩu phải có ít nhất 6 ký tự.";
            errorMessage.style.display = "block";
            return;
        }
        if (!csrfToken) {
            errorMessage.innerText = "Thiếu CSRF token. Vui lòng tải lại trang.";
            errorMessage.style.display = "block";
            return;
        }

        const btnSubmit = document.getElementById('btnLoginSubmit');
        const btnSpinner = document.getElementById('btnLoginSpinner');
        const btnText = document.getElementById('btnLoginText');
        btnSubmit.disabled = true;
        btnSpinner.classList.add('is-loading');
        btnText.textContent = 'Đang đăng nhập...';

        const data = { email: email, password: password, _token: csrfToken };

        $.ajax({
            url: urlLoginPost || '',
            type: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            data: JSON.stringify(data),
            success: function (response) {
                btnSubmit.disabled = false;
                btnSpinner.classList.remove('is-loading');
                btnText.textContent = 'Đăng nhập';
                if (response.status === "success") {
                    if (response.data && response.data.role === 1) window.location.href = urlAdmin;
                    else window.location.href = urlHome;
                } else {
                    errorMessage.innerText = response.message || '';
                    errorMessage.style.display = "block";
                }
            },
            error: function (xhr) {
                btnSubmit.disabled = false;
                btnSpinner.classList.remove('is-loading');
                btnText.textContent = 'Đăng nhập';
                // 419 = CSRF token hết hạn → tải lại trang để lấy token mới
                if (xhr.status === 419) {
                    window.location.reload();
                    return;
                }
                let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi không xác định.';
                errorMessage.innerText = msg;
                errorMessage.style.display = "block";
            }
        });
    });
})();
