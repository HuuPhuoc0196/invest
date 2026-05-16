@extends('Layout.Layout')

@section('title', 'Chính sách bảo mật — Quản lý đầu tư cá nhân')

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => 'Chính sách bảo mật — ' . config('app.name'),
        'description' => 'Chính sách bảo mật của Invest Manager: cách chúng tôi thu thập, sử dụng và bảo vệ thông tin cá nhân của bạn.',
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
        <div class="legal-hero__icon" aria-hidden="true">🔒</div>
        <h1 class="legal-hero__title">Chính sách bảo mật</h1>
        <p class="legal-hero__sub">
            Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn một cách nghiêm túc và minh bạch.
        </p>
        <div class="legal-updated">Cập nhật: 16/05/2025</div>
    </div>

    {{-- ─── SECTION 1 ─── --}}
    <div class="legal-section">
        <h2>1. Thông tin chúng tôi thu thập</h2>
        <p>
            Để cung cấp dịch vụ, chúng tôi thu thập các thông tin sau khi bạn đăng ký và sử dụng nền tảng:
        </p>
        <ul>
            <li><strong>Thông tin tài khoản:</strong> Họ tên và địa chỉ email bạn cung cấp khi đăng ký.</li>
            <li><strong>Lịch sử giao dịch ảo:</strong> Các lô mua, lệnh bán, số dư ví ảo và lịch sử nạp/rút tiền trong hệ thống mô phỏng.</li>
            <li><strong>Cài đặt thông báo:</strong> Danh sách mã cổ phiếu bạn theo dõi, giá mục tiêu và trạng thái bật/tắt email cảnh báo.</li>
            <li><strong>Dữ liệu kỹ thuật:</strong> Thông tin phiên đăng nhập (session cookie) để duy trì trạng thái đăng nhập.</li>
        </ul>
        <p>
            Chúng tôi <strong>không</strong> thu thập bất kỳ thông tin tài chính thực nào như số tài khoản ngân hàng, số thẻ tín dụng, thông tin đầu tư thực tế hoặc tài sản tài chính của bạn. Nền tảng này chỉ sử dụng dữ liệu ảo phục vụ mục đích học tập và theo dõi mô phỏng.
        </p>
    </div>

    {{-- ─── SECTION 2 ─── --}}
    <div class="legal-section">
        <h2>2. Cách chúng tôi sử dụng thông tin</h2>
        <p>Thông tin thu thập được sử dụng cho các mục đích sau:</p>
        <ul>
            <li><strong>Vận hành dịch vụ:</strong> Xác thực đăng nhập, hiển thị danh mục và tính toán P&amp;L/ROI theo phương pháp FIFO.</li>
            <li><strong>Gửi email thông báo:</strong> Cảnh báo giá khi mã cổ phiếu chạm ngưỡng mục tiêu bạn đặt, tóm tắt danh mục hàng ngày và thông báo hệ thống.</li>
            <li><strong>Cải thiện trải nghiệm:</strong> Phân tích cách người dùng tương tác để cải thiện giao diện và tính năng.</li>
            <li><strong>Bảo mật hệ thống:</strong> Phát hiện và ngăn chặn các hành vi bất thường, truy cập trái phép.</li>
        </ul>
        <p>
            Chúng tôi <strong>không bán, không trao đổi và không chia sẻ</strong> thông tin cá nhân của bạn với bất kỳ bên thứ ba nào cho mục đích thương mại. Thông tin chỉ được truy cập bởi đội ngũ vận hành hệ thống khi cần thiết để cung cấp dịch vụ.
        </p>
    </div>

    {{-- ─── SECTION 3 ─── --}}
    <div class="legal-section">
        <h2>3. Bảo mật dữ liệu</h2>
        <p>Chúng tôi áp dụng nhiều lớp bảo vệ để đảm bảo an toàn cho dữ liệu của bạn:</p>
        <ul>
            <li><strong>Mã hóa mật khẩu:</strong> Mật khẩu được mã hóa bằng thuật toán bcrypt — chúng tôi không thể đọc mật khẩu gốc của bạn.</li>
            <li><strong>Kết nối HTTPS:</strong> Toàn bộ dữ liệu truyền giữa trình duyệt và máy chủ được mã hóa qua HTTPS/TLS.</li>
            <li><strong>Cache server-side:</strong> Dữ liệu nhạy cảm được lưu cache phía server, không lộ ra client.</li>
            <li><strong>Không lưu thông tin thanh toán:</strong> Hệ thống không xử lý hay lưu trữ bất kỳ dữ liệu thanh toán thực nào.</li>
            <li><strong>Bảo vệ CSRF:</strong> Toàn bộ form sử dụng token CSRF để ngăn tấn công giả mạo yêu cầu.</li>
            <li><strong>Rate limiting:</strong> Giới hạn tần suất đăng nhập để ngăn tấn công brute-force.</li>
        </ul>
        <p>
            Dù chúng tôi áp dụng các biện pháp bảo mật tiêu chuẩn ngành, không có hệ thống nào đảm bảo an toàn tuyệt đối 100%. Chúng tôi khuyến khích bạn sử dụng mật khẩu mạnh và không chia sẻ thông tin đăng nhập.
        </p>
    </div>

    {{-- ─── SECTION 4 ─── --}}
    <div class="legal-section">
        <h2>4. Quyền của bạn</h2>
        <p>Bạn có các quyền sau đối với dữ liệu cá nhân của mình:</p>
        <ul>
            <li><strong>Chỉnh sửa thông tin:</strong> Cập nhật họ tên trong phần Thông tin cá nhân sau khi đăng nhập.</li>
            <li><strong>Đổi mật khẩu:</strong> Có thể đổi mật khẩu bất kỳ lúc nào trong phần Đổi mật khẩu.</li>
            <li><strong>Quản lý thông báo:</strong> Bật/tắt từng loại email thông báo trong phần Cài đặt email.</li>
            <li><strong>Xóa tài khoản:</strong> Liên hệ quản trị viên để yêu cầu xóa tài khoản. Khi tài khoản bị xóa, toàn bộ dữ liệu liên quan — bao gồm danh mục đầu tư, lịch sử giao dịch, danh sách theo dõi, ví tiền ảo — sẽ bị xóa vĩnh viễn và không thể khôi phục.</li>
        </ul>
        <div class="legal-highlight">
            <strong>Lưu ý khi xóa tài khoản:</strong> Thao tác xóa tài khoản là vĩnh viễn và không thể hoàn tác. Toàn bộ dữ liệu sẽ bị xóa cascade khỏi hệ thống ngay lập tức, bao gồm danh mục FIFO, lịch sử bán, danh sách follow, số dư và lịch sử nạp/rút tiền ảo.
        </div>
    </div>

    {{-- ─── SECTION 5 ─── --}}
    <div class="legal-section">
        <h2>5. Cookies</h2>
        <p>
            Chúng tôi chỉ sử dụng cookie kỹ thuật thiết yếu để vận hành dịch vụ. Cụ thể:
        </p>
        <ul>
            <li><strong>Session cookie:</strong> Lưu trạng thái đăng nhập của bạn trong phiên làm việc hiện tại. Cookie này tự động bị xóa khi bạn đóng trình duyệt hoặc đăng xuất.</li>
            <li><strong>CSRF token:</strong> Token bảo mật để ngăn tấn công giả mạo form, được làm mới theo mỗi phiên.</li>
        </ul>
        <p>
            Chúng tôi <strong>không</strong> sử dụng cookie theo dõi (tracking cookies), cookie quảng cáo hay công cụ phân tích bên thứ ba như Google Analytics. Dữ liệu phiên hoàn toàn không được chia sẻ với bên ngoài.
        </p>
    </div>

    {{-- ─── SECTION 6 ─── --}}
    <div class="legal-section" style="border-bottom: none; margin-bottom: 0;">
        <h2>6. Liên hệ</h2>
        <p>
            Nếu bạn có câu hỏi về chính sách bảo mật này hoặc muốn thực hiện quyền đối với dữ liệu cá nhân, vui lòng liên hệ chúng tôi qua:
        </p>
        <ul>
            <li><strong>Email:</strong> <a href="mailto:lehuuphuoc0196@gmail.com" style="color: var(--inv-accent);">lehuuphuoc0196@gmail.com</a></li>
            <li><strong>Form liên hệ:</strong> <a href="{{ route('contact') }}" style="color: var(--inv-accent);">Trang liên hệ</a></li>
        </ul>
        <p>
            Chúng tôi cam kết phản hồi mọi yêu cầu liên quan đến quyền riêng tư trong vòng 5 ngày làm việc.
        </p>
    </div>

</div>
@endsection
