@extends('Layout.LayoutLogin')

@section('title', 'Quản lý đầu tư chứng khoán')

@section('seo')
    <meta name="robots" content="noindex, follow">
@endsection

@section('header-css')
    @vite('resources/css/login.css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('body-content')
    @include('partials.page-title-invest', ['title' => 'Quản lý đầu tư thật dể dàng cùng với Invest manager', 'level' => 1])
    <p>Mã chứng khoán: <strong id="code"></strong></p>
    <p>Tình trạng: <strong id="risk"></strong></p>
    
    <div class="legend">
    <h3>Chú thích tình trang của cổ phiếu</h3>
    <ul>
        <li><span style="color: #27ae60; font-weight: bold;">✅ An toàn:</span> Đang giao dịch bình thường, không vi phạm quy định.</li>
        <li><span style="color: #f39c12; font-weight: bold;">⚠️ Cảnh báo:</span> Vào diện Cảnh báo hoặc Kiểm soát.</li>
        <li><span style="color: #e74c3c; font-weight: bold;">🔒 Hạn chế GD:</span> Hạn chế giao dịch, Cảnh báo + Kiểm soát.</li>
        <li><span style="color: #c0392b; font-weight: bold;">⛔ Đình chỉ/Huỷ:</span> Đình chỉ giao dịch hoặc Hủy niêm yết.</li>
    </ul>
    <a href="{{ route('home') }}" class="button-link">🏠 Trang chủ</a>
</div>
@endsection

@section('login-script')
    <script>
        window.__pageData = { baseUrl: "{{ url('') }}", stock: @json($stock) };
    </script>
    @vite('resources/js/pages/show-risk-level.js')
@endsection
