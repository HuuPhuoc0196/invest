{{-- Menu khách — cùng khung user-nav-primary (drawer / grid PC giống user đã đăng nhập) --}}
@php
    $isHome = request()->routeIs('home') || request()->is('trang-chu', 'home', 'user');
    $isLogin = request()->routeIs('login');
    $isRegister = request()->routeIs('register');
@endphp
<div class="user-nav-primary">
    <div class="user-nav-primary__inner">
        <div class="user-nav-primary__mid">
            <div class="user-nav-primary__cluster">
                <a href="{{ route('home') }}" class="button-link user-nav-link user-nav-link--guest user-nav-link--guest-home @if($isHome) user-nav-link--active @endif">🏠 Trang chủ</a>
                <a href="{{ route('login') }}" class="button-link user-nav-link user-nav-link--guest user-nav-link--guest-login @if($isLogin) user-nav-link--active @endif">🔑 Đăng nhập</a>
                <a href="{{ route('register') }}" class="button-link user-nav-link user-nav-link--guest user-nav-link--guest-register @if($isRegister) user-nav-link--active @endif">📝 Đăng ký</a>
            </div>
        </div>
    </div>
</div>
