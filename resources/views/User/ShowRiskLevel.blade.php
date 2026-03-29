@extends('Layout.LayoutLogin')

@section('title', 'Quản lý đầu tư chứng khoán')

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
    <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
</div>
@endsection

@section('login-script')
    <script>
        const baseUrl = "{{ url('') }}";
        function getRisk(rating) {
            switch (Number(rating)) {
                case 1:
                    return { label: 'An toàn', color: '#27ae60' };
                case 2:
                    return { label: 'Cảnh báo', color: '#f39c12' };
                case 3:
                    return { label: 'Hạn chế GD', color: '#e74c3c' };
                case 4:
                    return { label: 'Đình chỉ/Huỷ', color: '#c0392b' };
                default:
                    return { label: 'Chưa xác định', color: '#95a5a6' };
            }
        }

       document.addEventListener("DOMContentLoaded", function () {
            const stock = @json($stock);

            document.getElementById("code").textContent = stock?.code || "Không có trong hệ thống";
           if (stock?.risk_level) {
                const risk = getRisk(stock.risk_level);
                document.getElementById("risk").textContent = risk.label;
                document.getElementById("risk").style.color = risk.color; // nếu muốn đổi màu chữ
            } else {
                document.getElementById("risk").textContent = "Không xác định";
                document.getElementById("risk").style.color = "gray"; // màu mặc định
            }
       });
    </script>
@endsection