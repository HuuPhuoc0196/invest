@extends('Layout.Layout')

@section('title', 'Hướng dẫn sử dụng — Quản lý đầu tư cá nhân')

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => 'Hướng dẫn sử dụng — ' . config('app.name'),
        'description' => 'Hướng dẫn từng bước sử dụng nền tảng quản lý danh mục cổ phiếu cá nhân: đăng ký, nạp tiền ảo, theo dõi mã, mua bán và nhận cảnh báo giá email.',
    ])
    <script type="application/ld+json">
    {!! json_encode([
        '@context'    => 'https://schema.org',
        '@type'       => 'HowTo',
        'name'        => 'Cách sử dụng Quản lý đầu tư cá nhân',
        'description' => 'Hướng dẫn từng bước để quản lý danh mục cổ phiếu cá nhân',
        'step'        => [
            [
                '@type' => 'HowToStep',
                'name'  => 'Đăng ký tài khoản',
                'text'  => 'Truy cập trang đăng ký, nhập email và mật khẩu. Xác thực email qua link được gửi vào hộp thư.',
                'url'   => route('register'),
            ],
            [
                '@type' => 'HowToStep',
                'name'  => 'Nạp tiền ảo vào ví',
                'text'  => 'Sau khi đăng nhập, vào Nạp tiền để thêm số dư ảo vào ví. Số dư này dùng để mua cổ phiếu mô phỏng.',
            ],
            [
                '@type' => 'HowToStep',
                'name'  => 'Khám phá danh sách cổ phiếu',
                'text'  => 'Xem bảng giá tất cả mã cổ phiếu, lọc theo mức rủi ro và điểm đánh giá.',
                'url'   => route('home'),
            ],
            [
                '@type' => 'HowToStep',
                'name'  => 'Thêm mã vào danh sách theo dõi',
                'text'  => 'Follow mã cổ phiếu và đặt giá mục tiêu mua/bán. Hệ thống sẽ gửi email khi giá chạm ngưỡng.',
            ],
            [
                '@type' => 'HowToStep',
                'name'  => 'Mua cổ phiếu ảo',
                'text'  => 'Chọn mã, nhập số lượng và giá mua. Hệ thống tự động tính P&L theo phương pháp FIFO.',
            ],
            [
                '@type' => 'HowToStep',
                'name'  => 'Theo dõi hiệu suất đầu tư',
                'text'  => 'Xem P&L, ROI và phân tích hiệu suất danh mục theo thời gian thực.',
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/guide.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @auth
        @include('partials.user-nav-primary')
    @else
        @include('partials.guest-nav-actions')
    @endauth
@endsection

@section('user-body-content')
<div class="guide-page">

    {{-- ─── HERO ─── --}}
    <div class="guide-hero">
        <div class="guide-hero__icon" aria-hidden="true">📖</div>
        <h1 class="guide-hero__title">Hướng dẫn sử dụng</h1>
        <p class="guide-hero__sub">
            Bắt đầu quản lý danh mục cổ phiếu cá nhân chỉ trong vài bước đơn giản. Không cần kiến thức kỹ thuật, không cần kinh nghiệm đầu tư chuyên sâu.
        </p>
    </div>

    {{-- ─── STEPS ─── --}}
    <div class="guide-steps">

        {{-- Step 1 --}}
        <div class="guide-step">
            <div class="guide-step__num" aria-hidden="true">1</div>
            <h2 class="guide-step__title">Đăng ký tài khoản</h2>
            <p class="guide-step__body">
                Truy cập trang đăng ký và điền thông tin cơ bản: họ tên, địa chỉ email và mật khẩu. Sau khi đăng ký, hệ thống sẽ gửi một email xác thực đến hộp thư của bạn. Nhấp vào link xác thực để kích hoạt tài khoản và bắt đầu sử dụng.
            </p>
            <a href="{{ route('register') }}" class="guide-step__action">
                <span>Đăng ký ngay</span>
                <span aria-hidden="true">→</span>
            </a>
        </div>

        {{-- Step 2 --}}
        <div class="guide-step">
            <div class="guide-step__num" aria-hidden="true">2</div>
            <h2 class="guide-step__title">Nạp tiền ảo vào ví</h2>
            <p class="guide-step__body">
                Sau khi đăng nhập, vào mục <strong>Nạp tiền</strong> để thêm số dư ảo vào ví. Đây là số tiền mô phỏng dùng để thực hiện các giao dịch mua cổ phiếu ảo. Bạn có thể nạp bất kỳ số lượng nào và theo dõi số dư qua lịch sử nạp/rút.
            </p>
            @auth
                <a href="{{ url('/user/cashIn') }}" class="guide-step__action">
                    <span>Nạp tiền ảo</span>
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="guide-step__action">
                    <span>Đăng nhập để nạp tiền</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>

        {{-- Step 3 --}}
        <div class="guide-step">
            <div class="guide-step__num" aria-hidden="true">3</div>
            <h2 class="guide-step__title">Khám phá danh sách cổ phiếu</h2>
            <p class="guide-step__body">
                Xem bảng giá đầy đủ của tất cả mã cổ phiếu trên sàn. Sử dụng bộ lọc để tìm mã theo mức rủi ro (An toàn, Cảnh báo, Hạn chế), điểm đánh giá và nhóm chỉ số (VN30, VN100). Xem giá khuyến nghị mua/bán được tính toán từ trung bình giá lịch sử.
            </p>
            <a href="{{ route('home') }}" class="guide-step__action">
                <span>Xem bảng giá</span>
                <span aria-hidden="true">→</span>
            </a>
        </div>

        {{-- Step 4 --}}
        <div class="guide-step">
            <div class="guide-step__num" aria-hidden="true">4</div>
            <h2 class="guide-step__title">Thêm mã vào danh sách theo dõi</h2>
            <p class="guide-step__body">
                Follow mã cổ phiếu bạn quan tâm và đặt giá mục tiêu mua/bán. Bật thông báo email để hệ thống tự động gửi cảnh báo khi giá chạm ngưỡng bạn đặt. Bạn có thể bật/tắt riêng từng loại thông báo (mua/bán) cho từng mã.
            </p>
            @auth
                <a href="{{ url('/user/follow') }}" class="guide-step__action">
                    <span>Quản lý theo dõi</span>
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <a href="{{ route('register') }}" class="guide-step__action">
                    <span>Đăng ký để theo dõi</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>

        {{-- Step 5 --}}
        <div class="guide-step">
            <div class="guide-step__num" aria-hidden="true">5</div>
            <h2 class="guide-step__title">Mua cổ phiếu ảo</h2>
            <p class="guide-step__body">
                Chọn mã cổ phiếu, nhập số lượng, giá mua và ngày mua. Hệ thống sẽ trừ số tiền tương ứng từ ví ảo và ghi nhận lô mua mới vào danh mục. Mỗi lần mua tạo thành một lô độc lập theo phương pháp FIFO, đảm bảo tính toán P&amp;L chính xác.
            </p>
            @auth
                <a href="{{ url('/user/buy') }}" class="guide-step__action">
                    <span>Mua cổ phiếu</span>
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <a href="{{ route('register') }}" class="guide-step__action">
                    <span>Đăng ký để mua</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>

        {{-- Step 6 --}}
        <div class="guide-step">
            <div class="guide-step__num" aria-hidden="true">6</div>
            <h2 class="guide-step__title">Theo dõi hiệu suất đầu tư</h2>
            <p class="guide-step__body">
                Vào trang <strong>Hiệu suất đầu tư</strong> để xem P&amp;L và ROI của từng mã trong danh mục, so sánh giá mua trung bình với giá thị trường hiện tại. Xem tổng lãi/lỗ toàn danh mục và lịch sử giao dịch mua bán đầy đủ. Xuất danh mục sang PDF khi cần.
            </p>
            @auth
                <a href="{{ url('/user/investment-performance') }}" class="guide-step__action">
                    <span>Xem hiệu suất</span>
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <a href="{{ route('register') }}" class="guide-step__action">
                    <span>Đăng ký để xem</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>

    </div>

    {{-- ─── CTA ─── --}}
    @guest
        <div class="guide-cta">
            <div class="guide-cta__inner">
                <p class="guide-cta__title">Sẵn sàng bắt đầu?</p>
                <p class="guide-cta__body">
                    Tạo tài khoản miễn phí ngay hôm nay và bắt đầu hành trình quản lý danh mục đầu tư cá nhân của bạn.
                </p>
                <div class="guide-cta__actions">
                    <a href="{{ route('register') }}" class="guide-cta__btn guide-cta__btn--primary">Đăng ký miễn phí →</a>
                    <a href="{{ route('login') }}" class="guide-cta__btn guide-cta__btn--ghost">Đã có tài khoản</a>
                </div>
            </div>
        </div>
    @endguest

</div>
@endsection
