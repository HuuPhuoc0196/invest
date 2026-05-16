@php
    $area = $area ?? 'user';
    $logoV = file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0;
    $contactEmail = 'lehuuphuoc0196@gmail.com';
    $contactPhone = '0382834597';
    $contactPhoneTel = '+84382834597';
    $brandHref = $area === 'admin' ? route('admin.home') : route('home');
@endphp
<footer class="footer ft-invest">
    <div class="ft-top">
        <div class="ft-brand">
            <div class="ft-logo-row">
                <a href="{{ $brandHref }}" class="ft-logo-ico" aria-label="Trang chủ">
                    <img src="{{ route('site.logo') }}?v={{ $logoV }}" alt="Logo {{ config('app.name') }}" width="28" height="28" decoding="async">
                </a>
                <span class="ft-logo-name">Quản lý đầu tư cá nhân</span>
            </div>
            <p class="ft-desc">Nền tảng theo dõi và phân tích danh mục cổ phiếu, hỗ trợ nhà đầu tư cá nhân tại thị trường Việt Nam.</p>
            <div class="ft-contact-block">
                <a class="ft-email" href="mailto:{{ $contactEmail }}">
                    <span class="ft-email-ico" aria-hidden="true">📧</span>
                    {{ $contactEmail }}
                </a>
                <a class="ft-phone" href="tel:{{ $contactPhoneTel }}">
                    <span class="ft-phone-ico" aria-hidden="true">📞</span>
                    {{ $contactPhone }}
                </a>
            </div>
        </div>

        {{-- ===== Điều hướng ===== --}}
        <div class="ft-col">
            @if ($area === 'admin')
                <p class="ft-col-title">Quản lý</p>
                <ul class="ft-links">
                    <li><a href="{{ route('admin.home') }}"><span aria-hidden="true">🏠</span> Trang chủ</a></li>
                    <li><a href="{{ route('admin.stocks') }}"><span aria-hidden="true">📊</span> Quản lý cổ phiếu</a></li>
                    <li><a href="{{ route('admin.users') }}"><span aria-hidden="true">👥</span> Quản lý người dùng</a></li>
                </ul>
            @elseif ($area === 'guest')
                <p class="ft-col-title">Điều hướng</p>
                <ul class="ft-links">
                    <li><a href="{{ route('home') }}"><span aria-hidden="true">🏠</span> Trang chủ</a></li>
                    <li><a href="{{ route('stocks.vn30') }}"><span aria-hidden="true">📈</span> Cổ phiếu VN30</a></li>
                    <li><a href="{{ route('stocks.vn100') }}"><span aria-hidden="true">📊</span> Cổ phiếu VN100</a></li>
                </ul>
            @else
                <p class="ft-col-title">Điều hướng</p>
                <ul class="ft-links">
                    <li><a href="{{ route('home') }}"><span aria-hidden="true">🏠</span> Trang chủ</a></li>
                    <li><a href="{{ url('/user/profile') }}"><span aria-hidden="true">💼</span> Tài sản</a></li>
                    <li><a href="{{ url('/user/follow') }}"><span aria-hidden="true">🔔</span> Theo dõi</a></li>
                    <li><a href="{{ url('/user/investment-performance') }}"><span aria-hidden="true">📈</span> Hiệu quả đầu tư</a></li>
                </ul>
            @endif
        </div>

        {{-- ===== Tài khoản / Hệ thống ===== --}}
        <div class="ft-col">
            @if ($area === 'admin')
                <p class="ft-col-title">Hệ thống</p>
                <ul class="ft-links">
                    <li><a href="{{ route('insert') }}"><span aria-hidden="true">➕</span> Thêm mã</a></li>
                    <li><a href="{{ route('updateRiskForCode') }}"><span aria-hidden="true">🔄</span> Cập nhật rủi ro</a></li>
                    <li><a href="{{ route('admin.logs') }}"><span aria-hidden="true">📋</span> Logs</a></li>
                    <li><a href="{{ route('uploadFile') }}"><span aria-hidden="true">📁</span> Upload file</a></li>
                </ul>
            @elseif ($area === 'guest')
                <p class="ft-col-title">Tài khoản</p>
                <ul class="ft-links">
                    <li><a href="{{ route('login') }}"><span aria-hidden="true">🔑</span> Đăng nhập</a></li>
                    <li><a href="{{ route('register') }}"><span aria-hidden="true">📝</span> Đăng ký</a></li>
                    <li><a href="{{ route('forgotPassword') }}"><span aria-hidden="true">🔒</span> Quên mật khẩu</a></li>
                </ul>
            @else
                <p class="ft-col-title">Tài khoản</p>
                <ul class="ft-links">
                    <li><a href="{{ url('/user/infoProfile') }}"><span aria-hidden="true">👤</span> Thông tin cá nhân</a></li>
                    <li><a href="{{ route('user.cashIn') }}"><span aria-hidden="true">💰</span> Nạp tiền</a></li>
                    <li><a href="{{ route('user.cashOut') }}"><span aria-hidden="true">💵</span> Rút tiền</a></li>
                    <li><a href="{{ route('changePassword') }}"><span aria-hidden="true">🔑</span> Đổi mật khẩu</a></li>
                </ul>
            @endif
        </div>

        {{-- ===== Hỗ trợ (giống nhau cả 3 area) ===== --}}
        <div class="ft-col">
            <p class="ft-col-title">Hỗ trợ</p>
            <ul class="ft-links">
                <li><a href="{{ route('about') }}"><span aria-hidden="true">📖</span> Giới thiệu</a></li>
                <li><a href="{{ route('guide') }}"><span aria-hidden="true">🗺️</span> Hướng dẫn</a></li>
                <li><a href="{{ route('faq') }}"><span aria-hidden="true">❓</span> Hỏi đáp</a></li>
                <li><a href="{{ route('contact') }}"><span aria-hidden="true">📬</span> Liên hệ</a></li>
            </ul>
        </div>
    </div>

    <div class="ft-bottom">
        <p class="ft-copy">&copy; {{ date('Y') }} <span>Invest Manager</span>. All rights reserved.</p>
        <div class="ft-legal-links">
            <a href="{{ route('donate') }}">💖 Ủng hộ</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('privacy') }}">Chính sách bảo mật</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('terms') }}">Điều khoản</a>
        </div>
    </div>
</footer>
