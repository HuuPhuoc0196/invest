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
            <a href="{{ url('/user/profile') }}" class="button-link">👤 Thông tin cá nhân</a>
            <a href="{{ url('/user/follow') }}" class="button-link">🔔 Theo dõi</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <button type="button" class="button-link" onclick="document.getElementById('logout-form').submit();">
                🚪 Đăng xuất
            </button>
        </div>
        <div class="actions-right">
            <input type="text" id="searchInput" placeholder="Nhập mã CK...">
            <button onclick="searchStock()">🔍 Tìm kiếm</button>
        </div>
    </div>

    <h1>Danh sách mã cổ phiếu</h1>

    <table id="stock-table">
        <thead>
            <tr>
                <th>Mã cổ phiếu</th>
                <th>Giá mua tốt</th>
                <th>Giá hiện tại</th>
                <th>Rủi ro</th>
                <th>% Định giá</th>
            </tr>
        </thead>
        <tbody id="stockTableBody">
        </tbody>
    </table>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const baseUrl = "{{ url('') }}";
    const stocks = @json($stocks);
    const userPortfolios = @json($userPortfolios);
    var user = null;

    document.addEventListener("DOMContentLoaded", function() {
        user = new User();
        user.renderTable(stocks, userPortfolios);
        sortInit(stocks);
    });

    function searchStock() {
        user.searchStock(stocks, userPortfolios);
    }

    function sortInit(stocks){
        let sortDiffAsc = true;
        // Đảo chiều sort
        sortDiffAsc = !sortDiffAsc;

        // Sắp xếp theo chênh lệch giữa currentPrice và buyPrice
        stocks.sort((a, b) => {
            const buyA = parseFloat(a.recommended_buy_price);
            const currentA = parseFloat(a.current_price);
            const buyB = parseFloat(b.recommended_buy_price);
            const currentB = parseFloat(b.current_price);

            const percentA = buyA !== 0 ? ((currentA - buyA) / buyA) * 100 : 0;
            const percentB = buyB !== 0 ? ((currentB - buyB) / buyB) * 100 : 0;

            return sortDiffAsc ? percentB - percentA : percentA - percentB;
        });

        // Gọi hàm render lại bảng
        user.renderTable(stocks, userPortfolios);
    }
</script>

</html>