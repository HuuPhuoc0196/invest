@extends('Layout.Layout')

@section('title', 'Giới thiệu — Quản lý đầu tư cá nhân')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => 'Giới thiệu — ' . config('app.name'),
        'description' => 'Nền tảng quản lý danh mục cổ phiếu cá nhân: theo dõi FIFO, cảnh báo giá email, phân tích hiệu suất P&L/ROI và gợi ý đầu tư từ chuyên gia.',
    ])
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/about.css')
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
<main class="about-page-main">

    {{-- ─── HERO ─── --}}
    <section class="about-hero">
        <div class="about-hero__orb about-hero__orb--1"></div>
        <div class="about-hero__orb about-hero__orb--2"></div>
        <div class="about-hero__orb about-hero__orb--3"></div>

        <div class="about-hero__inner">
            <div class="about-hero__badge">🇻🇳 Thị trường chứng khoán Việt Nam</div>

            <h1 class="about-hero__title">
                <span class="about-hero__title-line1">Đầu tư thông minh</span>
                <span class="about-hero__title-line2">Quản lý chuyên nghiệp</span>
            </h1>

            <p class="about-hero__sub">
                Nền tảng theo dõi danh mục cổ phiếu cá nhân tại thị trường Việt Nam.
                Đơn giản, minh bạch và được cập nhật tự động.
            </p>

            <div class="about-hero__ctas">
                <a href="{{ route('register') }}" class="about-hero__cta-primary">🚀 Bắt đầu miễn phí</a>
                <a href="{{ route('home') }}" class="about-hero__cta-secondary">📊 Xem bảng giá</a>
            </div>

            <div class="about-hero__chips">
                <div class="about-hero__chip about-hero__chip--1">
                    <span class="about-hero__chip-icon">🔄</span>
                    <div>
                        <div class="about-hero__chip-value">Cập nhật tự động</div>
                        <div class="about-hero__chip-label">Giá cổ phiếu, sự kiện</div>
                    </div>
                </div>
                <div class="about-hero__chip about-hero__chip--2">
                    <span class="about-hero__chip-icon">🔔</span>
                    <div>
                        <div class="about-hero__chip-value">Email tự động</div>
                        <div class="about-hero__chip-label">Cảnh báo giá</div>
                    </div>
                </div>
                <div class="about-hero__chip about-hero__chip--3">
                    <span class="about-hero__chip-icon">🆓</span>
                    <div>
                        <div class="about-hero__chip-value">Miễn phí</div>
                        <div class="about-hero__chip-label">Không cần thẻ</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-hero__scroll">
            <span class="about-hero__scroll-arrow">▼</span>
            <span>Khám phá</span>
        </div>
    </section>

    {{-- ─── STATS STRIP ─── --}}
    <div class="about-stats-strip">
        <div class="about-stats-strip__inner">
            <div class="about-stat">
                <span class="about-stat__icon">📊</span>
                <span class="about-stat__number">100+</span>
                <span class="about-stat__label">Mã cổ phiếu</span>
            </div>
            <div class="about-stat-divider"></div>
            <div class="about-stat">
                <span class="about-stat__icon">⚡</span>
                <span class="about-stat__number">FIFO</span>
                <span class="about-stat__label">Tính toán chính xác</span>
            </div>
            <div class="about-stat-divider"></div>
            <div class="about-stat">
                <span class="about-stat__icon">📩</span>
                <span class="about-stat__number">Tự động</span>
                <span class="about-stat__label">Email cảnh báo giá</span>
            </div>
            <div class="about-stat-divider"></div>
            <div class="about-stat">
                <span class="about-stat__icon">🎯</span>
                <span class="about-stat__number">Miễn phí</span>
                <span class="about-stat__label">100% tính năng</span>
            </div>
        </div>
    </div>

    {{-- ─── SHOWCASE 1: Portfolio FIFO ─── --}}
    <section class="about-showcase-section">
        <div class="about-showcase-section__inner">
            <div class="about-showcase about-showcase--ltr">
                <div class="about-showcase__text">
                    <span class="about-showcase__tag about-showcase__tag--sky">Danh mục đầu tư</span>
                    <h2 class="about-showcase__title">Theo dõi danh mục <span class="about-showcase__hl about-showcase__hl--sky">FIFO</span> chính xác từng lô</h2>
                    <p class="about-showcase__desc">
                        Mỗi lần mua tạo thành một lô độc lập. Khi bán, hệ thống tự động áp dụng FIFO — xuất cổ phiếu từ lô cũ nhất trước, đảm bảo tính P&amp;L chính xác tuyệt đối theo chuẩn kế toán.
                    </p>
                    <ul class="about-showcase__bullets">
                        <li><span class="about-showcase__check">✓</span> Giá mua bình quân tự động theo FIFO</li>
                        <li><span class="about-showcase__check">✓</span> P&amp;L và ROI realtime cho từng mã</li>
                        <li><span class="about-showcase__check">✓</span> Lịch sử mua/bán đầy đủ, chi tiết</li>
                        <li><span class="about-showcase__check">✓</span> Ví tiền ảo quản lý dòng tiền</li>
                    </ul>
                </div>
                <div class="about-showcase__visual">
                    <div class="about-mock-portfolio">
                        <div class="about-mock-header">
                            <span class="about-mock-dot about-mock-dot--red"></span>
                            <span class="about-mock-dot about-mock-dot--yellow"></span>
                            <span class="about-mock-dot about-mock-dot--green"></span>
                            <span class="about-mock-title">Danh mục của tôi</span>
                        </div>
                        <div class="about-mock-portfolio__table">
                            <div class="about-mock-portfolio__th">
                                <span>Mã CK</span><span>Giá TB</span><span>Hiện tại</span><span>P&L</span>
                            </div>
                            <div class="about-mock-portfolio__row">
                                <span class="about-mock-portfolio__code">VNM</span>
                                <span class="about-mock-portfolio__price">76,000</span>
                                <span class="about-mock-portfolio__cur">86,500</span>
                                <span class="about-mock-portfolio__pnl about-mock-portfolio__pnl--up">+13.8%</span>
                            </div>
                            <div class="about-mock-portfolio__row">
                                <span class="about-mock-portfolio__code">FPT</span>
                                <span class="about-mock-portfolio__price">115,000</span>
                                <span class="about-mock-portfolio__cur">128,000</span>
                                <span class="about-mock-portfolio__pnl about-mock-portfolio__pnl--up">+11.3%</span>
                            </div>
                            <div class="about-mock-portfolio__row">
                                <span class="about-mock-portfolio__code">HPG</span>
                                <span class="about-mock-portfolio__price">28,500</span>
                                <span class="about-mock-portfolio__cur">26,200</span>
                                <span class="about-mock-portfolio__pnl about-mock-portfolio__pnl--down">-8.1%</span>
                            </div>
                            <div class="about-mock-portfolio__row">
                                <span class="about-mock-portfolio__code">MWG</span>
                                <span class="about-mock-portfolio__price">62,000</span>
                                <span class="about-mock-portfolio__cur">71,500</span>
                                <span class="about-mock-portfolio__pnl about-mock-portfolio__pnl--up">+15.3%</span>
                            </div>
                        </div>
                        <div class="about-mock-portfolio__footer">
                            <span>Tổng P&L hôm nay</span>
                            <span class="about-mock-portfolio__total">+12.4% &nbsp;▲ 24,800,000₫</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SHOWCASE 2: Email Alert ─── --}}
    <section class="about-showcase-section about-showcase-section--alt">
        <div class="about-showcase-section__inner">
            <div class="about-showcase about-showcase--rtl">
                <div class="about-showcase__visual">
                    <div class="about-mock-alert">
                        <div class="about-mock-header">
                            <span class="about-mock-dot about-mock-dot--red"></span>
                            <span class="about-mock-dot about-mock-dot--yellow"></span>
                            <span class="about-mock-dot about-mock-dot--green"></span>
                            <span class="about-mock-title">Hộp thư đến</span>
                        </div>
                        <div class="about-mock-alert__email">
                            <div class="about-mock-alert__from">
                                <div class="about-mock-alert__avatar">📩</div>
                                <div>
                                    <div class="about-mock-alert__sender">Invest App</div>
                                    <div class="about-mock-alert__time">vừa xong</div>
                                </div>
                                <div class="about-mock-alert__badge">Mới</div>
                            </div>
                            <div class="about-mock-alert__subject">🔔 Cảnh báo giá — VNM đạt ngưỡng MUA</div>
                            <div class="about-mock-alert__body">
                                <div class="about-mock-alert__row">
                                    <span>Mã cổ phiếu</span><strong>VNM</strong>
                                </div>
                                <div class="about-mock-alert__row">
                                    <span>Giá hiện tại</span><strong class="about-mock-alert__price-up">86,500₫ ▲</strong>
                                </div>
                                <div class="about-mock-alert__row">
                                    <span>Giá mục tiêu</span><strong>85,000₫</strong>
                                </div>
                                <div class="about-mock-alert__cta">Xem danh mục ngay →</div>
                            </div>
                        </div>
                        <div class="about-mock-alert__email about-mock-alert__email--dim">
                            <div class="about-mock-alert__from">
                                <div class="about-mock-alert__avatar">📊</div>
                                <div>
                                    <div class="about-mock-alert__sender">Invest App</div>
                                    <div class="about-mock-alert__time">hôm qua 09:00</div>
                                </div>
                            </div>
                            <div class="about-mock-alert__subject">📈 Tổng hợp danh mục theo dõi hôm nay</div>
                        </div>
                        <div class="about-mock-alert__email about-mock-alert__email--dim">
                            <div class="about-mock-alert__from">
                                <div class="about-mock-alert__avatar">💡</div>
                                <div>
                                    <div class="about-mock-alert__sender">Invest App</div>
                                    <div class="about-mock-alert__time">2 ngày trước</div>
                                </div>
                            </div>
                            <div class="about-mock-alert__subject">💰 Gợi ý mua hôm nay: FPT, TCB, VNM</div>
                        </div>
                    </div>
                </div>
                <div class="about-showcase__text">
                    <span class="about-showcase__tag about-showcase__tag--purple">Cảnh báo tự động</span>
                    <h2 class="about-showcase__title">Không bỏ lỡ cơ hội vì <span class="about-showcase__hl about-showcase__hl--purple">email tự động</span></h2>
                    <p class="about-showcase__desc">
                        Đặt giá mục tiêu mua và bán cho từng mã. Hệ thống theo dõi và gửi email ngay khi giá chạm ngưỡng — kể cả khi bạn không mở app.
                    </p>
                    <ul class="about-showcase__bullets">
                        <li><span class="about-showcase__check about-showcase__check--purple">✓</span> Cảnh báo khi giá ≤ ngưỡng mua đặt trước</li>
                        <li><span class="about-showcase__check about-showcase__check--purple">✓</span> Cảnh báo khi giá ≥ ngưỡng bán đặt trước</li>
                        <li><span class="about-showcase__check about-showcase__check--purple">✓</span> Email tổng hợp danh mục theo dõi hàng ngày</li>
                        <li><span class="about-showcase__check about-showcase__check--purple">✓</span> Bật/tắt từng loại thông báo riêng biệt</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SHOWCASE 3: Performance Chart ─── --}}
    <section class="about-showcase-section">
        <div class="about-showcase-section__inner">
            <div class="about-showcase about-showcase--ltr">
                <div class="about-showcase__text">
                    <span class="about-showcase__tag about-showcase__tag--emerald">Phân tích hiệu suất</span>
                    <h2 class="about-showcase__title">Xem <span class="about-showcase__hl about-showcase__hl--emerald">P&L · ROI</span> theo thời gian thực</h2>
                    <p class="about-showcase__desc">
                        Bảng phân tích hiệu suất so sánh giá mua bình quân với giá thị trường hiện tại, tính P&amp;L và ROI chính xác theo từng mã và tổng danh mục.
                    </p>
                    <ul class="about-showcase__bullets">
                        <li><span class="about-showcase__check about-showcase__check--emerald">✓</span> So sánh giá mua TB vs giá thị trường</li>
                        <li><span class="about-showcase__check about-showcase__check--emerald">✓</span> Tổng P&amp;L và ROI toàn danh mục</li>
                        <li><span class="about-showcase__check about-showcase__check--emerald">✓</span> Biểu đồ lãi/lỗ trực quan theo từng mã</li>
                        <li><span class="about-showcase__check about-showcase__check--emerald">✓</span> Lịch sử giao dịch mua/bán đầy đủ</li>
                    </ul>
                </div>
                <div class="about-showcase__visual">
                    <div class="about-mock-chart">
                        <div class="about-mock-header">
                            <span class="about-mock-dot about-mock-dot--red"></span>
                            <span class="about-mock-dot about-mock-dot--yellow"></span>
                            <span class="about-mock-dot about-mock-dot--green"></span>
                            <span class="about-mock-title">Hiệu suất đầu tư</span>
                        </div>
                        <div class="about-mock-chart__summary">
                            <div class="about-mock-chart__kpi">
                                <span class="about-mock-chart__kpi-label">Tổng P&L</span>
                                <span class="about-mock-chart__kpi-value about-mock-chart__kpi-value--up">+24,800,000₫</span>
                            </div>
                            <div class="about-mock-chart__kpi">
                                <span class="about-mock-chart__kpi-label">ROI</span>
                                <span class="about-mock-chart__kpi-value about-mock-chart__kpi-value--up">+12.4%</span>
                            </div>
                        </div>
                        <div class="about-mock-chart__bars">
                            <div class="about-mock-chart__bar-group">
                                <div class="about-mock-chart__bar about-mock-chart__bar--up" style="height:75%"></div>
                                <span class="about-mock-chart__bar-label">VNM</span>
                            </div>
                            <div class="about-mock-chart__bar-group">
                                <div class="about-mock-chart__bar about-mock-chart__bar--up" style="height:58%"></div>
                                <span class="about-mock-chart__bar-label">FPT</span>
                            </div>
                            <div class="about-mock-chart__bar-group">
                                <div class="about-mock-chart__bar about-mock-chart__bar--down" style="height:35%"></div>
                                <span class="about-mock-chart__bar-label">HPG</span>
                            </div>
                            <div class="about-mock-chart__bar-group">
                                <div class="about-mock-chart__bar about-mock-chart__bar--up" style="height:88%"></div>
                                <span class="about-mock-chart__bar-label">MWG</span>
                            </div>
                            <div class="about-mock-chart__bar-group">
                                <div class="about-mock-chart__bar about-mock-chart__bar--up" style="height:45%"></div>
                                <span class="about-mock-chart__bar-label">TCB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SHOWCASE 4: Expert Suggest ─── --}}
    <section class="about-showcase-section about-showcase-section--alt">
        <div class="about-showcase-section__inner">
            <div class="about-showcase about-showcase--rtl">
                <div class="about-showcase__visual">
                    <div class="about-mock-suggest">
                        <div class="about-mock-header">
                            <span class="about-mock-dot about-mock-dot--red"></span>
                            <span class="about-mock-dot about-mock-dot--yellow"></span>
                            <span class="about-mock-dot about-mock-dot--green"></span>
                            <span class="about-mock-title">💰 Gợi ý mua hôm nay</span>
                        </div>
                        <div class="about-mock-suggest__list">
                            <div class="about-mock-suggest__item">
                                <div class="about-mock-suggest__code-wrap">
                                    <span class="about-mock-suggest__code">VNM</span>
                                    <span class="about-mock-suggest__name">Vinamilk</span>
                                </div>
                                <div class="about-mock-suggest__right">
                                    <span class="about-mock-suggest__price">86,500₫</span>
                                    <span class="about-mock-suggest__badge about-mock-suggest__badge--hot">-8.2%</span>
                                </div>
                            </div>
                            <div class="about-mock-suggest__item">
                                <div class="about-mock-suggest__code-wrap">
                                    <span class="about-mock-suggest__code">FPT</span>
                                    <span class="about-mock-suggest__name">FPT Corp</span>
                                </div>
                                <div class="about-mock-suggest__right">
                                    <span class="about-mock-suggest__price">128,000₫</span>
                                    <span class="about-mock-suggest__badge about-mock-suggest__badge--hot">-5.1%</span>
                                </div>
                            </div>
                            <div class="about-mock-suggest__item">
                                <div class="about-mock-suggest__code-wrap">
                                    <span class="about-mock-suggest__code">TCB</span>
                                    <span class="about-mock-suggest__name">Techcombank</span>
                                </div>
                                <div class="about-mock-suggest__right">
                                    <span class="about-mock-suggest__price">54,300₫</span>
                                    <span class="about-mock-suggest__badge about-mock-suggest__badge--warm">-2.8%</span>
                                </div>
                            </div>
                            <div class="about-mock-suggest__footer">
                                <span>💡 Định giá dưới giá mua tốt → cơ hội tích lũy</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-showcase__text">
                    <span class="about-showcase__tag about-showcase__tag--amber">Gợi ý từ chuyên gia</span>
                    <h2 class="about-showcase__title">Gợi ý mua từ <span class="about-showcase__hl about-showcase__hl--amber">chuyên gia</span> mỗi ngày</h2>
                    <p class="about-showcase__desc">
                        Admin theo dõi và cập nhật danh sách mã cổ phiếu tiềm năng hàng ngày. Hệ thống tự động gợi ý khi giá thị trường thấp hơn giá mua tốt được đề xuất.
                    </p>
                    <ul class="about-showcase__bullets">
                        <li><span class="about-showcase__check about-showcase__check--amber">✓</span> Danh sách mã chuyên gia liên tục theo dõi</li>
                        <li><span class="about-showcase__check about-showcase__check--amber">✓</span> Gợi ý mua tự động theo định giá thực tế</li>
                        <li><span class="about-showcase__check about-showcase__check--amber">✓</span> Popup gợi ý thông minh khi đăng nhập</li>
                        <li><span class="about-showcase__check about-showcase__check--amber">✓</span> Thêm vào danh sách theo dõi chỉ 1 click</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── HOW IT WORKS ─── --}}
    <section class="about-section">
        <div class="about-section__inner">
            <p class="about-section__label">Cách hoạt động</p>
            <h2 class="about-section__title">Bắt đầu chỉ trong 3 bước</h2>
            <p class="about-section__sub">Không cần kiến thức kỹ thuật. Chỉ cần đăng ký và bắt đầu theo dõi.</p>

            <div class="about-steps">
                <div class="about-step">
                    <div class="about-step__number">1</div>
                    <h3 class="about-step__title">Đăng ký tài khoản</h3>
                    <p class="about-step__desc">Tạo tài khoản miễn phí bằng email. Xác thực và đăng nhập ngay lập tức.</p>
                </div>
                <div class="about-step">
                    <div class="about-step__number">2</div>
                    <h3 class="about-step__title">Thêm cổ phiếu</h3>
                    <p class="about-step__desc">Thêm các mã bạn đã mua vào danh mục. Hệ thống tự động tính P&L theo giá thị trường.</p>
                </div>
                <div class="about-step">
                    <div class="about-step__number">3</div>
                    <h3 class="about-step__title">Theo dõi & nhận thông báo</h3>
                    <p class="about-step__desc">Đặt cảnh báo giá và nhận email tự động. Không cần ngồi canh bảng điện.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── FEATURE CARDS ─── --}}
    <section class="about-section about-section--alt">
        <div class="about-section__inner">
            <p class="about-section__label">Tính năng</p>
            <h2 class="about-section__title">Mọi thứ bạn cần để đầu tư hiệu quả</h2>
            <p class="about-section__sub">Được thiết kế dành riêng cho nhà đầu tư cá nhân tại Việt Nam, đơn giản và đầy đủ.</p>

            <div class="about-features-grid">
                <div class="about-feature-card about-feature-card--sky">
                    <span class="about-feature-card__icon">📊</span>
                    <h3 class="about-feature-card__title">Theo dõi danh mục FIFO</h3>
                    <p class="about-feature-card__desc">Quản lý từng lô mua theo phương pháp FIFO (nhập trước xuất trước). Tính toán P&L và ROI chính xác cho từng mã cổ phiếu.</p>
                </div>
                <div class="about-feature-card about-feature-card--purple">
                    <span class="about-feature-card__icon">🔔</span>
                    <h3 class="about-feature-card__title">Cảnh báo giá tự động</h3>
                    <p class="about-feature-card__desc">Đặt giá mục tiêu mua và bán cho từng mã. Hệ thống tự động gửi email khi giá chạm ngưỡng, giúp bạn không bỏ lỡ cơ hội.</p>
                </div>
                <div class="about-feature-card about-feature-card--amber">
                    <span class="about-feature-card__icon">💡</span>
                    <h3 class="about-feature-card__title">Gợi ý từ chuyên gia</h3>
                    <p class="about-feature-card__desc">Danh sách mã cổ phiếu được chuyên gia theo dõi và đề xuất. Gợi ý mua hôm nay dựa trên mức định giá thực tế của từng mã.</p>
                </div>
                <div class="about-feature-card about-feature-card--emerald">
                    <span class="about-feature-card__icon">📈</span>
                    <h3 class="about-feature-card__title">Phân tích hiệu suất</h3>
                    <p class="about-feature-card__desc">Xem toàn bộ lịch sử mua bán, tính P&L, ROI và so sánh giá mua trung bình với giá thị trường cập nhật theo thời gian thực.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── CTA ─── --}}
    <section class="about-cta-section">
        <div class="about-cta-section__orb about-cta-section__orb--1"></div>
        <div class="about-cta-section__orb about-cta-section__orb--2"></div>
        <div class="about-cta-section__inner">
            <div class="about-cta-section__badge">✨ Miễn phí hoàn toàn</div>
            <h2 class="about-cta-section__title">Sẵn sàng bắt đầu hành trình đầu tư?</h2>
            <p class="about-cta-section__sub">Không cần thẻ tín dụng. Không giới hạn tính năng. Bắt đầu ngay hôm nay.</p>
            <div class="about-cta-section__btns">
                <a href="{{ route('register') }}" class="about-cta-section__btn">Tạo tài khoản ngay →</a>
                <a href="{{ route('contact') }}" class="about-cta-section__btn-ghost">Liên hệ hỗ trợ</a>
            </div>
        </div>
    </section>

</main>
@endsection
