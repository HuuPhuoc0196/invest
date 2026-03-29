@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Nạp tiền vào tài khoản')

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
    @include('partials.page-title-invest', ['title' => 'Nạp tiền vào tài khoản'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">

        <div class="form-group form-group-cash-row">
            <label class="cash-title">Số dư: <span class="cash"></span></label>
        </div>

        <div class="form-group">
            <label for="cashIn">Số tiền cần nạp:</label>
            <input type="text" id="cashIn" placeholder="VD: 1.000.000">
            <div class="error" id="errorCashInEmpty">Vui lòng nhập số tiền nạp</div>
            <div class="error" id="errorCashInType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="cashDate">Ngày nạp:</label>
            <input type="date" id="cashDate">
            <div class="error" id="errorCashDate">Vui lòng nhập Ngày nạp</div>
            <div class="error" id="errorCashDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Nạp tiền</button>
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
        const cashInInput = document.getElementById("cashIn");
        const cashDateInput = document.getElementById('cashDate');
        const btnFormSubmit = document.getElementById('btnFormSubmit');
        const toastEl = document.getElementById('toast');
        var cash = @json($cash);
        $(".cash").text(formatter.format(cash));

        function getTodayYmd() {
            const d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function setDefaultCashDate() {
            cashDateInput.value = getTodayYmd();
        }

        setDefaultCashDate();

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

        function canSubmitCashInForm() {
            const cashIn = parseNumber(cashInInput.value);
            const cashDate = cashDateInput.value.trim();
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!cashIn || !isNumber(cashIn)) return false;
            if (cashDate === '' || !dateRegex.test(cashDate) || isNaN(new Date(cashDate).getTime())) return false;
            return true;
        }

        function updateCashInSubmitButton() {
            if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitCashInForm();
        }

        cashInInput.addEventListener("input", () => {
            formatToVND(cashInInput);
            updateCashInSubmitButton();
        });

        cashDateInput.addEventListener("change", updateCashInSubmitButton);
        cashDateInput.addEventListener("input", updateCashInSubmitButton);
        updateCashInSubmitButton();

        function resetForm() {
            cashInInput.value = "";
            setDefaultCashDate();
            updateCashInSubmitButton();
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
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const cashDate = cashDateInput.value.trim();
            const cashIn = parseNumber(cashInInput.value);
            let isValid = true;

            // Kiểm tra định dạng ngày (DD-MM-YYYY)
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            // Validate Giá follow
            if (cashIn) {
                if (!isNumber(cashIn)) {
                    document.getElementById("errorCashInType").style.display = "block";
                    isValid = false;
                }
            }

            // validation date buy
            if (cashDate === '') {
                document.getElementById('errorCashDate').style.display = 'block';
                isValid = false;
            } else if (!dateRegex.test(cashDate)) {
                document.getElementById('errorCashDateType').style.display = 'block';
                isValid = false;
            } else if (isValid && isNaN(new Date(cashDate).getTime())) {
                document.getElementById('errorCashDateType').style.display = 'block';
                isValid = false;
            }

            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    cashIn: cashIn,
                    cashDate: cashDate
                };
                $.ajax({
                    url: baseUrl + '/user/cashIn',
                    type: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function (response) {
                        if (response.status == "success") {
                            toastEl.innerHTML = `✅ Đã nạp thành công số tiền: <b>${cashInInput.value}</b><br>`;
                            toastEl.className = "toast show";
                            let num1 = parseFloat(cash);
                            let num2 = parseFloat(cashInInput.value.replace(/\./g, '').replace(/,/g, ''));
                            cash = num1 + num2;
                            $(".cash").text(formatter.format(cash));
                            updateCashInSubmitButton();

                            toastSuccess();
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