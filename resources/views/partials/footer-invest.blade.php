@php
    $area = $area ?? 'user';
    $logoV = file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0;
    $contactEmail = 'lehuuphuoc0196@gmail.com';
    $contactPhone = '0382834597';
    $contactPhoneTel = '+84382834597';
    $brandHref = $area === 'admin' ? url('/admin') : route('home');
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

        <div class="ft-col">
            <p class="ft-col-title">Điều hướng</p>
            <ul class="ft-links">
                @if ($area === 'admin')
                    <li><a href="{{ url('/admin') }}">🏠 Trang chủ</a></li>
                    <li><a href="{{ url('/admin/stocks') }}">📊 Quản lý cổ phiếu</a></li>
                    <li><a href="{{ url('/admin/insert') }}">➕ Thêm mã</a></li>
                    <li><a href="{{ url('/admin/updateRiskForCode') }}">🔄 Cập nhật rủi ro</a></li>
                    <li><a href="{{ url('/admin/uploadFile') }}">📁 Upload file</a></li>
                @elseif ($area === 'guest')
                    <li><a href="{{ route('home') }}">🏠 Trang chủ</a></li>
                    <li><a href="{{ route('login') }}">🔑 Đăng nhập</a></li>
                    <li><a href="{{ route('register') }}">📝 Đăng ký</a></li>
                @else
                    <li><a href="{{ route('home') }}">🏠 Trang chủ</a></li>
                    <li><a href="{{ url('/user/profile') }}">💼 Tài sản</a></li>
                    <li><a href="{{ url('/user/follow') }}">🔔 Theo dõi</a></li>
                    <li><a href="{{ url('/user/investment-performance') }}">📈 Hiệu quả đầu tư</a></li>
                    <li><a href="{{ route('buy') }}">➕ Mua cổ phiếu</a></li>
                @endif
            </ul>
        </div>

        <div class="ft-col">
            <p class="ft-col-title">@if ($area === 'admin') Hệ thống @else Tài khoản @endif</p>
            <ul class="ft-links">
                @if ($area === 'admin')
                    <li><a href="{{ route('admin.logs') }}">📋 Logs</a></li>
                    <li><a href="{{ route('home') }}">📊 Xem trang người dùng</a></li>
                @elseif ($area === 'guest')
                    <li><a href="{{ route('forgotPassword') }}">🔒 Quên mật khẩu</a></li>
                @else
                    <li><a href="{{ url('/user/infoProfile') }}">👤 Thông tin cá nhân</a></li>
                    <li><a href="{{ route('user.cashIn') }}">💰 Nạp tiền</a></li>
                    <li><a href="{{ route('user.cashOut') }}">💵 Rút tiền</a></li>
                    <li><a href="{{ route('user.emailSettings') }}">📧 Cài đặt thông báo</a></li>
                    <li><a href="{{ route('changePassword') }}">🔑 Đổi mật khẩu</a></li>
                @endif
            </ul>
        </div>

        <div class="ft-col">
            <p class="ft-col-title">Hỗ trợ</p>
            <ul class="ft-links">
                <li><a href="mailto:{{ $contactEmail }}">💬 Liên hệ hỗ trợ</a></li>
                @if ($area === 'guest')
                    <li><a href="{{ route('home') }}">📊 Bảng giá công khai</a></li>
                @else
                    <li><a href="{{ route('home') }}">📊 Danh sách mã</a></li>
                @endif
                <li><a href="mailto:{{ $contactEmail }}?subject=Góp%20ý%20ứng%20dụng">✏️ Góp ý</a></li>
            </ul>
        </div>
    </div>

    <div class="ft-bottom">
        <p class="ft-copy">&copy; {{ date('Y') }} <span>Invest Manager</span>. All rights reserved.</p>
    </div>
</footer>
