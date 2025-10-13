@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Bán Cổ Phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/user/profile') }}" class="button-link">💼 Tài sản</a>
@endsection

@section('user-body-content')
    <h2>Bán Cổ Phiếu</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã Cổ Phiếu:</label>
            <select id="code">
                <option value="">-- Chọn mã cổ phiếu --</option>
            </select>
            <div class="error" id="errorCode">Vui lòng chọn Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="sellPrice">Giá bán:</label>
            <input type="text" id="sellPrice" placeholder="VD: 100000">
            <div class="error" id="errorSell">Vui lòng nhập Giá bán</div>
            <div class="error" id="errorSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="quantity">Khối lượng bán:</label>
            <input type="text" id="quantity" placeholder="VD: 5000">
            <div class="error" id="errorQuantity">Vui lòng nhập Khối lượng bán</div>
            <div class="error" id="errorQuantityType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="sellDate">Ngày bán:</label>
            <input type="date" id="sellDate">
            <div class="error" id="errorSellDate">Vui lòng nhập Ngày bán</div>
            <div class="error" id="errorSellDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Bán</button>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const userPortfolios = @json($userPortfolios);
        const formatter = new Intl.NumberFormat('vi-VN');
        const sellPriceInput = document.getElementById("sellPrice");
        const quantityInput = document.getElementById("quantity");
        const sellDateInput = document.getElementById('sellDate');

        document.addEventListener("DOMContentLoaded", function () {
            const select = document.getElementById("code");
            const quantityInput = document.getElementById("quantity");

            // Đổ option cho select
            userPortfolios.forEach(p => {
                const option = document.createElement("option");
                option.value = p.code;
                option.textContent = p.code;
                select.appendChild(option);
            });

            // Khi chọn mã thì tự động cập nhật quantity
            select.addEventListener("change", function () {
                const selectedCode = this.value;
                const selectedPortfolio = userPortfolios.find(p => p.code === selectedCode);
                if (selectedPortfolio) {
                    quantityInput.value = selectedPortfolio.total_quantity;
                    formatToVND(quantityInput);
                } else {
                    quantityInput.value = "";
                }
            });
        });


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

        sellPriceInput.addEventListener("input", () => {
            formatToVND(sellPriceInput);
        });

        quantityInput.addEventListener("input", () => {
            formatToVND(quantityInput);
        });

        function resetForm() {
            document.getElementById("code").value = "";
            sellPriceInput.value = "";
            quantityInput.value = "";
            sellDateInput.value = "";
        }

        function sellStocksOnForm(code, quantity) {
            const stock = userPortfolios.find(item => item.code === code);
            if (!stock) {
                return false; // không tìm thấy
            }
            if (quantity > stock.total_quantity) {
                return false; // vượt quá số lượng hiện có
            }
            stock.total_quantity -= quantity;
            return true;
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
            const sell = parseNumber(sellPriceInput.value);
            const quantity = parseNumber(quantityInput.value);
            const sellDate = sellDateInput.value.trim();
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Kiểm tra định dạng ngày (DD-MM-YYYY)
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

            let isValid = true;

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            // Validate mã CK
            if (!code) {
                document.getElementById("errorCode").style.display = "block";
                isValid = false;
            }

            // Validate Giá mua
            if (!sell) {
                document.getElementById("errorSell").style.display = "block";
                isValid = false;
            } else if (!isNumber(sell)) {
                document.getElementById("errorSellType").style.display = "block";
                isValid = false;
            }

            // Validate khối lượng giao dịch
            if (!quantity) {
                document.getElementById("errorQuantity").style.display = "block";
                isValid = false;
            } else if (!isNumber(quantity)) {
                document.getElementById("errorQuantityType").style.display = "block";
                isValid = false;
            }

            // validation date sell
            if (sellDate === '') {
                document.getElementById('errorSellDate').style.display = 'block';
                isValid = false;
            }else if(!dateRegex.test(sellDate)) {
                document.getElementById('errorSellDateType').style.display = 'block';
                isValid = false;
            }else if (isValid && isNaN(new Date(sellDate).getTime())) {
                document.getElementById('errorSellDateType').style.display = 'block';
                isValid = false;
            }

            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    code: code,
                    sell_price: sell,
                    quantity: quantity,
                    sell_date : sellDate
                };
                $.ajax({
                    url: baseUrl + '/user/sell',
                    type: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.status == "success") {
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `✅ Đã bán thành công mã <b>${code}</b><br>`;
                            toast.className = "toast show";
                            toastSuccess();
                            setTimeout(() => {
                                toast.className = toast.className.replace("show", "");
                            }, 3000);

                            // Reset form
                            resetForm();
                            
                            sellStocksOnForm(code, quantity);
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