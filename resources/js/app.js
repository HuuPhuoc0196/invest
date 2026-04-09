import './bootstrap';
import './stickyHeaderInset';
import Admin from './Admin';
import User from './User';

// Đưa class ra window để dùng trong balde
window.Admin = Admin;
window.User = User;

// Shared notify modal utility (replaces toast notifications)
window.showNotifyModal = function (type, message, onClose) {
    const modal    = document.getElementById('notify-modal');
    if (!modal) return;
    const icon     = document.getElementById('notifyModalIcon');
    const msgEl    = document.getElementById('notifyModalMsg');
    const closeBtn = document.getElementById('notifyModalClose');
    const backdrop = document.getElementById('notifyModalBackdrop');

    modal.classList.remove('home-notify-modal--success', 'home-notify-modal--error', 'home-notify-modal--warning');
    if (type === 'success') {
        modal.classList.add('home-notify-modal--success');
        icon.textContent = '✅';
    } else if (type === 'warning') {
        modal.classList.add('home-notify-modal--warning');
        icon.textContent = '⚠️';
    } else {
        modal.classList.add('home-notify-modal--error');
        icon.textContent = '❌';
    }
    msgEl.innerHTML = message;
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(function () { if (closeBtn) closeBtn.focus(); }, 50);

    function closeModal() {
        if (modal.contains(document.activeElement)) document.activeElement.blur();
        modal.setAttribute('aria-hidden', 'true');
        closeBtn.removeEventListener('click', closeModal);
        backdrop.removeEventListener('click', closeModal);
        if (typeof onClose === 'function') onClose();
    }
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
};