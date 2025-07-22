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
            <a href="{{ url('/user/profile') }}" class="button-link">👤 Thông tin cá nhân</a>

            <!-- 👇 Thêm phần nhập ngày và nút hiệu suất đầu tư -->
            <div style="margin-top: 10px;">
                <label>Từ:</label>
                <input type="date" id="startDate">
                <label>Đến:</label>
                <input type="date" id="endDate">
                <button onclick="handleInvestmentPerformance()">📈 Hiệu suất đầu tư</button>
            </div>
        </div>


        <div class="actions-right">
            <input type="text" id="searchInput" placeholder="Nhập mã CK...">
            <button onclick="searchStock()">🔍 Tìm kiếm</button>
        </div>
    </div>

    <h1>Lịch sử giao dịch</h1>

    <table id="stock-table">
        <thead>
            <tr>
                <th>Mã cổ phiếu</th>
                <th>Khối lượng đặt</th>
                <th>Giá trị giao dịch</th>
                <th>Ngày giao dịch</th>
                <th>Loại giao dịch</th>
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
    const stocks = @json($stocks);
    var user = null;

    document.addEventListener("DOMContentLoaded", function() {
        user = new User();
        user.renderTableInvest(stocks);
        sortInitInvest(stocks);
    });

    function searchStock() {
        user.searchStockInvest(stocks);
    }

    function sortInitInvest(stocks){
        
        // Gọi hàm render lại bảng  
        user.renderTableInvest(stocks);
    }

    // 👇 Hàm lọc và hiển thị theo ngày
    function handleInvestmentPerformance() {
        const startDateInput = document.getElementById('startDate').value;
        const endDateInput = document.getElementById('endDate').value;

        const startDate = startDateInput ? new Date(startDateInput) : null;
        const endDate = endDateInput ? new Date(endDateInput) : new Date(); // nếu không nhập thì mặc định là hôm nay

        // Đảm bảo endDate là cuối ngày
        endDate.setHours(23, 59, 59, 999);

        const filteredStocks = stocks.filter(stock => {
            const dateStr = stock.buy_date || stock.sell_date;
            if (!dateStr) return false;
            const stockDate = new Date(dateStr);

            if (startDate && endDate) {
                return stockDate >= startDate && stockDate <= endDate;
            } else if (startDate) {
                return stockDate >= startDate;
            } else if (endDate) {
                return stockDate <= endDate;
            }

            return true;
        });

        user.renderTableInvest(filteredStocks);
    }
</script>

</html>