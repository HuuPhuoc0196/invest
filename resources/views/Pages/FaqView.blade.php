@extends('Layout.Layout')

@section('title', 'Hỏi đáp thường gặp — Quản lý đầu tư cá nhân')

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => 'Hỏi đáp thường gặp — ' . config('app.name'),
        'description' => 'Câu hỏi thường gặp về nền tảng Quản lý đầu tư cá nhân: tài khoản, cổ phiếu, FIFO, cảnh báo giá email, bảo mật và hơn thế nữa.',
    ])
    <script type="application/ld+json">
    {!! json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => 'Ứng dụng này là gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Đây là công cụ quản lý danh mục cổ phiếu ảo, giúp nhà đầu tư cá nhân theo dõi danh mục, tính toán P&L/ROI theo phương pháp FIFO và nhận cảnh báo giá email tự động. Đây không phải sàn giao dịch thực và hoàn toàn miễn phí.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Dữ liệu giá cổ phiếu từ đâu?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Giá cổ phiếu được đồng bộ tự động từ dịch vụ dữ liệu thị trường chứng khoán Việt Nam (HOSE/HNX) và cập nhật theo lịch giao dịch hàng ngày.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Ứng dụng có hỗ trợ giao dịch thật không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Không. Đây là công cụ mô phỏng để học hỏi và luyện tập quản lý danh mục. Mọi giao dịch đều là ảo, không liên kết với bất kỳ tài khoản chứng khoán thực nào.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Ứng dụng có phí không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Hoàn toàn miễn phí. Bạn có thể sử dụng tất cả tính năng mà không mất bất kỳ chi phí nào.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Làm sao đăng ký tài khoản?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Truy cập trang đăng ký, nhập họ tên, địa chỉ email và mật khẩu. Sau đó xác thực email qua link được gửi vào hộp thư. Tài khoản sẽ được kích hoạt ngay sau khi xác thực.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Quên mật khẩu phải làm gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Sử dụng tính năng Quên mật khẩu trên trang đăng nhập. Nhập địa chỉ email đã đăng ký và hệ thống sẽ gửi link đặt lại mật khẩu vào hộp thư của bạn.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Có thể đổi email không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Hiện tại chức năng đổi email chưa được hỗ trợ trực tiếp trên nền tảng. Vui lòng liên hệ quản trị viên để được hỗ trợ.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Tài khoản bị khóa phải làm gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Nếu tài khoản của bạn bị khóa, vui lòng liên hệ chúng tôi qua form liên hệ hoặc email để được hỗ trợ mở khóa.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Mức rủi ro 1, 2, 3, 4 nghĩa là gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Mức rủi ro phản ánh tình trạng hoạt động của cổ phiếu theo quy định của HoSE/HNX: 1 = An toàn (giao dịch bình thường), 2 = Cảnh báo (có dấu hiệu cần chú ý), 3 = Hạn chế (bị hạn chế giao dịch), 4 = Đình chỉ (tạm ngừng giao dịch).',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'FIFO là gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'FIFO là viết tắt của First In First Out — nhập trước xuất trước. Khi bán cổ phiếu, hệ thống tự động trừ từ lô mua cũ nhất (theo ngày mua) trước tiên, đảm bảo tính P&L chính xác theo chuẩn kế toán.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'VN30 và VN100 là gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'VN30 là rổ 30 mã cổ phiếu có vốn hóa và thanh khoản lớn nhất trên HOSE, dùng làm chỉ số tham chiếu. VN100 gồm 100 mã hàng đầu, trong đó bao gồm cả 30 mã của VN30. Đây là các chỉ số thị trường quan trọng của chứng khoán Việt Nam.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Giá khuyến nghị mua/bán được tính thế nào?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Giá khuyến nghị được tính dựa trên phân tích trung bình giá lịch sử trong 1008 phiên giao dịch, kết hợp với mức rủi ro hiện tại và điểm đánh giá tổng thể của mã cổ phiếu. Đây chỉ là con số tham khảo, không phải lời khuyên đầu tư.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Làm sao để nhận cảnh báo giá?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Sau khi đăng nhập, vào mục Theo dõi và thêm mã cổ phiếu bạn muốn theo dõi. Đặt giá mục tiêu mua và/hoặc bán, sau đó bật thông báo email. Hệ thống sẽ tự động gửi cảnh báo khi giá chạm ngưỡng bạn đã đặt.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Tôi không nhận được email xác thực phải làm gì?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Trước tiên hãy kiểm tra thư mục Spam/Junk trong hộp thư. Nếu không thấy, thử đăng nhập lại bằng email và mật khẩu đã đăng ký — hệ thống sẽ tự động gửi lại email xác thực. Nếu vẫn không nhận được, liên hệ hỗ trợ.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Cảnh báo giá được gửi vào lúc nào?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Email cảnh báo giá theo dõi được gửi mỗi sáng trước giờ mở cửa phiên giao dịch, từ Thứ Hai đến Thứ Sáu. Bạn cũng nhận được email tổng hợp danh mục theo dõi hàng ngày.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Mật khẩu có được mã hóa không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Có. Mật khẩu được mã hóa bằng thuật toán bcrypt — một chuẩn mã hóa an toàn được sử dụng rộng rãi. Chúng tôi không thể đọc hay khôi phục mật khẩu gốc của bạn.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Ứng dụng có chia sẻ dữ liệu của tôi không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Không. Chúng tôi không bán, không trao đổi và không chia sẻ dữ liệu cá nhân của bạn với bất kỳ bên thứ ba nào. Chi tiết xem tại Chính sách bảo mật.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => 'Xóa tài khoản thì dữ liệu của tôi sẽ ra sao?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Khi tài khoản bị xóa, toàn bộ dữ liệu liên quan sẽ bị xóa vĩnh viễn và không thể khôi phục, bao gồm: danh mục đầu tư, lịch sử giao dịch mua bán, danh sách theo dõi, ví tiền ảo và lịch sử nạp/rút. Hãy liên hệ quản trị viên để thực hiện yêu cầu.',
                ],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/faq-page.css')
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
<div class="faq-page">

    {{-- ─── HERO ─── --}}
    <div class="faq-hero">
        <div class="faq-hero__icon" aria-hidden="true">❓</div>
        <h1 class="faq-hero__title">Hỏi đáp thường gặp</h1>
        <p class="faq-hero__sub">
            Tìm câu trả lời cho những thắc mắc phổ biến nhất về nền tảng. Không thấy câu hỏi của mình?
            <a href="{{ route('contact') }}" class="faq-hero__contact-link">Liên hệ chúng tôi</a>.
        </p>
    </div>

    {{-- ─── GROUP 1: Về ứng dụng ─── --}}
    <div class="faq-group">
        <h2 class="faq-group__title">Về ứng dụng</h2>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Ứng dụng này là gì?</h3>
            </summary>
            <div class="faq-answer">
                Đây là công cụ quản lý danh mục cổ phiếu ảo, giúp nhà đầu tư cá nhân theo dõi danh mục, tính toán P&amp;L và ROI theo phương pháp FIFO, và nhận cảnh báo giá email tự động. Đây <strong>không phải</strong> sàn giao dịch thực và hoàn toàn <strong>miễn phí</strong>.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Dữ liệu giá cổ phiếu từ đâu?</h3>
            </summary>
            <div class="faq-answer">
                Giá cổ phiếu được đồng bộ tự động từ dịch vụ dữ liệu thị trường chứng khoán Việt Nam (HOSE/HNX) và cập nhật theo lịch giao dịch hàng ngày (Thứ Hai đến Thứ Sáu).
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Ứng dụng có hỗ trợ giao dịch thật không?</h3>
            </summary>
            <div class="faq-answer">
                <strong>Không.</strong> Đây là công cụ mô phỏng để học hỏi và luyện tập quản lý danh mục. Mọi giao dịch — mua, bán, nạp/rút tiền — đều là <strong>ảo</strong> và không liên kết với bất kỳ tài khoản chứng khoán thực nào. Tiền trong ví ảo không có giá trị thực tế.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Ứng dụng có phí không?</h3>
            </summary>
            <div class="faq-answer">
                Hoàn toàn <strong>miễn phí</strong>. Bạn có thể đăng ký và sử dụng tất cả tính năng — theo dõi danh mục, cảnh báo giá email, phân tích P&amp;L/ROI, gợi ý đầu tư — mà không mất bất kỳ chi phí nào.
            </div>
        </details>
    </div>

    {{-- ─── GROUP 2: Về tài khoản ─── --}}
    <div class="faq-group">
        <h2 class="faq-group__title">Về tài khoản</h2>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Làm sao đăng ký tài khoản?</h3>
            </summary>
            <div class="faq-answer">
                Truy cập <a href="{{ route('register') }}">trang đăng ký</a>, điền họ tên, địa chỉ email và mật khẩu (tối thiểu 6 ký tự). Sau khi submit, hệ thống gửi email xác thực đến hộp thư của bạn. Nhấp vào link trong email để kích hoạt tài khoản và đăng nhập.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Quên mật khẩu phải làm gì?</h3>
            </summary>
            <div class="faq-answer">
                Sử dụng tính năng <a href="{{ route('forgotPassword') }}">Quên mật khẩu</a> trên trang đăng nhập. Nhập địa chỉ email đã đăng ký — hệ thống sẽ gửi link đặt lại mật khẩu có hiệu lực trong 60 phút về hộp thư của bạn. Kiểm tra cả thư mục Spam nếu không thấy.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Có thể đổi email không?</h3>
            </summary>
            <div class="faq-answer">
                Hiện tại chức năng đổi email chưa được hỗ trợ trực tiếp trên nền tảng. Vui lòng <a href="{{ route('contact') }}">liên hệ quản trị viên</a> để được hỗ trợ thay đổi địa chỉ email đăng ký.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Tài khoản bị khóa phải làm gì?</h3>
            </summary>
            <div class="faq-answer">
                Nếu tài khoản bị khóa hoặc bị đình chỉ, vui lòng liên hệ chúng tôi qua <a href="{{ route('contact') }}">form liên hệ</a> hoặc email <a href="mailto:lehuuphuoc0196@gmail.com">lehuuphuoc0196@gmail.com</a> để được hỗ trợ mở khóa và giải thích lý do.
            </div>
        </details>
    </div>

    {{-- ─── GROUP 3: Về cổ phiếu & đầu tư ─── --}}
    <div class="faq-group">
        <h2 class="faq-group__title">Về cổ phiếu &amp; đầu tư</h2>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Mức rủi ro 1, 2, 3, 4 nghĩa là gì?</h3>
            </summary>
            <div class="faq-answer">
                Mức rủi ro phản ánh tình trạng hoạt động của cổ phiếu theo quy định của HoSE/HNX:
                <ul style="margin-top: 8px; padding-left: 20px;">
                    <li><strong>1 — An toàn:</strong> Giao dịch bình thường, không có cảnh báo đặc biệt.</li>
                    <li><strong>2 — Cảnh báo:</strong> Có một số dấu hiệu cần chú ý (thanh khoản thấp, tài chính yếu, v.v.).</li>
                    <li><strong>3 — Hạn chế:</strong> Cổ phiếu bị sàn hạn chế giao dịch, chỉ được phép bán.</li>
                    <li><strong>4 — Đình chỉ:</strong> Tạm ngừng giao dịch theo quyết định của sàn hoặc cơ quan quản lý.</li>
                </ul>
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>FIFO là gì và tại sao dùng FIFO?</h3>
            </summary>
            <div class="faq-answer">
                <strong>FIFO</strong> (First In First Out — Nhập trước xuất trước) là phương pháp kế toán khi bán cổ phiếu: hệ thống tự động trừ từ lô mua <strong>cũ nhất</strong> (theo ngày mua) trước tiên. Phương pháp này đảm bảo tính P&amp;L chính xác và minh bạch, phù hợp với chuẩn kế toán và thực tế giao dịch chứng khoán tại Việt Nam.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>VN30 và VN100 là gì?</h3>
            </summary>
            <div class="faq-answer">
                <strong>VN30</strong> là rổ 30 mã cổ phiếu có vốn hóa và thanh khoản lớn nhất trên Sở Giao dịch Chứng khoán TP.HCM (HOSE), dùng làm chỉ số tham chiếu quan trọng của thị trường. <strong>VN100</strong> gồm 100 mã hàng đầu, bao gồm cả 30 mã của VN30. Đây là các benchmark phổ biến mà nhà đầu tư thường dùng để đánh giá hiệu suất danh mục.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Giá khuyến nghị mua/bán được tính như thế nào?</h3>
            </summary>
            <div class="faq-answer">
                Giá khuyến nghị được tính dựa trên phân tích trung bình giá lịch sử trong <strong>1008 phiên giao dịch</strong> (~4 năm), kết hợp với mức rủi ro hiện tại và điểm đánh giá tổng thể của mã. Công thức: <code>Giá khuyến nghị mua = Giá trung bình × % tùy chỉnh</code>. Đây là con số tham khảo phục vụ mô phỏng, <strong>không phải lời khuyên đầu tư thực tế</strong>.
            </div>
        </details>
    </div>

    {{-- ─── GROUP 4: Về email & thông báo ─── --}}
    <div class="faq-group">
        <h2 class="faq-group__title">Về email &amp; thông báo</h2>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Làm sao để nhận cảnh báo giá qua email?</h3>
            </summary>
            <div class="faq-answer">
                Sau khi đăng nhập, vào mục <strong>Theo dõi</strong>, thêm mã cổ phiếu và đặt giá mục tiêu mua và/hoặc bán. Bật cờ thông báo cho từng loại (Mua / Bán). Hệ thống sẽ tự động kiểm tra và gửi email cảnh báo vào buổi sáng mỗi ngày giao dịch khi giá chạm ngưỡng bạn đặt.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Tôi không nhận được email xác thực phải làm gì?</h3>
            </summary>
            <div class="faq-answer">
                Trước tiên hãy kiểm tra thư mục <strong>Spam / Junk</strong> trong hộp thư. Email xác thực đến từ địa chỉ được cấu hình trong hệ thống với tiêu đề "[Hệ thống đầu tư cá nhân] Xác nhận địa chỉ email". Nếu không thấy, thử đăng nhập lại — hệ thống sẽ tự động nhắc gửi lại email. Nếu vẫn không nhận được, vui lòng <a href="{{ route('contact') }}">liên hệ hỗ trợ</a>.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Cảnh báo giá được gửi vào lúc nào trong ngày?</h3>
            </summary>
            <div class="faq-answer">
                Email cảnh báo giá theo dõi được gửi mỗi sáng <strong>trước giờ mở cửa phiên giao dịch</strong>, từ Thứ Hai đến Thứ Sáu (ngày giao dịch). Bạn cũng nhận được email tổng hợp danh mục theo dõi hàng ngày. Vào cuối tuần và ngày lễ không có email cảnh báo.
            </div>
        </details>
    </div>

    {{-- ─── GROUP 5: Bảo mật ─── --}}
    <div class="faq-group" style="margin-bottom: 0;">
        <h2 class="faq-group__title">Bảo mật</h2>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Mật khẩu của tôi có được mã hóa không?</h3>
            </summary>
            <div class="faq-answer">
                Có. Mật khẩu được mã hóa bằng thuật toán <strong>bcrypt</strong> — một chuẩn mã hóa một chiều an toàn. Chúng tôi không thể đọc hay khôi phục mật khẩu gốc của bạn. Nếu quên mật khẩu, bạn phải sử dụng chức năng đặt lại mật khẩu.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Ứng dụng có chia sẻ dữ liệu của tôi cho bên thứ ba không?</h3>
            </summary>
            <div class="faq-answer">
                Không. Chúng tôi không bán, không trao đổi và không chia sẻ thông tin cá nhân của bạn với bất kỳ bên thứ ba nào. Dữ liệu chỉ được dùng để vận hành dịch vụ. Xem chi tiết tại <a href="{{ url('/chinh-sach-bao-mat') }}">Chính sách bảo mật</a>.
            </div>
        </details>

        <details class="faq-item">
            <summary class="faq-item__q">
                <h3>Xóa tài khoản thì dữ liệu của tôi sẽ ra sao?</h3>
            </summary>
            <div class="faq-answer">
                Khi tài khoản bị xóa, <strong>toàn bộ dữ liệu</strong> liên quan sẽ bị xóa vĩnh viễn và không thể khôi phục, bao gồm: danh mục FIFO, lịch sử giao dịch mua/bán, danh sách theo dõi, ví tiền ảo và lịch sử nạp/rút. Vui lòng <a href="{{ route('contact') }}">liên hệ quản trị viên</a> để yêu cầu xóa tài khoản.
            </div>
        </details>
    </div>

</div>
@endsection
