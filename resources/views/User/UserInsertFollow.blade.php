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
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Thêm cổ phiếu theo dõi'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
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
            <label for="followPriceBuy">Giá mua theo dõi:</label>
            <input type="text" id="followPriceBuy" placeholder="VD: 100000">
            <div class="error" id="errorFollowPriceBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="followPriceSell">Giá bán theo dõi:</label>
            <input type="text" id="followPriceSell" placeholder="VD: 150000">
            <div class="error" id="errorFollowPriceSellType">Vui lòng nhập Số</div>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Thêm</button>
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
        const followPriceBuyInput = document.getElementById("followPriceBuy");
        const followPriceSellInput = document.getElementById("followPriceSell");
        const codeInput = document.getElementById("code");
        const btnCheckCode = document.getElementById("btnCheckCode");
        const btnFormSubmit = document.getElementById("btnFormSubmit");

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

        function canSubmitInsertFollowForm() {
            const code = codeInput.value.trim();
            const fpb = parseNumber(followPriceBuyInput.value);
            const fps = parseNumber(followPriceSellInput.value);
            if (!code) return false;
            if (fpb && !isNumber(fpb)) return false;
            if (fps && !isNumber(fps)) return false;
            return true;
        }

        function updateInsertFollowSubmitButton() {
            const hasCode = codeInput.value.trim().length > 0;
            btnCheckCode.disabled = !hasCode;
            if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitInsertFollowForm();
            if (hasCode) {
                btnCheckCode.style.background = '#3498db';
                btnCheckCode.style.color = '#fff';
                btnCheckCode.style.cursor = 'pointer';
            } else {
                btnCheckCode.style.background = '#ccc';
                btnCheckCode.style.color = '#666';
                btnCheckCode.style.cursor = 'not-allowed';
            }
        }

        followPriceBuyInput.addEventListener("input", () => {
            formatToVND(followPriceBuyInput);
            updateInsertFollowSubmitButton();
        });

        followPriceSellInput.addEventListener("input", () => {
            formatToVND(followPriceSellInput);
            updateInsertFollowSubmitButton();
        });

        codeInput.addEventListener("input", updateInsertFollowSubmitButton);
        updateInsertFollowSubmitButton();

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

                        // Auto-fill Giá mua theo dõi = Giá mua tốt (recommended_buy_price)
                        if (response.data && response.data.recommended_buy_price) {
                            followPriceBuyInput.value = formatter.format(response.data.recommended_buy_price);
                        }
                        // Auto-fill Giá bán theo dõi = Giá bán tốt (recommended_sell_price)
                        if (response.data && response.data.recommended_sell_price) {
                            followPriceSellInput.value = formatter.format(response.data.recommended_sell_price);
                        }
                        updateInsertFollowSubmitButton();
                    } else if (response.status === 'warning') {
                        toast.innerHTML = `⚠️ ${response.message}`;
                        toast.className = 'toast show';
                        toastError();

                        // Vẫn auto-fill giá để user tham khảo
                        if (response.data && response.data.recommended_buy_price) {
                            followPriceBuyInput.value = formatter.format(response.data.recommended_buy_price);
                        }
                        if (response.data && response.data.recommended_sell_price) {
                            followPriceSellInput.value = formatter.format(response.data.recommended_sell_price);
                        }
                    } else {
                        const raw = response.message || 'Mã cổ phiếu không tồn tại trong hệ thống.';
                        const plain = String(raw).replace(/<[^>]*>/g, '');
                        followPriceBuyInput.value = '';
                        followPriceSellInput.value = '';
                        updateInsertFollowSubmitButton();
                        Swal.fire({
                            icon: 'error',
                            title: 'Không tìm thấy mã',
                            text: plain,
                            confirmButtonText: 'Đóng'
                        });
                    }
                    if (response.status === 'success' || response.status === 'warning') {
                        setTimeout(() => {
                            toast.className = toast.className.replace('show', '');
                        }, 4000);
                    }
                },
                error: function(xhr) {
                    let msg = 'Lỗi kết nối, vui lòng thử lại.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = String(xhr.responseJSON.message).replace(/<[^>]*>/g, '');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: msg,
                        confirmButtonText: 'Đóng'
                    });
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
            followPriceBuyInput.value = "";
            followPriceSellInput.value = "";
            updateInsertFollowSubmitButton();
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
            const followPriceBuy = parseNumber(followPriceBuyInput.value);
            const followPriceSell = parseNumber(followPriceSellInput.value);
            let isValid = true;

            document.querySelectorAll(".error").forEach(el => el.style.display = "none");

            // Validate mã CK
            if (!code) {
                document.getElementById("errorCode").style.display = "block";
                isValid = false;
            }

            // Validate Giá mua follow
            if (followPriceBuy) {
                if (!isNumber(followPriceBuy)) {
                    document.getElementById("errorFollowPriceBuyType").style.display = "block";
                    isValid = false;
                }
            }

            // Validate Giá bán follow
            if (followPriceSell) {
                if (!isNumber(followPriceSell)) {
                    document.getElementById("errorFollowPriceSellType").style.display = "block";
                    isValid = false;
                }
            }

            // Nếu hợp lệ
            if (isValid) {
                const data = {
                    code: code,
                    followPriceBuy: followPriceBuy,
                    followPriceSell: followPriceSell
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