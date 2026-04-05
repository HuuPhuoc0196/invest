@php
    $isHome = request()->routeIs('home') || request()->is('trang-chu', 'home', 'user');
    $isProfile = request()->is('user/profile');
    $isBuy = request()->routeIs('buy');
    $isSell = request()->routeIs('sell');
    $isInvestmentPerf = request()->is('user/investment-performance*');

    $isFollowList = request()->is('user/follow');
    $isFollowUpdate = request()->is('user/updateFollow*');
    $isInsertFollow = request()->routeIs('insertFollow');
    $showFollowSub = $isFollowList || $isFollowUpdate || $isInsertFollow;

    $isInfo = request()->is('user/infoProfile*') || request()->is('user/updateInfoProfile*');
    $isCashIn = request()->is('user/cashIn*');
    $isCashOut = request()->is('user/cashOut*');
    $isEmail = request()->is('user/email-settings*');

    $isAssetParent = $isProfile || $isBuy || $isSell || $isCashIn || $isCashOut;
    $isFollowParent = $showFollowSub;
@endphp
<div class="user-nav-primary">
    <div class="user-nav-primary__inner">
        <div class="user-nav-primary__mid">
            <div class="user-nav-primary__cluster">
                <a href="{{ route('home') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isHome])>🏠 Trang chủ</a>

                <div class="user-nav-primary__follow-branch">
                    <a href="{{ url('/user/follow') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isFollowParent])>🔔 Theo dõi</a>
                </div>

                <a href="{{ url('/user/investment-performance') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInvestmentPerf])>📈 Hiệu suất đầu tư</a>

                <div class="user-nav-primary__asset-branch">
                    <a href="{{ url('/user/profile') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isAssetParent])>💼 Tài sản</a>
                </div>

                <a href="{{ url('/user/infoProfile') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInfo])>👤 Thông tin cá nhân</a>
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
