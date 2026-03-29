@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thay đổi mật khẩu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Thay đổi mật khẩu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="password">Mật khẩu:</label>
            <input type="password" id="password">
            <div class="error" id="errorPassword">Vui lòng nhập mật khẩu</div>
            <div class="error" id="errorPasswordLength">Mật khẩu phải có ít nhất 6 ký tự.</div>
        </div>

        <div class="form-group">
            <label for="newPassword">Mật khẩu mới:</label>
            <input type="password" id="newPassword">
            <div class="error" id="errorNewPassword">Vui lòng nhập mật khẩu</div>
            <div class="error" id="errorNewPasswordLength">Mật khẩu phải có ít nhất 6 ký tự.</div>
        </div>

        <div class="form-group">
            <label for="reNewPassword">Nhập lại mật khẩu mới:</label>
            <input type="password" id="reNewPassword">
            <div class="error" id="errorReNewPassword">Vui lòng nhập mật khẩu</div>
            <div class="error" id="errorReNewPasswordRe">Nhập lại mật khẩu không đúng</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Cập nhật</button>
    </div>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const btnFormSubmit = document.getElementById('btnFormSubmit');

        function canSubmitChangePasswordForm() {
            const password = document.getElementById("password").value.trim();
            const newPassword = document.getElementById("newPassword").value.trim();
            const reNewPassword = document.getElementById("reNewPassword").value.trim();
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
            // Xóa class cũ trước khi thêm class mới
            toast.classList.remove("toast-success", "toast-error");
            toast.classList.add("toast-success");
            toast.classList.add("toast", "show");
        }

        function toastError() {
            // Xóa class cũ trước khi thêm class mới
            toast.classList.remove("toast-success", "toast-error");
            toast.classList.add("toast-error");
            toast.classList.add("toast", "show");
        }

        function removeError(){
            document.getElementById("errorPassword").style.display = "none";
            document.getElementById("errorPasswordLength").style.display = "none";
            document.getElementById("errorNewPassword").style.display = "none";
            document.getElementById("errorNewPasswordLength").style.display = "none";
            document.getElementById("errorReNewPassword").style.display = "none";
            document.getElementById("errorReNewPasswordRe").style.display = "none";
        }

        function removeValue(){
            document.getElementById("password").value = "";
            document.getElementById("newPassword").value = "";
            document.getElementById("reNewPassword").value = "";
            updateChangePasswordSubmitButton();
        }

        function submitForm() {
            removeError();

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
           
            const password = document.getElementById("password").value.trim();
            const newPassword = document.getElementById("newPassword").value.trim();
            const reNewPassword = document.getElementById("reNewPassword").value.trim();

            let isValid = true;

            if (!password) {
                document.getElementById("errorPassword").style.display = "block";
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById("errorPasswordLength").style.display = "block";
                isValid = false;
            }

            if (!newPassword) {
                document.getElementById("errorNewPassword").style.display = "block";
                isValid = false;
            } else if (newPassword.length < 6) {
                document.getElementById("errorNewPasswordLength").style.display = "block";
                isValid = false;
            }

            if (!reNewPassword) {
                document.getElementById("errorReNewPassword").style.display = "block";
                isValid = false;
            } else if (reNewPassword !== newPassword) {
                document.getElementById("errorReNewPasswordRe").style.display = "block";
                isValid = false;
            }


            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    password: password,
                    newPassword: newPassword
                };
                $.ajax({
                    url: baseUrl + '/user/changePassword/',
                    type: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.status == "success") {
                            removeValue();
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `✅ Đã cập nhật thành công <br>`;
                            toast.className = "toast show";
                            toastSuccess();
                            setTimeout(() => {
                                toast.className = toast.className.replace("show", "");
                            }, 3000);

                        } else {
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `❌` + response.message;
                            toast.className = "toast show";
                            toastError();
                            setTimeout(() => {
                                toast.className = toast.className.replace("show", "");
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        const toast = document.getElementById("toast");
                        toast.innerHTML = '❌ Lỗi: ' + xhr.responseJSON.message;
                        toast.className = "toast show";
                        toastError();
                        setTimeout(() => {
                            toast.className = toast.className.replace("show", "");
                        }, 5000);
                    }
                });
            }
        }
    </script>
@endsection