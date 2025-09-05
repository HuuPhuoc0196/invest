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
    <h1>Quản lý đầu tư thật dể dàng cùng với Invest manager</h1>
    <p>Mã chứng khoán: <strong id="code"></strong></p>
    <p>Tình trạng: <strong id="risk"></strong></p>
    
    <div class="legend">
    <h3>Chú thích tình trang của cổ phiếu</h3>
    <ul>
        <li><span style="color: green; font-weight: bold;">An toàn:</span> Cổ phiếu đang giao dịch bình thường, ra khỏi diện Cảnh báo và bị kiểm soát hoặc không vi phạm quy định.</li>
        <li><span style="color: orange; font-weight: bold;">Tốt:</span> Cổ phiếu ra khỏi diện hạn chế giao dịch, vào diện cảnh báo, vào diện kiểm soát.</li>
        <li><span style="color: orangered; font-weight: bold;">Nguy hiểm:</span> Cổ phiếu vào diện Cảnh báo và bị kiểm soát, vào diện Cảnh báo và hạn chế giao dịch, ra khỏi diện Đình chỉ giao dịch.</li>
        <li><span style="color: red; font-weight: bold;">Cực kỳ xấu:</span> Cổ phiếu vào diện Đình chỉ giao dịch, Hủy niêm yết cổ phiếu.</li>
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
                    return { label: 'An toàn', color: 'green' };
                case 2:
                    return { label: 'Tốt', color: 'orange' };
                case 3:
                    return { label: 'Nguy hiểm', color: 'OrangeRed' };
                case 4:
                    return { label: 'Cực kỳ xấu', color: 'red' };
                default:
                    return { label: 'Không xác định', color: 'gray' };
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