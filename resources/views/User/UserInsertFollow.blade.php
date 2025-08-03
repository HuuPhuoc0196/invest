<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Invest</title>
    @vite('resources/js/app.js')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

</head>

<body class="antialiased">
    <div class="actions">
        <div class="actions-left">
            <a href="{{ url('/user/follow') }}" class="button-link">🔔 Theo dõi</a>
        </div>
    </div>

    <h2>Thêm cổ phiếu theo dõi</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã Cổ Phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT">
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Thêm</button>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const baseUrl = "{{ url('') }}";

    function resetForm() {
        document.getElementById("code").value = "";
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
        const code = document.getElementById("code").value.trim().toUpperCase();
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let isValid = true;

        document.querySelectorAll(".error").forEach(el => el.style.display = "none");

        // Validate mã CK
        if (!code) {
            document.getElementById("errorCode").style.display = "block";
            isValid = false;
        }
        // Nếu hợp lệ
        if (isValid) {
            // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
            const data = {
                code: code
            };
            $.ajax({
                url: baseUrl + '/user/insertFollow',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.status == "success") {
                        const toast = document.getElementById("toast");
                        toast.innerHTML = `✅ Đã thêm thành công mã <b>${code}</b><br>`;
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

</html>