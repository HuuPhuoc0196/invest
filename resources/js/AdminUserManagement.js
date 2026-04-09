const { baseUrl, currentAdminId } = window.__pageData || {};

let pendingDeleteUser = null;

function getRoleLabel(role) {
    return Number(role) === 1 ? 'Quản trị viên' : 'Nhà đầu tư';
}

function getActiveLabel(active) {
    return Number(active) === 1 ? 'Đã active' : 'Chưa active';
}

function getEmailVerifiedLabel(emailVerifiedAt) {
    return emailVerifiedAt ? 'Đã xác thực' : 'Chưa xác thực';
}

function formatDateTime(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('vi-VN');
}

window.renderUserTable = function(users) {
    const tbody = document.getElementById('userTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    users.forEach(user => {
        const row = document.createElement('tr');
        const isSelf = Number(currentAdminId) === Number(user.id);
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.email || ''}</td>
            <td>${user.name || ''}</td>
            <td><span class="user-role-badge user-role-badge--${Number(user.role) === 1 ? 'admin' : 'investor'}">${getRoleLabel(user.role)}</span></td>
            <td><span class="user-status-badge user-status-badge--${Number(user.active) === 1 ? 'active' : 'inactive'}">${getActiveLabel(user.active)}</span></td>
            <td><span class="user-status-badge user-status-badge--${user.email_verified_at ? 'active' : 'inactive'}">${getEmailVerifiedLabel(user.email_verified_at)}</span></td>
            <td>${formatDateTime(user.created_at)}</td>
            <td>
                <button type="button" onclick="location.href='${baseUrl}/admin/users/update/${user.id}'">Cập nhật</button>
                <button type="button" class="btn-delete" onclick="confirmDeleteUser(${user.id})" ${isSelf ? 'disabled title="Không thể tự xoá tài khoản đang đăng nhập"' : ''}>Xoá</button>
            </td>
        `;
        tbody.appendChild(row);
    });
};

window.confirmDeleteUser = function(userId) {
    const allUsers = (window.__pageData && window.__pageData.users) || [];
    const user = allUsers.find(u => Number(u.id) === Number(userId));
    if (!user) return;
    const modal = document.getElementById('deleteUserModal');
    const emailEl = document.getElementById('deleteUserEmail');
    const btn = document.getElementById('btnDeleteUserConfirm');
    if (!modal || !emailEl || !btn) return;

    pendingDeleteUser = user;
    emailEl.textContent = user.email || `ID ${user.id}`;
    btn.disabled = false;
    btn.textContent = 'Xoá';
    modal.style.display = 'flex';
};

window.closeDeleteUserModal = function() {
    const modal = document.getElementById('deleteUserModal');
    if (!modal) return;
    pendingDeleteUser = null;
    modal.style.display = 'none';
};

window.runDeleteUser = function() {
    if (!pendingDeleteUser) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const btn = document.getElementById('btnDeleteUserConfirm');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang xoá...';
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${baseUrl}/admin/users/delete/${encodeURIComponent(pendingDeleteUser.id)}`;

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_token';
    input.value = token;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
};

window.openUserNoticeModal = function(type, message) {
    const modal = document.getElementById('userNoticeModal');
    const title = document.getElementById('userNoticeTitle');
    const msg = document.getElementById('userNoticeMessage');
    if (!modal || !title || !msg) return;
    modal.classList.remove('is-success', 'is-error');
    modal.classList.add(type === 'success' ? 'is-success' : 'is-error');
    title.textContent = type === 'success' ? 'Thành công' : 'Thông báo';
    msg.innerHTML = message || '';
    modal.style.display = 'flex';
};

window.closeUserNoticeModal = function() {
    const modal = document.getElementById('userNoticeModal');
    if (!modal) return;
    modal.style.display = 'none';
};

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deleteUserModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) window.closeDeleteUserModal();
        });
    }
    const noticeModal = document.getElementById('userNoticeModal');
    if (noticeModal) {
        noticeModal.addEventListener('click', function(e) {
            if (e.target === this) window.closeUserNoticeModal();
        });
    }

    const flashSuccess = (window.__pageData && window.__pageData.flashSuccess) || '';
    if (flashSuccess) {
        window.openUserNoticeModal('success', '✅ ' + flashSuccess);
    }
});
