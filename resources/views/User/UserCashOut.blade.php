@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Rut tiền')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
@endsection

@section('user-body-content')
    <h2>Rút tiền</h2>

    <div class="form-container">

        <div class="form-group">
            <label class="cash-title">Số dư: <span class="cash"></span></label>
        </div>

        <div class="form-group">
            <label for="cashOut">Số tiền cần rút:</label>
            <input type="text" id="cashOut" placeholder="VD: 1.000.000">
            <div class="error" id="errorCashOutType">Vui lòng nhập Số</div>
            <div class="error" id="errorCashOutPriceType">Số dư không đủ</div>
        </div>

        <div class="form-group">
            <label for="cashDate">Ngày rút:</label>
            <input type="date" id="cashDate">
            <div class="error" id="errorCashDate">Vui lòng nhập Ngày nạp</div>
            <div class="error" id="errorCashDateType">Vui lòng nhập ngày hợp lệ</div>
        </div>

        <div id="toast" class="toast"></div>

        <button onclick="submitForm()">Rút tiền</button>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const formatter = new Intl.NumberFormat('vi-VN');
        const cashOutInput = document.getElementById("cashOut");
        const cashDateInput = document.getElementById('cashDate');
        var cash = @json($cash);
        let cashMony = formatter.format(cash);
        $(".cash").text(cashMony);
        $('#cashOut').attr('placeholder', cashMony);

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

        cashOutInput.addEventListener("input", () => {
            formatToVND(cashOutInput);
        });

        function resetForm() {
            cashOutInput.value = "";
            cashDateInput.value = "";
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
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const cashDate = cashDateInput.value.trim();
            const cashOut = parseNumber(cashOutInput.value);
            let isValid = true;

            // Kiểm tra định dạng ngày (DD-MM-YYYY)
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            // Validate Giá follow
            if (!isNumber(cashOut)) {
                document.getElementById("errorCashOutType").style.display = "block";
                isValid = false;
            }

            if ((Number(cashOut) > Number(cash))) {
                document.getElementById("errorCashOutPriceType").style.display = "block";
                isValid = false;
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
                    cashOut: cashOut,
                    cashDate: cashDate
                };
                $.ajax({
                    url: baseUrl + '/user/cashOut',
                    type: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function (response) {
                        if (response.status == "success") {
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `✅ Đã rút thành công số tiền: <b>${cashOutInput.value}</b><br>`;
                            toast.className = "toast show";
                            let num1 = parseFloat(cash);
                            let num2 = parseFloat(cashOutInput.value.replace(/\./g, '').replace(/,/g, ''));
                            cash = num1 - num2;
                            cashMony = formatter.format(cash);
                            $(".cash").text(cashMony);
                            $('#cashOut').attr('placeholder', cashMony);
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
                    error: function (xhr) {
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