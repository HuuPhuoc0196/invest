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
            <a href="{{ url('/user/profile') }}" class="button-link">👤 Thông tin cá nhân</a>
        </div>
    </div>

    <h2>Mua Cổ Phiếu</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã Cổ Phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT">
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100000">
            <div class="error" id="errorBuy">Vui lòng nhập Giá mua</div>
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="quantity">Khối lượng giao dịch:</label>
            <input type="text" id="quantity" placeholder="VD: 5000">
            <div class="error" id="errorQuantity">Vui lòng nhập Khối lượng giao dịch</div>
            <div class="error" id="errorQuantityType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="buyDate">Ngày mua:</label>
            <input type="date" id="buyDate">
            <div class="error" id="errorBuyDate">Vui lòng nhập Ngày mua</div>
            <div class="error" id="errorBuyDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Mua</button>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const baseUrl = "{{ url('') }}";
    const formatter = new Intl.NumberFormat('vi-VN');
    const buyPriceInput = document.getElementById("buyPrice");
    const quantityInput = document.getElementById("quantity");
    const buyDateInput = document.getElementById('buyDate');

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

    quantityInput.addEventListener("input", () => {
        formatToVND(quantityInput);
    });

    function resetForm() {
        document.getElementById("code").value = "";
        buyPriceInput.value = "";
        quantityInput.value = "";
        buyDateInput.value = "";
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
        const quantity = parseNumber(quantityInput.value);
        const buyDate = buyDateInput.value.trim();
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
        if (!buy) {
            document.getElementById("errorBuy").style.display = "block";
            isValid = false;
        } else if (!isNumber(buy)) {
            document.getElementById("errorBuyType").style.display = "block";
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

        // validation date buy
        if (buyDate === '') {
            document.getElementById('errorBuyDate').style.display = 'block';
            isValid = false;
        }else if(!dateRegex.test(buyDate)) {
            document.getElementById('errorBuyDateType').style.display = 'block';
            isValid = false;
        }else if (isValid && isNaN(new Date(buyDate).getTime())) {
            document.getElementById('errorBuyDateType').style.display = 'block';
            isValid = false;
        }

        // Nếu hợp lệ
        if (isValid) {
            // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
            const data = {
                code: code,
                buy_price: buy,
                quantity: quantity,
                buy_date : buyDate
            };
            console.log(data);
            $.ajax({
                url: baseUrl + '/user/buy',
                type: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.status == "success") {
                        const toast = document.getElementById("toast");
                        toast.innerHTML = `✅ Đã mua thành công mã <b>${code}</b><br>`;
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