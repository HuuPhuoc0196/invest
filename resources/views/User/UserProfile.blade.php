@extends('Layout.Layout')

@section('title', 'Danh sách mã cổ phiếu đang giữ')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
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
    <a href="{{ url('/user/buy') }}" class="button-link">➕ Mua cổ phiếu</a>
    <a href="{{ url('/user/sell') }}" class="button-link">❌ Bán cổ phiếu</a>
    <a href="{{ url('/user/investment-performance') }}" class="button-link">📈 Hiệu quả đầu tư</a>
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    <h1>Danh sách mã cổ phiếu đang giữ</h1>

    <div class="table-container">
        <table id="stock-table">
            <thead>
                <tr>
                    <th>Mã cổ phiếu</th>
                    <th>Khối lượng nắm giữ</th>
                    <th>Giá vốn</th>
                    <th>Giá hiện tại</th>
                    <th>Giá trị vốn</th>
                    <th>Giá trị thị trường</th>
                    <th>Tiền lãi</th>
                    <th>% lãi</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
    </div>
@endsection

@section('user-script')
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
@endsection