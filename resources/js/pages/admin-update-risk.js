const { baseUrl } = window.__pageData || {};

function isUpdateRiskFormReady() {
    return document.getElementById('code').value.trim().length > 0;
}

function updateUpdateRiskSubmitButton() {
    const btn = document.getElementById('btnFormSubmit');
    if (btn) btn.disabled = !isUpdateRiskFormReady();
}

document.getElementById('code').addEventListener('input', updateUpdateRiskSubmitButton);

function resetForm() { document.getElementById('code').value = ''; updateUpdateRiskSubmitButton(); }

function toastSuccess() {
    const toast = document.getElementById('toast');
    toast.classList.remove('toast-success', 'toast-error');
    toast.classList.add('toast-success', 'toast', 'show');
}

function toastError() {
    const toast = document.getElementById('toast');
    toast.classList.remove('toast-success', 'toast-error');
    toast.classList.add('toast-error', 'toast', 'show');
}

function submitForm() {
    const code = document.getElementById('code').value.trim().toUpperCase();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let isValid = true;

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!code) { document.getElementById('errorCode').style.display = 'block'; isValid = false; }

    if (isValid) {
        $.ajax({
            url: baseUrl + '/admin/updateRiskForCode', type: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            data: JSON.stringify({ code }),
            success: function (response) {
                const toast = document.getElementById('toast');
                if (response.status === 'success') {
                    toast.innerHTML = `✅ Đã cập nhật mức độ rủi ro cho cổ phiếu: <b>${code}</b><br>`;
                    toast.className = 'toast show';
                    toastSuccess();
                    setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000);
                    resetForm();
                } else {
                    toast.innerHTML = `❌` + response.message;
                    toast.className = 'toast show';
                    toastError();
                    setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
                }
            },
            error: function (xhr) {
                const toast = document.getElementById('toast');
                toast.innerHTML = '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi');
                toast.className = 'toast show';
                toastError();
                setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
            }
        });
    }
}
window.submitForm = submitForm;
