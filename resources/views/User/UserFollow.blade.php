@extends('Layout.Layout')

@section('title', 'Danh sách mã cổ phiếu theo dõi')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/userFollow.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('user-info')
    <div class="user-info">
        {{-- <img src="{{ asset('images/default-avatar.png') }}" alt="User Avatar" class="avatar"> --}}
        <div class="user-details">
            <p class="user-name">👤 {{ Auth::user()->name }}</p>
            <p class="user-email">📧 {{ Auth::user()->email }}</p>
        </div>
    </div>
@endsection  

@section('actions-left')
    <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
    <a href="{{ url('/user/insertFollow') }}" class="button-link">➕ Thêm mới</a>
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    <h1>Danh sách mã cổ phiếu</h1>

    <div class="table-container">
        <table id="stock-table">
            <thead>
                <tr>
                    <th>Mã cổ phiếu</th>
                    <th>Giá theo dõi</th>
                    <th>Giá hiện tại</th>
                    <th>Rủi ro</th>
                    <th>% Định giá</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
    </div>
    <!-- Modal xác nhận xoá -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
        <p>Bạn có chắc chắn muốn xoá?</p>
        <button id="confirmYes">Có</button>
        <button id="confirmNo">Không</button>
    </div>
@endsection

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const stocks = @json($stocks);
        const userFollow = @json($userFollow);
        var user = null;
        let deleteUrl = "";

        document.addEventListener("DOMContentLoaded", function() {
            user = new User();
            user.renderTableUserFollow(stocks, userFollow);
            sortInit(stocks);
        });

        function confirmDelete(code) {
            deleteUrl = `${baseUrl}/user/deleteFollow/${code}`;
            document.getElementById("confirmModal").style.display = "flex";
        }

        function searchStock() {
            user.searchStockUserFollow(stocks, userFollow);
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
            user.renderTableUserFollow(stocks, userFollow);
        }

        document.getElementById("confirmYes").onclick = function () {
            window.location.href = deleteUrl;
        };

        document.getElementById("confirmNo").onclick = function () {
            document.getElementById("confirmModal").style.display = "none";
            deleteUrl = "";
        };
    </script>
@endsection