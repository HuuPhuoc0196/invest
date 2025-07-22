<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Invest</title>
    @vite('resources/js/app.js')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

</head>

<body class="antialiased">
    <div class="actions">
        <div class="actions-left">
            <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
            <a href="{{ url('/user/buy') }}" class="button-link">➕ Mua cổ phiếu</a>
            <a href="{{ url('/user/sell') }}" class="button-link">❌ Bán cổ phiếu</a>
            <a href="{{ url('/user/investment-performance') }}" class="button-link">📈 Hiệu quả đầu tư</a>
        </div>

        <div class="actions-right">
            <input type="text" id="searchInput" placeholder="Nhập mã CK...">
            <button onclick="searchStock()">🔍 Tìm kiếm</button>
        </div>
    </div>

    <h1>Danh sách mã cổ phiếu đang giữ</h1>

    <table id="stock-table">
        <thead>
            <tr>
                <th>Mã cổ phiếu</th>
                <th>Khối lượng nắm giữ</th>
                <th>Giá vốn</th>
                <th>Giá hiện tại</th>
                <th>Giá trị giao dịch</th>
                <th>Tiền lãi</th>
                <th>% lãi</th>
            </tr>
        </thead>
        <tbody id="stockTableBody">
        </tbody>
    </table>
    </div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const baseUrl = "{{ url('') }}";
    const userPortfolios = @json($userPortfolios);
    var user = null;

    document.addEventListener("DOMContentLoaded", function() {
        user = new User();
        user.renderTableProfile(userPortfolios);
    });

    function searchStock() {
        user.searchStockProfile(userPortfolios);
    }
</script>

</html>