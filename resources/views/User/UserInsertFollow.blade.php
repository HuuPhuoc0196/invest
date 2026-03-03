@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Thêm cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <a href="{{ url('/user/follow') }}" class="button-link">🔔 Theo dõi</a>
@endsection

@section('user-body-content')
    <h2>Thêm cổ phiếu theo dõi</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã Cổ Phiếu:</label>
            <div style="display: flex; gap: 8px; align-items: center;">
                <input type="text" id="code" placeholder="VD: FPT" style="flex: 1; min-width: 0;">
                <button type="button" id="btnCheckCode" onclick="checkStockCode()" disabled
                    style="width: auto; white-space: nowrap; padding: 6px 12px; border: none; border-radius: 5px; cursor: not-allowed; background: #ccc; color: #666; font-size: 13px; transition: all 0.3s; flex-shrink: 0;">
                    🔍 Kiểm tra
                </button>
            </div>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="followPrice">Giá theo dõi:</label>
            <input type="text" id="followPrice" placeholder="VD: 100000">
            <div class="error" id="errorFollowPriceType">Vui lòng nhập Số</div>
        </div>

        <div id="toast" class="toast"></div>

        <button id="btnSubmit" onclick="submitForm()" disabled style="opacity: 0.5; cursor: not-allowed;">Thêm</button>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const formatter = new Intl.NumberFormat('vi-VN');
        const followPriceInput = document.getElementById("followPrice");
        const codeInput = document.getElementById("code");
        const btnCheckCode = document.getElementById("btnCheckCode");

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

        followPriceInput.addEventListener("input", () => {
            formatToVND(followPriceInput);
        });

        // Toggle check button & submit button based on code input
        const btnSubmit = document.getElementById("btnSubmit");
        codeInput.addEventListener("input", function() {
            const hasValue = this.value.trim().length > 0;
            btnCheckCode.disabled = !hasValue;
            btnSubmit.disabled = !hasValue;
            if (hasValue) {
                btnCheckCode.style.background = '#3498db';
                btnCheckCode.style.color = '#fff';
                btnCheckCode.style.cursor = 'pointer';
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            } else {
                btnCheckCode.style.background = '#ccc';
                btnCheckCode.style.color = '#666';
                btnCheckCode.style.cursor = 'not-allowed';
                btnSubmit.style.opacity = '0.5';
                btnSubmit.style.cursor = 'not-allowed';
            }
        });

        function checkStockCode() {
            const code = codeInput.value.trim().toUpperCase();
            if (!code) return;

            btnCheckCode.disabled = true;
            btnCheckCode.innerHTML = '⏳ Đang kiểm tra...';

            $.ajax({
                url: baseUrl + '/user/checkStockCode/' + code,
                type: 'GET',
                success: function(response) {
                    const toast = document.getElementById("toast");
                    if (response.status === 'success') {
                        toast.innerHTML = `✅ ${response.message}`;
                        toast.className = 'toast show';
                        toastSuccess();

                        // Auto-fill Giá theo dõi = Giá mua tốt (recommended_buy_price)
                        if (response.data && response.data.recommended_buy_price) {
                            followPriceInput.value = formatter.format(response.data.recommended_buy_price);
                        }
                    } else if (response.status === 'warning') {
                        toast.innerHTML = `⚠️ ${response.message}`;
                        toast.className = 'toast show';
                        toastError();

                        // Vẫn auto-fill giá để user tham khảo
                        if (response.data && response.data.recommended_buy_price) {
                            followPriceInput.value = formatter.format(response.data.recommended_buy_price);
                        }
                    } else {
                        toast.innerHTML = `❌ ${response.message}`;
                        toast.className = 'toast show';
                        toastError();
                        followPriceInput.value = '';
                    }
                    setTimeout(() => {
                        toast.className = toast.className.replace('show', '');
                    }, 4000);
                },
                error: function(xhr) {
                    const toast = document.getElementById("toast");
                    toast.innerHTML = '❌ Lỗi kết nối, vui lòng thử lại.';
                    toast.className = 'toast show';
                    toastError();
                    setTimeout(() => {
                        toast.className = toast.className.replace('show', '');
                    }, 5000);
                },
                complete: function() {
                    btnCheckCode.disabled = false;
                    btnCheckCode.innerHTML = '🔍 Kiểm tra';
                    const hasValue = codeInput.value.trim().length > 0;
                    if (hasValue) {
                        btnCheckCode.style.background = '#3498db';
                        btnCheckCode.style.color = '#fff';
                        btnCheckCode.style.cursor = 'pointer';
                    }
                }
            });
        }

        function resetForm() {
            codeInput.value = "";
            followPriceInput.value = "";
            btnCheckCode.disabled = true;
            btnCheckCode.style.background = '#ccc';
            btnCheckCode.style.color = '#666';
            btnCheckCode.style.cursor = 'not-allowed';
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
        }

        function toastSuccess() {
            const toast = document.getElementById("toast");
            toast.classList.remove("toast-success", "toast-error");
            toast.classList.add("toast-success");
            toast.classList.add("toast", "show");
        }

        function toastError() {
            const toast = document.getElementById("toast");
            toast.classList.remove("toast-success", "toast-error");
            toast.classList.add("toast-error");
            toast.classList.add("toast", "show");
        }

        function submitForm() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const code = codeInput.value.trim().toUpperCase();
            const followPrice = parseNumber(followPriceInput.value);
            let isValid = true;

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            // Validate mã CK
            if (!code) {
                document.getElementById("errorCode").style.display = "block";
                isValid = false;
            }

            // Validate Giá follow
            if (followPrice) {
                if (!isNumber(followPrice)) {
                    document.getElementById("errorFollowPriceType").style.display = "block";
                    isValid = false;
                }
            }

            // Nếu hợp lệ
            if (isValid) {
                const data = {
                    code: code,
                    followPrice: followPrice
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
                            resetForm();
                        } else {
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `❌ ` + response.message;
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