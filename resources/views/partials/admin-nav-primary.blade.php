{{-- Menu admin — LayoutAdmin. PC: hàng 1 = link trái + Đăng xuất phải; hàng 2 = Thêm/Xuất/Nhập. Mobile: cuộn + Đăng xuất ghim đáy. --}}
@php
    $showStocksSub = request()->routeIs('admin.stocks', 'admin.stocks.insert') || request()->routeIs('admin.update');
@endphp
<div class="admin-nav-primary">
    <div class="admin-nav-primary__inner">
        <div class="admin-nav-primary__mid">
            <div class="admin-nav-primary__cluster">
                <a href="{{ route('admin.home') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.home')])>🏠 Trang chủ</a>
                <div class="admin-nav-primary__stock-branch">
                    <a href="{{ route('admin.stocks') }}" @class(['button-link', 'user-nav-link--active' => request()->is('admin/stocks', 'admin/stocks/*') || request()->routeIs('admin.update', 'admin.delete')])>📊 Quản lý cổ phiếu</a>
                    @if ($showStocksSub)
                        <div class="admin-nav-stocks-sub" role="group" aria-label="Thao tác quản lý cổ phiếu">
                            <a href="{{ route('admin.stocks.insert') }}" @class(['button-link', 'user-nav-link--active' => request()->routeIs('admin.stocks.insert')])>➕ Thêm cổ phiếu</a>
                            <a href="{{ route('admin.stocks.exportCsv') }}" class="button-link" onclick="return confirm('Bạn có muốn xuất file CSV không?');">📄 Xuất file csv</a>
                            @if (request()->routeIs('admin.stocks'))
                                <a href="javascript:void(0)" class="button-link" onclick="openImportModal()">📥 Nhập file csv</a>
                            @else
                                <a href="{{ route('admin.stocks') }}?import=1" class="button-link">📥 Nhập file csv</a>
                            @endif
                        </div>
                    @endif
                </div>
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
