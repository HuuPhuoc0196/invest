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

    $isInfo = request()->is('user/infoProfile*') || request()->is('user/updateInfoProfile*') || request()->routeIs('changePassword');
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
                <a href="{{ route('home') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isHome]) data-tip="Trang chủ">🏠 <span class="anp-label">Trang chủ</span></a>

                <div class="user-nav-primary__follow-branch">
                    <a href="{{ url('/user/follow') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isFollowParent]) data-tip="Theo dõi">🔔 <span class="anp-label">Theo dõi</span></a>
                </div>

                <a href="{{ url('/user/investment-performance') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInvestmentPerf]) data-tip="Hiệu suất đầu tư">📈 <span class="anp-label">Hiệu suất đầu tư</span></a>

                <div class="user-nav-primary__asset-branch">
                    <a href="{{ url('/user/profile') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isAssetParent]) data-tip="Tài sản">💼 <span class="anp-label">Tài sản</span></a>
                </div>

                <a href="{{ url('/user/infoProfile') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isInfo]) data-tip="Thông tin cá nhân">👤 <span class="anp-label">Thông tin cá nhân</span></a>
                <a href="{{ route('user.emailSettings') }}" @class(['button-link', 'user-nav-link', 'user-nav-link--active' => $isEmail]) data-tip="Cài đặt thông báo">📧 <span class="anp-label">Cài đặt thông báo</span></a>
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
