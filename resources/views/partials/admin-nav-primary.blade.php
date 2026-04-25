<div class="admin-nav-primary">
    <div class="admin-nav-primary__inner">
        <div class="admin-nav-primary__mid">
            <div class="admin-nav-primary__cluster">
                <a href="{{ route('admin.home') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.home')])>🏠 Trang chủ</a>
                <a href="{{ route('admin.stocks') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/stocks', 'admin/stocks/*') || request()->routeIs('admin.update', 'admin.delete')])>📊 Quản lý cổ phiếu</a>
                <a href="{{ route('admin.users') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/users', 'admin/users/*') || request()->routeIs('admin.users', 'admin.users.update', 'admin.users.delete')])>👥 Quản lý user</a>
                <a href="{{ url('/admin/infoProfile') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/infoProfile*') || request()->is('admin/updateInfoProfile*') || request()->routeIs('admin.changePassword')])>👤 Thông tin cá nhân</a>
                <a href="{{ route('admin.logs') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.logs', 'admin.logsVPS', 'admin.logsVPS.data')])>📋 Logs</a>
            </div>
        </div>
        <button type="button" class="button-link user-nav-link--logout" onclick="document.getElementById('logout-form').submit();">
            🚪 Đăng xuất
        </button>
    </div>
</div>
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
