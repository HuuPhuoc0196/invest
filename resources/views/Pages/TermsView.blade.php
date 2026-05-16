@extends('Layout.Layout')

@section('title', 'Điều khoản sử dụng — Quản lý đầu tư cá nhân')

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => 'Điều khoản sử dụng — ' . config('app.name'),
        'description' => 'Điều khoản sử dụng nền tảng Quản lý đầu tư cá nhân: quyền và nghĩa vụ của người dùng, giới hạn trách nhiệm và các hành vi bị cấm.',
    ])
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/legal.css')
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
<div class="legal-page">

    {{-- ─── HERO ─── --}}
    <div class="legal-hero">
        <div class="legal-hero__icon" aria-hidden="true">📋</div>
        <h1 class="legal-hero__title">Điều khoản sử dụng</h1>
        <p class="legal-hero__sub">
            Vui lòng đọc kỹ điều khoản trước khi sử dụng dịch vụ. Việc sử dụng nền tảng đồng nghĩa với việc bạn chấp thuận các điều khoản này.
        </p>
        <div class="legal-updated">Cập nhật: 16/05/2025</div>
    </div>

    {{-- ─── SECTION 1 ─── --}}
    <div class="legal-section">
        <h2>1. Chấp thuận điều khoản</h2>
        <p>
            Bằng cách truy cập và sử dụng nền tảng Quản lý đầu tư cá nhân ("Dịch vụ"), bạn xác nhận rằng bạn đã đọc, hiểu và đồng ý bị ràng buộc bởi các Điều khoản sử dụng này cùng với <a href="{{ url('/chinh-sach-bao-mat') }}" style="color: var(--inv-accent);">Chính sách bảo mật</a> của chúng tôi.
        </p>
        <p>
            Nếu bạn không đồng ý với bất kỳ điều khoản nào, vui lòng ngừng sử dụng Dịch vụ. Chúng tôi có quyền cập nhật các điều khoản này và sẽ thông báo khi có thay đổi đáng kể.
        </p>
    </div>

    {{-- ─── SECTION 2 ─── --}}
    <div class="legal-section">
        <h2>2. Tính chất dịch vụ</h2>
        <div class="legal-highlight">
            <strong>QUAN TRỌNG — Vui lòng đọc kỹ:</strong> Nền tảng này là <strong>công cụ quản lý danh mục đầu tư ảo</strong> phục vụ mục đích học tập và theo dõi mô phỏng. Đây <strong>KHÔNG PHẢI</strong> là dịch vụ tư vấn tài chính, <strong>KHÔNG PHẢI</strong> sàn giao dịch chứng khoán thực và <strong>KHÔNG PHẢI</strong> dịch vụ môi giới đầu tư được cấp phép.
        </div>
        <p>
            Tất cả các giao dịch diễn ra trên nền tảng — bao gồm mua cổ phiếu, bán cổ phiếu, nạp/rút tiền — đều là <strong>mô phỏng ảo</strong> và không có giá trị pháp lý hay tài chính thực tế. Số tiền ảo trong ví không đại diện cho bất kỳ tài sản thực nào.
        </p>
        <p>
            Thông tin giá cổ phiếu, mức rủi ro và gợi ý đầu tư được cung cấp "như hiện trạng" cho mục đích tham khảo. Đây <strong>không phải</strong> là lời khuyên đầu tư chuyên nghiệp. <strong>Mọi quyết định đầu tư thực tế là trách nhiệm hoàn toàn của người dùng.</strong>
        </p>
    </div>

    {{-- ─── SECTION 3 ─── --}}
    <div class="legal-section">
        <h2>3. Tài khoản người dùng</h2>
        <p>Khi tạo và sử dụng tài khoản, bạn cam kết:</p>
        <ul>
            <li><strong>Cung cấp thông tin chính xác:</strong> Họ tên và địa chỉ email phải là thông tin thực và hợp lệ của bạn.</li>
            <li><strong>Bảo mật thông tin đăng nhập:</strong> Bạn chịu trách nhiệm bảo vệ mật khẩu và mọi hoạt động xảy ra dưới tài khoản của mình.</li>
            <li><strong>Một người một tài khoản:</strong> Mỗi cá nhân chỉ được đăng ký và sử dụng một tài khoản duy nhất.</li>
            <li><strong>Xác thực email:</strong> Bạn phải xác thực địa chỉ email trước khi có thể sử dụng đầy đủ các tính năng của Dịch vụ.</li>
            <li><strong>Thông báo vi phạm bảo mật:</strong> Nếu phát hiện tài khoản bị truy cập trái phép, hãy liên hệ chúng tôi ngay lập tức.</li>
        </ul>
    </div>

    {{-- ─── SECTION 4 ─── --}}
    <div class="legal-section">
        <h2>4. Hành vi bị cấm</h2>
        <p>Khi sử dụng Dịch vụ, bạn không được thực hiện các hành vi sau:</p>
        <ul>
            <li><strong>Spam và quấy rối:</strong> Gửi nội dung spam, quấy rối người dùng khác hoặc đội ngũ vận hành.</li>
            <li><strong>Tấn công hệ thống:</strong> Cố gắng hack, tấn công DDoS, khai thác lỗ hổng bảo mật hoặc can thiệp vào hoạt động của hệ thống.</li>
            <li><strong>Chia sẻ tài khoản:</strong> Chia sẻ thông tin đăng nhập cho người khác sử dụng.</li>
            <li><strong>Mục đích thương mại:</strong> Sử dụng Dịch vụ hoặc dữ liệu từ Dịch vụ cho bất kỳ mục đích thương mại nào mà không có sự cho phép bằng văn bản từ chúng tôi.</li>
            <li><strong>Thu thập dữ liệu trái phép:</strong> Sử dụng bot, crawler hoặc công cụ tự động để thu thập dữ liệu từ nền tảng.</li>
            <li><strong>Giả mạo:</strong> Mạo danh người dùng khác, quản trị viên hoặc nhân viên của chúng tôi.</li>
            <li><strong>Vi phạm pháp luật:</strong> Sử dụng Dịch vụ cho bất kỳ mục đích vi phạm pháp luật Việt Nam hoặc quốc tế.</li>
        </ul>
        <p>
            Vi phạm các quy định trên có thể dẫn đến việc tạm khóa hoặc xóa vĩnh viễn tài khoản của bạn mà không cần thông báo trước.
        </p>
    </div>

    {{-- ─── SECTION 5 ─── --}}
    <div class="legal-section">
        <h2>5. Giới hạn trách nhiệm</h2>
        <p>
            Dịch vụ được cung cấp theo nguyên tắc <strong>"as-is"</strong> (như hiện trạng) và <strong>"as-available"</strong> (khi có sẵn). Chúng tôi không đảm bảo:
        </p>
        <ul>
            <li>Dịch vụ hoạt động liên tục 100% uptime hay không có lỗi.</li>
            <li>Dữ liệu giá cổ phiếu luôn chính xác tuyệt đối và cập nhật theo thời gian thực.</li>
            <li>Các gợi ý đầu tư sẽ mang lại lợi nhuận trong thực tế.</li>
        </ul>
        <div class="legal-highlight">
            Chúng tôi <strong>không chịu trách nhiệm</strong> cho bất kỳ thiệt hại tài chính, quyết định đầu tư hay tổn thất nào phát sinh từ việc sử dụng thông tin trên nền tảng này. Người dùng tự chịu hoàn toàn trách nhiệm với mọi quyết định đầu tư thực tế của mình.
        </div>
        <p>
            Chúng tôi không chịu trách nhiệm cho các thiệt hại gián tiếp, ngẫu nhiên, đặc biệt hoặc hậu quả phát sinh từ việc sử dụng hoặc không thể sử dụng Dịch vụ.
        </p>
    </div>

    {{-- ─── SECTION 6 ─── --}}
    <div class="legal-section">
        <h2>6. Thay đổi dịch vụ</h2>
        <p>
            Chúng tôi có quyền thay đổi, thêm mới, tạm ngừng hoặc ngừng cung cấp bất kỳ tính năng nào của Dịch vụ vào bất kỳ thời điểm nào. Các thay đổi đáng kể sẽ được thông báo qua:
        </p>
        <ul>
            <li>Email gửi đến địa chỉ email đã đăng ký của bạn (với những thay đổi quan trọng).</li>
            <li>Thông báo trên giao diện nền tảng khi bạn đăng nhập.</li>
            <li>Cập nhật ngày "Cập nhật" trên trang Điều khoản sử dụng và Chính sách bảo mật.</li>
        </ul>
        <p>
            Việc tiếp tục sử dụng Dịch vụ sau khi có thay đổi điều khoản đồng nghĩa với việc bạn chấp nhận các điều khoản mới.
        </p>
    </div>

    {{-- ─── SECTION 7 ─── --}}
    <div class="legal-section" style="border-bottom: none; margin-bottom: 0;">
        <h2>7. Liên hệ</h2>
        <p>
            Nếu bạn có thắc mắc về Điều khoản sử dụng này, vui lòng liên hệ chúng tôi:
        </p>
        <ul>
            <li><strong>Email:</strong> <a href="mailto:lehuuphuoc0196@gmail.com" style="color: var(--inv-accent);">lehuuphuoc0196@gmail.com</a></li>
            <li><strong>Form liên hệ:</strong> <a href="{{ route('contact') }}" style="color: var(--inv-accent);">Trang liên hệ</a></li>
        </ul>
    </div>

</div>
@endsection
