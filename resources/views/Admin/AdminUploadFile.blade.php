@extends('Layout.LayoutAdmin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thêm Mã cổ phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/admin') }}" class="button-link">🏠 Trang chủ</a>
@endsection

@section('admin-body-content')
    <h2>Thêm Mã cổ phiếu</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="file">Chọn file .txt:</label>
            <input type="file" id="file" accept=".txt">
            <div class="error" id="errorFile">Vui lòng chọn file .txt</div>
        </div>
        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Upload</button>
    </div>
@endsection

@section('admin-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";

        function resetForm() {
            document.getElementById("file").value = "";
        }

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
            const fileInput = document.getElementById("file");
            const file = fileInput.files[0];
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            if (!file) {
                document.getElementById("errorFile").style.display = "block";
                return;
            }

            if (!file.name.toLowerCase().endsWith('.txt')) {
                document.getElementById("errorFile").style.display = "block";
                document.getElementById("errorFile").innerText = "Chỉ chấp nhận file .txt";
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: baseUrl + '/admin/uploadFile',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response);
                    if (response.status == "success") {
                        const toast = document.getElementById("toast");
                        toast.innerHTML = `✅ File đã được upload thành công<br>`;
                        toast.className = "toast show";
                        toastSuccess();
                        setTimeout(() => {
                            toast.className = toast.className.replace("show", "");
                        }, 3000);

                        // Reset form
                        resetForm();
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
                    toast.innerHTML = '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error');
                    toast.className = "toast show";
                    toastError();
                    setTimeout(() => {
                        toast.className = toast.className.replace("show", "");
                    }, 5000);
                }
            });
        }
    </script>
@endsection