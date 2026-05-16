(function () {
    var data = window.__donateData || {};
    var accountNumber = data.accountNumber || '02319798401';
    var donateContent = data.donateContent || 'Nguoi dung donate';
    var qrUrl = 'https://img.vietqr.io/image/TPB-' + accountNumber + '-compact2.png'
        + '?addInfo=' + encodeURIComponent(donateContent)
        + '&accountName=Invest+Team';

    window.openDonateModal = function () {
        var modal = document.getElementById('donateModal');
        var contentEl = document.getElementById('donate-content-text');
        var copyBtn = document.getElementById('donate-copy-content-btn');
        var img = document.getElementById('donate-qr-img');
        if (!modal) return;

        if (contentEl) contentEl.textContent = donateContent;
        if (copyBtn) {
            copyBtn.onclick = function () {
                window.copyDonateText(donateContent, copyBtn);
            };
        }
        if (img && img.src !== qrUrl) img.src = qrUrl;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeDonateModal = function () {
        var modal = document.getElementById('donateModal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    window.copyDonateText = function (text, btnEl) {
        if (!navigator.clipboard) return;
        navigator.clipboard.writeText(text).then(function () {
            if (!btnEl) return;
            var orig = btnEl.textContent;
            btnEl.textContent = '✅';
            btnEl.style.color = '#6ee7b7';
            setTimeout(function () {
                btnEl.textContent = orig;
                btnEl.style.color = '';
            }, 1500);
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('donateModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) window.closeDonateModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') window.closeDonateModal();
        });
    });
})();
