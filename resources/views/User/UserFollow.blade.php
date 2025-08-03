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
            <a href="{{ url('/user/insertFollow') }}" class="button-link">➕ Thêm mới</a>
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
    <!-- Modal xác nhận xoá -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
        <p>Bạn có chắc chắn muốn xoá?</p>
        <button id="confirmYes">Có</button>
        <button id="confirmNo">Không</button>
    </div>
</div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const baseUrl = "{{ url('') }}";
    const stocks = @json($stocks);
    const userFollow = @json($userFollow);
    var user = null;
    let deleteUrl = "";

    document.addEventListener("DOMContentLoaded", function() {
        user = new User();
        user.renderTable(stocks, userFollow);
        sortInit(stocks);
    });

    function confirmDelete(code) {
        deleteUrl = `${baseUrl}/user/deleteFollow/${code}`;
        document.getElementById("confirmModal").style.display = "flex";
    }

    function searchStock() {
        user.searchStock(stocks, userFollow);
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
        user.renderTable(stocks, userFollow);
    }

    document.getElementById("confirmYes").onclick = function () {
        window.location.href = deleteUrl;
    };

    document.getElementById("confirmNo").onclick = function () {
        document.getElementById("confirmModal").style.display = "none";
        deleteUrl = "";
    };
</script>

</html>