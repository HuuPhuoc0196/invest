@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập nhật cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
    <style>
        .form-group-auto-sync {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group-auto-sync label {
            margin-bottom: 0;
            flex-shrink: 0;
        }
        .auto-sync-toggle {
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.2s ease, filter 0.15s ease;
        }
    </style>
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Cập nhật cổ phiếu theo dõi'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã cổ phiếu:</label>
            <input type="text" id="code" placeholder="VD: FPT" disabled>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="followPriceBuy">Giá mua theo dõi:</label>
            <input type="text" id="followPriceBuy" placeholder="VD: 100000">
             <div class="error" id="errorFollowPriceBuy">Vui lòng nhập Giá mua</div>
            <div class="error" id="errorFollowPriceBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="followPriceSell">Giá bán theo dõi:</label>
            <input type="text" id="followPriceSell" placeholder="VD: 150000">
            <div class="error" id="errorFollowPriceSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group form-group-auto-sync">
            <label>Tự động đồng bộ:</label>
            <input type="hidden" id="autoSync" name="autoSync" value="{{ (int) data_get($userFollow, 'auto_sync', 1) }}">
            <button type="button" id="autoSyncToggle" class="auto-sync-toggle auto-sync-on" aria-pressed="true">Bật</button>
        </div>

        <div id="toast" class="toast"></div>

        <button type="button" id="btnFormSubmit" onclick="submitForm()" disabled>Cập nhật</button>
    </div>
        </div>
    </div>
@endsection

@section('user-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const btnFormSubmit = document.getElementById('btnFormSubmit');

        function canSubmitUpdateFollowForm() {
            const code = document.getElementById("code").value.trim().toUpperCase();
            const followPriceBuy = parseNumber(document.getElementById("followPriceBuy").value);
            const followPriceSell = parseNumber(document.getElementById("followPriceSell").value);
            if (!code) return false;
            if (!followPriceBuy || !isNumber(followPriceBuy)) return false;
            if (followPriceSell && !isNumber(followPriceSell)) return false;
            return true;
        }

        function updateUpdateFollowSubmitButton() {
            if (btnFormSubmit) btnFormSubmit.disabled = !canSubmitUpdateFollowForm();
        }

        document.addEventListener("DOMContentLoaded", function () {
            const userFollow = @json($userFollow);
            document.getElementById("code").value = userFollow.code || "";
            document.getElementById("followPriceBuy").value = userFollow.follow_price_buy ? Number(userFollow.follow_price_buy).toLocaleString('vi-VN') : '';
            document.getElementById("followPriceSell").value = userFollow.follow_price_sell ? Number(userFollow.follow_price_sell).toLocaleString('vi-VN') : '';

            const autoSync = userFollow.auto_sync !== undefined ? parseInt(userFollow.auto_sync, 10) : 1;
            const autoSyncInput = document.getElementById("autoSync");
            const autoSyncToggle = document.getElementById("autoSyncToggle");
            autoSyncInput.value = autoSync;
            if (autoSync === 1) {
                autoSyncToggle.textContent = "Bật";
                autoSyncToggle.classList.remove("auto-sync-off");
                autoSyncToggle.classList.add("auto-sync-on");
                autoSyncToggle.setAttribute("aria-pressed", "true");
            } else {
                autoSyncToggle.textContent = "Tắt";
                autoSyncToggle.classList.remove("auto-sync-on");
                autoSyncToggle.classList.add("auto-sync-off");
                autoSyncToggle.setAttribute("aria-pressed", "false");
            }
            autoSyncToggle.addEventListener("click", function () {
                const current = parseInt(autoSyncInput.value, 10);
                const next = current === 1 ? 0 : 1;
                autoSyncInput.value = next;
                if (next === 1) {
                    autoSyncToggle.textContent = "Bật";
                    autoSyncToggle.classList.remove("auto-sync-off");
                    autoSyncToggle.classList.add("auto-sync-on");
                    autoSyncToggle.setAttribute("aria-pressed", "true");
                } else {
                    autoSyncToggle.textContent = "Tắt";
                    autoSyncToggle.classList.remove("auto-sync-on");
                    autoSyncToggle.classList.add("auto-sync-off");
                    autoSyncToggle.setAttribute("aria-pressed", "false");
                }
            });
            updateUpdateFollowSubmitButton();
        });
        const baseUrl = "{{ url('') }}";
        const formatter = new Intl.NumberFormat('vi-VN');
        const followPriceBuyInput = document.getElementById("followPriceBuy");
        const followPriceSellInput = document.getElementById("followPriceSell");

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

        followPriceBuyInput.addEventListener("input", () => {
            formatToVND(followPriceBuyInput);
            updateUpdateFollowSubmitButton();
        });

        followPriceSellInput.addEventListener("input", () => {
            formatToVND(followPriceSellInput);
            updateUpdateFollowSubmitButton();
        });

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
           
            const code = document.getElementById("code").value.trim().toUpperCase();
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
            if (!followPriceBuy) {
                document.getElementById("errorFollowPriceBuy").style.display = "block";
                isValid = false;
            } else if (!isNumber(followPriceBuy)) {
                document.getElementById("errorFollowPriceBuyType").style.display = "block";
                isValid = false;
            }

            // Validate Giá bán follow
            if (followPriceSell && !isNumber(followPriceSell)) {
                document.getElementById("errorFollowPriceSellType").style.display = "block";
                isValid = false;
            }

            // Nếu hợp lệ
            if (isValid) {
                // Gửi AJAX đến server hoặc lưu vào DB ở đây nếu cần
                const data = {
                    code: code,
                    followPriceBuy: followPriceBuy,
                    followPriceSell: followPriceSell || null,
                    autoSync: parseInt(document.getElementById("autoSync").value, 10)
                };
                $.ajax({
                    url: baseUrl + '/user/updateFollow/' + code,
                    type: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.status == "success") {
                            const toast = document.getElementById("toast");
                            toast.innerHTML = `✅ Đã cập nhật thành công mã <b>${code}</b><br>`;
                            toast.className = "toast show";
                            toastSuccess();
                            setTimeout(() => {
                                window.location.href = baseUrl + '/user/follow';
                            }, 800);
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