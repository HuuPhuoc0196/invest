@extends('Layout.Layout')

@section('title', 'Liên hệ — Quản lý đầu tư cá nhân')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => 'Liên hệ — ' . config('app.name'),
        'description' => 'Liên hệ với chúng tôi qua form, email hoặc số điện thoại. Chúng tôi sẵn sàng hỗ trợ bạn.',
    ])
    <script type="application/ld+json">
    {!! json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => 'Ứng dụng có miễn phí không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Có, hoàn toàn miễn phí. Bạn chỉ cần đăng ký tài khoản bằng email là có thể sử dụng tất cả tính năng.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Dữ liệu giá cổ phiếu từ đâu?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Giá cổ phiếu được đồng bộ tự động từ dịch vụ dữ liệu thị trường chứng khoán Việt Nam và cập nhật theo lịch giao dịch.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Tôi quên mật khẩu thì làm gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Dùng tính năng Quên mật khẩu trên trang đăng nhập. Hệ thống sẽ gửi link đặt lại mật khẩu về email của bạn.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Làm sao để nhận cảnh báo giá?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Sau khi đăng nhập, vào mục Theo dõi, thêm mã cổ phiếu và đặt giá mục tiêu mua/bán. Hệ thống sẽ gửi email tự động khi giá chạm ngưỡng.',
                ],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/contact.css')
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
<div class="contact-page">

    {{-- ─── HEADER ─── --}}
    <div class="contact-header">
        <p class="contact-header__label">Liên hệ</p>
        <h1 class="contact-header__title">Chúng tôi lắng nghe bạn</h1>
        <p class="contact-header__sub">
            Có câu hỏi hoặc cần hỗ trợ? Hãy gửi tin nhắn — chúng tôi sẽ phản hồi sớm nhất có thể.
        </p>
    </div>

    <div class="contact-layout">

        {{-- ─── LEFT: INFO + FAQ ─── --}}
        <div class="contact-info-panel">
            <p class="contact-info-panel__title">Thông tin liên hệ</p>

            <div class="contact-info-item">
                <div class="contact-info-item__ico">📧</div>
                <div class="contact-info-item__body">
                    <div class="contact-info-item__label">Email</div>
                    <a href="mailto:lehuuphuoc0196@gmail.com" class="contact-info-item__value">lehuuphuoc0196@gmail.com</a>
                </div>
            </div>

            <div class="contact-info-item">
                <div class="contact-info-item__ico">📞</div>
                <div class="contact-info-item__body">
                    <div class="contact-info-item__label">Điện thoại</div>
                    <a href="tel:+84382834597" class="contact-info-item__value">0382 834 597</a>
                </div>
            </div>

            <div class="contact-info-item">
                <div class="contact-info-item__ico">🕐</div>
                <div class="contact-info-item__body">
                    <div class="contact-info-item__label">Thời gian phản hồi</div>
                    <span class="contact-info-item__value">Thường trong vòng 24 giờ</span>
                </div>
            </div>

            {{-- FAQ --}}
            <div class="contact-faq">
                <p class="contact-faq__title">Câu hỏi thường gặp</p>

                <div class="contact-faq-item">
                    <div class="contact-faq-item__q" role="button" tabindex="0">
                        <span>Ứng dụng có miễn phí không?</span>
                        <span class="contact-faq-item__chevron">▼</span>
                    </div>
                    <div class="contact-faq-item__a">
                        Có, hoàn toàn miễn phí. Bạn chỉ cần đăng ký tài khoản bằng email là có thể sử dụng tất cả tính năng.
                    </div>
                </div>

                <div class="contact-faq-item">
                    <div class="contact-faq-item__q" role="button" tabindex="0">
                        <span>Dữ liệu giá cổ phiếu từ đâu?</span>
                        <span class="contact-faq-item__chevron">▼</span>
                    </div>
                    <div class="contact-faq-item__a">
                        Giá cổ phiếu được đồng bộ tự động từ dịch vụ dữ liệu thị trường chứng khoán Việt Nam và cập nhật theo lịch giao dịch.
                    </div>
                </div>

                <div class="contact-faq-item">
                    <div class="contact-faq-item__q" role="button" tabindex="0">
                        <span>Tôi quên mật khẩu thì làm gì?</span>
                        <span class="contact-faq-item__chevron">▼</span>
                    </div>
                    <div class="contact-faq-item__a">
                        Dùng tính năng <a href="{{ route('forgotPassword') }}" style="color:var(--inv-accent)">Quên mật khẩu</a> trên trang đăng nhập. Hệ thống sẽ gửi link đặt lại mật khẩu về email của bạn.
                    </div>
                </div>

                <div class="contact-faq-item">
                    <div class="contact-faq-item__q" role="button" tabindex="0">
                        <span>Làm sao để nhận cảnh báo giá?</span>
                        <span class="contact-faq-item__chevron">▼</span>
                    </div>
                    <div class="contact-faq-item__a">
                        Sau khi đăng nhập, vào mục <strong>Theo dõi</strong>, thêm mã cổ phiếu và đặt giá mục tiêu mua/bán. Hệ thống sẽ gửi email tự động khi giá chạm ngưỡng.
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── RIGHT: FORM ─── --}}
        <div class="contact-form-panel">
            <p class="contact-form-panel__title">Gửi tin nhắn cho chúng tôi</p>

            <form id="contact-form" novalidate>
                <div class="contact-form-row">
                    <div class="contact-form-group">
                        <label for="cf-name">Họ tên <span class="req">*</span></label>
                        <input type="text" id="cf-name" name="name" placeholder="Nguyễn Văn A" autocomplete="name">
                        <div class="contact-form-group__error" data-error="name"></div>
                    </div>
                    <div class="contact-form-group">
                        <label for="cf-email">Email <span class="req">*</span></label>
                        <input type="email" id="cf-email" name="email" placeholder="email@example.com" autocomplete="email">
                        <div class="contact-form-group__error" data-error="email"></div>
                    </div>
                </div>

                <div class="contact-form-group">
                    <label for="cf-subject">Tiêu đề <span class="req">*</span></label>
                    <input type="text" id="cf-subject" name="subject" placeholder="Tôi cần hỗ trợ về...">
                    <div class="contact-form-group__error" data-error="subject"></div>
                </div>

                <div class="contact-form-group">
                    <label for="cf-message">Nội dung <span class="req">*</span></label>
                    <textarea id="cf-message" name="message" rows="6" placeholder="Mô tả chi tiết vấn đề hoặc câu hỏi của bạn..."></textarea>
                    <div class="contact-form-group__error" data-error="message"></div>
                </div>

                <div class="contact-form-submit">
                    <button type="submit" class="contact-form-submit-btn">
                        <span class="contact-form-submit-btn__text">Gửi tin nhắn</span>
                        <span>→</span>
                    </button>
                </div>

                <div class="contact-form-feedback"></div>
            </form>
        </div>

    </div>
</div>
@endsection

@section('user-script')
    <script>
        window.__contactRoute = "{{ route('contact') }}";
    </script>
    @vite('resources/js/pages/contact.js')
@endsection
