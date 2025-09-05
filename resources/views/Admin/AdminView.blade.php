@extends('Layout.LayoutAdmin')

@section('title', 'Danh sách mã cổ phiếu')

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
    <div style="display: flex; gap: 5px;">
        <a href="{{ url('/admin') }}" class="button-link">🏠 Trang chủ</a>
        <a href="{{ url('/admin/insert') }}" class="button-link">➕ Thêm mới</a>
        <a href="{{ url('/admin/updateRiskForCode') }}" class="button-link">🔃 Cập nhật rủi ro</a>
    </div>
    <div style="display: flex; gap: 5px;">
        @if ($statusSync->status_sync_price == 0)
            <button onclick="syncData()">🔄 Sync Giá hiện tại</button>
        @else
            <button onclick="syncData()" disabled style="opacity: 0.5; cursor: not-allowed;">🔄 Sync Giá hiện tại</button>
        @endif

        @if ($statusSync->status_sync_risk == 0)
            <button onclick="syncDataRisk()">🔄 Sync Rủi ro</button>
        @else
            <button onclick="syncDataRisk()" disabled style="opacity: 0.5; cursor: not-allowed;">🔄 Sync Rủi ro</button>
        @endif
    </div>
    <div style="display: flex; gap: 5px;">
        <a href="{{ url('/admin/logs') }}" class="button-link" target="_blank" rel="noopener noreferrer">👁️ Logs Hosting</a>
        <a href="{{ url('/admin/logsVPS') }}" class="button-link" target="_blank" rel="noopener noreferrer">👁️ Logs VPS</a>
        <button type="button" class="button-link" onclick="document.getElementById('logout-form').submit();">
            🚪 Đăng xuất
        </button>
    </div>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập mã CK...">
    <button onclick="searchStock()">🔍 Tìm kiếm</button>
@endsection

@section('admin-body-content')
    <h1>Danh sách mã cổ phiếu</h1>
    <div class="table-container">
        <table id="stock-table">
            <thead>
                <tr>
                    <th>Mã cổ phiếu</th>
                    <th>Giá mua tốt</th>
                    <th>Giá hiện tại</th>
                    <th>Rủi ro</th>
                    <th>% Định giá</th>
                    <th>Cập nhật</th>
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
    </div>
    <!-- Modal xác nhận sync data-->
    <div id="confirmModalSync" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
            <p>Bạn có muốn đồng bộ hoá giá không?</p>
            <button id="confirmYesSync">Có</button>
            <button id="confirmNoSync">Không</button>
        </div>
    </div>
     <!-- Modal xác nhận sync rui ro -->
    <div id="confirmModalSyncRisk" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
            <p>Bạn có muốn đồng bộ hoá rủi ro?</p>
            <button id="confirmYesSyncRisk">Có</button>
            <button id="confirmNoSyncRisk">Không</button>
        </div>
    </div>
@endsection

@section('admin-script')
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const stocks = @json($stocks);
        var admin = null;
        let deleteUrl = "";

        document.addEventListener("DOMContentLoaded", function() {
            admin = new Admin();
            admin.renderTable(stocks);
            sortInit(stocks);
        });

        function searchStock() {
            admin.searchStock(stocks);
        }

        function confirmDelete(code) {
            deleteUrl = `${baseUrl}/admin/delete/${code}`;
            document.getElementById("confirmModal").style.display = "flex";
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
            admin.renderTable(stocks);
        }

        function syncData() {
            // Biến dùng để xác định hành động hiện tại là gì
            window.pendingAction = "sync";
            document.getElementById("confirmModalSync").style.display = "flex";
        }

        function syncDataRisk() {
            // Biến dùng để xác định hành động hiện tại là gì
            window.pendingAction = "syncRisk";
            document.getElementById("confirmModalSyncRisk").style.display = "flex";
        }

        document.getElementById("confirmYesSync").onclick = function () {
            if (window.pendingAction === "sync") {
                fetch(`${baseUrl}/api/admin/collect`, {
                    method: "GET",
                })
            }

            // Đóng modal
            document.getElementById("confirmModalSync").style.display = "none";
            window.pendingAction = null;
        };

        document.getElementById("confirmNoSync").onclick = function () {
            document.getElementById("confirmModalSync").style.display = "none";
            window.pendingAction = null;
        };

        document.getElementById("confirmYesSyncRisk").onclick = function () {
            if (window.pendingAction === "syncRisk") {
                fetch(`${baseUrl}/api/admin/collectRisk`, {
                    method: "GET",
                })
            }

            // Đóng modal
            document.getElementById("confirmModalSyncRisk").style.display = "none";
            window.pendingAction = null;
        };

        document.getElementById("confirmNoSyncRisk").onclick = function () {
            document.getElementById("confirmModalSyncRisk").style.display = "none";
            window.pendingAction = null;
        };

        document.getElementById("confirmYes").onclick = function () {
            window.location.href = deleteUrl;
        };

        document.getElementById("confirmNo").onclick = function () {
            document.getElementById("confirmModal").style.display = "none";
            deleteUrl = "";
        };

    </script>
@endsection