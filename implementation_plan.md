# Implementation Plan — Invest App Upgrade

> Tài liệu kế hoạch triển khai toàn bộ cải tiến, tính năng mới và sửa lỗi.
> **Trạng thái:** DRAFT — chờ xác nhận từ user trước khi thực hiện bất kỳ thay đổi nào.
> **Ngày soạn:** 2026-05-16

---

## Mục lục

- [PHẦN 1 — Bug Fixes (Ưu tiên cao)](#phần-1--bug-fixes-ưu-tiên-cao)
- [PHẦN 2 — Tính năng mới](#phần-2--tính-năng-mới)
- [PHẦN 3 — UI/UX Improvements](#phần-3--uiux-improvements)
- [PHẦN 4 — SEO & Marketing](#phần-4--seo--marketing)
- [PHẦN 5 — Security](#phần-5--security)
- [PHẦN 6 — Performance](#phần-6--performance)
- [PHẦN 7 — VPS Improvements](#phần-7--vps-improvements)
- [PHẦN 8 — VPS ↔ Webapp Integration](#phần-8--vps--webapp-integration)
- [PHẦN 9 — Admin Features](#phần-9--admin-features)
- [PHẦN 10 — Before/After Summary](#phần-10--beforeafter-summary)
- [PHẦN 11 — Implementation Roadmap](#phần-11--implementation-roadmap)

---

## PHẦN 1 — Bug Fixes (Ưu tiên cao)

---

### 1.1 VPS: Admin Follow Cache Không Được Xóa Sau Sync

**Mức độ:** Critical — dữ liệu admin_follow hiển thị sai cho user sau khi admin thêm/xóa mã.

#### Trạng thái hiện tại (BEFORE)
- `syncStocks10M.py` insert vào bảng `admin_follow` khi đồng bộ.
- `cacheHelper.py` có các hàm: `clear_stocks()`, `clear_user_follows()`, `clear_user_portfolios()`, `clear_status_sync()`, `clear_stock(code)`, `flush()`.
- **THIẾU:** không có hàm `clear_admin_follow()` trong `cacheHelper.py`.
- Kết quả: sau khi VPS cập nhật `admin_follow`, webapp vẫn dùng cache cũ → user thấy danh sách gợi ý theo dõi và gợi ý mua cũ (sai).
- Cache webapp cho admin_follow TTL = 86400s (1 ngày) → sai tối đa 24 giờ.

#### Trạng thái sau khi fix (AFTER)
- VPS gọi webapp clear cache sau mỗi lần sync admin_follow.
- User thấy dữ liệu gợi ý mới ngay sau lần sync tiếp theo.

#### Thay đổi cần thực hiện

**Server:** VPS

**File:** `/root/stocks/cacheHelper.py`

Thêm hàm mới vào cuối file (trước hoặc sau hàm `flush()`):

```python
def clear_admin_follow(caller_file=__file__):
    """Xóa cache admin_follow trên webapp sau khi VPS cập nhật bảng admin_follow."""
    url = f"{WEBAPP_URL}/api/cache/clear-table"
    data = json.dumps({"table": "admin_follow"})
    cmd = [
        "curl", "-s", "-X", "POST",
        "-H", f"X-Cron-Secret: {CRON_SECRET}",
        "-H", "Content-Type: application/json",
        "--http2",
        "-d", data,
        url
    ]
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
        logging.info(f"[cacheHelper] clear_admin_follow: {result.stdout.strip()}")
    except Exception as e:
        logging.error(f"[cacheHelper] clear_admin_follow error: {e}", exc_info=True)
```

**File:** `/root/stocks/syncStocks10M.py` (hoặc file nào đang insert `admin_follow`)

Sau đoạn code insert/update admin_follow, gọi:
```python
from cacheHelper import clear_admin_follow
clear_admin_follow(__file__)
```

**Lưu ý:** Webapp đã hỗ trợ sẵn endpoint `/api/cache/clear-table` với table `admin_follow` (CacheService.php đã có `case 'admin_follow'`). Chỉ cần thêm phía VPS.

---

### 1.2 Webapp: Sitemap Thiếu `/gioi-thieu` và `/lien-he`

**Mức độ:** Medium — ảnh hưởng SEO, Google không index 2 trang mới.

#### Trạng thái hiện tại (BEFORE)
Sitemap XML tại `/sitemap.xml` chỉ có 4 URL:
- `/trang-chu` (priority 1.0)
- `/dang-nhap` (priority 0.7)
- `/dang-ky` (priority 0.6)
- `/quen-mat-khau` (priority 0.4)

Hai trang `/gioi-thieu` và `/lien-he` đã implemented nhưng bị bỏ quên trong sitemap.

#### Trạng thái sau khi fix (AFTER)
Sitemap có 6 URL, 2 trang public mới được Google crawl và index.

#### Thay đổi cần thực hiện

**Server:** Webapp

**File:** `routes/web.php` — bên trong closure của `Route::get('/sitemap.xml', ...)` tại dòng ~46

Thêm vào mảng `$urls`:
```php
['loc' => route('about'),   'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'],
['loc' => route('contact'), 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.7'],
```

Sau khi thêm cũng cần xóa cache sitemap cũ:
```bash
php artisan cache:forget sitemap_xml
```

---

### 1.3 Security: Email Enumeration trong AuthService

**Mức độ:** Medium-High — lộ thông tin email nào đã đăng ký.

#### Trạng thái hiện tại (BEFORE)
**File:** `app/Services/AuthService.php` dòng ~72

```php
// Khi quên mật khẩu, trả về thông báo rõ ràng:
return ['status' => 'error', 'message' => 'Email không tồn tại.'];
```

Attacker có thể brute-force để biết email nào đã đăng ký trong hệ thống.

#### Trạng thái sau khi fix (AFTER)
Dù email tồn tại hay không, response luôn như nhau:
```
"Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu."
```

#### Thay đổi cần thực hiện

**Server:** Webapp

**File:** `app/Services/AuthService.php`

Thay đổi method `forgotPassword()`:
```php
// BEFORE
if (!$user) {
    return ['status' => 'error', 'message' => 'Email không tồn tại.'];
}
// ... tạo token, gửi mail
return ['status' => 'success', 'message' => 'Email đặt lại mật khẩu đã được gửi.'];

// AFTER — luôn trả về cùng message
if ($user) {
    // ... tạo token, gửi mail
}
return ['status' => 'success', 'message' => 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.'];
```

---

## PHẦN 2 — Tính Năng Mới

---

### 2.1 P&L Chart Over Time (Biểu đồ lãi/lỗ theo thời gian)

#### Mô tả
Hiển thị biểu đồ đường thể hiện tổng giá trị danh mục và P&L qua từng ngày/tuần/tháng. Giúp user thấy được xu hướng danh mục của mình.

#### Kiến trúc

```
VPS (cron hàng ngày) → snapshot toàn bộ danh mục user → INSERT vào portfolio_snapshots
Webapp → query portfolio_snapshots → JSON API → Chart JS/CSS
```

#### SQL — Bảng mới cần tạo

```sql
-- Migration: 2026_05_16_000001_create_portfolio_snapshots_table.php
CREATE TABLE portfolio_snapshots (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    snapshot_date   DATE NOT NULL,
    total_cost      DECIMAL(20, 2) NOT NULL DEFAULT 0  COMMENT 'Tổng tiền đã bỏ ra (giá mua × qty)',
    total_value     DECIMAL(20, 2) NOT NULL DEFAULT 0  COMMENT 'Tổng giá trị theo giá hiện tại',
    total_pnl       DECIMAL(20, 2) NOT NULL DEFAULT 0  COMMENT 'total_value - total_cost',
    total_roi       DECIMAL(10, 4) NOT NULL DEFAULT 0  COMMENT '(total_pnl / total_cost) * 100, đơn vị %',
    cash_balance    DECIMAL(20, 2) NOT NULL DEFAULT 0  COMMENT 'Số dư ví ảo tại thời điểm snapshot',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    UNIQUE KEY uniq_user_date (user_id, snapshot_date),
    INDEX idx_user_id (user_id),
    INDEX idx_snapshot_date (snapshot_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Logic Flow

**VPS (cron hàng ngày, chạy sau khi sync giá xong — khoảng 15:30-16:00 giờ VN):**

```python
# File mới: /root/stocks/snapshotPortfolio.py
# 1. Lấy tất cả users có portfolio đang mở (session_closed_flag = 0)
# 2. Với mỗi user, tính:
#    - total_cost: SUM(buy_price * quantity) WHERE session_closed_flag = 0
#    - total_value: SUM(current_price * quantity) — join với stocks
#    - total_pnl: total_value - total_cost
#    - total_roi: total_pnl / total_cost * 100
#    - cash_balance: cash_follow.cash
# 3. INSERT INTO portfolio_snapshots (user_id, snapshot_date, ...) VALUES (...)
#    ON DUPLICATE KEY UPDATE ... (idempotent, chạy nhiều lần không sao)
# 4. Gọi clear cache nếu cần

# SQL query mẫu:
SELECT
    up.user_id,
    SUM(up.buy_price * up.quantity) AS total_cost,
    SUM(s.current_price * up.quantity) AS total_value
FROM user_portfolios up
JOIN stocks s ON s.id = up.stock_id
WHERE up.session_closed_flag = 0
GROUP BY up.user_id;
```

**Webapp — Routes mới:**

```
GET /user/portfolio-chart          → UserController@portfolioChart (view)
GET /user/portfolio-chart/data     → UserController@portfolioChartData (JSON)
```

**Webapp — Controller:**

```php
// UserController@portfolioChartData — JSON API
public function portfolioChartData(Request $request)
{
    $userId = Auth::id();
    $period = $request->query('period', '3m'); // 1m, 3m, 6m, 1y, all

    $from = match($period) {
        '1m'  => now()->subMonth(),
        '3m'  => now()->subMonths(3),
        '6m'  => now()->subMonths(6),
        '1y'  => now()->subYear(),
        'all' => now()->subYears(10),
        default => now()->subMonths(3),
    };

    $snapshots = PortfolioSnapshot::where('user_id', $userId)
        ->where('snapshot_date', '>=', $from->toDateString())
        ->orderBy('snapshot_date')
        ->get(['snapshot_date', 'total_cost', 'total_value', 'total_pnl', 'total_roi', 'cash_balance']);

    return response()->json(['data' => $snapshots]);
}
```

**Webapp — Chart rendering:**

Dùng `Chart.js` (CDN — không thêm vào Vite để tránh tăng bundle size). Import chỉ trên trang portfolio chart:
```blade
@push('header-js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush
```

Chart: Line chart, 2 đường — `Giá trị danh mục` (xanh) và `Chi phí đầu tư` (xám). Khu vực giữa tô màu xanh (lãi) hoặc đỏ (lỗ).

**Files cần tạo mới:**
- `database/migrations/2026_05_16_000001_create_portfolio_snapshots_table.php`
- `app/Models/PortfolioSnapshot.php`
- `/root/stocks/snapshotPortfolio.py` (VPS)
- `resources/views/User/UserPortfolioChart.blade.php`
- `resources/css/pages/user-portfolio-chart.css`
- `resources/js/pages/user-portfolio-chart.js`

**Files cần sửa:**
- `routes/web.php` — thêm 2 routes
- `app/Http/Controllers/User/User.php` — thêm 2 methods
- `vite.config.js` — đăng ký CSS/JS mới
- VPS crontab: thêm entry chạy `snapshotPortfolio.py` hàng ngày lúc 16:00

---

### 2.2 Risk History UI (Lịch sử thay đổi Risk Level)

#### Mô tả
Hiển thị timeline lịch sử thay đổi mức độ rủi ro (risk level) của từng mã cổ phiếu. Bảng `stock_risk_history` đã có 684 rows nhưng webapp chưa có UI.

#### Kiến trúc

**Bảng đã tồn tại:** `stock_risk_history`

Cấu trúc dự kiến (cần xác minh qua `SHOW COLUMNS FROM stock_risk_history` trên VPS):
```sql
-- Cấu trúc DỰ KIẾN (cần verify):
-- id              BIGINT PK AUTO_INCREMENT
-- code            VARCHAR(10)        -- mã cổ phiếu (hoặc stock_id FK)
-- risk_level      INT                -- 1=An toàn, 2=Cảnh báo, 3=Hạn chế, 4=Đình chỉ
-- event_date      DATE               -- ngày có hiệu lực
-- created_at      TIMESTAMP
```

> **⚠️ Lưu ý:** Phải chạy `SHOW COLUMNS FROM stock_risk_history` trên VPS trước khi code để xác nhận tên cột chính xác.

#### Logic Flow

**Webapp — Routes mới:**

```
GET /co-phieu/{code}/lich-su-risk   → StockController@riskHistory (hoặc thêm vào PagesController)
```

Hoặc implement dưới dạng **modal** trong trang chủ — click vào mức risk của mã nào đó → modal hiện timeline.

**Đề xuất:** Dùng modal trên trang chủ (`UserView`) vì đơn giản hơn, không cần route mới:

```
GET /user/stock/{code}/risk-history    → JSON → UserController@stockRiskHistory
```

```php
public function stockRiskHistory(string $code)
{
    $code = strtoupper($code);
    $history = DB::table('stock_risk_history')
        ->where('code', $code)                  // tên cột cần verify
        ->orderBy('event_date', 'desc')
        ->limit(50)
        ->get();

    return response()->json(['data' => $history]);
}
```

**UI Design:**

```
Modal: "Lịch sử Risk — {CODE}"
─────────────────────────────────
  2026-03-15   [■ An toàn]     →  [■ Cảnh báo]
  2026-01-10   [■ Cảnh báo]   →  [■ An toàn]
  2025-11-20   [■ An toàn]    →  [■ Cảnh báo]
  ...
─────────────────────────────────
Timeline dạng list, màu theo risk level:
  1 = xanh lá (#10b981)
  2 = vàng (#f59e0b)
  3 = cam (#ef4444)
  4 = đỏ đậm (#7f1d1d)
```

**Files cần tạo:**
- `app/Models/StockRiskHistory.php`

**Files cần sửa:**
- `app/Http/Controllers/User/User.php` — thêm method `stockRiskHistory()`
- `routes/web.php` — thêm route
- `resources/views/User/UserView.blade.php` — thêm modal HTML
- `resources/js/pages/user-home.js` — thêm logic mở modal + fetch JSON

---

### 2.3 Dividend History UI (Lịch sử cổ tức)

#### Mô tả
Hiển thị lịch sử cổ tức/quyền của từng mã cổ phiếu. Bảng `dividend_adjustments` đã có 11 rows.

#### Kiến trúc

**Bảng đã tồn tại:** `dividend_adjustments`

Cấu trúc dự kiến (cần verify):
```sql
-- Cấu trúc DỰ KIẾN (cần verify):
-- id                  BIGINT PK
-- code                VARCHAR(10)    -- mã CK (hoặc stock_id)
-- ex_date             DATE           -- ngày giao dịch không hưởng quyền
-- dividend_per_share  DECIMAL        -- giá trị cổ tức/CP (hoặc tỷ lệ nếu stock dividend)
-- type                VARCHAR(50)    -- 'cash', 'stock', 'split'
-- ratio               DECIMAL        -- tỷ lệ (cho stock dividend/split)
-- created_at          TIMESTAMP
```

> **⚠️ Lưu ý:** Phải xác minh cột trước khi implement.

#### Logic Flow

Implement tương tự risk history — route JSON + modal trong trang chủ hoặc trang Stock Detail (2.5).

**Route:**
```
GET /user/stock/{code}/dividend-history   → UserController@stockDividendHistory (JSON)
```

**UI Design:**

```
Modal: "Lịch sử cổ tức — {CODE}"
───────────────────────────────────
  Ex-date        Loại      Giá trị/Tỷ lệ
  2025-12-10    Tiền mặt   500 đồng/CP
  2024-11-15    Cổ phiếu   10% (1:10)
  2024-06-20    Tiền mặt   800 đồng/CP
───────────────────────────────────
```

**Files cần tạo:**
- `app/Models/DividendAdjustment.php`

**Files cần sửa:**
- `app/Http/Controllers/User/User.php` — thêm method `stockDividendHistory()`
- `routes/web.php` — thêm route
- `resources/views/User/UserView.blade.php` — thêm modal

---

### 2.4 Export Portfolio (PDF / Excel / CSV)

#### Mô tả
Cho phép user tải về danh mục đầu tư dưới dạng CSV (đơn giản, không cần package) hoặc PDF (cần package).

#### Kiến trúc

**Đề xuất triển khai theo 3 giai đoạn:**

**Giai đoạn 1 (nhanh, không cần package):** CSV Export
```
GET /user/portfolio/export/csv   → Stream download file CSV
```

**Giai đoạn 2 (cần package):** PDF Export
- Package: `barryvdh/laravel-dompdf` — thêm vào `composer.json`
- `GET /user/portfolio/export/pdf`

**Giai đoạn 3 (cần package):** Excel Export
- Package: `maatwebsite/laravel-excel`
- `GET /user/portfolio/export/excel`

**Đề xuất thực hiện Giai đoạn 1 trước** vì không cần thêm dependency.

#### Logic CSV Export

```php
// UserController@exportPortfolioCsv
public function exportPortfolioCsv()
{
    $userId = Auth::id();
    $portfolio = UserPortfolio::getPortfolioWithStockInfo($userId);

    $filename = 'danh-muc-' . now()->format('Ymd-His') . '.csv';
    $headers = [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ];

    $callback = function() use ($portfolio) {
        $handle = fopen('php://output', 'w');
        // UTF-8 BOM để Excel đọc được tiếng Việt
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Mã CP', 'Số lượng', 'Giá mua TB', 'Giá hiện tại', 'Giá trị', 'Lãi/Lỗ', 'ROI (%)']);
        foreach ($portfolio as $row) {
            fputcsv($handle, [
                $row->code,
                $row->total_quantity,
                number_format($row->avg_buy_price, 0),
                number_format($row->current_price, 0),
                number_format($row->current_price * $row->total_quantity, 0),
                number_format(($row->current_price - $row->avg_buy_price) * $row->total_quantity, 0),
                round(($row->current_price / $row->avg_buy_price - 1) * 100, 2),
            ]);
        }
        fclose($handle);
    };

    return response()->stream($callback, 200, $headers);
}
```

**Files cần sửa:**
- `app/Http/Controllers/User/User.php` — thêm methods export
- `routes/web.php` — thêm routes
- `resources/views/User/UserProfile.blade.php` — thêm nút Download CSV/PDF

---

### 2.5 Stock Detail Page (Trang chi tiết cổ phiếu)

#### Mô tả
Trang public `/co-phieu/{code}` hiển thị toàn bộ thông tin về một mã cổ phiếu: giá, risk, recommended price, lịch sử risk, lịch sử cổ tức, danh mục user (nếu đăng nhập).

**Lợi ích SEO:** Mỗi mã CK có trang riêng → Google index từng trang → tăng organic traffic.

#### Routes

```
GET /co-phieu/{code}    → PagesController@stockDetail (public, không cần auth)
```

#### Logic Flow

```php
// PagesController@stockDetail
public function stockDetail(string $code)
{
    $code = strtoupper($code);
    $stock = Stock::getByCode($code);

    if (!$stock) {
        abort(404, "Mã cổ phiếu {$code} không tồn tại.");
    }

    $riskHistory = DB::table('stock_risk_history')
        ->where('code', $code)
        ->orderBy('event_date', 'desc')
        ->limit(20)
        ->get();

    $dividendHistory = DB::table('dividend_adjustments')
        ->where('code', $code)
        ->orderBy('ex_date', 'desc')
        ->limit(20)
        ->get();

    // Nếu đã đăng nhập — lấy danh mục của user với mã này
    $userHolding = null;
    if (Auth::check() && Auth::user()->role === 0) {
        $userHolding = UserPortfolio::getStockHolding(Auth::id(), $stock->id);
    }

    return view('Pages.StockDetailView', compact('stock', 'riskHistory', 'dividendHistory', 'userHolding'));
}
```

#### UI Design

```
┌─────────────────────────────────────────────────────┐
│  ← Quay lại         VNM — Vinamilk                  │
│  Risk: [■ An toàn]  Giá: 72,000₫  (+1.2%)          │
├─────────────────────────────────────────────────────┤
│  THÔNG TIN CƠ BẢN                                   │
│  Giá mua khuyến nghị:  68,500₫                      │
│  Giá bán khuyến nghị:  75,000₫                      │
│  Giá trung bình 1008 phiên: 70,200₫                 │
│  Volume: 1,234,567  |  Volume TB: 980,000           │
├─────────────────────────────────────────────────────┤
│  LỊCH SỬ RISK (timeline)                            │
│  2026-03-15  Cảnh báo → An toàn                     │
│  ...                                                │
├─────────────────────────────────────────────────────┤
│  LỊCH SỬ CỔ TỨC                                     │
│  2025-12-10  Tiền mặt  500đ/CP                      │
│  ...                                                │
├─────────────────────────────────────────────────────┤
│  [CẦN AUTH] Danh mục của bạn với VNM               │
│  Số lượng: 1,000 CP  |  Giá mua TB: 68,200₫        │
│  Lãi: +3,800,000₫ (+5.57%)                         │
└─────────────────────────────────────────────────────┘
```

#### SEO cho trang stock detail

```blade
@section('seo')
    @include('partials.seo-public', [
        'pageTitle'   => $stock->code . ' — ' . config('app.name'),
        'description' => 'Theo dõi giá cổ phiếu ' . $stock->code . ', giá hiện tại ' . number_format($stock->current_price) . '₫, lịch sử risk và cổ tức.',
        'canonical'   => url('/co-phieu/' . $stock->code),
    ])
@endsection
```

**Files cần tạo:**
- `resources/views/Pages/StockDetailView.blade.php`
- `resources/css/pages/stock-detail.css`

**Files cần sửa:**
- `app/Http/Controllers/Pages/PagesController.php` — thêm method `stockDetail()`
- `routes/web.php` — thêm route + cập nhật sitemap để include tất cả stock URLs
- `vite.config.js` — đăng ký `stock-detail.css`

---

### 2.6 In-app Notification Badge (Thông báo trong ứng dụng)

#### Mô tả
Badge số đỏ trên icon chuông trong navbar, hiển thị số thông báo chưa đọc. VPS webhook gửi notification sau mỗi lần sync hoặc khi giá chạm ngưỡng follow.

#### SQL — Bảng mới cần tạo

```sql
-- Migration: 2026_05_16_000002_create_notifications_table.php
CREATE TABLE notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    type        VARCHAR(50) NOT NULL                COMMENT 'follow_alert, risk_change, system, sync_done',
    title       VARCHAR(255) NOT NULL,
    body        TEXT,
    data        JSON                                COMMENT 'payload: {code, old_risk, new_risk, ...}',
    read_at     TIMESTAMP NULL DEFAULT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    
    INDEX idx_user_unread (user_id, read_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Logic Flow

**VPS → Webapp Webhook:**

```python
# cacheHelper.py thêm hàm:
def notify_user(user_id: int, type: str, title: str, body: str, data: dict = {}):
    """Gửi in-app notification cho user qua webapp API."""
    url = f"{WEBAPP_URL}/api/notifications/create"
    payload = json.dumps({
        "user_id": user_id,
        "type": type,
        "title": title,
        "body": body,
        "data": data
    })
    cmd = ["curl", "-s", "-X", "POST",
           "-H", f"X-Cron-Secret: {CRON_SECRET}",
           "-H", "Content-Type: application/json",
           "--http2", "-d", payload, url]
    subprocess.run(cmd, capture_output=True, timeout=10)
```

**Webapp — API nhận webhook từ VPS:**

```
POST /api/notifications/create    middleware: cron.secret
```

```php
// NotificationController@create (mới)
public function create(Request $request): JsonResponse
{
    $data = $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'type'    => 'required|string|max:50',
        'title'   => 'required|string|max:255',
        'body'    => 'nullable|string',
        'data'    => 'nullable|array',
    ]);

    Notification::create($data);

    return response()->json(['status' => 'ok']);
}
```

**Webapp — API cho frontend:**

```
GET /user/notifications/count     → JSON {unread_count: 3}
GET /user/notifications           → JSON list (paginated)
POST /user/notifications/read-all → mark all as read
POST /user/notifications/{id}/read → mark one as read
```

**Frontend — Badge UI:**

Trong `Layout.blade.php` (navbar), thêm icon chuông với badge:
```blade
@auth
    @if(Auth::user()->role === 0)
    <a href="#" id="notif-bell" class="notif-bell-btn" aria-label="Thông báo">
        🔔 <span id="notif-badge" class="notif-badge hidden">0</span>
    </a>
    @endif
@endauth
```

`app.js` (hoặc User.js) polling mỗi 60 giây:
```js
async function fetchNotifCount() {
    const res = await axios.get('/user/notifications/count');
    const count = res.data.unread_count;
    const badge = document.getElementById('notif-badge');
    if (badge) {
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
    }
}
setInterval(fetchNotifCount, 60000);
fetchNotifCount();
```

**Files cần tạo:**
- `database/migrations/2026_05_16_000002_create_notifications_table.php`
- `app/Models/Notification.php` (đặt tên `AppNotification` để tránh trùng với Laravel Notification facade, hoặc namespace riêng)
- `app/Http/Controllers/Api/NotificationController.php`
- `app/Http/Controllers/User/NotificationController.php`
- `resources/views/User/UserNotifications.blade.php`
- `resources/css/pages/user-notifications.css`

**Files cần sửa:**
- `routes/web.php` — thêm user notification routes
- `routes/api.php` — thêm `/api/notifications/create`
- `resources/views/Layout/Layout.blade.php` — thêm bell icon + badge
- `resources/js/app.js` hoặc `User.js` — thêm polling logic

---

### 2.7 Public Watchlist / Community (Danh sách theo dõi cộng đồng)

#### Mô tả
User có thể tạo danh sách cổ phiếu và chia sẻ công khai. Người khác (kể cả guest) có thể xem và copy danh sách về follow của mình.

#### SQL — Bảng mới cần tạo

```sql
-- Migration: 2026_05_16_000003_create_community_watchlists_table.php
CREATE TABLE community_watchlists (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE              COMMENT 'URL-friendly: vnm-fpt-top-picks',
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    is_public   TINYINT(1) NOT NULL DEFAULT 1,
    view_count  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_public (is_public),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration: 2026_05_16_000004_create_community_watchlist_stocks_table.php
CREATE TABLE community_watchlist_stocks (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    watchlist_id    BIGINT UNSIGNED NOT NULL,
    stock_id        BIGINT UNSIGNED NOT NULL,
    note            VARCHAR(255),
    created_at      TIMESTAMP NULL,
    
    UNIQUE KEY uniq_watchlist_stock (watchlist_id, stock_id),
    FOREIGN KEY (watchlist_id) REFERENCES community_watchlists(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Routes

```
GET  /watchlist                    → public, danh sách watchlist nổi bật
GET  /watchlist/{slug}             → public, xem chi tiết 1 watchlist
POST /user/watchlist               → tạo watchlist mới (auth required)
PUT  /user/watchlist/{id}          → sửa watchlist (auth + owner)
DELETE /user/watchlist/{id}        → xóa (auth + owner)
POST /user/watchlist/{id}/stocks   → thêm mã vào watchlist
DELETE /user/watchlist/{id}/stocks/{stockId} → xóa mã
POST /user/watchlist/{id}/follow-all → copy toàn bộ vào user follow
```

**Files cần tạo:**
- `app/Models/CommunityWatchlist.php`
- `app/Models/CommunityWatchlistStock.php`
- `app/Http/Controllers/Pages/WatchlistController.php` (public views)
- `app/Http/Controllers/User/WatchlistController.php` (CRUD)
- `resources/views/Pages/WatchlistIndexView.blade.php`
- `resources/views/Pages/WatchlistDetailView.blade.php`
- `resources/views/User/UserWatchlistView.blade.php`

---

### 2.8 Page Visit Tracking (Theo dõi lượt truy cập)

#### Mô tả
Ghi lại mọi lượt truy cập trang của cả user đã đăng nhập lẫn anonymous. Admin xem thống kê: user nào truy cập bao nhiêu lần/tuần/tháng/năm, trang nào được xem nhiều nhất.

#### SQL — Bảng mới cần tạo

```sql
-- Migration: 2026_05_16_000005_create_page_visits_table.php
CREATE TABLE page_visits (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NULL                          COMMENT 'NULL = anonymous visitor',
    session_id  VARCHAR(64) NOT NULL                          COMMENT 'Laravel session ID',
    page        VARCHAR(500) NOT NULL                         COMMENT 'Path: /trang-chu, /user/profile, ...',
    page_title  VARCHAR(255)                                  COMMENT 'Tên trang dễ đọc',
    method      VARCHAR(10) NOT NULL DEFAULT 'GET',
    ip_address  VARCHAR(45),
    user_agent  VARCHAR(512),
    visited_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_visited_at (visited_at),
    INDEX idx_page (page(100)),
    INDEX idx_session_id (session_id),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Lý do dùng `ON DELETE SET NULL`:** Khi admin xóa user, vẫn giữ lại dữ liệu visit để phân tích; chỉ set `user_id = NULL`.

#### Logic Flow

**Webapp — Middleware mới:**

```php
// app/Http/Middleware/TrackPageVisit.php
class TrackPageVisit
{
    // Danh sách path không cần track (asset, debug, API, etc.)
    private const SKIP_PREFIXES = [
        '/api/', '/__debug/', '/build/', '/storage/',
        '/robots.txt', '/sitemap.xml', '/favicon',
        '/admin/logsVPS/data', // polling endpoint
        '/user/notifications/count', // polling
        '/user/portfolio-chart/data', // JSON API
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Chỉ track GET requests, không track AJAX JSON responses
        if ($request->method() !== 'GET') {
            return $response;
        }

        $path = $request->path();
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with('/' . $path, $prefix)) {
                return $response;
            }
        }

        // Track trong background (không block response)
        try {
            DB::table('page_visits')->insert([
                'user_id'    => Auth::id(), // NULL nếu chưa đăng nhập
                'session_id' => $request->session()->getId(),
                'page'       => '/' . $path,
                'page_title' => $this->getPageTitle($path),
                'method'     => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 512),
                'visited_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail — không ảnh hưởng trải nghiệm user
            Log::warning('TrackPageVisit failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function getPageTitle(string $path): string
    {
        return match(true) {
            $path === 'trang-chu'          => 'Trang chủ',
            $path === 'gioi-thieu'         => 'Giới thiệu',
            $path === 'lien-he'            => 'Liên hệ',
            str_starts_with($path, 'co-phieu/') => 'Chi tiết: ' . strtoupper(substr($path, 9)),
            $path === 'dang-nhap'          => 'Đăng nhập',
            $path === 'dang-ky'            => 'Đăng ký',
            str_starts_with($path, 'user/') => 'User: ' . $path,
            str_starts_with($path, 'admin/') => 'Admin: ' . $path,
            default                        => $path,
        };
    }
}
```

**Đăng ký middleware trong `app/Http/Kernel.php`:**

```php
protected $middleware = [
    // ... các middleware hiện có
    \App\Http\Middleware\TrackPageVisit::class,
];
```

Đặt sau `SecurityHeaders` trong `$middleware` array (global middleware).

#### Admin — Quản lý truy cập

**Route mới:**

```
GET /admin/access-management         → Admin@accessManagement (view)
GET /admin/access-management/data    → Admin@accessManagementData (JSON cho AJAX filter)
```

**Controller logic:**

```php
// Admin@accessManagement
public function accessManagement(Request $request)
{
    $period = $request->query('period', 'week'); // week, month, year
    $from = match($period) {
        'week'  => now()->startOfWeek(),
        'month' => now()->startOfMonth(),
        'year'  => now()->startOfYear(),
        default => now()->startOfWeek(),
    };

    // Top users by visit count
    $topUsers = DB::table('page_visits')
        ->join('users', 'users.id', '=', 'page_visits.user_id')
        ->where('page_visits.visited_at', '>=', $from)
        ->whereNotNull('page_visits.user_id')
        ->select(
            'users.id',
            'users.name',
            'users.email',
            DB::raw('COUNT(*) as visit_count'),
            DB::raw('MAX(page_visits.visited_at) as last_visit')
        )
        ->groupBy('users.id', 'users.name', 'users.email')
        ->orderByDesc('visit_count')
        ->limit(50)
        ->get();

    // Top pages
    $topPages = DB::table('page_visits')
        ->where('visited_at', '>=', $from)
        ->select('page', 'page_title', DB::raw('COUNT(*) as view_count'))
        ->groupBy('page', 'page_title')
        ->orderByDesc('view_count')
        ->limit(20)
        ->get();

    // Anonymous stats
    $anonymousCount = DB::table('page_visits')
        ->where('visited_at', '>=', $from)
        ->whereNull('user_id')
        ->count();

    $authenticatedCount = DB::table('page_visits')
        ->where('visited_at', '>=', $from)
        ->whereNotNull('user_id')
        ->count();

    return view('Admin.AdminAccessManagement', compact(
        'topUsers', 'topPages', 'anonymousCount', 'authenticatedCount', 'period'
    ));
}
```

**UI Design — Admin Quản lý truy cập:**

```
┌──────────────────────────────────────────────────────────────┐
│  Quản lý truy cập    [Tuần này ▼]  [Tháng này]  [Năm nay]  │
├──────────────────────────────────────────────────────────────┤
│  KPI tổng quan:                                              │
│  👤 Lượt truy cập có đăng nhập: 1,234                       │
│  👁 Lượt truy cập ẩn danh: 4,567                            │
├──────────────────────────────────────────────────────────────┤
│  TOP USERS (tuần này)                                        │
│  #  Tên           Email          Lượt   Lần cuối            │
│  1  Nguyễn Văn A  a@b.c          89     2026-05-16 09:45    │
│  2  Trần Thị B    b@c.d          45     2026-05-15 14:22    │
│  ...                                                         │
├──────────────────────────────────────────────────────────────┤
│  TOP TRANG được xem nhiều nhất                               │
│  /trang-chu         1,234 lượt                               │
│  /user/profile        456 lượt                               │
│  /gioi-thieu          123 lượt                               │
└──────────────────────────────────────────────────────────────┘
```

**Admin Nav — thêm menu item:**

File: `resources/views/partials/admin-nav-primary.blade.php`

Thêm link "Quản lý truy cập" vào navigation của admin.

**Files cần tạo:**
- `database/migrations/2026_05_16_000005_create_page_visits_table.php`
- `app/Http/Middleware/TrackPageVisit.php`
- `resources/views/Admin/AdminAccessManagement.blade.php`
- `resources/css/pages/admin-access-management.css`
- `resources/js/pages/admin-access-management.js` (filter period, table sort)

**Files cần sửa:**
- `app/Http/Kernel.php` — đăng ký middleware global
- `app/Http/Controllers/Admin/Admin.php` — thêm 2 methods
- `routes/web.php` — thêm 2 admin routes
- `resources/views/partials/admin-nav-primary.blade.php` — thêm menu item
- `vite.config.js` — đăng ký CSS/JS mới

**Lưu ý hiệu năng:**

Bảng `page_visits` có thể tăng rất nhanh (hàng nghìn rows/ngày). Cần:
1. **Cleanup cron job** — xóa records cũ hơn 1 năm, chạy hàng tuần
2. **Index hợp lý** — đã thiết kế index trên `user_id`, `visited_at`, `page`
3. **Cân nhắc partitioning** theo `visited_at` nếu traffic lớn (sau này)

---

## PHẦN 3 — UI/UX Improvements

---

### 3.1 Skeleton Loading

**Trạng thái hiện tại:** Khi tải trang, bảng dữ liệu trống rồi hiện lên đột ngột.

**Giải pháp:** Thêm CSS skeleton animation cho bảng dữ liệu chính.

**Implementation:** CSS-only, không cần JS library.

```css
/* Thêm vào app.css */
.skeleton-row td {
    background: linear-gradient(90deg,
        rgba(99, 179, 237, 0.05) 25%,
        rgba(99, 179, 237, 0.12) 50%,
        rgba(99, 179, 237, 0.05) 75%
    );
    background-size: 200% 100%;
    animation: skeleton-shimmer 1.5s infinite;
    border-radius: 4px;
    color: transparent;
}
@keyframes skeleton-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
```

**Trang áp dụng:** UserView (bảng giá), UserFollow, UserProfile.

**Files cần sửa:**
- `resources/css/app.css` — thêm skeleton CSS
- Các JS files tương ứng — thêm skeleton rows khi đang tải

---

### 3.2 Empty States (Trạng thái rỗng)

**Trạng thái hiện tại:** Khi danh sách trống, chỉ hiện bảng trống.

**Giải pháp:** Thêm empty state với icon + message + CTA.

```blade
{{-- Component: partials/empty-state.blade.php --}}
<div class="empty-state">
    <div class="empty-state__icon">{{ $icon ?? '📭' }}</div>
    <div class="empty-state__title">{{ $title }}</div>
    <div class="empty-state__desc">{{ $desc ?? '' }}</div>
    @if(isset($ctaText) && isset($ctaUrl))
        <a href="{{ $ctaUrl }}" class="btn-filter-primary">{{ $ctaText }}</a>
    @endif
</div>
```

Ví dụ usage trong UserFollow:
```blade
@if($follows->isEmpty())
    @include('partials.empty-state', [
        'icon'    => '🔔',
        'title'   => 'Chưa có mã nào được theo dõi',
        'desc'    => 'Thêm mã cổ phiếu để nhận cảnh báo giá tự động.',
        'ctaText' => 'Thêm theo dõi',
        'ctaUrl'  => route('user.insertFollow'),
    ])
@endif
```

**Files cần tạo:**
- `resources/views/partials/empty-state.blade.php`
- CSS cho `.empty-state` trong `app.css`

---

### 3.3 Onboarding Flow (Xác thực email + Resend)

**Trạng thái hiện tại:**
- User đăng ký → nhận email verify → không có nút resend nếu không nhận được.
- User 9 (production) vẫn `active=0` sau nhiều ngày — không có cách nào resend tự phục vụ.

**Giải pháp:** Thêm route resend verification email.

**Route:**
```
POST /email/resend-verification   → LoginController@resendVerification
```

**Logic:**
```php
public function resendVerification(Request $request)
{
    $user = User::where('email', $request->email)->first();
    if ($user && !$user->hasVerifiedEmail()) {
        $user->sendEmailVerificationNotification();
    }
    // Trả về generic message (tránh email enumeration)
    return response()->json(['message' => 'Nếu email tồn tại và chưa xác thực, chúng tôi đã gửi lại email.']);
}
```

**UI:** Trong trang login, khi hiện thông báo "Email chưa xác thực", thêm link/nút "Gửi lại email xác thực".

**Files cần sửa:**
- `app/Http/Controllers/Login/Login.php` — thêm method
- `routes/web.php` — thêm route
- `resources/views/Login/Login.blade.php` — thêm nút resend
- `resources/js/pages/login.js` — xử lý click resend

---

### 3.4 KPI Summary Cards trên Dashboard

**Trạng thái hiện tại:** Trang chủ user chỉ có bảng giá, không có overview về danh mục.

**Giải pháp:** 4 KPI cards ở đầu trang `UserView`:

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│ 💼 Danh mục │  │ 💰 Số dư   │  │ 📈 Tổng P&L │  │ 🎯 ROI TB  │
│   12 mã     │  │  45,000,000 │  │ +8,300,000  │  │  +18.4%    │
│             │  │             │  │             │  │            │
└─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘
```

**Implementation:** Computed trong PHP khi render `UserView`, không thêm query nặng (reuse cached data đã có).

---

## PHẦN 4 — SEO & Marketing

---

### 4.1 JSON-LD Schema

**Thêm vào `Layout.blade.php` (trang public):**

```blade
@if(Request::routeIs('home'))
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "{{ config('app.name') }}",
  "url": "{{ config('app.url') }}",
  "applicationCategory": "FinanceApplication",
  "description": "Nền tảng theo dõi danh mục cổ phiếu cá nhân tại thị trường Việt Nam",
  "offers": {"@type": "Offer", "price": "0", "priceCurrency": "VND"}
}
</script>
@endif
```

**Với Stock Detail Page (`/co-phieu/{code}`):**

```blade
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FinancialProduct",
  "name": "{{ $stock->code }}",
  "url": "{{ url('/co-phieu/' . $stock->code) }}",
  "price": "{{ $stock->current_price }}"
}
</script>
```

---

### 4.2 Open Graph Tags

**Thêm vào `seo-public.blade.php`:**

```blade
<meta property="og:type"        content="website">
<meta property="og:url"         content="{{ $canonical ?? url()->current() }}">
<meta property="og:title"       content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image"       content="{{ $ogImage ?? url('/og-default.jpg') }}">
<meta name="twitter:card"       content="summary_large_image">
<meta name="twitter:title"      content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $description }}">
```

---

### 4.3 Improved Sitemap

Sau khi thêm Stock Detail Page, cập nhật sitemap để include tất cả mã CK:

```php
// Trong routes/web.php — sitemap route
// Thêm stock detail URLs
$stocks = Stock::getAllStocks(); // Cached, không query thêm
foreach ($stocks as $stock) {
    $urls[] = [
        'loc'        => route('stock.detail', ['code' => strtolower($stock['code'])]),
        'lastmod'    => $stock['updated_at'] ?? $today,
        'changefreq' => 'daily',
        'priority'   => '0.6',
    ];
}
```

**Lưu ý:** Sitemap có thể lớn (200+ stocks). Cân nhắc cache riêng với TTL = 6 giờ thay vì 24 giờ.

---

## PHẦN 5 — Security

---

### 5.1 Rate Limiting Public Pages

**Trạng thái hiện tại:** Các trang public (`/trang-chu`, `/gioi-thieu`, `/lien-he`) không có rate limiting.

**Giải pháp:** Thêm rate limiter cho trang `/lien-he` POST (form contact) và cho stock detail page.

```php
// app/Http/Kernel.php hoặc RouteServiceProvider.php
RateLimiter::for('contact-form', function (Request $request) {
    return Limit::perMinute(3)->by($request->ip()); // 3 lần gửi form/phút/IP
});

RateLimiter::for('public-pages', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip()); // 60 req/phút/IP (đã có global 60)
});
```

**Áp dụng cho route contact:**

```php
Route::post('/lien-he', [PagesController::class, 'contact'])
    ->middleware('throttle:contact-form')
    ->name('contact');
```

---

### 5.2 Stricter CSP (Content Security Policy)

**Trạng thái hiện tại:** CSP trong `SecurityHeaders.php` cho phép một số CDN nhưng cần review.

**Thay đổi đề xuất:**

```php
// SecurityHeaders.php
$csp = "default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://fonts.gstatic.com; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self'; " .
    "frame-src 'none'; " .
    "object-src 'none'; " .
    "base-uri 'self';";
$response->headers->set('Content-Security-Policy', $csp);
```

**Lưu ý:** Sau khi thêm Chart.js CDN (feature 2.1), cần thêm `https://cdn.jsdelivr.net` vào `script-src`.

---

### 5.3 File Upload Protection

**Trạng thái hiện tại:** `ImportStocksCsvRequest` validate `file|max:2048` nhưng không check MIME type cụ thể.

**Cải thiện:**

```php
// ImportStocksCsvRequest.php
'csv_file' => [
    'required',
    'file',
    'max:2048',
    'mimes:csv,txt',                     // Thêm MIME check
    'mimetypes:text/csv,text/plain',     // Double-check actual MIME
],
```

---

### 5.4 Session Security

Kiểm tra và đảm bảo `config/session.php`:

```php
'secure'    => env('SESSION_SECURE_COOKIE', true),  // HTTPS only (production)
'http_only' => true,                                  // No JS access
'same_site' => 'lax',                                 // CSRF protection
'lifetime'  => 120,                                   // 2 giờ (giảm từ default 120 nếu đang khác)
```

**Thêm vào `.env` production:**

```
SESSION_SECURE_COOKIE=true
```

---

### 5.5 Sensitive Data trong Log

**Trạng thái hiện tại:** Cần review xem có log nào chứa `password`, `token`, `secret` không.

**Action:** Chạy Grep tìm `Log::` calls có thể leak sensitive data:

```bash
grep -r "Log::" app/ --include="*.php" | grep -E "password|token|secret|key" | grep -v ".env"
```

**Sau đó:** Mask hoặc xóa các log statement đó.

---

## PHẦN 6 — Performance

---

### 6.1 Cache Strategy Improvements

**Vấn đề 1:** `portfolio_snapshots` (feature mới) không cần cache riêng vì dữ liệu ít thay đổi trong ngày.

**Vấn đề 2:** Sitemap được cache 86400s nhưng có thể stale khi thêm stock mới.

**Giải pháp:** Sau khi insert/delete stock, clear cache sitemap:

```php
// StockService.php — sau insert/delete
CacheService::forget('sitemap_xml');
```

**Vấn đề 3:** `page_visits` insert on every request — xem xét dùng queue job nếu traffic cao.

**Giải pháp ban đầu:** INSERT trực tiếp (acceptable cho traffic thấp-trung). Khi traffic cao, switch sang `dispatch(new TrackVisitJob(...))->afterResponse()`.

---

### 6.2 DB Query Optimization

**Bảng `page_visits` cleanup job:**

Thêm vào `/api/admin/deleteLogs` endpoint (hoặc tạo endpoint mới):

```php
// Sync.php hoặc route mới
DB::table('page_visits')
    ->where('visited_at', '<', now()->subYear())
    ->delete();
```

**Index review:** Đảm bảo các bảng mới có index phù hợp (đã thiết kế trong SQL DDL ở trên).

---

## PHẦN 7 — VPS Improvements

---

### 7.1 Structured JSON Logging

**Trạng thái hiện tại:** VPS log dạng text thuần, khó parse.

**Giải pháp:** Thêm JSON formatter vào logging config VPS:

```python
# /root/stocks/logConfig.py (file mới)
import logging
import json
from datetime import datetime

class JsonFormatter(logging.Formatter):
    def format(self, record):
        return json.dumps({
            'time':    datetime.utcnow().isoformat(),
            'level':   record.levelname,
            'file':    record.filename,
            'message': record.getMessage(),
        }, ensure_ascii=False)
```

Sau đó import và dùng `JsonFormatter` trong mỗi script VPS.

---

### 7.2 Better Single-Stock Update API Response

**Trạng thái hiện tại:** `/admin/sync/run-update-stock/{code}` trả về response không rõ ràng khi lỗi.

**Cải thiện:** Chuẩn hóa response format:

```json
{
    "status": "success|error",
    "code": "VNM",
    "old_price": 70000,
    "new_price": 72000,
    "old_risk": 1,
    "new_risk": 1,
    "duration_ms": 1234,
    "message": "Sync thành công"
}
```

---

### 7.3 Health Check Endpoint

**Trạng thái hiện tại:** Không có endpoint kiểm tra trạng thái VPS service.

**Thêm route mới vào webapp:**

```
GET /api/health    → JSON: {status: 'ok', timestamp: '...', db: 'ok', cache: 'ok'}
```

Không cần `cron.secret` middleware — endpoint này public nhưng không lộ thông tin nhạy cảm.

---

## PHẦN 8 — VPS ↔ Webapp Integration

---

### 8.1 Webhook Sau Khi Sync Hoàn Thành

**Trạng thái hiện tại:** Sau khi VPS sync giá xong, webapp không biết → user phải F5 để thấy giá mới.

**Giải pháp:** VPS gọi webhook sau mỗi sync batch → Webapp lưu timestamp vào cache → Frontend so sánh timestamp để biết có dữ liệu mới không.

**Flow:**

```
VPS sync done → POST /api/sync/completed (X-Cron-Secret) → Webapp lưu cache 'last_sync_at' = now()
Frontend poll GET /api/sync/status mỗi 30s → nếu last_sync_at mới hơn lần check cuối → show toast "Giá đã cập nhật. Tải lại?"
```

**Webapp API mới:**

```php
// routes/api.php
Route::post('/sync/completed', [SyncStatusController::class, 'completed'])
    ->middleware('cron.secret');

Route::get('/sync/status', [SyncStatusController::class, 'status']); // public
```

```php
// SyncStatusController@completed
public function completed(): JsonResponse
{
    Cache::put('last_sync_at', now()->toIso8601String(), 86400);
    return response()->json(['status' => 'ok']);
}

// SyncStatusController@status
public function status(): JsonResponse
{
    return response()->json([
        'last_sync_at' => Cache::get('last_sync_at'),
    ]);
}
```

**VPS `cacheHelper.py` thêm:**

```python
def notify_sync_completed():
    """Thông báo webapp rằng sync đã hoàn thành."""
    url = f"{WEBAPP_URL}/api/sync/completed"
    cmd = ["curl", "-s", "-X", "POST",
           "-H", f"X-Cron-Secret: {CRON_SECRET}",
           "--http2", url]
    subprocess.run(cmd, capture_output=True, timeout=10)
```

---

### 8.2 Real-time Sync Status Indicator

**Trạng thái hiện tại:** Admin thấy icon xoay "Đang sync" nhưng không biết khi nào xong.

**Cải thiện:** Thêm "Lần cập nhật gần nhất: 10 phút trước" hiển thị trên trang chủ user và admin dashboard, dùng `last_sync_at` từ cache.

---

## PHẦN 9 — Admin Features

---

### 9.1 Analytics Dashboard

**Thêm section vào `/admin` (AdminView) với:**

- Tổng users (đã active / chưa active)
- Users đăng nhập trong 7 ngày qua
- Tổng stocks đang theo dõi
- Tổng lượt truy cập hôm nay (query `page_visits` — từ feature 2.8)
- Biểu đồ sparkline đăng ký user theo ngày (7 ngày gần nhất)

**SQL cho stats:**

```sql
-- Users active trong 7 ngày
SELECT COUNT(DISTINCT user_id) FROM page_visits
WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND user_id IS NOT NULL;

-- Đăng ký mới theo ngày (7 ngày)
SELECT DATE(created_at) as date, COUNT(*) as count
FROM users
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
```

---

### 9.2 Rating Price Management UI

**Trạng thái hiện tại:** `rating_stocks` và `recommended_buy_price`, `recommended_sell_price` được tính tự động bởi VPS. Admin không có UI để xem phân phối rating.

**Giải pháp:** Thêm tab hoặc section trong `AdminStockManagement` hiển thị:
- Phân phối rating (histogram)
- Stocks có rating cao nhất / thấp nhất
- Lọc stock theo rating range

---

### 9.3 Sync History View

**Trạng thái hiện tại:** `stock_status_logs` có bảng nhưng chỉ có `id` + timestamps.

**Cần VPS update:** Khi sync xong, insert vào `stock_status_logs`:

```sql
ALTER TABLE stock_status_logs
    ADD COLUMN action        VARCHAR(50)  NOT NULL DEFAULT 'sync_price',
    ADD COLUMN stocks_synced INT          NOT NULL DEFAULT 0,
    ADD COLUMN duration_ms   INT          NOT NULL DEFAULT 0,
    ADD COLUMN status        VARCHAR(20)  NOT NULL DEFAULT 'success',
    ADD COLUMN error_message TEXT,
    ADD INDEX idx_created_at (created_at);
```

**Webapp:** Thêm route `/admin/sync-history` hiển thị log sync 30 ngày gần nhất.

---

## PHẦN 10 — Before/After Summary

### Tóm tắt Before/After cho từng hạng mục

| # | Hạng mục | BEFORE (hiện tại) | AFTER (sau khi implement) |
|---|----------|-------------------|--------------------------|
| 1.1 | Admin Follow Cache VPS | VPS sync → admin_follow → cache webapp KHÔNG được xóa → user thấy gợi ý cũ tối đa 24h | VPS sync → xóa cache ngay → user thấy gợi ý mới |
| 1.2 | Sitemap | 4 URLs, thiếu `/gioi-thieu` và `/lien-he` | 6 URLs, Google index đầy đủ |
| 1.3 | Email Enumeration | "Email không tồn tại" → lộ thông tin user | Message chung, không lộ |
| 2.1 | P&L Chart | Không có biểu đồ lãi/lỗ theo thời gian | Biểu đồ đường interactive, lọc 1m/3m/6m/1y |
| 2.2 | Risk History UI | 684 rows trong DB nhưng không hiển thị | Modal timeline risk trên trang chủ |
| 2.3 | Dividend History UI | 11 rows trong DB nhưng không hiển thị | Modal lịch sử cổ tức |
| 2.4 | Export Portfolio | Không có | Download CSV/PDF danh mục |
| 2.5 | Stock Detail Page | Không có trang riêng mỗi mã CK | `/co-phieu/{code}` — trang đầy đủ, SEO |
| 2.6 | Notification Badge | Chỉ email, không có in-app | Badge số đỏ trên navbar, real-time |
| 2.7 | Community Watchlist | Không có | Tạo và share danh sách công khai |
| 2.8 | Page Visit Tracking | Không theo dõi | Admin thấy ai xem gì, bao nhiêu lần/tuần/tháng/năm |
| 3.1 | Skeleton Loading | Trang trắng khi load | Shimmer animation |
| 3.2 | Empty States | Bảng trống rỗng | Icon + message + CTA |
| 3.3 | Onboarding | Không có nút resend verify | Nút "Gửi lại email xác thực" |
| 3.4 | KPI Cards | Không có overview danh mục | 4 KPI cards đầu trang |
| 4.1 | JSON-LD | Không có | Schema.org cho WebApplication và FinancialProduct |
| 4.2 | Open Graph | Không có | OG tags đầy đủ trên trang public |
| 4.3 | Sitemap | 4 URLs | 200+ URLs (all stocks + pages) |
| 5.1 | Rate Limit Public | Không có | 3 lần gửi form contact/phút/IP |
| 5.3 | Email Enumeration | Lộ info | Generic message |
| 6.1 | Cache Sitemap | Stale khi thêm stock | Tự invalidate sau insert/delete stock |
| 7.1 | VPS Logging | Text thuần | JSON structured |
| 8.1 | Sync Webhook | Không có | Toast "Giá đã cập nhật" sau mỗi sync |
| 9.1 | Admin Analytics | Không có | Dashboard với stats user/traffic |
| 9.3 | Sync History | `stock_status_logs` trống | Log lịch sử sync 30 ngày |

---

## PHẦN 11 — Implementation Roadmap

### Ưu tiên và thứ tự thực hiện đề xuất

#### Sprint 1 — Fixes ngay (1-2 ngày)
1. ✅ Fix VPS `clear_admin_follow()` (1 giờ)
2. ✅ Fix Sitemap thêm `/gioi-thieu` + `/lien-he` (15 phút)
3. ✅ Fix Email Enumeration AuthService (15 phút)
4. ✅ Fix CSV upload MIME validation (10 phút)

#### Sprint 2 — High-value, ít công (3-5 ngày)
5. Page Visit Tracking (2.8) — Middleware + bảng `page_visits` + Admin view
6. KPI Summary Cards (3.4) — Reuse data đã có
7. Empty States (3.2) — CSS + Blade component
8. Resend Email Verification (3.3) — 1 route + 1 method
9. Rate Limiting Contact Form (5.1)
10. Open Graph Tags (4.2) — Sửa `seo-public.blade.php`
11. JSON-LD Schema (4.1) — Thêm vào Layout

#### Sprint 3 — Medium features (1-2 tuần)
12. Risk History UI (2.2) — Reuse bảng có sẵn
13. Dividend History UI (2.3) — Reuse bảng có sẵn
14. Export Portfolio CSV (2.4) — Giai đoạn 1 không cần package
15. Sync Webhook + Status Indicator (8.1, 8.2)
16. Skeleton Loading (3.1)
17. Stock Detail Page (2.5) — Trang công khai + SEO

#### Sprint 4 — Complex features (2-4 tuần)
18. P&L Chart Over Time (2.1) — Cần VPS cron mới + migration
19. In-app Notification Badge (2.6) — Cần migration + webhook VPS
20. Sync History (9.3) — Cần ALTER TABLE + VPS update

#### Sprint 5 — Nice-to-have (Backlog)
21. Community Watchlist (2.7) — 2 bảng mới, CRUD đầy đủ
22. Analytics Dashboard Admin (9.1)
23. VPS JSON Logging (7.1)
24. Rating Price Management UI (9.2)
25. Export Portfolio PDF (2.4 giai đoạn 2)
26. Stricter CSP (5.2)
27. Session Security Review (5.4)
28. Sensitive Data Log Review (5.5)

---

### Danh sách Migrations cần tạo (theo thứ tự)

| File | Bảng | Sprint |
|------|------|--------|
| `2026_05_16_000001_create_portfolio_snapshots_table.php` | `portfolio_snapshots` | 4 |
| `2026_05_16_000002_create_notifications_table.php` | `notifications` | 4 |
| `2026_05_16_000003_create_community_watchlists_table.php` | `community_watchlists` | 5 |
| `2026_05_16_000004_create_community_watchlist_stocks_table.php` | `community_watchlist_stocks` | 5 |
| `2026_05_16_000005_create_page_visits_table.php` | `page_visits` | 2 |
| (ALTER) `stock_status_logs` thêm các cột | `stock_status_logs` | 4 |

### Danh sách Files mới cần tạo (tổng hợp)

**Backend PHP:**
- `app/Http/Middleware/TrackPageVisit.php`
- `app/Http/Controllers/Api/NotificationController.php`
- `app/Http/Controllers/User/NotificationController.php`
- `app/Http/Controllers/User/WatchlistController.php` (Sprint 5)
- `app/Http/Controllers/Pages/WatchlistController.php` (Sprint 5)
- `app/Models/PortfolioSnapshot.php`
- `app/Models/AppNotification.php` (đặt tên khác Notification để tránh conflict)
- `app/Models/StockRiskHistory.php`
- `app/Models/DividendAdjustment.php`
- `app/Models/CommunityWatchlist.php` (Sprint 5)
- `app/Models/CommunityWatchlistStock.php` (Sprint 5)

**Views Blade:**
- `resources/views/partials/empty-state.blade.php`
- `resources/views/User/UserPortfolioChart.blade.php`
- `resources/views/User/UserNotifications.blade.php`
- `resources/views/Admin/AdminAccessManagement.blade.php`
- `resources/views/Pages/StockDetailView.blade.php`
- `resources/views/Pages/WatchlistIndexView.blade.php` (Sprint 5)
- `resources/views/Pages/WatchlistDetailView.blade.php` (Sprint 5)

**CSS:**
- `resources/css/pages/user-portfolio-chart.css`
- `resources/css/pages/user-notifications.css`
- `resources/css/pages/admin-access-management.css`
- `resources/css/pages/stock-detail.css`

**JavaScript:**
- `resources/js/pages/user-portfolio-chart.js`
- `resources/js/pages/admin-access-management.js`

**VPS Python:**
- `/root/stocks/snapshotPortfolio.py` (Sprint 4)
- `/root/stocks/logConfig.py` (Sprint 5)

---

### Checklist trước khi implement mỗi feature

Trước khi code bất kỳ feature nào liên quan đến bảng DB mới trên VPS:
- [ ] Xác nhận cấu trúc bảng hiện có bằng `SHOW COLUMNS FROM {table}` trên VPS
- [ ] Test migration trên local trước khi chạy production
- [ ] Backup DB production trước khi ALTER TABLE

Trước khi deploy lên production:
- [ ] `npm run build` thành công, không có lỗi
- [ ] Test trên local XAMPP
- [ ] Clear cache sau deploy: `php artisan config:cache && php artisan route:cache && php artisan cache:clear`

---

*Tài liệu này chỉ phục vụ mục đích planning. Không có code nào được triển khai cho đến khi user xác nhận.*
