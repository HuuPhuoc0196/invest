@extends('Layout.LayoutAdmin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thêm Mã Cổ Phiếu')

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
    <h2>Thêm Mã Cổ Phiếu</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã Cổ Phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT">
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua tốt:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100000">
            <div class="error" id="errorBuy">Vui lòng nhập Giá mua tốt</div>
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="currentPrice">Giá hiện tại:</label>
            <input type="text" id="currentPrice" placeholder="VD: 120000">
            <div class="error" id="errorCurrent">Vui lòng nhập Giá hiện tại</div>
            <div class="error" id="errorCurrentType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="risk">Trạng thái:</label>
            <select id="risk">
                <option value="1">An toàn</option>
                <option value="2">Cảnh báo</option>
                <option value="3">Hạn chế GD</option>
                <option value="4">Đình chỉ/Huỷ</option>
            </select>
        </div>
        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Thêm mới</button>
    </div>
@endsection

@section('admin-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const formatter = new Intl.NumberFormat('vi-VN');
        const buyPriceInput = document.getElementById("buyPrice");
        const currentPriceInput = document.getElementById("currentPrice");

        function isNumber(value) {
            return !isNaN(value) && value.trim() !== '';
        }

        function parseNumber(str) {
            return str.replace(/[^\d]/g, "");
        }

        function formatToVND(input) {
            let raw = parseNumber(input.value);
            if (raw === "") return input.value = "";

            let formatted = formatter.format(raw);
            input.value = formatted;
        }

        buyPriceInput.addEventListener("input", () => {
            formatToVND(buyPriceInput);
        });

        currentPriceInput.addEventListener("input", () => {
            formatToVND(currentPriceInput);
        });

        function resetForm() {
            document.getElementById("code").value = "";
            buyPriceInput.value = "";
            currentPriceInput.value = "";
            document.getElementById("risk").value = "1";
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
            const buy = parseNumber(buyPriceInput.value);
            const current = parseNumber(currentPriceInput.value);
            const risk = document.getElementById("risk").value;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            let isValid = true;

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            // Validate mã CK
            if (!code) {
                document.getElementById("errorCode").style.display = "block";
                isValid = false;
            }

            // Validate Giá mua
            if (!buy) {
                document.getElementById("errorBuy").style.display = "block";
                isValid = false;
            } else if (!isNumber(buy)) {
                document.getElementById("errorBuyType").style.display = "block";
                isValid = false;
            }

            // Validate Giá hiện tại
            if (!current) {
                document.getElementById("errorCurrent").style.display = "block";
                isValid = false;
            } else if (!isNumber(current)) {
                document.getElementById("errorCurrentType").style.display = "block";
                isValid = false;
            }
            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    code: code,
                    buyPrice: buy,
                    currentPrice: current,
                    risk: risk
                };
                $.ajax({
                    url: baseUrl + '/admin/insert',
                    type: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.status == "success") {
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `✅ Đã thêm mã <b>${code}</b><br>`;
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
@endsection