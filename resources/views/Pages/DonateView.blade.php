@extends('Layout.Layout')

@section('title', 'Ủng hộ dự án — Quản lý đầu tư cá nhân')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('seo')
    <meta name="robots" content="noindex, follow">
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/donate.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
    @vite('resources/js/pages/donate.js')
@endsection

@section('actions-left')
    @auth
        @include('partials.user-nav-primary')
    @else
        @include('partials.guest-nav-actions')
    @endauth
@endsection

@section('user-body-content')
<script>
    window.__donateData = {
        accountNumber: '02319798401',
        bank: 'TPBank',
        donateContent: @json($email ? $email . ' donate' : 'Nguoi dung donate'),
    };
</script>

<main class="donate-page-main">

    {{-- ─── HERO ─── --}}
    <section class="donate-hero">
        <div class="donate-hero__orb donate-hero__orb--1"></div>
        <div class="donate-hero__orb donate-hero__orb--2"></div>
        <div class="donate-hero__icon" aria-hidden="true">💖</div>
        <h1 class="donate-hero__title">Ủng hộ dự án</h1>
        <p class="donate-hero__sub">Cộng đồng đầu tư thông minh Việt Nam</p>
    </section>

    {{-- ─── CARDS ─── --}}
    <section class="donate-cards" aria-label="Giới thiệu">
        <div class="donate-card">
            <div class="donate-card__icon" aria-hidden="true">👥</div>
            <h2 class="donate-card__title">Đội ngũ tâm huyết</h2>
            <p class="donate-card__body">
                Chúng tôi là tập hợp những người đam mê từ lĩnh vực Công nghệ thông tin đến
                các chuyên gia đầu tư chứng khoán giàu kinh nghiệm. Không ngừng nâng cấp
                bản thân và tìm ra những hướng đi để tạo ra các khoản đầu tư bền vững và an toàn.
            </p>
        </div>

        <div class="donate-card">
            <div class="donate-card__icon" aria-hidden="true">🔬</div>
            <h2 class="donate-card__title">Phân tích bài bản</h2>
            <p class="donate-card__body">
                Hệ thống được xây dựng dựa trên phân tích chuyên sâu: báo cáo tài chính,
                báo cáo doanh nghiệp, quy mô công ty. Kết hợp phân tích kỹ thuật dòng tiền
                và biến động giá, đội ngũ luôn nhận diện cơ hội sớm nhất và cập nhật
                thường xuyên đến người dùng.
            </p>
        </div>

        <div class="donate-card">
            <div class="donate-card__icon" aria-hidden="true">🧘</div>
            <h2 class="donate-card__title">Đầu tư không áp lực</h2>
            <p class="donate-card__body">
                Bạn không cần giao dịch thường xuyên, không cần xem bảng điện hàng ngày
                ảnh hưởng đến công việc. Chúng tôi hướng đến đầu tư dài hạn từ những
                khoản tiền nhàn rỗi — an toàn, biên lợi nhuận tốt và không đòi hỏi
                kiến thức chuyên sâu về tài chính.
            </p>
        </div>

        <div class="donate-card donate-card--highlight">
            <div class="donate-card__icon" aria-hidden="true">✨</div>
            <h2 class="donate-card__title">Dịch vụ miễn phí</h2>
            <p class="donate-card__body">
                Chúng tôi giám sát thị trường thay bạn, cung cấp những mã cổ phiếu tốt nhất
                và thời điểm mua phù hợp nhất. Bạn không cần biết quá nhiều về đầu tư —
                chúng tôi ở đây để làm điều đó.
            </p>
            <p class="donate-card__free">Hoàn toàn miễn phí</p>
        </div>
    </section>

    {{-- ─── CTA ─── --}}
    <section class="donate-cta">
        <div class="donate-cta__inner">
            <h2 class="donate-cta__title">Bạn đã nhận được giá trị từ hệ thống?</h2>
            <p class="donate-cta__body">
                Chúng tôi đã và đang cống hiến rất nhiều để dự án này phục vụ cộng đồng một cách tốt nhất.
                Nếu hệ thống đã giúp bạn đạt được lợi nhuận tốt hơn, hãy ủng hộ chúng tôi
                một khoản nhỏ để duy trì và tiếp tục phát triển dự án.
            </p>
            <button type="button" class="donate-btn" onclick="window.openDonateModal()">
                💖 Donate ngay
            </button>
        </div>
    </section>

</main>

{{-- ─── MODAL QR ─── --}}
<div id="donateModal" class="donate-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="donateModalTitle">
    <div class="donate-modal-content">
        <button type="button" class="donate-modal-close" onclick="window.closeDonateModal()" aria-label="Đóng">&times;</button>
        <h2 id="donateModalTitle" class="donate-modal-title">💖 Thông tin chuyển khoản</h2>

        <div class="donate-modal-info">
            <div class="donate-modal-row">
                <span class="donate-modal-label">Ngân hàng</span>
                <span class="donate-modal-value">TPBank</span>
            </div>
            <div class="donate-modal-row">
                <span class="donate-modal-label">Chủ tài khoản</span>
                <span class="donate-modal-value">LE HUU PHUOC</span>
            </div>
            <div class="donate-modal-row">
                <span class="donate-modal-label">Số tài khoản</span>
                <span class="donate-modal-value">
                    <span id="donate-stk">02319798401</span>
                    <button type="button" class="donate-copy-btn" onclick="window.copyDonateText('02319798401', this)" aria-label="Copy số tài khoản">📋</button>
                </span>
            </div>
            <div class="donate-modal-row">
                <span class="donate-modal-label">Nội dung</span>
                <span class="donate-modal-value">
                    <span id="donate-content-text"></span>
                    <button type="button" class="donate-copy-btn" id="donate-copy-content-btn" aria-label="Copy nội dung">📋</button>
                </span>
            </div>
        </div>

        <div class="donate-qr-wrap">
            <img id="donate-qr-img" src="" alt="QR chuyển khoản TPBank" class="donate-qr-img" loading="lazy">
        </div>

        <p class="donate-modal-note">
            🙏 Mọi ủng hộ đều được trân trọng. Không bắt buộc số tiền.
        </p>
    </div>
</div>
@endsection
