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
            if (response.status === 'success') {
                showNotifyModal('success', '✅ File đã được upload thành công');
                resetForm();
            } else {
                showNotifyModal('error', '❌ ' + (response.message || 'Có lỗi xảy ra.'));
            }
        },
        error: function (xhr) {
            showNotifyModal('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        }
    });
}
window.submitForm = submitForm;
