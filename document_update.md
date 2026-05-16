# Kế Hoạch Nâng Cấp — Investment Manager Webapp

> **Phân tích bởi:** Claude Code (AI Senior Engineer perspective)  
> **Ngày:** 2026-05-16  
> **Dựa trên:** Điều tra toàn bộ webapp (Laravel/PHP) + VPS server (Python/FastAPI)  
> **Phiên bản webapp:** Laravel 9.x, domain `investment-manager.xyz`  
> **Phiên bản VPS:** Python/FastAPI, `180.93.42.13`

---

## Tóm Tắt Hệ Thống Hiện Tại

### Webapp (Laravel PHP)
- **URL:** `https://investment-manager.xyz`
- **Stack:** Laravel 9, Vite, Axios, file cache (TTL 86400s)
- **Role:** Giao diện người dùng — quản lý danh mục, follow, mua/bán ảo, admin panel
- **DB:** MySQL `investme_invest` tại `42.119.236.233:3306` (cùng với VPS)
- **Public pages:** `/gioi-thieu` + `/lien-he` **đã implement** (PagesController + views + CSS đã có)

### VPS Server (Python/FastAPI)
- **URL:** `http://180.93.42.13`
- **Stack:** FastAPI, Selenium Chrome, vnstock, MySQL connector
- **Role:** Sync data tự động — giá (15:00), risk (mỗi 6h), cổ tức (08:30), volume breakout (20:00)
- **Email:** Gửi thông báo tự động cho users (portfolio, follow signals, risk changes, dividends)

### Số Liệu Production Thực Tế (2026-05-16)

| Metric | Giá trị | Ghi chú |
|--------|---------|---------|
| Active users | 3 | Giai đoạn early-stage / personal use |
| Open portfolio lots | 2 | 2 users có portfolio đang mở |
| Total follows | 306 | 3 users, nhiều follow của owner |
| Stocks tracked | 321 | 253 An toàn, 41 Cảnh báo, 24 Hạn chế, 3 Đình chỉ |
| VN30 stocks | 30 | Subset của 321 |
| VN100 stocks | 65 | Subset của 321 |
| Admin follow list | 69 mã | Auto-tracked bởi syncStocks10M |
| Risk history records | 684 | Lịch sử risk events từ VietStock |
| Dividend adjustments | 11 | Điều chỉnh cổ tức đã xử lý |
| Rating price reference | 10,000 VND | Mệnh giá cổ phiếu VN (par value) |

### Insight Quan Trọng Từ Số Liệu

- **Đây là giai đoạn startup sớm** — 3 active users, phần lớn là owner/tester. Ưu tiên làm product tốt hơn là build infrastructure phức tạp.
- **306 follows / 3 users = 102 follows/user trung bình** — owner theo dõi rất nhiều mã, cần UX tốt để quản lý lượng lớn
- **69 admin_follow mã** bị stale cache (vì thiếu endpoint clear) — đây là bug thực tế ảnh hưởng admin mỗi ngày
- **684 risk history records** đang lưu nhưng webapp chưa hiển thị — data phong phú bị lãng phí
- **stocks_vn = 1000** cho 226 mã (không phải VN30/VN100) — có thể là VN1000 index hoặc UPCOM

### Điểm Yếu Tổng Thể Cần Giải Quyết
1. **[BUG] Cache `admin_follow` stale** — VPS thiếu `clear_admin_follow()` trong `cacheHelper.py`
2. **[BUG] No resend email verification** — User 9 bị mắc kẹt vì không verify email
3. **[BUG] Sitemap thiếu 2 trang mới** — `/gioi-thieu` + `/lien-he` chưa trong sitemap
4. **[DATA] 684 risk history records chưa được hiển thị** trong webapp
5. **[DATA] `vnindex` table rỗng** — từng được plan nhưng chưa implement
6. **[DOC] CLAUDE.md schema `stocks` sai** — `stocks_vn` không phải "Tên công ty" (đã fix)
7. **[UX] Không có charts, KPI cards** — chỉ bảng số liệu
8. **[UX] Không có onboarding flow** cho user mới
9. **[SECURITY] Email enumeration** tại forgot password
10. **[VPS] Thiếu health check + monitoring**

---

## PHẦN 1 — SỬA LỖI KHẨN CẤP (Bug / Data Integrity)

### 1.1 Cache Sync `admin_follow` — Ưu Tiên CAO (Chỉ Cần Sửa VPS)

**Điều tra thực tế:**
- **Webapp đã sẵn sàng**: `CacheController.php` line 76 đã có `admin_follow` trong validation list; `CacheService.php` line 251–255 đã có `case 'admin_follow':` xóa `admin_follow_stock_ids` và `admin_follow_stocks`.
- **VPS thiếu**: `cacheHelper.py` không có hàm `clear_admin_follow()`. `syncStocks10M.py` không gọi bất kỳ cache clear nào sau khi insert `admin_follow`.

**Hậu quả:** Admin follow list bị stale tối đa 24h. 69 mã đang trong `admin_follow` production DB — admin xem danh sách theo dõi không thấy cập nhật real-time.

**Fix duy nhất (VPS — `/root/stocks/cacheHelper.py`):**
```python
# Thêm hàm mới sau clear_stock():
def clear_admin_follow():
    """Xoa cache bang admin_follow (admin_follow_stock_ids, admin_follow_stocks)."""
    _call('/api/cache/clear-table', {'table': 'admin_follow'})
```

**Gọi trong `syncStocks10M.py`** sau mỗi lần insert `admin_follow` thành công (và sau mỗi lần chạy xong nếu có thay đổi).

**Note:** `dividend_adjustments` và `stock_risk_history` — webapp không cache 2 bảng này (query trực tiếp hoặc chưa có UI), nên không cần endpoint mới.

---

### 1.2 Sitemap Thiếu 2 Trang Public Mới

**File:** `routes/web.php` line 42–64

Sitemap hiện chỉ có: `home`, `login`, `register`, `forgotPassword`. Sau khi implement `/gioi-thieu` và `/lien-he`, hai route này **không được thêm vào sitemap** — Google không biết 2 trang này tồn tại.

**Fix (routes/web.php):**
```php
$urls = [
    ['loc' => route('home'),          'lastmod' => $today, 'changefreq' => 'daily',   'priority' => '1.0'],
    ['loc' => route('about'),         'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'],
    ['loc' => route('contact'),       'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => route('register'),      'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => route('login'),         'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => route('forgotPassword'),'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.3'],
];
```

Sau khi sửa, cần clear sitemap cache: `Cache::forget('sitemap_xml')` hoặc `php artisan cache:clear`.

---

### 1.3 Email Enumeration Tại Trang Quên Mật Khẩu

**File:** `app/Services/AuthService.php` line 72

```php
if (!$existingUser) {
    return ['status' => 'error', 'message' => 'Email không tồn tại.'];  // ← Tiết lộ email có tồn tại
}
```

Kẻ tấn công có thể brute-force danh sách email để biết email nào đã đăng ký.

**Fix:**
```php
if (!$existingUser) {
    // Trả về message không tiết lộ email có tồn tại hay không
    return ['status' => 'success', 'message' => 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi link đặt lại mật khẩu.'];
}
```

**Lưu ý:** Ở quy mô hiện tại (3 users), đây không phải rủi ro cao nhưng nên fix trước khi scale.

---

### 1.4 Lỗi Document CLAUDE.md — Bảng `stocks` Schema Sai

**Vấn đề đã sửa:** CLAUDE.md mô tả `stocks_vn | string | Tên công ty (tiếng Việt)` — sai hoàn toàn. Thực tế:
- `stocks_vn` là `INT` với default `1000`
- `30` = VN30 member, `100` = VN100 member, `1000` = không thuộc VN30/VN100
- Không có field "Tên công ty" trong bảng `stocks`

**Đã fix:** CLAUDE.md đã được cập nhật với schema đầy đủ (18 fields thực tế từ production DB).

**Ý nghĩa cho webapp:** Khi filter hoặc hiển thị badge VN30/VN100, check `stocks_vn IN (30, 100)`, không phải `IS NOT NULL`.

---

### 1.5 `/risk-history/{code}` — Endpoint Tồn Tại Trên VPS Nhưng Webapp Không Dùng

VPS đã có `GET /risk-history/{code}` trả về lịch sử risk events của 1 mã (public, không cần auth). Đây là dữ liệu quý giá (theo dõi lịch sử cảnh báo) nhưng webapp chưa hiển thị ở đâu cả.

**Vấn đề:** User không biết cổ phiếu đã từng bị cảnh báo bao nhiêu lần, vào ngày nào.

---

## PHẦN 2 — TÍNH NĂNG MỚI CHO USER

### 2.1 Biểu Đồ Lãi/Lỗ Theo Thời Gian (P&L Chart)

**Hiện trạng:** Webapp chỉ hiển thị P&L dạng số (bảng tĩnh tại thời điểm hiện tại). Không có history.

**Đề xuất:** Thêm chart hiển thị tổng giá trị danh mục theo thời gian.

**Cách implement (không cần DB mới):**
- VPS đã có `syncPrice.py` chạy 15:00 Mon–Fri cập nhật `current_price`
- Thêm bảng `portfolio_snapshots` vào DB: `(user_id, date, total_value, total_cost, pnl_pct)`
- VPS tính snapshot sau mỗi `syncPrice` → insert/update
- Webapp query snapshots → vẽ chart dùng thư viện nhẹ (Chart.js CDN hoặc pure SVG/CSS)

**Ưu tiên:** Cao — đây là tính năng "wow" nhất của ứng dụng đầu tư.

**UX wireframe (mobile-first):**
```
┌─────────────────────────────────────┐
│  Hiệu suất danh mục                │
│  [1T] [3T] [6T] [1N] [Tất cả]     │  ← period picker
│                                     │
│   ▲ +12.5%                         │
│  ┌──────────────────────────────┐  │
│  │    /\      /\               │  │
│  │   /  \    /  \    /\       │  │
│  │  /    \  /    \  /  \     │  │
│  │ /      \/      \/    \    │  │
│  └──────────────────────────────┘  │
│  12/04   19/04   26/04   05/05     │
└─────────────────────────────────────┘
```

---

### 2.2 Lịch Sử Risk — Xem Cảnh Báo Của Từng Mã

**Hiện trạng:** VPS có bảng `stock_risk_history` và API `GET /risk-history/{code}`. Webapp chưa hiển thị.

**Đề xuất:** Thêm section "Lịch sử rủi ro" trên trang chi tiết mã (hiện chưa có trang chi tiết mã).

**Cách implement:**
- Webapp gọi VPS `GET /risk-history/{code}` hoặc query trực tiếp DB (cùng DB)
- Hoặc đơn giản hơn: thêm API route `GET /api/stock/{code}/risk-history` → query DB `stock_risk_history`
- Hiển thị dạng timeline trong modal hoặc expandable row

**UX:**
```
VNM — Lịch sử rủi ro
──────────────────────────────
✅ An toàn     [Hiện tại]
⚠️ Cảnh báo    13/05/2026 → Đưa vào diện cảnh báo (ChannelID 19)
⚠️ Cảnh báo    23/04/2026 → Đưa vào diện cảnh báo (ChannelID 22)
✅ An toàn     15/03/2026 → Ra khỏi danh sách kiểm soát
```

---

### 2.3 Lịch Sử Điều Chỉnh Cổ Tức

**Hiện trạng:** VPS có bảng `dividend_adjustments` ghi lại mọi sự kiện cổ tức đã xử lý. Webapp chưa có UI.

**Đề xuất:** Thêm trang/section "Lịch sử cổ tức" cho user.

**Data từ `dividend_adjustments`:**
- `stock_id`, `gdkhq_date`, `adj_type`, `adj_factor`, `note_raw`, `processed_at`

**Giá trị:** User hiểu tại sao giá mua TB của họ thay đổi — minh bạch hơn.

**UX:**
```
📊 Điều chỉnh cổ tức VNM
─────────────────────────────────────────────
01/04/2026  cash_dividend    -2.000đ/CP  "Cổ tức tiền mặt 2%"
15/12/2025  stock_dividend   adj 100:15  "Cổ tức cổ phiếu tỷ lệ 15%"
```

---

### 2.4 Volume Breakout Alert Cho User

**Hiện trạng:** `syncStocks10M.py` chạy 20:00 Mon–Thu, phát hiện mã có GTGD vượt ngưỡng → chỉ email admin. User không biết.

**Đề xuất:** Hiển thị "Mã nổi bật phiên hôm nay" trên homepage user — danh sách các mã có volume breakout từ `admin_follow` mà hệ thống tự phát hiện.

**Cách implement:**
- Reuse data từ bảng `admin_follow` (đã có)
- Thêm cột `detected_date` vào `admin_follow` để biết mã được thêm ngày nào
- Webapp hiển thị các mã được thêm trong 24h qua với badge "Nổi bật hôm nay 🔥"

**UX:**
```
🔥 Mã nổi bật phiên hôm nay (20:00 Thứ 2-5)
─────────────────────────────────────────────
VNM  ┊ 85.000đ ┊ GTGD: 125 tỷ ┊ Điểm: 8/10 ┊ [Theo dõi]
FPT  ┊ 98.000đ ┊ GTGD: 210 tỷ ┊ Điểm: 9/10 ┊ [Theo dõi]
```

---

### 2.5 Export Danh Mục (PDF / Excel)

**Hiện trạng:** Không có export. Admin có export CSV cho stocks nhưng user không có gì.

**Đề xuất:** Nút "Xuất báo cáo" trên trang Portfolio.

**Format đề xuất (simple HTML → PDF):**
- Dùng Laravel DomPDF (package phổ biến) hoặc đơn giản hơn là HTML table → print CSS
- Excel: PHP Spreadsheet hoặc simple CSV download (không cần package)

**Content:**
```
BÁO CÁO DANH MỤC — [User Name] — [Date]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Mã   Giá mua TB  Giá hiện tại  SL  P&L (đ)  ROI (%)
VNM  80.000      85.000        100  +500.000  +6.25%
FPT  90.000      98.000         50  +400.000  +8.89%
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Tổng vốn đầu tư: 12.500.000đ
Tổng P&L hiện tại: +900.000đ (+7.2%)
```

---

### 2.6 Cài Đặt Thông Báo Email Chi Tiết Hơn

**Hiện trạng:** User có `email-settings` nhưng chỉ có toggle session_closed_flag và follow email. Không có granular control.

**Đề xuất thêm:**
- Toggle "Nhận email tổng kết phiên giao dịch 15:00" (portfolio summary từ `noticeStock.send_portfolio_emails`)
- Toggle "Nhận email cảnh báo cổ tức" (từ `syncDividend` → user email)
- Toggle "Nhận email thay đổi rủi ro" (từ `syncRisk` → user email)
- Time zone / frequency preference

---

### 2.7 So Sánh Với VN-Index Benchmark

**Hiện trạng:** User xem P&L của danh mục nhưng không biết thị trường chung đang ra sao.

**Đề xuất:** Hiển thị % thay đổi VN-Index cạnh % lãi/lỗ danh mục của user.

**Data source:** VPS đã có `sendEmailVnindex` và sync VN30/VN100 (`syncStockVN.py`). Có thể thêm sync VN-Index price tương tự.

**UX (simple):**
```
Danh mục của bạn: +6.2% ↑
VN-Index (cùng kỳ): +4.1% ↑  → Bạn outperform 2.1%
```

---

### 2.8 Trang Chi Tiết Cổ Phiếu (Stock Detail Page)

**Hiện trạng:** Không có trang chi tiết cho từng mã. Tất cả thông tin gộp trong bảng tổng.

**Đề xuất:** Route `GET /co-phieu/{code}` — trang chi tiết mã cổ phiếu.

**Content:**
- Giá hiện tại, % thay đổi, khối lượng
- Giá đề xuất mua/bán (admin recommended)
- Mức rủi ro + badge
- Rating 1-10
- Lịch sử risk (từ `stock_risk_history`)
- Lịch sử cổ tức (từ `dividend_adjustments`)
- Biểu đồ giá (nếu VCI API public)
- Danh mục của user với mã này (nếu đăng nhập)
- Nút "Theo dõi" / "Mua" nhanh

---

### 2.9 Notification Badge Trong App

**Hiện trạng:** Thông báo chỉ qua email. Không có in-app notification.

**Đề xuất đơn giản (không cần WebSocket):**
- Bảng `notifications` mới: `(user_id, type, message, is_read, created_at)`
- VPS sau mỗi sync gọi webhook → Laravel tạo record notification cho các user liên quan
- Webapp hiển thị badge số trên icon bell (header)
- Dropdown danh sách thông báo gần nhất

**Loại notification:**
- 🔔 `risk_changed` — "VNM chuyển sang Cảnh báo (Cấp 2)"
- 💰 `dividend` — "FPT: Điều chỉnh cổ tức cổ phiếu tỷ lệ 15%"
- 📈 `follow_signal` — "VHM đã chạm giá mục tiêu mua 55.000đ"
- 📊 `price_sync` — "Cập nhật giá phiên 15/05 xong"

---

### 2.10 Public Watchlist / Gợi Ý Cộng Đồng

**Đây là tính năng dài hạn** — cho phép users chia sẻ watchlist công khai và xem top mã được nhiều user theo dõi nhất.

**Business value:** Tạo network effect, tăng retention.

---

## PHẦN 3 — NÂNG CẤP UI/UX

### 3.1 Bảng Giá Homepage — Thêm Bộ Lọc Nâng Cao

**Hiện trạng:** Trang chủ `/trang-chu` có bảng giá với filter cơ bản.

**Đề xuất thêm:**
- Filter theo `risk_level` (chỉ xem mã An toàn)
- Filter theo `stocks_vn` (VN30 / VN100)
- Filter theo `rating_stocks` (≥ 7/10)
- Sort theo `percent_stock` (% thay đổi hôm nay)
- Sort theo `volume` (khối lượng giao dịch)
- Ô tìm kiếm realtime (filter theo code/tên công ty `stocks_vn`)

### 3.2 Skeleton Loading States

**Hiện trạng:** Khi load dữ liệu, trang trắng hoặc flash layout. Không có loading animation.

**Đề xuất:** Thêm skeleton loading CSS cho tất cả bảng data.

```css
/* Skeleton loading */
.skeleton-row {
    background: linear-gradient(90deg, #1e293b 25%, #2a3a52 50%, #1e293b 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
    height: 40px;
    margin-bottom: 8px;
}
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
```

### 3.3 Click Để Sort Trên Mọi Bảng

**Hiện trạng:** Các bảng (stocks, portfolio, follows) không sort được khi click header.

**Đề xuất:** Thêm client-side sort cho tất cả bảng data lớn (không cần AJAX, sort DOM).

```js
// Generic table sort — thêm vào app.js
function initSortableTable(tableId) {
    const table = document.getElementById(tableId);
    table.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => sortTable(table, th));
    });
}
```

### 3.4 Sticky Column Cho Mã Cổ Phiếu Trên Mobile

**Hiện trạng:** Bảng stocks rộng → scroll ngang trên mobile → mất cột "Mã CK".

**Đã implement** trong các modal suggest, nhưng **chưa apply** cho bảng chính trang chủ và trang portfolio.

### 3.5 Dark/Light Mode Toggle

**Hiện trạng:** Luôn dark theme. Một số user prefer light mode ban ngày.

**Đề xuất:** Toggle lưu vào `localStorage`, apply class `light-mode` vào `<html>`.

**Effort:** Thấp (override CSS variables) nhưng cần time để test toàn bộ colors.

### 3.6 Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `G H` | Về trang chủ |
| `G P` | Mở trang portfolio |
| `G F` | Mở trang follow |
| `G B` | Mở trang mua |
| `?` | Hiện help overlay |

### 3.7 Empty States Tốt Hơn

**Hiện trạng:** Khi user chưa có portfolio/follow, hiện bảng trống rất bland.

**Đề xuất:**
```
┌─────────────────────────────────────┐
│          📊                         │
│   Danh mục của bạn trống            │
│   Bắt đầu thêm cổ phiếu để         │
│   theo dõi hiệu suất đầu tư         │
│                                     │
│      [Mua cổ phiếu đầu tiên →]     │
└─────────────────────────────────────┘
```

### 3.8 Onboarding Flow Cho User Mới

**Hiện trạng:** User đăng ký xong → vào trang chủ, không biết làm gì.

**Đề xuất:** 3-step onboarding tooltip/overlay:
1. "Nạp tiền ảo để bắt đầu" → point to CashIn
2. "Chọn cổ phiếu bạn muốn mua" → point to table
3. "Đặt cảnh báo giá" → point to Follow

Flag trong DB: `users.onboarding_done` (bool). Chỉ hiện 1 lần.

### 3.9 Số Liệu Summary Cards Trên Dashboard

**Hiện trạng:** Trang portfolio bắt đầu bằng bảng ngay. Không có KPI summary.

**Đề xuất:** 4 card ở đầu trang:
```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Tổng danh mục│ │ Lãi/Lỗ hôm  │ │ ROI tổng     │ │ Số mã đang  │
│ 125.000.000đ │ │ nay +2.3%   │ │ +14.5%       │ │ nắm: 5      │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```

---

## PHẦN 4 — SEO & MARKETING

### 4.1 Trang Giới Thiệu `/gioi-thieu` và Liên Hệ `/lien-he`

**Trạng thái:** Đã có plan thiết kế chi tiết trong `floofy-bouncing-corbato.md`. **Cần implement ngay.**

**SEO impact:** Trang giới thiệu giúp Google hiểu website về gì. Trang liên hệ là trust signal.

### 4.2 Structured Data (JSON-LD)

**Hiện trạng:** Chưa có Schema.org markup.

**Thêm vào layout:**
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FinancialService",
  "name": "Investment Manager",
  "description": "Nền tảng theo dõi danh mục cổ phiếu cá nhân tại thị trường Việt Nam",
  "url": "https://investment-manager.xyz",
  "serviceType": "Portfolio Management",
  "areaServed": "VN"
}
</script>
```

### 4.3 Open Graph / Twitter Card Tags

**Hiện trạng:** Chưa có OG tags. Khi share link lên Zalo/Facebook không có preview.

```html
<!-- Thêm vào meta partial -->
<meta property="og:title" content="{{ $title ?? 'Investment Manager' }}">
<meta property="og:description" content="{{ $description ?? '...' }}">
<meta property="og:image" content="{{ asset('images/og-cover.png') }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:card" content="summary_large_image">
```

**og-cover.png:** Tạo ảnh 1200×630px với branding của app (dark theme, logo, tagline).

### 4.4 Cải Thiện Sitemap.xml

**Hiện trạng:** Sitemap đã có nhưng chỉ có trang chủ.

**Thêm vào sitemap:**
```xml
<url><loc>https://investment-manager.xyz/trang-chu</loc><priority>1.0</priority></url>
<url><loc>https://investment-manager.xyz/gioi-thieu</loc><priority>0.8</priority></url>
<url><loc>https://investment-manager.xyz/lien-he</loc><priority>0.7</priority></url>
<url><loc>https://investment-manager.xyz/dang-ky</loc><priority>0.9</priority></url>
```

### 4.5 Core Web Vitals Optimization

**Check:** Google PageSpeed Insights trên homepage `/trang-chu`.

**Probable issues:**
- LCP: Bảng giá cổ phiếu render client-side → Google thấy trang trống
- CLS: Không có reserved height cho bảng → layout shift khi load
- FID/INP: Bảng lớn với nhiều event listeners

**Fix đề xuất:**
- Server-side render bảng giá public (không cần auth) → SEO + LCP
- Skeleton placeholder có cùng height với bảng thực → giảm CLS
- Virtualize bảng nếu > 100 rows

### 4.6 Page Title và Meta Description Động

**Hiện trạng:** Hầu hết trang đều có title/description nhưng cần kiểm tra lại.

**Check list:**
- `/trang-chu` → "Bảng giá cổ phiếu Việt Nam — Investment Manager"
- `/dang-ky` → "Đăng ký miễn phí — Quản lý đầu tư cổ phiếu cá nhân"
- `/gioi-thieu` → "Giới thiệu — Nền tảng theo dõi danh mục cổ phiếu"

---

## PHẦN 5 — BẢO MẬT

### 5.1 Rate Limiting Trên Public Pages

**Hiện trạng:** `ThrottleAuthPosts` middleware chỉ áp dụng cho POST `/dang-nhap` và POST `/dang-ky`. Trang chủ không có rate limit.

**Đề xuất:**
```php
// routes/web.php
Route::get('/trang-chu', [...])->middleware('throttle:120,1');  // 120 req/phút
Route::get('/sitemap.xml', [...])->middleware('throttle:30,1');
```

### 5.2 Content Security Policy (CSP) Stricter

**Hiện trạng:** `SecurityHeaders` middleware đã có header nhưng cần kiểm tra CSP value.

**Recommend CSP (bắt đầu với report-only mode):**
```
Content-Security-Policy-Report-Only:
  default-src 'self';
  script-src 'self' 'nonce-{RANDOM}';
  style-src 'self' 'unsafe-inline' fonts.googleapis.com;
  font-src 'self' fonts.gstatic.com;
  img-src 'self' data:;
  connect-src 'self';
  report-uri /csp-report
```

Dùng `report-only` trước để detect violations, sau đó enforce.

### 5.3 SQL Injection Review

**Hiện trạng:** Dùng Eloquent ORM và Query Builder — phần lớn an toàn. Cần check các chỗ dùng raw query.

**Grep để tìm:**
```bash
grep -r "DB::statement\|DB::select\|whereRaw\|orderByRaw" app/
```

Đảm bảo tất cả đều dùng bindings `?` thay vì string interpolation.

### 5.4 Bảo Vệ File Upload

**Hiện trạng:** Admin có upload CSV (`/admin/stocks/import-csv`). Cần kiểm tra validation.

**Check:**
- Validate MIME type thực (không chỉ extension)
- Giới hạn file size
- Sanitize filename trước khi log
- Không lưu upload vào public directory

### 5.5 Session Security

**Đề xuất kiểm tra `.env`:**
```
SESSION_SECURE_COOKIE=true    # Chỉ gửi cookie qua HTTPS
SESSION_SAME_SITE=strict      # Chống CSRF qua cookie attribute
SESSION_LIFETIME=120          # 2 giờ timeout (hiện tại bao nhiêu?)
```

### 5.6 Email Enumeration Attack

**Hiện trạng:** Trang quên mật khẩu tiết lộ "Email này không tồn tại trong hệ thống".

**Fix:** Luôn trả về "Nếu email tồn tại, chúng tôi đã gửi link đặt lại mật khẩu." dù email có tồn tại hay không.

### 5.7 VPS API Key Rotation

**Hiện trạng:** `API_KEY` trong `/root/stocks/.env` là static key dài hạn. Không có cơ chế rotation.

**Đề xuất:** Định kỳ 6 tháng/1 năm, rotate key và update cả 2 file:
- `/root/stocks/.env` → `API_KEY=new_key`
- Webapp `.env` → `SYNC_API_KEY=new_key`

Thêm vào CLAUDE.md reminder về rotation schedule.

### 5.8 Logging Sensitive Data

**Check:** Đảm bảo log không ghi email users, password attempts, hay API keys.

```bash
grep -r "Log::.*email\|Log::.*password\|Log::.*secret" app/
```

---

## PHẦN 6 — PERFORMANCE & SCALABILITY

### 6.1 Cache Strategy Cải Thiện

**Hiện trạng:** File cache với TTL 1 ngày. Hoạt động tốt nhưng có điểm yếu.

**Vấn đề:**
- Lock timeout 10s → user chờ nếu DB slow khi cache miss
- File cache không share được giữa nhiều server (nếu scale)

**Đề xuất ngắn hạn:**
- Giảm lock timeout xuống 5s với fallback query trực tiếp nếu timeout
- Monitor số lần cache miss/hit (thêm log counter)

**Đề xuất dài hạn (khi scale):**
- Migrate sang Redis: `CACHE_DRIVER=redis` — TTL vẫn 1 ngày nhưng share được
- Redis cũng support pub/sub → real-time notifications sau này

### 6.2 Database Query Optimization

**Hiện trạng:** CacheService wrap các query nặng. Nhưng cần check N+1 queries.

**Điểm cần kiểm tra:**
- `User@profile` — có join hay N+1 cho portfolio?
- `Admin@stockManagement` — fetch all stocks (có lazy loading không?)
- Email jobs trên VPS đang query user-by-user hay batch?

**Tool:** `Laravel Debugbar` (dev only) để detect N+1.

### 6.3 VPS Memory Management

**Hiện trạng:** Selenium Chrome được khởi động cho mỗi lần fetch VietStock data (risk + dividend).

**Vấn đề:** Chrome tiêu thụ ~200-300MB RAM. Với syncRisk chạy 4 lần/ngày, mỗi lần qua 100+ mã → Chrome được khởi động rồi đóng nhiều lần.

**Current approach:** Session reuse trong cùng 1 run (tốt). Chrome đóng sau khi run xong.

**Đề xuất:** Monitor RAM usage trên VPS sau mỗi syncRisk. Nếu RAM vượt 1GB → add swap hoặc nâng VPS tier.

### 6.4 Email Queue

**Hiện trạng:** Email gửi synchronous trong sync jobs. Nếu SMTP slow → toàn bộ sync job bị delay.

**Đề xuất:** Dùng Laravel Queue cho email (webapp side):
```php
Mail::to($email)->queue(new ContactMail($data));  // thay vì ->send()
```

**VPS side:** Email Python cũng có thể bị block nếu Gmail SMTP slow. Hiện tại không có timeout config rõ ràng.

### 6.5 Pagination Cho Bảng Lớn

**Hiện trạng:** Bảng stocks trang chủ load tất cả ~100+ mã cùng lúc (phụ thuộc số mã trong DB).

**Đề xuất:**
- Infinite scroll hoặc pagination cho bảng public
- Client-side virtual scrolling nếu > 200 rows (chỉ render visible rows)

---

## PHẦN 7 — VPS SERVER IMPROVEMENTS

### 7.1 Health Check Endpoint

**Hiện trạng:** Không có health check endpoint chuẩn.

**Thêm vào `api.py`:**
```python
@app.get("/health")
async def health_check():
    """Public health check — no auth required."""
    # Check DB connection
    try:
        row = fetch_one("SELECT 1", [])
        db_ok = row is not None
    except Exception:
        db_ok = False
    
    return {
        "status": "ok" if db_ok else "degraded",
        "db": "connected" if db_ok else "error",
        "timestamp": datetime.now().isoformat()
    }
```

**Use case:** Monitoring service (UptimeRobot, Better Uptime) ping `/health` mỗi 5 phút.

### 7.2 Retry Logic Với Exponential Backoff

**Hiện trạng:** `syncRisk.py` có retry 5 lần với `time.sleep(10)` fixed. `syncPrice.py` retry tùy script.

**Cải thiện:**
```python
def fetch_with_retry(fetch_func, max_retries=5, base_delay=2):
    for attempt in range(max_retries):
        result = fetch_func()
        if result:
            return result
        delay = base_delay * (2 ** attempt)  # 2, 4, 8, 16, 32s
        time.sleep(min(delay, 60))  # cap tại 60s
    return None
```

### 7.3 Structured Logging (JSON)

**Hiện trạng:** Log format: `YYYY-MM-DD HH:MM:SS - LEVEL - message` — dễ đọc bằng mắt nhưng khó parse.

**Đề xuất:** Thêm JSON format cho machine-readable log (dùng song song với text log):
```python
import json

def log_structured(level, event, **kwargs):
    entry = {
        "ts": datetime.now().isoformat(),
        "level": level,
        "event": event,
        **kwargs
    }
    print(json.dumps(entry, ensure_ascii=False))  # → stdout
```

**Use case:** Có thể ship logs vào Elasticsearch/Loki sau này.

### 7.4 VPS Monitoring

**Đề xuất setup đơn giản (free tier):**
- **UptimeRobot** (free): Ping `http://180.93.42.13/health` mỗi 5 phút → alert email nếu down
- **Grafana Cloud** (free tier): Ship metrics từ VPS (CPU, RAM, disk, sync success/failure count)

**Metrics cần track:**
- `sync_price_success_count` — số mã sync giá thành công/phiên
- `sync_risk_changes_count` — số mã thay đổi risk/run
- `email_sent_count` — số email gửi/ngày
- `cache_clear_error_count` — lỗi clear cache webapp

### 7.5 Cron Schedule Optimization

**Hiện trạng:** Cron schedule hiện tại tốt nhưng có thể optimize:

| Script | Hiện tại | Đề xuất | Lý do |
|--------|---------|---------|-------|
| `syncRisk` | 0,6,12,18h | 6,12,18,23h | Giảm run lúc nửa đêm khi không giao dịch |
| `noticeUserFollow` | 07:00 | 07:30 | Cho phép syncPrice chạy xong và settle trước |
| `syncRatingPrice` | 03:00 | 01:00 | Chạy sớm hơn để admin thấy rating update khi thức dậy |

### 7.6 Single-Stock Update API — Better Response

**Hiện trạng:** `/run-sync-update-stocks/{code}` return `status: processing` nếu > 30s. User không biết khi nào xong.

**Đề xuất:** Thêm polling endpoint:
```
GET /status-update-stock/{code}
→ {"status": "processing"|"done"|"not_started", "started_at": "...", "done_at": "..."}
```

Webapp có thể poll mỗi 5s sau khi trigger, hiện toast "Đang cập nhật..." → "Cập nhật xong!" khi `status: done`.

### 7.7 Backup Database

**Hiện trạng:** Không thấy backup config trong documentation.

**Đề xuất:**
```bash
# Cron: 02:00 Sunday
0 2 * * 0 mysqldump -h 42.119.236.233 -u investme_phuoc -pPhuoc@123 investme_invest | gzip > /root/backups/db_$(date +%Y%m%d).sql.gz
# Xóa backup cũ hơn 30 ngày
find /root/backups -name "db_*.sql.gz" -mtime +30 -delete
```

**Lưu ý:** Lưu backup ra ngoài VPS (Google Drive, S3) để phòng mất VPS.

---

## PHẦN 8 — TÍCH HỢP VPS ↔ WEBAPP

### 8.1 Webhook Sau Mỗi Sync Hoàn Tất

**Hiện trạng:** VPS clear cache webapp sau sync nhưng webapp không biết sync đã chạy xong.

**Đề xuất:** Thêm webhook call từ VPS → webapp khi sync xong:
```python
# cacheHelper.py — thêm hàm
def notify_sync_complete(sync_type, stats):
    """Gọi webhook về webapp để cập nhật UI."""
    payload = {
        "event": "sync_complete",
        "type": sync_type,  # "price", "risk", "dividend"
        "stats": stats,
        "timestamp": datetime.now().isoformat()
    }
    _call_webhook('/api/webhooks/sync-complete', payload)
```

**Webapp handler:**
```php
// POST /api/webhooks/sync-complete (middleware: cron.secret)
public function syncComplete(Request $request) {
    // Update status_sync, maybe broadcast event
    // Nếu có WebSocket: broadcast to admin dashboard
    return response()->json(['ok' => true]);
}
```

### 8.2 Real-Time Status Sync Indicator

**Hiện trạng thực tế (điều tra ngày 2026-05-16):**
- `status_sync_risk` field: VPS cập nhật khi syncRisk start (=1) và stop (=0) ✅
- `status_sync_price` field: **Legacy, không được dùng** — luôn = 0
- **Webapp `SyncService.php`** có code đọc/ghi `status_sync_risk` nhưng chỉ dùng trong admin manual sync trigger
- **Không có frontend display** — không có badge hay indicator nào hiển thị trạng thái sync cho admin

**Đề xuất:** Admin dashboard hiện badge pulsing khi sync đang chạy:
```
● Đang sync risk (bắt đầu 18:00)  ← chỉ hiện khi status_sync_risk = 1
```

Implement bằng cách: Admin page load → JS poll `/api/status-sync` mỗi 30s → hiển thị badge nếu `status_sync_risk = 1`.

### 8.3 Admin Follow Cache — Fix Ngay

**Xem 1.1** — đây là bug cần fix khẩn cấp nhất. Sau khi `syncStocks10M.py` insert `admin_follow`, webapp vẫn cache cũ.

**Fix flow:**
```
syncStocks10M.py insert → cacheHelper.clear_admin_follow() → webapp xóa cache
```

---

## PHẦN 9 — TÍNH NĂNG ADMIN

### 9.1 Dashboard Analytics Cho Admin

**Hiện trạng:** Admin dashboard chỉ có bảng cổ phiếu và user management.

**Đề xuất thêm:**
- Số users active trong 30 ngày
- Số lần giao dịch mua/bán hôm nay
- Top 10 mã được follow nhiều nhất
- Tỷ lệ user có portfolio vs chỉ xem bảng giá
- Số email gửi trong 7 ngày (từ VPS logs)

### 9.2 Quản Lý Rating Price

**Hiện trạng:** Bảng `rating_price` có reference price cho tính rating. Admin không có UI sửa giá này.

**Đề xuất:** Thêm form đơn giản trong admin: "Cập nhật giá tham chiếu rating" → INSERT vào `rating_price`.

### 9.3 Xem Lịch Sử Sync

**Hiện trạng:** Admin có thể xem logs qua log-viewer nhưng không có summary dashboard về sync history.

**Đề xuất:** Bảng `sync_history`: `(id, sync_type, started_at, finished_at, success_count, fail_count, changed_count)`.
VPS ghi vào DB sau mỗi sync. Webapp hiện bảng này trong admin dashboard.

### 9.4 Bulk Email Test

**Hiện trạng:** Không có cách test email template trực tiếp từ admin UI.

**Đề xuất:** Admin button "Gửi email test" → gửi 1 email với data mẫu về `MAIL_FROM_ADDRESS`.

---

## PHẦN 10 — ROADMAP ƯU TIÊN

### Ưu Tiên 1 — Fix Ngay (< 1 tuần)

| # | Task | File cần sửa | Effort | Status |
|---|------|-------------|--------|--------|
| 1 | VPS: Thêm `clear_admin_follow()` vào `cacheHelper.py` + gọi trong `syncStocks10M.py` | VPS `/root/stocks/cacheHelper.py`, `syncStocks10M.py` | 30m | ❌ Chưa làm |
| 2 | Thêm `POST /resend-verification` route + UI link trong login error | `routes/web.php`, `Login.php`, `login.js` | 2h | ❌ Chưa làm |
| 3 | Thêm `/gioi-thieu` + `/lien-he` vào sitemap + clear cache | `routes/web.php` | 15m | ❌ Chưa làm |
| 4 | Thêm Open Graph tags | `Layout.blade.php`, partials | 1h | ❌ Chưa làm |
| 5 | Fix email enumeration trên forgot password (`AuthService.php` line 72) | `AuthService.php` | 30m | ❌ Chưa làm |
| 6 | SESSION_SECURE_COOKIE=true trong `.env` production | `.env` | 5m | ❌ Chưa làm |
| — | Trang `/gioi-thieu` và `/lien-he` | — | — | ✅ **Đã implement** |
| — | Webapp `admin_follow` cache endpoint | `CacheController.php`, `CacheService.php` | — | ✅ **Đã implement** |

### Ưu Tiên 2 — Nâng Cao UX (1-2 tháng)

| # | Task | Effort |
|---|------|--------|
| 7 | KPI Summary Cards trên dashboard user | 1 ngày |
| 8 | Sort được bằng click header cho tất cả bảng | 1 ngày |
| 9 | Lịch sử risk cho từng mã (gọi DB trực tiếp) | 1 ngày |
| 10 | Lịch sử cổ tức hiển thị cho user | 1 ngày |
| 11 | Skeleton loading states | 0.5 ngày |
| 12 | Trang chi tiết mã cổ phiếu | 3 ngày |

### Ưu Tiên 3 — Tính Năng Lớn (2-4 tháng)

| # | Task | Effort |
|---|------|--------|
| 13 | P&L Chart theo thời gian (cần VPS thêm snapshot) | 1 tuần |
| 14 | Export portfolio PDF/CSV | 3 ngày |
| 15 | In-app notification system | 1 tuần |
| 16 | VPS health check + UptimeRobot monitoring | 2h |
| 17 | VN-Index benchmark comparison | 3 ngày |
| 18 | Admin analytics dashboard | 1 tuần |

### Ưu Tiên 4 — Long-term (4-12 tháng)

| # | Task |
|---|------|
| 19 | PWA / Mobile app |
| 20 | Redis cache (khi scale) |
| 21 | Real-time WebSocket notification |
| 22 | Social watchlist sharing |
| 23 | API public cho third-party integration |

---

## PHẦN 11 — KỸ THUẬT QUAN TRỌNG CẦN GHI NHỚ

### Điểm Đặc Biệt Của Hệ Thống Này

1. **VPS và Webapp cùng DB** — Không có API giữa hai hệ thống (ngoài cache clear). Đây là thiết kế đơn giản nhưng tạo tight coupling. Nếu VPS thay đổi DB schema → webapp bị ảnh hưởng ngay.

2. **cacheHelper dùng subprocess curl, không dùng requests** — Lý do: LiteSpeed WAF trên webapp server block HTTP/1.1 từ VPS IP. `curl` mặc định HTTP/2 → bypass được. Đây là gotcha quan trọng khi debug cache sync issues.

3. **syncRisk dùng supervisorctl, không dùng subprocess** — Lý do: Chrome (Selenium) cần cô lập hoàn toàn — nếu crash không kéo theo toàn bộ FastAPI process.

4. **FIFO portfolio logic** — Khi user bán, trừ từ lot cũ nhất. `session_closed_flag=1` khi lot hết. Bất kỳ thay đổi nào về logic bán phải tôn trọng FIFO order.

5. **Dividend điều chỉnh giá mua TB** — `syncDividend` không chỉ điều chỉnh `user_portfolios.buy_price` mà còn điều chỉnh `stocks.recommended_buy_price`. Logic này là đặc biệt — ít app đầu tư nào tự adjust recommended price sau cổ tức.

6. **Rating algorithm** — Phụ thuộc vào `rating_price.price` (reference benchmark). Nếu giá thị trường tăng mạnh mà không update `rating_price`, tất cả mã sẽ bị underrate. Admin cần manually update `rating_price` định kỳ.

7. **Volume breakout logic** — `syncStocks10M` không auto-insert nếu `risk_level >= 2` (Fix 2026-05-12). Cần giữ nguyên rule này — mã rủi ro cao không nên được auto-recommend.

### Anti-patterns Cần Tránh

- ❌ Không query DB trực tiếp trong Controller — luôn đi qua Service layer
- ❌ Không gọi cache clear trong Model — phải trong Service sau write operation
- ❌ Không hardcode stock code uppercase — luôn dùng `strtoupper()` trước query
- ❌ Không thêm email gửi synchronous cho user actions trong request cycle — dùng Queue
- ❌ Không thêm `--workers 2+` vào uvicorn — xem DEPLOY_NOTES.md về race condition

---

## PHẦN 12 — MÔI TRƯỜNG VÀ SECRETS

### Checklist Bảo Mật Secrets

| Secret | Location | Rotation | Hiện trạng |
|--------|---------|---------|------------|
| `CRON_API_SECRET` | Webapp `.env` | Chưa có | 🔴 Cần set reminder |
| `SYNC_API_KEY` | Webapp `.env` | Chưa có | 🔴 Cần set reminder |
| `API_KEY` | VPS `.env` | Chưa có | 🔴 Cần set reminder |
| `WEBAPP_CACHE_SECRET` | VPS `.env` | Chưa có | 🔴 Cần set reminder |
| `DB_PASSWORD` | Cả 2 | Chưa có | 🟡 Remote DB |
| `SMTP_PASSWORD` | VPS `.env` | Gmail app password | 🟡 Theo Google policy |
| `MAIL_PASSWORD` | Webapp `.env` | Gmail app password | 🟡 |

**Khuyến nghị:** Set reminder 6 tháng/lần để rotate tất cả API keys.

### Environment Variables Cần Thêm Vào Webapp

```env
# Thêm vào .env.example để document
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SESSION_LIFETIME=120

# Cache webhook secret (khi thêm webhook từ VPS)
SYNC_WEBHOOK_SECRET=   # generate: openssl rand -hex 32
```

---

---

## PHẦN 13 — KHÁM PHÁ: BẢNG ẨN TRONG PRODUCTION DB

### 13.1 Bảng `vnindex` — Tồn Tại Nhưng Chưa Được Dùng

**Schema (production):**
```sql
CREATE TABLE vnindex (
  id INT,
  index_hot DECIMAL,      -- "Vùng nóng" VN-Index?
  index_current DECIMAL,  -- Giá trị VN-Index hiện tại
  index_suggest DECIMAL,  -- Mức gợi ý
  created_at DATETIME,
  updated_at DATETIME
)
```

**Hiện trạng:** Bảng này **rỗng hoàn toàn** (0 rows). Không có script VPS nào đọc hay ghi vào bảng này. Không có webapp route nào truy cập.

**Phán đoán:** Đây là bảng được tạo từ giai đoạn đầu dự án, dự định để lưu dữ liệu VN-Index hàng ngày và đưa ra gợi ý dựa trên mức "hot/cold" của chỉ số. Chưa bao giờ được implement.

**Tiềm năng:** Nếu implement, có thể:
- Sync VN-Index daily từ VCI API (đã có `sendEmailVnindex` endpoint)
- Hiển thị VN-Index trend trên homepage
- So sánh hiệu suất danh mục vs VN-Index (xem Section 2.7)

### 13.2 Bảng `notice_stock_follow` — Legacy, Không Dùng

**Schema:** `id, id_stocks, id_users, notice_buy, notice_sell, notice_flag, create_at, update_at`

**Hiện trạng:** **Rỗng hoàn toàn**. Đây là bảng cũ (naming convention `id_stocks` vs `stock_id` mới, `create_at` không phải `created_at`) — bị thay thế bởi `user_follows` hiện tại.

**Action:** Có thể drop bảng này để dọn dẹp schema. Không có code nào tham chiếu tới nó.

### 13.3 Bảng `stock_status_logs` — Tạo Ra Nhưng Chưa Dùng

**Hiện trạng:** Bảng tồn tại trong webapp DB, rỗng hoàn toàn. Được nhắc trong CLAUDE.md là "Audit log cho các thao tác sync stock. Hiện chỉ có `id` + timestamps (mở rộng sau)."

**Tiềm năng:** Có thể dùng làm audit trail khi admin thêm/xóa stock. Hiện tại không có code ghi vào.

### 13.4 `status_sync` — Có Field `status_sync_price` Chưa Được Document

**Thực tế từ production:**
```
status_sync_price: 0,  -- Không được document ở đâu!
status_sync_risk:  0,  
created_at: 2025-08-07
updated_at: 2026-05-16 00:03:17
```

**Phát hiện:** `status_sync_price` field tồn tại nhưng không được document trong CLAUDE.md hay VPS DOCUMENTATION.md. Webapp có thể dùng field này để hiển thị "Đang sync giá..." indicator cho admin.

### 13.5 `sync_stocks_10M_conf` — Config Production Thực Tế

```
user_id:      2           -- Admin user ID (admin_follow inserted cho user này)
price:        10,000 VND  -- Giá tham chiếu (mệnh giá cổ phiếu VN)
volume_add:   1,000,000   -- Ngưỡng thêm = 1M × 10,000 = 10 tỷ/ngày
volume_follow: 10,000,000 -- Ngưỡng follow = 10M × 10,000 = 100 tỷ/ngày
```

**Ý nghĩa:** Mã có GTGD > 100 tỷ/ngày → auto insert vào admin_follow. Mã có GTGD > 10 tỷ/ngày nhưng chưa trong DB → email cảnh báo admin thêm vào.

---

## PHẦN 14 — CÁCH TƯ DUY PHÁT TRIỂN (Startup Mindset)

### 14.1 Giai Đoạn Hiện Tại: Product-Market Fit

Với **3 active users**, đây không phải giai đoạn scale. Ưu tiên đúng:

| Không nên làm | Nên làm |
|--------------|---------|
| Redis, microservices, queue workers | Fix bugs, polish UX |
| A/B testing framework | Thêm tính năng core người dùng cần |
| CI/CD pipeline phức tạp | Làm cho sản phẩm shareable (invite friends) |
| Kubernetes | Monitoring VPS đơn giản |
| GraphQL API | Tận dụng data đã có (risk history, dividend) |

### 14.1b Phân Tích Users Thực Tế (Production Data)

| User ID | Email | Role | Status | Ghi chú |
|---------|-------|------|--------|---------|
| 1 | lehuuphuoc0196@gmail.com | User (role=0) | active | Owner — tài khoản user chính |
| 2 | admin@gmail.com | Admin (role=1) | active | Owner — tài khoản admin |
| 8 | lehuuphuoc0196@icloud.com | User (role=0) | active | Owner — tài khoản test |
| 9 | tuanomc97@gmail.com | User (role=0) | **inactive** | Người dùng bên ngoài, đăng ký nhưng chưa verify email |

**Insight quan trọng:** User thực sự đầu tiên từ bên ngoài (Tuan Nguyen) đã đăng ký nhưng **KHÔNG verify email**. Đây là churn điểm cần fix:
- Trang sau khi đăng ký có hướng dẫn kiểm tra email không?
- Email xác thực có vào spam không?
- Có resend email verification nếu không nhận được không?

**Điều tra thực tế:**
- `register.js` line 109: Sau đăng ký, modal hiển thị `"Kiểm tra hộp thư <b>email</b> để xác thực."` ✅ OK
- **THIẾU:** Không có endpoint resend verification email
- **THIẾU:** Không có trang `/dang-ky/thanh-cong` riêng — chỉ modal thoáng qua

**Action cần làm:**
1. Thêm route `POST /resend-verification` → gửi lại email xác thực
2. Khi user đăng nhập với `active=0` → redirect đến trang "Email chưa xác thực" + nút "Gửi lại email"
3. Admin có thể manually activate user từ `/admin/users` (kiểm tra đã có chưa)

---

### 14.2 Con Đường Tăng Users (User Acquisition)

**Vấn đề cốt lõi:** Không có tính năng "invite" hay viral loop. User dùng một mình, không có lý do chia sẻ với người khác.

**Đề xuất viral loops:**
1. **Chia sẻ watchlist**: "Xem danh sách 10 mã tôi đang theo dõi: [link]" → visitor thấy → đăng ký để xem đầy đủ
2. **Public portfolio** (opt-in): User chọn public → link share → landing page với thành tích (nặc danh)
3. **Email referral**: "Mời bạn bè → cả hai được badge 'Early Adopter'"
4. **Telegram bot** (dài hạn): Gửi signal thông qua Telegram thay vì chỉ email

### 14.3 Retention — Giữ User Quay Lại

**Hiện trạng:** User nhận email mỗi ngày lúc 07:00 (follow signals) và 15:00 (portfolio summary). Đây là hook tốt nhưng có thể cải thiện:

- Email 07:00 gửi kể cả khi không có signal → spam → user unsubscribe
- Nên: chỉ gửi khi có tín hiệu thực sự (hiện tại đã implement nhưng cần verify)
- Thêm "Bảng tóm tắt tuần" mỗi Thứ Hai → habit-forming

### 14.4 Monetization Potential (Tương Lai)

| Tier | Giá | Tính năng |
|------|-----|----------|
| Free | 0đ | Tối đa 20 follows, email 1 lần/ngày |
| Pro | 99.000đ/tháng | Unlimited follows, email realtime, P&L chart |
| Premium | 299.000đ/tháng | + Gợi ý cá nhân hóa, portfolio public |

**Note:** Không implement freemium quá sớm. Trước tiên cần có ≥100 active users mới biết feature nào đáng charge.

---

*Tài liệu này sẽ được cập nhật liên tục theo tiến độ implement. Mỗi item hoàn thành sẽ được đánh dấu ✅.*
