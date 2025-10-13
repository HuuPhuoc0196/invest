@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập nhật thông tin cá nhân')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/user/infoProfile') }}" class="button-link">👤 Thông tin cá nhân</a>
@endsection

@section('user-body-content')
    <h2>Cập nhật thông tin cá nhân</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="name">Tên:</label>
            <input type="text" id="name">
            <div class="error" id="errorName">Vui lòng nhập tên của bạn</div>
            <div class="error" id="errorNameLength">Tên phải có ít nhất 2 ký tự.</div>
        </div>

        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Cập nhật</button>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const user = @json($user);
            document.getElementById("name").value = user.name|| "";
        });
        const baseUrl = "{{ url('') }}";
       
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

        function submitForm() {
            document.getElementById("errorName").style.display = "none";
            document.getElementById("errorNameLength").style.display = "none";

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
           
            const name = document.getElementById("name").value.trim();

            let isValid = true;

            if (!name) {
                document.getElementById("errorName").style.display = "block";
                isValid = false;
            } else if (name.length < 2) {
                document.getElementById("errorNameLength").style.display = "block";
                isValid = false;
            }
            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    name: name
                };
                $.ajax({
                    url: baseUrl + '/user/updateInfoProfile/',
                    type: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.status == "success") {
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