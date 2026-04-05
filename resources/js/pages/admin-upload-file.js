const { baseUrl } = window.__pageData || {};
const fileInputEl = document.getElementById('file');

function isUploadFileReady() {
    const file = fileInputEl.files[0];
    return !!(file && file.name.toLowerCase().endsWith('.txt'));
}

function updateUploadSubmitButton() {
    const btn = document.getElementById('btnFormSubmit');
    if (btn) btn.disabled = !isUploadFileReady();
}

fileInputEl.addEventListener('change', function () {
    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));
    document.getElementById('errorFile').innerText = 'Vui lòng chọn file .txt';
    updateUploadSubmitButton();
});

function resetForm() { fileInputEl.value = ''; updateUploadSubmitButton(); }

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
    const fileInput = document.getElementById('file');
    const file = fileInput.files[0];
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.error').forEach(el => (el.style.display = 'none'));

    if (!file) { document.getElementById('errorFile').style.display = 'block'; return; }
    if (!file.name.toLowerCase().endsWith('.txt')) {
        document.getElementById('errorFile').style.display = 'block';
        document.getElementById('errorFile').innerText = 'Chỉ chấp nhận file .txt';
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    $.ajax({
        url: baseUrl + '/admin/uploadFile', type: 'POST',
        headers: { 'X-CSRF-TOKEN': token },
        data: formData, processData: false, contentType: false,
        success: function (response) {
            const toast = document.getElementById('toast');
            if (response.status === 'success') {
                toast.innerHTML = '✅ File đã được upload thành công<br>';
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
            toast.innerHTML = '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error');
            toast.className = 'toast show';
            toastError();
            setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 5000);
        }
    });
}
window.submitForm = submitForm;
