@extends('Layout.LayoutAdmin')
@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cập Nhật Mã Cổ Phiếu')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminStockInsert.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    <div class="action-bar">
        <a href="{{ url('/admin/stocks') }}" class="button-link">📊 Quản lý cổ phiếu</a>
        <a href="javascript:void(0)" class="button-link" style="background:#27ae60;" onclick="openSyncStockModal()">🔄 Cập nhật cổ phiếu</a>
    </div>
@endsection

@section('admin-body-content')
    <h2>Cập Nhật Mã Cổ Phiếu</h2>

    <div class="form-container">
        <div class="form-group">
            <label for="code">Mã Cổ Phiếu: <span class="required">*</span></label>
            <input type="text" id="code" placeholder="VD: FPT" disabled>
            <div class="error" id="errorCode">Vui lòng nhập Mã cổ phiếu</div>
        </div>

        <div class="form-group">
            <label for="currentPrice">Giá hiện tại: <span class="required">*</span></label>
            <input type="text" id="currentPrice" placeholder="VD: 120,000">
            <div class="error" id="errorCurrent">Vui lòng nhập Giá hiện tại</div>
            <div class="error" id="errorCurrentType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="priceAvg">Giá trung bình:</label>
            <input type="text" id="priceAvg" placeholder="VD: 110,000">
            <div class="error" id="errorPriceAvgType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="buyPrice">Giá mua tốt:</label>
            <input type="text" id="buyPrice" placeholder="VD: 100,000">
            <div class="error" id="errorBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="sellPrice">Giá bán tốt:</label>
            <input type="text" id="sellPrice" placeholder="VD: 150,000">
            <div class="error" id="errorSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="percentBuy">Tỉ lệ mua (%):</label>
            <input type="text" id="percentBuy" placeholder="VD: 80">
            <div class="error" id="errorPercentBuyType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="percentSell">Tỉ lệ bán (%):</label>
            <input type="text" id="percentSell" placeholder="VD: 120">
            <div class="error" id="errorPercentSellType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="risk">Trạng thái: <span class="required">*</span></label>
            <select id="risk">
                <option value="1">An toàn</option>
                <option value="2">Cảnh báo</option>
                <option value="3">Hạn chế GD</option>
                <option value="4">Đình chỉ/Huỷ</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ratingStocks">Điểm:</label>
            <input type="text" id="ratingStocks" placeholder="VD: 8.5">
            <div class="error" id="errorRatingType">Vui lòng nhập Số</div>
        </div>

        <div class="form-group">
            <label for="stocksVn">Thuộc VN:</label>
            <input type="text" id="stocksVn" placeholder="VD: 1000">
            <div class="error" id="errorStocksVnType">Vui lòng nhập Số</div>
        </div>

        <div id="toast" class="toast"></div>

        <button onclick="submitUpdateForm()">Cập nhật</button>
    </div>
    <!-- Modal confirm sync update stock -->
    <div id="syncStockModal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width:350px;">
            <span class="modal-close" onclick="closeSyncStockModal()">&times;</span>
            <h2 style="font-size:20px;margin-bottom:18px;">Xác nhận cập nhật cổ phiếu</h2>
            <div style="margin-bottom:18px;">Bạn có chắc chắn muốn cập nhật dữ liệu cho mã <b id="syncStockCode"></b>?</div>
            <div style="display:flex;justify-content:center;gap:12px;">
                <button class="btn-cancel" onclick="closeSyncStockModal()">Huỷ</button>
                <button class="btn-import" id="btnSyncStock" onclick="runSyncStock()">Đồng ý</button>
            </div>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminStockUpdate.js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
        const stockData = @json($stock);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Modal logic
        function openSyncStockModal() {
            document.getElementById('syncStockCode').textContent = stockData.code;
            document.getElementById('syncStockModal').style.display = 'flex';
        }
        function closeSyncStockModal() {
            document.getElementById('syncStockModal').style.display = 'none';
        }
        function runSyncStock() {
            const btn = document.getElementById('btnSyncStock');
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';
            $.ajax({
                url: baseUrl + '/admin/sync/run-update-stock/' + encodeURIComponent(stockData.code),
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    btn.disabled = false;
                    btn.textContent = 'Đồng ý';
                    closeSyncStockModal();
                    if (res && res.status === 'success') {
                        showToast('✅ ' + (res.message || ('Đã gửi yêu cầu cập nhật cho mã ' + stockData.code)));
                    } else {
                        showToast('❌ ' + (res && res.message ? res.message : 'Lỗi gửi yêu cầu cập nhật!'));
                    }
                },
                error: function(xhr) {
                    btn.disabled = false;
                    btn.textContent = 'Đồng ý';
                    closeSyncStockModal();
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi gửi yêu cầu cập nhật!';
                    showToast('❌ ' + msg);
                }
            });
        }
        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.innerHTML = msg;
            toast.className = 'toast show';
            setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3500);
        }
    </script>
    <style>
        .action-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: center;
            max-width: 520px;
            margin: 0 auto 18px auto;
        }
        @media (max-width: 600px) {
            .action-bar { flex-direction: column; max-width: 100%; }
        }
        .btn-sync-stock {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #27ae60;
            color: #fff;
            border: none;
            border-radius: 22px;
            padding: 8px 22px 8px 16px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(39,174,96,0.08);
            transition: background 0.18s, box-shadow 0.18s;
            min-width: 170px;
            justify-content: center;
        }
        .btn-sync-stock:hover {
            background: #219150;
            box-shadow: 0 4px 16px rgba(39,174,96,0.16);
        }
        .btn-sync-icon {
            font-size: 18px;
            vertical-align: middle;
        }
        .button-link {
            min-width: 170px;
            text-align: center;
        }
        .modal-overlay { position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:2000; display:flex; align-items:center; justify-content:center; }
        .modal-content { background:#fff; border-radius:12px; padding:28px 24px 20px 24px; position:relative; box-shadow:0 8px 32px rgba(0,0,0,0.18); }
        .modal-close { position:absolute; top:10px; right:16px; font-size:26px; color:#aaa; cursor:pointer; font-weight:bold; }
        .modal-close:hover { color:#e74c3c; }
        .btn-cancel { padding:8px 22px; border:1px solid #bdc3c7; background:#ecf0f1; color:#2c3e50; border-radius:6px; cursor:pointer; font-size:14px; }
        .btn-cancel:hover { background:#d5dbdb; }
        .btn-import { padding:8px 22px; border:none; background:#27ae60; color:#fff; border-radius:6px; cursor:pointer; font-size:14px; }
        .btn-import:hover { background:#1e8449; }
        .btn-import:disabled { background:#95a5a6; cursor:not-allowed; }
        .btn-sync-stock {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #27ae60;
            color: #fff;
            border: none;
            border-radius: 22px;
            padding: 7px 18px 7px 14px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(39,174,96,0.08);
            transition: background 0.18s, box-shadow 0.18s;
        }
        .btn-sync-stock:hover {
            background: #219150;
            box-shadow: 0 4px 16px rgba(39,174,96,0.16);
        }
    </style>
@endsection