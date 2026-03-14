# Tổng quan Codebase – Hệ thống đầu tư cổ phiếu

> Tài liệu tóm tắt để điều tra / onboard nhanh. Cập nhật khi có thay đổi lớn.

## Công nghệ

- **Framework:** Laravel 9, PHP 8+
- **Database:** PostgreSQL (`ext-pgsql`)
- **Auth:** Laravel Sanctum
- **Mục đích:** Web quản lý danh mục cổ phiếu cá nhân (theo dõi mã, mua/bán ảo, nạp/rút tiền ảo, follow mã, nhận email thông báo).

---

## Phân quyền

- **`users.role`:** `0` = User, `1` = Admin
- **Middleware:**
  - `admin` → chỉ `role = 1`, redirect về `/admin`
  - `user` → chỉ `role = 0`, redirect về `/home`, `/user/*`

---

## Luồng chính

### 1. Admin (`/admin`, middleware `auth` + `admin`)

- **Admin view:** Danh sách cổ phiếu + trạng thái sync (`StatusSync`: `status_sync_price`, `status_sync_risk`)
- **CRUD cổ phiếu:** Insert/Update/Delete theo `code`; quản lý stock (insert, update, export/import CSV)
- **Sync:** Cập nhật risk theo 1 mã: `updateRiskForCode` (form + POST)
- **Logs:** Xem `storage/logs/laravel.log`, logs VPS, upload file
- **Stocks:** Export CSV, Import CSV, Insert từng mã

### 2. User (`/home`, `/user/*`, middleware `auth` + `user`)

- **Trang chủ (`UserView`):** Danh sách cổ phiếu hệ thống + danh mục user (từ `UserPortfolio::getProfileUser`)
- **Profile:** Tổng tài sản = `cash_follow.cash` + (cổ phiếu đang giữ × giá hiện tại); tiền nạp/rút từ `cash_in` / `cash_out`
- **Mua/Bán:**
  - **Buy:** Trừ `cash_follow.cash`, thêm bản ghi `user_portfolios` (FIFO)
  - **Sell:** Cộng tiền vào `cash_follow`, ghi `user_portfolios_sell`, FIFO trên các lô mua
- **Follow mã:** `user_follows` – user theo dõi mã với `follow_price_buy`, `follow_price_sell`, `notice_flag`
- **Nạp/Rút tiền ảo:** `cashIn` / `cashOut` → bảng `cash_in`, `cash_out`; số dư theo dõi ở `cash_follow`
- **Cài đặt email:** Session closed theo mã, cài đặt email cho follow (lưu flag/email settings)
- **Đổi mật khẩu, cập nhật tên:** `User::updateInfoProfile`, `changePassword`

### 3. Sync / API (dùng cho cron hoặc gọi ngoài)

- **`/api/admin/collect`:** Cập nhật giá hiện tại tất cả mã (`getNewPrice` → gọi API lấy giá)
- **`/api/admin/collectRisk`:** Cập nhật `risk_level` tất cả mã (`getNewRisk` → gọi `http://163.61.182.174/getRiskFromHTML`)
- **`/api/admin/getSuggestInvestment`:** Gửi email gợi ý đầu tư theo giá mua đề xuất
- **Email:** Risk thay đổi, VN-Index, cổ phiếu volume 1tr/10tr, lỗi hệ thống, follow hằng ngày… (đích mặc định: `EmailService` → `lehuuphuoc0196@gmail.com`)
- **Khác:** `deleteLogs`, `sendEmailRisk`, `sendEmailStocks`, `sendEmailStocksFollow`, `followStocksEveryDay`, `sendEmailVnindex`, `sendEmailError`

---

## Database (chính)

| Bảng | Mục đích |
|------|----------|
| `users` | id, name, email, password, role (0/1) |
| `stocks` | id, code, recommended_buy_price, current_price, recommended_sell_price, price_avg, percent_buy/sell, risk_level, rating_stocks, stocks_vn, volume |
| `user_portfolios` | user_id, stock_id, buy_price, buy_date, quantity, session_closed_flag |
| `user_portfolios_sell` | user_id, stock_id, sell_price, sell_date, quantity |
| `user_follows` | user_id, stock_id, follow_price_buy, follow_price_sell, notice_flag |
| `cash_follow` | user_id, cash (số dư ảo) |
| `cash_in` | user_id, cash_in, cash_date |
| `cash_out` | user_id, cash_out, cash_date |
| `status_sync` | status_sync_price, status_sync_risk |
| `stock_status_logs` | Log trạng thái cổ phiếu |

**FIFO:** Khi bán, trừ dần từ các lô mua cũ nhất trong `user_portfolios`; tổng hợp còn giữ và giá TB trong `UserPortfolio::getProfileUser`, `getPortfolioWithUserBuy`, `getStockHolding`.

---

## Controllers / Files quan trọng

| File | Chức năng |
|------|-----------|
| `app/Http/Controllers/Admin/Admin.php` | Toàn bộ admin: view, CRUD stock, stock management, export/import |
| `app/Http/Controllers/User/User.php` | Toàn bộ user: show, profile, buy, sell, follow, cashIn/cashOut, email settings, đổi mật khẩu, update profile |
| `app/Http/Controllers/Sync/Sync.php` | Sync giá/risk, gợi ý đầu tư, gửi email, collect risk từ API ngoài, upload file, logs |
| `app/Http/Controllers/Login/Login.php` | Login, register, forgot password, profile (guest) |
| `app/Services/EmailService.php` | Gửi mail thông báo (risk, VN-Index, gợi ý, lỗi, follow) |
| `app/Http/Middleware/AdminMiddleware.php` | Cho phép role = 1 |
| `app/Http/Middleware/UserMiddleware.php` | Cho phép role = 0 |

---

## Routes đáng nhớ

**Guest:** `/login`, `/register`, `/forgotPassword`, `/profile`

**Admin:** `/admin`, `/admin/insert`, `/admin/update/{code}`, `/admin/delete/{code}`, `/admin/stocks`, `/admin/stocks/export-csv`, `/admin/stocks/import-csv`, `/admin/stocks/insert`, `/admin/updateRiskForCode`, `/admin/logs`, `/admin/logsVPS`, `/admin/uploadFile`

**User:** `/home`, `/user`, `/user/profile`, `/user/buy`, `/user/sell`, `/user/follow`, `/user/cashIn`, `/user/cashOut`, `/user/investment-performance`, `/user/email-settings`, …

**API (sync/cron):** `/api/admin/collect`, `/api/admin/collectRisk`, `/api/admin/getSuggestInvestment`, `/api/admin/sendEmailRisk`, `/api/admin/sendEmailStocks`, `/api/admin/sendEmailStocksFollow`, `/api/admin/followStocksEveryDay`, `/api/admin/sendEmailVnindex`, `/api/admin/sendEmailError`, `/api/admin/deleteLogs`

---

*Tạo để tham chiếu khi điều tra / hỏi đáp về codebase.*
