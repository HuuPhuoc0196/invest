<div class="admin-nav-primary">
    <div class="admin-nav-primary__inner">
        <div class="admin-nav-primary__mid">
            <div class="admin-nav-primary__cluster">
                <a href="{{ route('admin.home') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.home')]) data-tip="Trang chủ">🏠 <span class="anp-label">Trang chủ</span></a>
                <a href="{{ route('admin.stocks') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/stocks', 'admin/stocks/*') || request()->routeIs('admin.update', 'admin.delete')]) data-tip="Quản lý cổ phiếu">📊 <span class="anp-label">Quản lý cổ phiếu</span></a>
                <a href="{{ route('admin.users') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/users', 'admin/users/*') || request()->routeIs('admin.users', 'admin.users.update', 'admin.users.delete')]) data-tip="Quản lý user">👥 <span class="anp-label">Quản lý user</span></a>
                <a href="{{ route('admin.accessManagement') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.accessManagement')]) data-tip="Quản lý truy cập">🔐 <span class="anp-label">Quản lý truy cập</span></a>
                <a href="{{ route('admin.crontab') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.crontab', 'admin.crontab.*')]) data-tip="Crontab">⏰ <span class="anp-label">Crontab</span></a>
                <a href="{{ route('admin.logs') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.logs', 'admin.logsVPS', 'admin.logsVPS.data')]) data-tip="Logs">📋 <span class="anp-label">Logs</span></a>
                <a href="{{ route('admin.infoProfile') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/infoProfile*') || request()->is('admin/updateInfoProfile*') || request()->routeIs('admin.changePassword')]) data-tip="Thông tin cá nhân">👤 <span class="anp-label">Thông tin cá nhân</span></a>
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
