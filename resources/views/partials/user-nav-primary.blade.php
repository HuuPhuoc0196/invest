{{-- Menu user — cùng tư tưởng admin: PC hàng 1 + Đăng xuất phải; submenu dưới nhánh; mobile drawer cuộn + Đăng xuất đáy. --}}
@php
    $isHome = request()->routeIs('home') || request()->is('user');
    $isProfile = request()->is('user/profile');
    $isBuy = request()->routeIs('buy');
    $isSell = request()->routeIs('sell');
    $isInvestmentPerf = request()->is('user/investment-performance*');
    $showAssetSub = $isProfile || $isBuy || $isSell || $isInvestmentPerf;

    $isFollowList = request()->is('user/follow');
    $isFollowUpdate = request()->is('user/updateFollow*');
    $isInsertFollow = request()->routeIs('insertFollow');
    $showFollowSub = $isFollowList || $isFollowUpdate || $isInsertFollow;

    $isInfo = request()->is('user/infoProfile*') || request()->is('user/updateInfoProfile*');
    $isCashIn = request()->is('user/cashIn*');
    $isCashOut = request()->is('user/cashOut*');
    $isEmail = request()->is('user/email-settings*');

    $isAssetParent = $showAssetSub;
    $isFollowParent = $showFollowSub;
@endphp
<div class="user-nav-primary">
    <div class="user-nav-primary__inner">
        <div class="user-nav-primary__mid">
            <div class="user-nav-primary__cluster">
                <a href="{{ url('/home') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isHome])>🏠 Trang chủ</a>

                <div class="user-nav-primary__asset-branch">
                    <a href="{{ url('/user/profile') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isAssetParent])>💼 Tài sản</a>
                    @if ($showAssetSub)
                        <div class="user-nav-asset-sub" role="group" aria-label="Thao tác tài sản">
                            <a href="{{ url('/user/buy') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isBuy])>➕ Mua cổ phiếu</a>
                            <a href="{{ url('/user/sell') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isSell])>❌ Bán cổ phiếu</a>
                            <a href="{{ url('/user/investment-performance') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInvestmentPerf])>📈 Hiệu quả đầu tư</a>
                        </div>
                    @endif
                </div>

                <div class="user-nav-primary__follow-branch">
                    <a href="{{ url('/user/follow') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isFollowParent])>🔔 Theo dõi</a>
                    @if ($showFollowSub)
                        <div class="user-nav-follow-sub" role="group" aria-label="Thao tác theo dõi">
                            <a href="{{ url('/user/follow') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => ($isFollowList || $isFollowUpdate) && !$isInsertFollow])>📋 Danh sách theo dõi</a>
                            <a href="{{ route('insertFollow') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInsertFollow])>➕ Thêm theo dõi</a>
                        </div>
                    @endif
                </div>

                <a href="{{ url('/user/infoProfile') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInfo])>👤 Thông tin cá nhân</a>
                <a href="{{ url('/user/cashIn') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isCashIn])>💰 Nạp tiền</a>
                <a href="{{ url('/user/cashOut') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isCashOut])>💵 Rút tiền</a>
                <a href="{{ url('/user/email-settings') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isEmail])>📧 Cài đặt thông báo</a>
            </div>
        </div>
        <button type="button" class="button-link user-nav-link user-nav-link--logout" onclick="document.getElementById('logout-form-user-nav').submit();">
            🚪 Đăng xuất
        </button>
    </div>
</div>
<form id="logout-form-user-nav" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
