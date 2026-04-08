<div class="admin-nav-primary">
    <div class="admin-nav-primary__inner">
        <div class="admin-nav-primary__mid">
            <div class="admin-nav-primary__cluster">
                <a href="{{ route('admin.home') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.home')])>🏠 Trang chủ</a>
                <a href="{{ route('admin.stocks') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/stocks', 'admin/stocks/*') || request()->routeIs('admin.update', 'admin.delete')])>📊 Quản lý cổ phiếu</a>
                <a href="{{ url('/admin/logs') }}" class="button-link" target="_blank" rel="noopener noreferrer">👁️ Logs Hosting</a>
                <a href="{{ url('/admin/logsVPS') }}" class="button-link" target="_blank" rel="noopener noreferrer">👁️ Logs VPS</a>
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
