@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Mua Cổ Phiếu')

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
    @include('partials.page-title-invest', ['title' => 'Mua Cổ Phiếu'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group form-group-cash-row">
            <label class="cash-title">Số dư: <span class="cash"></span></label>
        </div>

        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <div class="code-input-with-check">
                <input type="text" id="code" placeholder="VD: FPT" autocomplete="off">
                <button type="button" id="btnCheckCode" class="btn-check-stock-code" disabled>Kiểm tra</button>
            </div>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100000">
            <div class="error" id="errorBuy">Vui lòng nhập Giá mua</div>
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
            <div class="error" id="errorBuyRange">Giá mua không hợp lệ!</div>
        </div>

        <div class="form-group">
            <label for="quantity">Khối lượng giao dịch:</label>
            <input type="text" id="quantity" placeholder="VD: 5000" inputmode="numeric" autocomplete="off">
            <div class="error" id="errorQuantity">Vui lòng nhập Khối lượng giao dịch</div>
            <div class="error" id="errorQuantityType">Vui lòng nhập Số</div>
            <div class="error" id="errorQuantityRange">Khối lượng giao dịch không hợp lệ!</div>

            <div id="totalAmount" style="color: red; font-weight: bold; margin-top: 5px;"></div>
            <div class="error" id="errorCashBuyType" style="color: red; font-weight: bold; margin-top: 5px;">Số dư
                không đủ</div>
        </div>

        <div class="form-group">
            <label for="buyDate">Ngày mua:</label>
            <input type="date" id="buyDate">
            <div class="error" id="errorBuyDate">Vui lòng nhập Ngày mua</div>
            <div class="error" id="errorBuyDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Mua</button>
    </div>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const formatter = new Intl.NumberFormat('vi-VN');
        const buyPriceInput = document.getElementById("buyPrice");
        const quantityInput = document.getElementById("quantity");
        const buyDateInput = document.getElementById('buyDate');
        const codeInput = document.getElementById("code");
        const btnFormSubmit = document.getElementById('btnFormSubmit');
        const btnCheckCode = document.getElementById('btnCheckCode');
        const toastEl = document.getElementById('toast');
        const BUY_PRICE_MAX = {{ json_encode((float) ($buyPriceMax ?? '9999999999999.99')) }};
        {{-- JS Number mất chính xác với PHP_INT_MAX; giới hạn UI theo MAX_SAFE_INTEGER, server vẫn validate đủ --}}
        const QUANTITY_MAX = {{ min((int) ($quantityMax ?? PHP_INT_MAX), 9007199254740991) }};
        var cash = parseFloat(@json($cash));
        if (!Number.isFinite(cash)) cash = 0;
        let cashMony = formatter.format(cash);
        $(".cash").text(cashMony);

        function getTodayYmd() {
            const d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function setDefaultBuyDate() {
            buyDateInput.value = getTodayYmd();
        }

        setDefaultBuyDate();

        function updateCheckCodeButton() {
            if (!btnCheckCode) return;
            const hasCode = codeInput.value.trim().length > 0;
            btnCheckCode.disabled = !hasCode;
        }

        function checkStockCode() {
            const code = codeInput.value.trim().toUpperCase();
            if (!code) return;

            const prevLabel = btnCheckCode.textContent;
            btnCheckCode.disabled = true;
            btnCheckCode.textContent = 'Đang kiểm tra...';

            $.ajax({
                url: baseUrl + '/user/validate-stock/' + encodeURIComponent(code),
                type: 'GET',
                success: function (response) {
                    if (response.status === 'success') {
                        toastEl.innerHTML = `✅ ${response.message}`;
                        toastEl.className = 'toast show';
                        toastSuccess();

                        const d = response.data;
                        if (d && d.current_price != null && String(d.current_price).trim() !== '') {
                            const n = Number(d.current_price);
                            if (!isNaN(n) && n > 0) {
                                buyPriceInput.value = formatter.format(Math.round(n));
                                buyPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                        updateTotalAmount();
                        updateBuySubmitButton();
                        setTimeout(function () {
                            toastEl.className = toastEl.className.replace('show', '');
                        }, 4000);
                    } else {
                        const raw = response.message || 'Mã cổ phiếu không tồn tại trong hệ thống.';
                        const plain = String(raw).replace(/<[^>]*>/g, '');
                        Swal.fire({
                            icon: 'error',
                            title: 'Không tìm thấy mã',
                            text: plain,
                            confirmButtonText: 'Đóng',
                            target: document.body,
                            heightAuto: false
                        });
                    }
                },
                error: function (xhr) {
                    let msg = 'Lỗi kết nối, vui lòng thử lại.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = String(xhr.responseJSON.message).replace(/<[^>]*>/g, '');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: msg,
                        confirmButtonText: 'Đóng',
                        target: document.body,
                        heightAuto: false
                    });
                },
                complete: function () {
                    btnCheckCode.textContent = prevLabel;
                    updateCheckCodeButton();
                }
            });
        }

        btnCheckCode.addEventListener('click', checkStockCode);

        updateBuySubmitButton();
        updateCheckCodeButton();

        function isNumber(value) {
            return !isNaN(value) && String(value).trim() !== '';
        }

        function parseNumber(str) {
            return str.replace(/[^\d]/g, "");
        }

        /** Ngày yyyy-mm-dd hợp lệ theo lịch (tránh lỗi timezone của Date.parse) */
        function isValidCalendarDateYmd(s) {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
            const y = parseInt(s.slice(0, 4), 10);
            const m = parseInt(s.slice(5, 7), 10);
            const d = parseInt(s.slice(8, 10), 10);
            const dt = new Date(y, m - 1, d);
            return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
        }

        function formatToVND(input) {
            let raw = parseNumber(input.value);
            if (raw === "") return input.value = "";

            let formatted = formatter.format(raw);
            input.value = formatted;
        }

        /** Khối lượng: chỉ số nguyên, format nhóm nghìn */
        function formatQuantityInteger(input) {
            const raw = input.value.replace(/\D/g, "");
            if (raw === "") return input.value = "";
            const n = parseInt(raw, 10);
            if (!Number.isFinite(n)) return input.value = "";
            input.value = formatter.format(n);
        }

        function syncBuyRangeErrors() {
            const erBuy = document.getElementById("errorBuyRange");
            const erQty = document.getElementById("errorQuantityRange");
            if (erBuy) erBuy.style.display = "none";
            if (erQty) erQty.style.display = "none";

            const buyStr = parseNumber(buyPriceInput.value);
            const qtyStr = parseNumber(quantityInput.value);
            if (buyStr !== "") {
                const buyN = parseFloat(buyStr);
                if (Number.isFinite(buyN) && buyN > BUY_PRICE_MAX && erBuy) erBuy.style.display = "block";
            }
            if (qtyStr !== "") {
                const qtyN = parseInt(qtyStr, 10);
                if (!Number.isFinite(qtyN) || qtyN < 1 || qtyN > QUANTITY_MAX) {
                    if (erQty) erQty.style.display = "block";
                }
            }
        }

        function canSubmitBuyForm() {
            const code = codeInput.value.trim().toUpperCase();
            const buyStr = parseNumber(buyPriceInput.value);
            const qtyStr = parseNumber(quantityInput.value);
            const buyDate = buyDateInput.value.trim();

            if (!code || !buyStr || !qtyStr) return false;
            if (!isNumber(buyStr) || !isNumber(qtyStr)) return false;

            const buyN = parseFloat(buyStr);
            const qtyN = parseInt(qtyStr, 10);
            if (!Number.isFinite(buyN) || buyN <= 0) return false;
            if (!Number.isFinite(qtyN) || qtyN < 1) return false;
            if (buyN > BUY_PRICE_MAX) return false;
            if (qtyN > QUANTITY_MAX) return false;

            const cashBuy = buyN * qtyN;
            if (!Number.isFinite(cashBuy) || cashBuy > cash) return false;

            if (buyDate === "" || !isValidCalendarDateYmd(buyDate)) return false;

            return true;
        }

        function updateBuySubmitButton() {
            syncBuyRangeErrors();
            if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitBuyForm();
        }

        buyPriceInput.addEventListener("input", () => {
            formatToVND(buyPriceInput);
            updateTotalAmount();
            updateBuySubmitButton();
        });

        quantityInput.addEventListener("input", () => {
            formatQuantityInteger(quantityInput);
            updateTotalAmount();
            updateBuySubmitButton();
        });

        codeInput.addEventListener("input", function () {
            updateBuySubmitButton();
            updateCheckCodeButton();
        });
        buyDateInput.addEventListener("change", updateBuySubmitButton);
        buyDateInput.addEventListener("input", updateBuySubmitButton);

        function updateTotalAmount() {
            const buy = parseFloat(parseNumber(buyPriceInput.value)) || 0;
            const quantity = parseInt(parseNumber(quantityInput.value), 10) || 0;
            const total = buy > 0 && quantity > 0 ? buy * quantity : 0;

            document.getElementById("totalAmount").textContent = total > 0
                ? `Tổng tiền: ${formatter.format(total)} VND`
                : '';
        }

        function resetForm() {
            codeInput.value = "";
            buyPriceInput.value = "";
            quantityInput.value = "";
            setDefaultBuyDate();
            document.getElementById("errorBuyRange").style.display = "none";
            document.getElementById("errorQuantityRange").style.display = "none";
            updateBuySubmitButton();
            updateCheckCodeButton();
        }

        function toastSuccess() {
            toastEl.classList.remove("toast-success", "toast-error");
            toastEl.classList.add("toast-success");
            toastEl.classList.add("toast", "show");
        }

        function toastError() {
            toastEl.classList.remove("toast-success", "toast-error");
            toastEl.classList.add("toast-error");
            toastEl.classList.add("toast", "show");
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
            } else if (parseFloat(buy) > BUY_PRICE_MAX) {
                document.getElementById("errorBuyRange").style.display = "block";
                isValid = false;
            }

            // Validate khối lượng giao dịch
            if (!quantity) {
                document.getElementById("errorQuantity").style.display = "block";
                isValid = false;
            } else if (!isNumber(quantity)) {
                document.getElementById("errorQuantityType").style.display = "block";
                isValid = false;
            } else {
                const qn = parseInt(quantity, 10);
                if (!Number.isFinite(qn) || qn < 1 || qn > QUANTITY_MAX) {
                    document.getElementById("errorQuantityRange").style.display = "block";
                    isValid = false;
                }
            }

            let cashBuy = Number(buy) * Number(quantity);
            if (isValid && cashBuy > cash) {
                document.getElementById("errorCashBuyType").style.display = "block";
                isValid = false;
            }

            // validation date buy
            if (buyDate === '') {
                document.getElementById('errorBuyDate').style.display = 'block';
                isValid = false;
            } else if (!dateRegex.test(buyDate) || !isValidCalendarDateYmd(buyDate)) {
                document.getElementById('errorBuyDateType').style.display = 'block';
                isValid = false;
            }

            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    code: code,
                    buy_price: parseFloat(buy),
                    quantity: parseInt(quantity, 10),
                    buy_date: buyDate
                };
                $.ajax({
                    url: baseUrl + '/user/buy',
                    type: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function (response) {
                        if (response.status == "success") {
                            toastEl.innerHTML = `✅ Đã mua thành công mã <b>${code}</b><br>`;
                            toastEl.className = "toast show";
                            cash = parseFloat(cash) - cashBuy;
                            if (!Number.isFinite(cash)) cash = 0;
                            cashMony = formatter.format(cash);
                            $(".cash").text(cashMony);
                            updateBuySubmitButton();
                            toastSuccess();
                            document.getElementById("totalAmount").textContent = "";
                            setTimeout(() => {
                                toastEl.className = toastEl.className.replace("show", "");
                            }, 3000);

                            // Reset form
                            resetForm();
                        } else {
                            toastEl.innerHTML = `❌` + response.message;
                            toastEl.className = "toast show";
                            toastError();
                            setTimeout(() => {
                                toastEl.className = toastEl.className.replace("show", "");
                            }, 5000);
                        }
                    },
                    error: function (xhr) {
                        console.log(xhr);
                        toastEl.innerHTML = '❌ Lỗi: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi');
                        toastEl.className = "toast show";
                        toastError();
                        setTimeout(() => {
                            toastEl.className = toastEl.className.replace("show", "");
                        }, 5000);
                    }
                });
            }
        }
    </script>
@endsection