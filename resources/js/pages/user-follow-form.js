// Moved from UserUpdateFollow.blade.php <script> block
(function () {
    const { baseUrl, userFollow } = window.__pageData || {};

    const btnFormSubmit = document.getElementById('btnFormSubmit');

    // ── Modal helpers ──────────────────────────────────────────────
    const modal      = document.getElementById('update-follow-notify-modal');
    const modalIcon  = document.getElementById('updateFollowNotifyIcon');
    const modalMsg   = document.getElementById('updateFollowNotifyMsg');
    const modalClose = document.getElementById('updateFollowNotifyClose');

    function showModal(type, icon, message) {
        modal.classList.remove('home-notify-modal--success', 'home-notify-modal--error');
        modal.classList.add(type === 'success' ? 'home-notify-modal--success' : 'home-notify-modal--error');
        modalIcon.textContent = icon;
        modalMsg.innerHTML = message;
        modal.setAttribute('aria-hidden', 'false');
        setTimeout(() => modalClose.focus(), 50);
    }

    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        if (btnFormSubmit) btnFormSubmit.focus();
    }

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    modal.querySelector('.home-notify-modal__backdrop').addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeModal();
        }
    });
    // ───────────────────────────────────────────────────────────────

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

    function toastSuccess() {}
    function toastError() {}

    function submitForm() {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const code = document.getElementById("code").value.trim().toUpperCase();
        const followPriceBuy = parseNumber(followPriceBuyInput.value);
        const followPriceSell = parseNumber(followPriceSellInput.value);
        let isValid = true;

        document.querySelectorAll(".error").forEach(el => el.style.display = "none");

        if (!code) {
            document.getElementById("errorCode").style.display = "block";
            isValid = false;
        }

        if (!followPriceBuy) {
            document.getElementById("errorFollowPriceBuy").style.display = "block";
            isValid = false;
        } else if (!isNumber(followPriceBuy)) {
            document.getElementById("errorFollowPriceBuyType").style.display = "block";
            isValid = false;
        }

        if (followPriceSell && !isNumber(followPriceSell)) {
            document.getElementById("errorFollowPriceSellType").style.display = "block";
            isValid = false;
        }

        if (isValid) {
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
                success: function (response) {
                    if (response.status == "success") {
                        showModal('success', '✅', `Đã cập nhật thành công mã <b>${code}</b>`);
                        modalClose.addEventListener('click', function onClose() {
                            window.location.href = baseUrl + '/user/follow';
                            modalClose.removeEventListener('click', onClose);
                        }, { once: true });
                    } else {
                        showModal('error', '❌', response.message || 'Có lỗi xảy ra.');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi kết nối.';
                    showModal('error', '❌', msg);
                }
            });
        }
    }
    window.submitForm = submitForm;
})();
