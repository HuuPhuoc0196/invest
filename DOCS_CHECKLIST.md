# Checklist – Viết tài liệu project Invest

> Mục tiêu: Tổng hợp toàn bộ tài liệu vào một file `CLAUDE.md` duy nhất, tiếng Việt,
> để AI (Claude Code) đọc và hiểu project ngay từ đầu session.

---

## Tiến độ tổng thể

- [x] Tạo `CLAUDE.md` hoàn chỉnh ✅
- [x] Cập nhật `README.md` ✅
- [x] Xóa 4 file doc cũ đã được merge ✅

---

## Nội dung cần có trong CLAUDE.md

### Phần 1 – Tổng quan
- [ ] Tên project, mục đích, đối tượng người dùng
- [ ] URL local (`http://localhost/invest/public`)
- [ ] Link GitHub
- [ ] Trạng thái project (đang phát triển / production-ready)

### Phần 2 – Công nghệ sử dụng
- [ ] Backend: Laravel 9, PHP 8+, MySQL, Sanctum, Guzzle
- [ ] Frontend: Vite 4, Axios, Lodash, Puppeteer, Cheerio
- [ ] Packages đặc biệt: opcodesio/log-viewer
- [ ] Dev tools: PHPUnit, Laravel Pint, Spatie Ignition

### Phần 3 – Commands nhanh
- [ ] Setup môi trường lần đầu
- [ ] Lệnh dev hàng ngày (`php artisan serve`, `npm run dev`)
- [ ] Build production (`npm run build`)
- [ ] Cache, routes, logs (`artisan cache:clear`, `route:list`, v.v.)
- [ ] Testing (`php artisan test`, `./vendor/bin/pint`)

### Phần 4 – Kiến trúc hệ thống
- [ ] Sơ đồ layers: Route → Middleware → Controller → Service → Model → DB
- [ ] Pattern Service Layer (logic ở Services, không phải Controllers)
- [ ] Role-based access: Admin (`role=1`) / User (`role=0`)
- [ ] Cơ chế cache + FIFO portfolio

### Phần 5 – Cấu trúc thư mục
- [ ] `app/Http/Controllers/` (4 controllers chính)
- [ ] `app/Services/` (8 services)
- [ ] `app/Models/` (12 models)
- [ ] `app/Http/Middleware/` (14 middlewares)
- [ ] `resources/views/` (50 blade templates, 3 layouts)
- [ ] `resources/js/` + `resources/css/`
- [ ] `database/migrations/` (10 files)
- [ ] `routes/web.php` + `routes/api.php`

### Phần 6 – Database Schema (chi tiết)
- [ ] **`users`** – id, name, email, password, role (0/1), email_verified_at, active
- [ ] **`stocks`** – id, code, recommended_buy_price, current_price, recommended_sell_price, risk_level, rating_stocks, volume_avg
- [ ] **`user_portfolios`** – id, user_id, stock_id, buy_price, buy_date, quantity, session_closed_flag (FIFO lots)
- [ ] **`user_portfolio_sells`** – id, user_id, stock_id, sell_price, sell_date, quantity
- [ ] **`user_follows`** – id, user_id, stock_id, follow_price_buy, follow_price_sell, notice_flag (unique: user+stock)
- [ ] **`cash_follow`** – user_id, cash (số dư ví ảo)
- [ ] **`cash_in`** – user_id, cash_in, cash_date
- [ ] **`cash_out`** – user_id, cash_out, cash_date
- [ ] **`status_sync`** – status_sync_price, status_sync_risk
- [ ] **`stock_status_logs`** – audit log
- [ ] **`personal_access_tokens`** – Sanctum tokens
- [ ] **`password_resets`** – reset tokens
- [ ] **`failed_jobs`** – queue failures
- [ ] Mô tả quan hệ giữa các bảng (FK, cascade)

### Phần 7 – Routes & API Reference
- [ ] **SEO/Public**: `/robots.txt`, `/sitemap.xml`, `/logo.svg`
- [ ] **Guest** (middleware `guest`): `/dang-nhap`, `/dang-ky`, `/quen-mat-khau`, `/dat-lai-mat-khau`
- [ ] **Public**: `/trang-chu` (trang chủ, không cần đăng nhập)
- [ ] **Admin** (middleware `auth+admin`): toàn bộ `/admin/*`
- [ ] **User** (middleware `auth+user`): toàn bộ `/user/*`
- [ ] **API Sanctum**: `GET /api/user`
- [ ] **API Sync** (middleware `cron.secret`): tất cả `/api/admin/*`
- [ ] **API Cache** (middleware `cron.secret`): tất cả `/api/cache/*`
- [ ] Header xác thực API: `X-Cron-Secret` hoặc `Authorization: Bearer`

### Phần 8 – Modules & Features
- [ ] **8.1 Authentication** – register, email verify, login, forgot/reset password
- [ ] **8.2 Admin** – CRUD stocks, import/export CSV, follow & suggest management, user management, logs viewer
- [ ] **8.3 Portfolio (Mua/Bán)** – Buy trừ cash ảo + tạo FIFO lot, Sell cộng cash + ghi lịch sử, investment performance
- [ ] **8.4 Follow System** – theo dõi giá, email alert khi chạm giá
- [ ] **8.5 Cash Management** – cashIn, cashOut, số dư `cash_follow`
- [ ] **8.6 Sync Jobs** – collectPrice, collectRisk, sendEmail* các loại
- [ ] **8.7 Cache System** – file cache, TTL 1 ngày, lock mechanism, cache keys pattern, API clear cache
- [ ] **8.8 Logging & Monitoring** – daily rotating log, email khi WARNING/ERROR (throttle 5 phút), bypass throttle CRITICAL+

### Phần 9 – Services
- [ ] Bảng tóm tắt 8 services (tên, file path, mục đích chính)
- [ ] Cache keys của từng service (pattern invalidation)

### Phần 10 – Frontend
- [ ] JS structure (`app.js`, `Admin.js`, `User.js`, `pages/`)
- [ ] CSS structure (`app.css`, `theme-invest-app.css`, `pages/`)
- [ ] 3 Blade layouts (`Layout`, `LayoutLogin`, `LayoutAdmin`)
- [ ] Vite config (dev/build commands)

### Phần 11 – Configuration & Environment
- [ ] Tất cả `.env` variables quan trọng + giải thích
- [ ] `CRON_API_SECRET` – bắt buộc thay trước production
- [ ] `SYNC_SERVICE_URL` – URL service sync bên ngoài
- [ ] `MAIL_NOTIFICATION_TO` – email nhận thông báo hệ thống
- [ ] Config files quan trọng (`logging.php`, `sanctum.php`, `cache.php`)

### Phần 12 – Conventions & Patterns
- [ ] Service Layer pattern (không viết logic trong Controller)
- [ ] Cache key naming convention
- [ ] FIFO portfolio logic (`session_closed_flag`)
- [ ] Logging convention (dùng `Log::*`, không dùng `dd()` trong production)
- [ ] Middleware naming và usage

### Phần 13 – Development Guide
- [ ] Setup môi trường local từ đầu (step by step)
- [ ] Debug routes có sẵn (`/__debug/test-log`, `/__debug/logo`)
- [ ] Troubleshooting thường gặp (log không tạo, cache không hoạt động, email không gửi)

---

## Files cần xử lý

| File | Việc cần làm | Trạng thái |
|------|-------------|------------|
| `CLAUDE.md` | Tạo mới – file tổng hợp chính | [x] ✅ Hoàn thành |
| `README.md` | Cập nhật – bỏ nội dung Laravel generic, thêm intro project | [x] ✅ Hoàn thành |
| `CODEBASE_OVERVIEW.md` | Xóa – nội dung đã merge vào CLAUDE.md | [x] ✅ Đã xóa |
| `LOGGING_CONFIGURATION.md` | Xóa – nội dung đã merge vào CLAUDE.md §8.8 | [x] ✅ Đã xóa |
| `CACHE_IMPLEMENTATION.md` | Xóa – nội dung đã merge vào CLAUDE.md §8.7 | [x] ✅ Đã xóa |
| `LOG_EMAIL_NOTIFICATION.md` | Xóa – nội dung đã merge vào CLAUDE.md §8.8 | [x] ✅ Đã xóa |
| `docs/blade-seo.md` | Giữ nguyên – guidelines kỹ thuật Blade/SEO riêng | ✅ Giữ nguyên |

---

## Tiêu chí hoàn thành

- CLAUDE.md có đủ 13 phần nêu trên
- AI đọc CLAUDE.md hiểu được: project làm gì, cấu trúc ra sao, cách thêm feature mới, cách debug
- Không còn thông tin nào bị bỏ sót từ 4 file doc cũ
- README.md ngắn gọn, project-specific, link tới CLAUDE.md để đọc chi tiết
