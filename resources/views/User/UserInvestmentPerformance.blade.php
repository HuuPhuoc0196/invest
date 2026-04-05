@extends('Layout.Layout')

@section('title', 'Hiệu xuất Đầu tư')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/adminStockManagement.css')
    @vite('resources/css/pages/investment-performance.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

{{-- @section('user-info')
    <div class="user-info">
        <img src="{{ asset('images/default-avatar.png') }}" alt="User Avatar" class="avatar">
        <div class="user-details">
            <p class="user-name">👤 {{ Auth::user()->name }}</p>
            <p class="user-email">📧 {{ Auth::user()->email }}</p>
        </div>
    </div>
@endsection   --}}

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Lịch sử giao dịch', 'level' => 1])

    <div class="inv-perf-quick-wrap">
        <span class="inv-perf-quick-label">Lọc nhanh:</span>
        <div class="inv-perf-quick-btns">
            <button class="inv-perf-quick-btn" data-years="1">1 năm</button>
            <button class="inv-perf-quick-btn" data-years="3">3 năm</button>
            <button class="inv-perf-quick-btn" data-years="5">5 năm</button>
            <button class="inv-perf-quick-btn" data-years="0">Tất cả</button>
        </div>
    </div>

    <div class="inv-perf-filter-wrap">
            <div class="inv-perf-filter-bar">
            <label for="startDate" class="inv-perf-filter-label">Từ:</label>
            <input type="date" id="startDate" class="inv-perf-filter-date" autocomplete="off">
            <label for="endDate" class="inv-perf-filter-label">Đến:</label>
            <input type="date" id="endDate" class="inv-perf-filter-date" autocomplete="off">
            <button type="button" class="inv-perf-filter-btn" onclick="handleInvestmentPerformance()">🔍 Lọc dữ liệu</button>
        </div>
    </div>

    <div class="table-container">
        <table id="stock-table">
            <thead>
                <tr>
                    <th class="col-code-sticky">Mã CK</th>
                    <th>Khối lượng đặt</th>
                    <th>Giá</th>
                    <th>Giá trị giao dịch</th>
                    <th>Ngày giao dịch</th>
                    <th class="th-has-filter">
                        <div class="th-filter-wrap">
                            <span class="th-filter-label">Loại giao dịch</span>
                            <select id="filterType" class="th-filter-select" onchange="handleInvestmentPerformance()">
                                <option value="">Tất cả</option>
                                <option value="Buy">Mua</option>
                                <option value="Sell">Bán</option>
                            </select>
                        </div>
                    </th>
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
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            stocks: @json($stocks)
        };
    </script>
    @vite('resources/js/pages/investment-performance.js')
@endsection
