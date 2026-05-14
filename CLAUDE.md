# CLAUDE.md — Tài liệu kỹ thuật: Invest App

> Tài liệu này được viết để AI (Claude Code) đọc và hiểu toàn bộ project ngay từ đầu session.
> Cập nhật khi có thay đổi lớn về kiến trúc, database hoặc tính năng mới.

---

## Mục lục

1. [Tổng quan project](#1-tổng-quan-project)
2. [Công nghệ sử dụng](#2-công-nghệ-sử-dụng)
3. [Commands nhanh](#3-commands-nhanh)
4. [Kiến trúc hệ thống](#4-kiến-trúc-hệ-thống)
5. [Cấu trúc thư mục](#5-cấu-trúc-thư-mục)
6. [Database Schema](#6-database-schema)
7. [Routes & API Reference](#7-routes--api-reference)
8. [Modules & Features](#8-modules--features)
9. [Services](#9-services)
10. [Frontend Structure](#10-frontend-structure)
11. [Configuration & Environment](#11-configuration--environment)
12. [Conventions & Patterns](#12-conventions--patterns)
13. [Development Guide](#13-development-guide)

---

## 1. Tổng quan project

**Tên:** Quản lý đầu tư cá nhân (Invest App)

**Mục đích:** Ứng dụng web theo dõi danh mục cổ phiếu cá nhân trên thị trường chứng khoán Việt Nam. Hỗ trợ mua/bán ảo, quản lý tiền ảo, đặt cảnh báo giá, nhận email thông báo tự động.

**Đối tượng người dùng:**
- **Admin** (`role=1`): Quản lý hệ thống – CRUD cổ phiếu, đồng bộ giá/risk, xem logs
- **User** (`role=0`): Nhà đầu tư cá nhân – theo dõi danh mục, mua/bán ảo, cài đặt follow giá

**URL local:** `http://localhost/invest/public`

**GitHub:** `https://github.com/HuuPhuoc0196/invest.git`

**Trạng thái:** Production-ready, đang phát triển thêm tính năng

---

## 2. Công nghệ sử dụng

### Backend
| Công nghệ | Version | Mục đích |
|-----------|---------|---------|
| Laravel | 9.19+ | Framework chính |
| PHP | 8.0.2+ | Ngôn ngữ backend |
| MySQL | (local) | Database chính |
| PostgreSQL | (production) | Database production (`ext-pgsql`) |
| Laravel Sanctum | 3.0+ | Token-based API authentication |
| Guzzle HTTP | 7.2+ | HTTP client (gọi API ngoài) |
| Monolog | (qua Laravel) | Logging system |

### Frontend
| Công nghệ | Version | Mục đích |
|-----------|---------|---------|
| Vite | 4.0.0 | Build tool, hot reload |
| Axios | 1.1.2 | HTTP client (AJAX) |
| Lodash | 4.17.19 | Utility functions |
| Puppeteer | 24.14.0 | Web scraping tự động |
| Cheerio | 1.1.1 | DOM parsing (server-side) |

### Packages đặc biệt
- **`opcodesio/log-viewer` 3.24+** – UI xem log file trong trình duyệt (Admin → Logs)
- **`laravel/pint`** – Code style fixer (dev)
- **`spatie/laravel-ignition`** – Debug error page đẹp (dev)

---

## 3. Commands nhanh

### Setup lần đầu
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Cấu hình DB trong .env rồi chạy:
php artisan migrate
```

### Dev hàng ngày
```bash
php artisan serve          # Khởi động server local (nếu không dùng XAMPP)
npm run dev                # Build + hot reload
```

### Build production
```bash
npm run build              # Compile assets vào public/build/
php artisan config:cache   # Cache config
php artisan route:cache    # Cache routes
```

### Artisan thường dùng
```bash
php artisan cache:clear    # Xóa toàn bộ cache
php artisan route:list     # Xem tất cả routes
php artisan tinker         # REPL (debug nhanh)
php artisan migrate        # Chạy migrations mới
php artisan migrate:rollback  # Rollback migration
```

### Testing & Code quality
```bash
php artisan test           # Chạy PHPUnit tests
./vendor/bin/pint          # Fix code style (Laravel Pint)
./vendor/bin/pint --test   # Kiểm tra style (không sửa)
```

---

## 4. Kiến trúc hệ thống

### Request Lifecycle
```
HTTP Request
    ↓
routes/web.php hoặc routes/api.php
    ↓
Middleware Group (web / api)
    ↓
Middleware cụ thể (auth, admin, user, cron.secret, ...)
    ↓
Controller (Admin / User / Login / Sync / CacheController)
    ↓
Service (business logic)
    ↓
Model → CacheService → Database (MySQL)
    ↓
Response (Blade View / JSON)
```

### Phân tầng (Layers)
| Tầng | Thư mục | Vai trò |
|------|---------|---------|
| **Route** | `routes/` | Định nghĩa URL → Controller mapping |
| **Middleware** | `app/Http/Middleware/` | Auth, role check, rate limit, security headers |
| **Controller** | `app/Http/Controllers/` | Nhận request, gọi service, trả response |
| **Service** | `app/Services/` | Toàn bộ business logic |
| **Model** | `app/Models/` | ORM, database queries, cache |
| **Database** | `database/migrations/` | Schema định nghĩa |

### Role-based Access
| Role | Giá trị | Middleware | Khu vực |
|------|---------|-----------|---------|
| Admin | `role = 1` | `admin` | `/admin/*` |
| User | `role = 0` | `user` | `/home`, `/user/*` |

---

## 5. Cấu trúc thư mục

```
invest/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/Admin.php        # Admin panel: CRUD stocks, users, logs, sync
│   │   │   ├── User/User.php          # User: home, buy, sell, follow, cash, profile
│   │   │   ├── Login/Login.php        # Auth: login, register, forgot/reset password
│   │   │   ├── Sync/Sync.php          # API sync: giá, risk, email jobs
│   │   │   └── Api/CacheController.php # API cache management
│   │   ├── Middleware/                # 14 middleware files
│   │   │   ├── AdminMiddleware.php    # role=1 only
│   │   │   ├── UserMiddleware.php     # role=0 only
│   │   │   ├── VerifyCronSecret.php   # Kiểm tra X-Cron-Secret header
│   │   │   ├── SecurityHeaders.php    # HTTP security headers
│   │   │   └── ThrottleAuthPosts.php  # Rate limit login attempts
│   │   └── Requests/                  # Form Request validation
│   ├── Logging/
│   │   ├── CustomDailyLogger.php      # Daily rotating log handler
│   │   └── EmailErrorHandler.php      # Gửi email khi WARNING/ERROR/CRITICAL
│   ├── Mail/
│   │   └── LogErrorNotificationMail.php # Mail class cho error notifications
│   ├── Models/                        # 12 Eloquent models
│   │   ├── User.php
│   │   ├── Stock.php
│   │   ├── UserPortfolio.php          # user_portfolios (FIFO lots)
│   │   ├── UserPortfolioSell.php      # user_portfolio_sells
│   │   ├── UserFollow.php             # user_follows (price alerts)
│   │   ├── UserCashFollow.php         # cash_follow (ví ảo)
│   │   ├── UserCashIn.php             # cash_in
│   │   ├── UserCashOut.php            # cash_out
│   │   ├── StockStatusLog.php         # stock_status_logs
│   │   ├── StatusSync.php             # status_sync
│   │   ├── AdminFollow.php            # admin follow suggestions
│   │   └── AdminSuggest.php           # admin investment suggestions
│   ├── Services/                      # 8 service files (business logic)
│   └── Providers/                     # Service providers
├── config/                            # 16 config files
│   ├── logging.php                    # Custom daily logger + email handler
│   ├── sanctum.php                    # Token auth
│   └── cache.php                      # File cache, TTL 86400s
├── database/
│   ├── migrations/                    # 10 migration files
│   ├── factories/
│   └── seeders/
├── docs/
│   └── blade-seo.md                   # Blade template & SEO guidelines
├── resources/
│   ├── css/                           # 27 CSS files
│   │   ├── app.css                    # Global styles
│   │   ├── theme-invest-app.css       # Theme chính
│   │   └── pages/                     # CSS riêng từng trang
│   ├── js/                            # 30+ JS files
│   │   ├── app.js                     # Base bundle (Axios, CSRF)
│   │   ├── Admin.js                   # Admin logic
│   │   ├── User.js                    # User logic
│   │   └── pages/                     # JS riêng từng trang
│   └── views/                         # 50 Blade templates
│       ├── Layout/                    # 3 layout chính
│       ├── Admin/                     # Admin views
│       ├── User/                      # User views
│       ├── Login/                     # Auth views
│       ├── Emails/                    # Email templates
│       └── partials/                  # Reusable components
├── routes/
│   ├── web.php                        # Web routes
│   └── api.php                        # API routes
├── storage/
│   ├── framework/cache/data/          # File cache storage
│   └── logs/                          # laravel_YYYYMMDD.log (daily rotating)
├── .env                               # Environment config
├── .env.example                       # Template (commit này, .env không commit)
├── vite.config.js                     # Vite build config
├── composer.json                      # PHP dependencies
└── package.json                       # Node dependencies
```

---

## 6. Database Schema

### Bảng `users`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `name` | string | Tên hiển thị |
| `email` | string (unique) | Email đăng nhập |
| `email_verified_at` | timestamp, nullable | Thời điểm xác thực email |
| `active` | tinyint | 0=chưa kích hoạt, 1=đã xác thực email |
| `role` | tinyint, default 0 | 0=User, 1=Admin |
| `password` | string | Hashed password (bcrypt) |
| `remember_token` | string | Remember me token |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `stocks`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `code` | string | Mã cổ phiếu (VD: VNM, FPT) |
| `recommended_buy_price` | decimal(15,2) | Giá mua đề xuất |
| `current_price` | decimal(15,2) | Giá hiện tại (cập nhật từ sync) |
| `recommended_sell_price` | decimal(15,2), nullable | Giá bán đề xuất |
| `risk_level` | tinyint | Mức độ rủi ro (từ API ngoài) |
| `rating_stocks` | decimal(5,2), nullable | Điểm đánh giá |
| `volume_avg` | bigint, nullable | Khối lượng giao dịch TB |
| `price_avg` | decimal | Giá trung bình (tính toán) |
| `percent_buy` | decimal | % lời nếu mua theo giá đề xuất |
| `percent_sell` | decimal | % lời nếu bán theo giá đề xuất |
| `stocks_vn` | string | Tên công ty (tiếng Việt) |
| `volume` | bigint | Khối lượng giao dịch gần nhất |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `user_portfolios` (FIFO lots – cổ phiếu đang giữ)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint (FK → users) | Cascade delete |
| `stock_id` | bigint (FK → stocks) | Cascade delete |
| `buy_price` | double(15,2) | Giá mua lô này |
| `buy_date` | date | Ngày mua |
| `quantity` | bigint | Số lượng cổ phiếu lô này |
| `session_closed_flag` | tinyint | 0=đang giữ, 1=đã bán hết (FIFO) |
| `created_at`, `updated_at` | timestamp | Timestamps |

> **FIFO:** Khi bán, trừ dần từ lô cũ nhất (`session_closed_flag=0`, sắp xếp theo `buy_date` asc). Khi lô bị bán hết, set `session_closed_flag=1`.

### Bảng `user_portfolio_sells` (lịch sử bán)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint (FK → users) | Cascade delete |
| `stock_id` | bigint (FK → stocks) | Cascade delete |
| `sell_price` | double(15,2) | Giá bán |
| `sell_date` | date | Ngày bán |
| `quantity` | bigint | Số lượng đã bán |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `user_follows` (cảnh báo giá)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint (FK → users) | Cascade delete |
| `stock_id` | bigint (FK → stocks) | Cascade delete |
| `follow_price_buy` | decimal, nullable | Giá mục tiêu mua |
| `follow_price_sell` | decimal, nullable | Giá mục tiêu bán |
| `notice_flag` | tinyint | 0=tắt, 1=bật thông báo |
| `notice_buy` | tinyint | Cờ bật thông báo mua |
| `notice_sell` | tinyint | Cờ bật thông báo bán |
| `auto_sync` | tinyint | Tự động sync |
| `created_at`, `updated_at` | timestamp | Timestamps |

> **Unique constraint:** `(user_id, stock_id)` – mỗi user chỉ follow mỗi mã 1 lần.

### Bảng `cash_follow` (ví tiền ảo)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint | ID user |
| `cash` | decimal | Số dư ví ảo hiện tại |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `cash_in` (lịch sử nạp tiền)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint | ID user |
| `cash_in` | decimal | Số tiền nạp |
| `cash_date` | date | Ngày nạp |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `cash_out` (lịch sử rút tiền)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint | ID user |
| `cash_out` | decimal | Số tiền rút |
| `cash_date` | date | Ngày rút |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `status_sync`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `status_sync_price` | string/timestamp | Lần cuối sync giá |
| `status_sync_risk` | string/timestamp | Lần cuối sync risk |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Bảng `stock_status_logs`
Audit log cho các thao tác sync stock. Hiện chỉ có `id` + timestamps (mở rộng sau).

### Bảng hệ thống Laravel
- **`personal_access_tokens`** – Sanctum API tokens
- **`password_resets`** – Token đặt lại mật khẩu
- **`failed_jobs`** – Queue jobs thất bại

### Quan hệ giữa các bảng
```
users (1) ─────── (many) user_portfolios
users (1) ─────── (many) user_portfolio_sells
users (1) ─────── (many) user_follows
users (1) ─────── (1)    cash_follow
users (1) ─────── (many) cash_in
users (1) ─────── (many) cash_out

stocks (1) ──────── (many) user_portfolios
stocks (1) ──────── (many) user_portfolio_sells
stocks (1) ──────── (many) user_follows
```

### Cache Keys Pattern
| Bảng | Cache Key | Xóa khi |
|------|-----------|---------|
| stocks | `stocks_all`, `stock_code_{CODE}`, `stock_risk_{CODE}` | Insert/Update/Delete stock, Import CSV, Sync |
| user_portfolios | `user_portfolio_profile_{USER_ID}`, `user_portfolio_stock_info_{USER_ID}`, `user_portfolio_buy_{USER_ID}`, `user_portfolio_session_{USER_ID}` | Buy, Sell |
| user_portfolio_sells | `user_portfolio_sell_{USER_ID}` | Sell |
| user_follows | `user_follow_{USER_ID}`, `user_follow_notice_{USER_ID}` | Insert/Update/Delete follow |
| cash_follow | `user_cash_{USER_ID}` | CashIn, CashOut, Buy, Sell |
| users | `user_{USER_ID}` | Update user name |
| status_sync | `status_sync` | Sau mỗi sync |

---

## 7. Routes & API Reference

### Public / SEO
| Method | URL | Mô tả |
|--------|-----|-------|
| GET | `/robots.txt` | SEO robots file |
| GET | `/sitemap.xml` | XML sitemap (cached 1 ngày) |
| GET | `/logo.svg` | Logo SVG (served qua Laravel, không phải file tĩnh) |
| GET | `/trang-chu` | Trang chủ (không cần đăng nhập) – `name: home` |
| GET | `/` | Redirect: Admin → `/admin`, User → `/trang-chu` |

### Guest Routes (middleware: `guest`)
| Method | URL | Mô tả |
|--------|-----|-------|
| GET/POST | `/dang-nhap` | Đăng nhập – `name: login` |
| GET/POST | `/dang-ky` | Đăng ký – `name: register` |
| GET/POST | `/quen-mat-khau` | Quên mật khẩu – `name: forgotPassword` |
| GET/POST | `/dat-lai-mat-khau` | Đặt lại mật khẩu – `name: password.reset` |
| GET | `/email/verify/{id}/{hash}` | Xác thực email (signed URL) |

> Redirect 301: `/login` → `/dang-nhap`, `/register` → `/dang-ky`, `/forgotPassword` → `/quen-mat-khau`

### Admin Routes (middleware: `auth` + `admin`)
| Method | URL | Controller Action | Mô tả |
|--------|-----|-------------------|-------|
| GET | `/admin` | `Admin@show` | Dashboard admin |
| GET | `/admin/logs` | `Admin@logs` | Log viewer |
| GET/POST | `/admin/insert` | `Admin@insert` | Thêm stock |
| GET/PUT | `/admin/update/{code}` | `Admin@update` | Sửa stock |
| POST | `/admin/delete/{code}` | `Admin@delete` | Xóa stock |
| GET | `/admin/stocks` | `Admin@stockManagement` | Quản lý stocks |
| GET | `/admin/stocks/export-csv` | `Admin@exportStocksCsv` | Export CSV |
| POST | `/admin/stocks/import-csv` | `Admin@importStocksCsv` | Import CSV |
| GET/POST | `/admin/stocks/insert` | `Admin@stockInsert` | Thêm stock (form khác) |
| GET | `/admin/stocks/follow` | `Admin@adminFollow` | Admin follow list |
| POST | `/admin/stocks/follow/batch` | `Admin@addFollowBatch` | Thêm follow hàng loạt |
| POST | `/admin/stocks/follow/batch-delete` | `Admin@deleteFollowBatch` | Xóa follow hàng loạt |
| DELETE | `/admin/stocks/follow/{stockId}` | `Admin@deleteFollow` | Xóa 1 follow |
| GET | `/admin/stocks/suggest` | `Admin@adminSuggest` | Gợi ý đầu tư |
| POST | `/admin/stocks/suggest/batch` | `Admin@addSuggestBatch` | Thêm gợi ý hàng loạt |
| DELETE | `/admin/stocks/suggest/{stockId}` | `Admin@deleteSuggest` | Xóa 1 gợi ý |
| POST | `/admin/stocks/suggest/batch-delete` | `Admin@deleteSuggestBatch` | Xóa gợi ý hàng loạt |
| GET | `/admin/users` | `Admin@userManagement` | Quản lý users |
| GET/PUT | `/admin/users/update/{id}` | `Admin@updateUser` | Sửa user |
| POST | `/admin/users/delete/{id}` | `Admin@deleteUser` | Xóa user |
| GET | `/admin/infoProfile` | `Admin@infoProfile` | Profile admin |
| GET/PUT | `/admin/updateInfoProfile` | `Admin@updateInfoProfile` | Cập nhật profile |
| GET/PUT | `/admin/changePassword` | `Admin@changePassword` | Đổi mật khẩu |
| GET | `/admin/logsVPS` | `Sync@getLogsVPS` | Logs từ VPS |
| GET | `/admin/logsVPS/data` | `Sync@getLogsVPSData` | Data logs VPS |
| GET/POST | `/admin/uploadFile` | `Sync@uploadFile` | Upload file |
| GET/POST | `/admin/updateRiskForCode` | `Sync@updateRiskForCode` | Update risk 1 mã |
| POST | `/admin/sync/run-update-stock/{code}` | `Sync@runSyncUpdateStock` | Sync update 1 stock |

### User Routes (middleware: `auth` + `user`)
| Method | URL | Controller Action | Mô tả |
|--------|-----|-------------------|-------|
| GET | `/user/profile` | `User@profile` | Profile + portfolio tổng hợp |
| GET | `/user/infoProfile` | `User@infoProfile` | Thông tin cá nhân |
| GET | `/user/follow` | `User@follow` | Danh sách follow |
| GET | `/user/investment-performance` | `User@investmentPerformance` | Hiệu suất đầu tư |
| GET/POST | `/user/buy` | `User@buy` | Mua cổ phiếu |
| GET/POST | `/user/sell` | `User@sell` | Bán cổ phiếu |
| GET/POST | `/user/insertFollow` | `User@insertFollow` | Thêm follow |
| POST | `/user/addFollowBatch` | `User@addFollowBatch` | Thêm follow hàng loạt |
| GET | `/user/checkStockCode/{code}` | `User@checkStockCode` | Kiểm tra mã stock |
| GET | `/user/validate-stock/{code}` | `User@validateStockCode` | Validate mã stock |
| GET/PUT | `/user/updateFollow/{code}` | `User@updateFollow` | Cập nhật follow |
| GET | `/user/deleteFollow/{code}` | `User@deleteFollow` | Xóa follow |
| POST | `/user/deleteFollowAll` | `User@deleteAllFollow` | Xóa tất cả follow |
| POST | `/user/deleteFollowBatch` | `User@deleteFollowBatch` | Xóa follow hàng loạt |
| GET/POST | `/user/cashIn` | `User@cashIn` | Nạp tiền ảo |
| GET/POST | `/user/cashOut` | `User@cashOut` | Rút tiền ảo |
| GET/PUT | `/user/updateInfoProfile` | `User@updateInfoProfile` | Cập nhật tên |
| GET/PUT | `/user/changePassword` | `User@changePassword` | Đổi mật khẩu |
| GET | `/user/email-settings` | `User@emailSettings` | Cài đặt email |
| POST | `/user/email-settings/save-session-closed` | `User@saveSessionClosedFlags` | Lưu session closed |
| POST | `/user/email-settings-follow/save` | `User@saveEmailSettingsFollow` | Lưu cài đặt follow email |
| POST | `/logout` | (closure) | Đăng xuất |

### API Routes
#### Sanctum
| Method | URL | Mô tả |
|--------|-----|-------|
| GET | `/api/user` | Lấy thông tin user đang đăng nhập (Bearer token) |

#### Sync Jobs (middleware: `cron.secret` – Header: `X-Cron-Secret`)
| Method | URL | Mô tả |
|--------|-----|-------|
| GET | `/api/admin/deleteLogs` | Xóa log cũ hơn 30 ngày |
| POST | `/api/admin/sendEmailError` | Gửi email thông báo lỗi |
| GET | `/api/admin/sendEmailStocksFollow` | Gửi email cổ phiếu follow |
| GET | `/api/admin/followStocksEveryDay` | Gửi email follow hàng ngày |
| GET | `/api/admin/sendEmailVnindex` | Gửi email VN-Index |

#### Cache Management (middleware: `cron.secret`)
| Method | URL | Body | Mô tả |
|--------|-----|------|-------|
| GET | `/api/cache/info` | – | Thông tin cache (driver, số file, dung lượng) |
| POST | `/api/cache/clear-all` | – | Xóa toàn bộ cache |
| POST | `/api/cache/clear-table` | `{"table": "stocks"}` | Xóa cache theo bảng |
| POST | `/api/cache/clear-user` | `{"user_id": 1}` | Xóa cache user |
| POST | `/api/cache/clear-stock` | `{"code": "VNM"}` | Xóa cache stock code |
| POST | `/api/cache/clear-keys` | `{"keys": [...]}` | Xóa cache theo danh sách keys |

> **Gọi API từ cron/VPS:** Thêm header `X-Cron-Secret: {CRON_API_SECRET}` hoặc `Authorization: Bearer {CRON_API_SECRET}`. Sai secret → HTTP 403.

### Debug Routes (chỉ khi `APP_DEBUG=true`)
| URL | Mô tả |
|-----|-------|
| `/__debug/logo` | Kiểm tra logo SVG config |
| `/__debug/test-log` | Test logging system |
| `/__debug/test-log-warning` | Test WARNING log + email |
| `/__debug/test-log-error` | Test ERROR log + email |
| `/__debug/test-log-critical` | Test CRITICAL log + email |

---

## 8. Modules & Features

### 8.1 Authentication
**Files:** [app/Http/Controllers/Login/Login.php](app/Http/Controllers/Login/Login.php), [app/Services/AuthService.php](app/Services/AuthService.php)

**Luồng đăng ký:**
1. User điền form `/dang-ky`
2. `AuthService` validate + tạo user (`active=0`, `role=0`)
3. Gửi email xác thực (signed URL `/email/verify/{id}/{hash}`)
4. User bấm link → `active=1`, redirect về login

**Luồng đăng nhập:**
1. POST `/dang-nhap` với email + password
2. `Auth::attempt()` kiểm tra credentials
3. Kiểm tra `active=1` (đã xác thực email)
4. Redirect theo role: `role=1` → `/admin`, `role=0` → `/trang-chu`

**Luồng quên mật khẩu:**
1. POST email → gửi reset link (token trong `password_resets`)
2. User click link → form đặt mật khẩu mới
3. Validate token → update password → redirect login

---

### 8.2 Admin Module
**Files:** [app/Http/Controllers/Admin/Admin.php](app/Http/Controllers/Admin/Admin.php), [app/Services/StockService.php](app/Services/StockService.php)

**Tính năng:**
- **CRUD stocks:** Thêm/sửa/xóa mã cổ phiếu. Xóa cascade xóa tất cả portfolio, follow liên quan.
- **Stock Management:** Export CSV (tải về toàn bộ stocks), Import CSV (thêm/cập nhật hàng loạt).
- **Admin Follow:** Danh sách mã admin theo dõi (khác với user follow).
- **Admin Suggest:** Gợi ý đầu tư từ admin cho users.
- **User Management:** Xem, sửa, xóa user accounts.
- **Log Viewer:** Dùng `opcodesio/log-viewer` để xem `storage/logs/laravel_YYYYMMDD.log`.
- **Logs VPS:** Xem logs từ VPS bên ngoài qua `SYNC_SERVICE_URL`.

---

### 8.3 Portfolio – Mua/Bán
**Files:** [app/Http/Controllers/User/User.php](app/Http/Controllers/User/User.php), [app/Services/PortfolioService.php](app/Services/PortfolioService.php)

**Luồng mua cổ phiếu (Buy):**
1. User chọn mã + số lượng + giá mua + ngày mua
2. Kiểm tra số dư `cash_follow.cash` đủ không
3. Trừ `cash_follow.cash` = `cash - (buy_price × quantity)`
4. Tạo bản ghi `user_portfolios` (1 FIFO lot mới)
5. Clear cache: `user_portfolio_*`, `user_cash_*`

**Luồng bán cổ phiếu (Sell) – FIFO:**
1. User chọn mã + số lượng + giá bán + ngày bán
2. Lấy các lô mua cũ nhất (`session_closed_flag=0`, sort `buy_date` asc)
3. Trừ dần từng lô: nếu lô hết → `session_closed_flag=1`
4. Tạo bản ghi `user_portfolio_sells`
5. Cộng `cash_follow.cash` += `sell_price × quantity`
6. Clear cache liên quan

**Investment Performance:** Tính P&L, ROI so sánh giá mua TB với giá hiện tại.

---

### 8.4 Follow System (Cảnh báo giá)
**Files:** [app/Services/FollowService.php](app/Services/FollowService.php), [app/Models/UserFollow.php](app/Models/UserFollow.php)

**Tính năng:**
- Mỗi user follow mỗi mã chỉ 1 lần (unique constraint)
- Đặt `follow_price_buy` (cảnh báo khi giá ≤ giá này → nên mua)
- Đặt `follow_price_sell` (cảnh báo khi giá ≥ giá này → nên bán)
- `notice_buy` / `notice_sell`: bật/tắt từng loại thông báo
- Email gửi qua `sendEmailStocksFollow` (API cron hàng ngày)

---

### 8.5 Cash Management (Ví tiền ảo)
**Files:** [app/Services/CashService.php](app/Services/CashService.php), Models: `UserCashIn`, `UserCashOut`, `UserCashFollow`

**Nạp tiền (CashIn):**
1. User nhập số tiền + ngày
2. Tạo bản ghi `cash_in`
3. Cộng `cash_follow.cash` += số tiền nạp

**Rút tiền (CashOut):**
1. Kiểm tra số dư đủ không
2. Tạo bản ghi `cash_out`
3. Trừ `cash_follow.cash` -= số tiền rút

**Số dư hiển thị:** `cash_follow.cash` (duy nhất 1 bản ghi per user)

---

### 8.6 Sync Jobs
**Files:** [app/Http/Controllers/Sync/Sync.php](app/Http/Controllers/Sync/Sync.php), [app/Services/SyncService.php](app/Services/SyncService.php), [app/Services/EmailService.php](app/Services/EmailService.php)

Tất cả sync jobs được gọi qua API (cron server hoặc manual):

| API Endpoint | Mô tả |
|-------------|-------|
| `/api/admin/deleteLogs` | Xóa log files cũ hơn 30 ngày |
| `/api/admin/sendEmailError` | Gửi email thông báo lỗi hệ thống |
| `/api/admin/sendEmailStocksFollow` | Email cảnh báo giá cho users có follow |
| `/api/admin/followStocksEveryDay` | Email tổng hợp follow hàng ngày |
| `/api/admin/sendEmailVnindex` | Email báo cáo VN-Index |
| `/admin/updateRiskForCode` (web) | Cập nhật risk level cho 1 mã cụ thể |
| `/admin/sync/run-update-stock/{code}` (web) | Sync update 1 stock từ service ngoài |

**External service:** `SYNC_SERVICE_URL` trong `.env` – URL của service bên ngoài cung cấp giá và risk level.

---

### 8.7 Cache System
**Files:** [app/Services/CacheService.php](app/Services/CacheService.php), [app/Http/Controllers/Api/CacheController.php](app/Http/Controllers/Api/CacheController.php)

**Cơ chế:**
- **Driver:** File (`CACHE_DRIVER=file`), lưu tại `storage/framework/cache/data/`
- **TTL mặc định:** 86400 giây (1 ngày)
- **Lock mechanism:** Chỉ 1 request query DB khi cache miss, các request khác chờ (lock timeout: 10s) → tránh cache stampede
- **Tự động invalidate:** Mỗi service xóa cache sau khi write data

**Sử dụng:**
```php
// Lấy cache (hoặc query DB nếu chưa có)
CacheService::remember('cache_key', CacheService::TTL_ONE_DAY, fn() => Model::query());

// Xóa 1 key
CacheService::forget('cache_key');

// Xóa nhiều keys
CacheService::forgetMany(['key1', 'key2']);

// Xóa cache theo pattern stock
CacheService::clearStockCache('VNM');
```

**Lưu ý production:** Đổi `CRON_API_SECRET` mới trước khi deploy. Generate: `openssl rand -hex 32`.

---

### 8.8 Logging & Monitoring
**Files:** [app/Logging/CustomDailyLogger.php](app/Logging/CustomDailyLogger.php), [app/Logging/EmailErrorHandler.php](app/Logging/EmailErrorHandler.php)

**Log files:**
- Format tên: `storage/logs/laravel_YYYYMMDD.log`
- Retention: 30 ngày (tự động xóa file cũ hơn)
- Channel config: `config/logging.php` → stack → `daily_custom`

**Email notification tự động:**
| Level | Throttle | Mô tả |
|-------|---------|-------|
| WARNING | 5 phút (300s) | Cảnh báo thông thường |
| ERROR | 5 phút (300s) | Runtime errors |
| CRITICAL | Không throttle | Lỗi nghiêm trọng |
| ALERT | Không throttle | Cần hành động ngay |
| EMERGENCY | Không throttle | Hệ thống không khả dụng |

**Recipient:** `MAIL_FROM_ADDRESS` trong `.env` (hoặc config thêm trong `config/logging.php`).

**Sử dụng trong code:**
```php
use Illuminate\Support\Facades\Log;

Log::info('Thông tin thông thường');
Log::warning('Cảnh báo', ['context' => 'data']);
Log::error('Lỗi', ['exception' => $e->getMessage()]);
Log::critical('Lỗi nghiêm trọng');  // Gửi email ngay, không throttle
```

---

## 9. Services

| Service | File | Mục đích chính | Tương tác cache |
|---------|------|----------------|-----------------|
| `AuthService` | [app/Services/AuthService.php](app/Services/AuthService.php) | Đăng ký, đăng nhập, reset password, cập nhật tên | Xóa `user_{id}` khi update tên |
| `StockService` | [app/Services/StockService.php](app/Services/StockService.php) | CRUD stocks, import/export CSV | Xóa `stocks_all`, `stock_code_*` sau write |
| `PortfolioService` | [app/Services/PortfolioService.php](app/Services/PortfolioService.php) | Mua/bán FIFO, tính P&L, ROI | Xóa `user_portfolio_*`, `user_cash_*` |
| `FollowService` | [app/Services/FollowService.php](app/Services/FollowService.php) | CRUD follow alerts, batch operations | Xóa `user_follow_*` sau write |
| `CashService` | [app/Services/CashService.php](app/Services/CashService.php) | Nạp/rút tiền ảo, kiểm tra số dư | Xóa `user_cash_{id}` sau write |
| `EmailService` | [app/Services/EmailService.php](app/Services/EmailService.php) | Gửi tất cả loại email (risk, follow, VN-Index, lỗi, gợi ý) | Không dùng cache |
| `SyncService` | [app/Services/SyncService.php](app/Services/SyncService.php) | Fetch giá/risk từ API ngoài, cập nhật DB | Xóa `stocks_all`, `stock_*` sau sync |
| `CacheService` | [app/Services/CacheService.php](app/Services/CacheService.php) | Get/set cache với lock, clear cache theo pattern | Là cache layer cho toàn bộ system |

---

## 10. Frontend Structure

### JavaScript (`resources/js/`)
```
js/
├── app.js          # Base bundle: Axios setup, CSRF token, global config
├── Admin.js        # Logic trang admin (form submit, stock management)
├── User.js         # Logic trang user (buy/sell form, follow, cash)
└── pages/          # JS riêng cho từng trang (30+ files)
    ├── adminInsert.js
    ├── adminView.js
    ├── userHome.js
    ├── userBuy.js
    ├── userSell.js
    └── ...
```

### CSS (`resources/css/`)
```
css/
├── app.css                  # Global styles, resets
├── theme-invest-app.css     # Theme màu sắc chính của app
└── pages/                   # CSS riêng từng trang (15+ files)
    ├── adminView.css
    ├── userHome.css
    └── ...
```

### Blade Layouts (`resources/views/Layout/`)
| Layout | File | Dùng cho |
|--------|------|---------|
| User/Public | `Layout.blade.php` | Trang chủ, user pages, home |
| Auth | `LayoutLogin.blade.php` | Login, register, reset password |
| Admin | `LayoutAdmin.blade.php` | Toàn bộ admin panel |

### Cách include assets (Vite)
```blade
{{-- Trong Blade template --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
@vite(['resources/css/pages/userHome.css', 'resources/js/pages/userHome.js'])
```

### Vite Commands
```bash
npm run dev    # Dev server với hot reload (port 5173)
npm run build  # Build tối ưu → public/build/ (production)
```

---

## 11. Configuration & Environment

### Biến `.env` quan trọng

| Biến | Mặc định | Mô tả |
|------|----------|-------|
| `APP_NAME` | Quản lý đầu tư cá nhân | Tên app |
| `APP_ENV` | `local` | Môi trường (`local` / `production`) |
| `APP_DEBUG` | `true` | Debug mode (false ở production) |
| `APP_URL` | `http://localhost/invest/public` | URL gốc của app |
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_DATABASE` | `invest` | Tên database |
| `CACHE_DRIVER` | `file` | Cache driver |
| `LOG_CHANNEL` | `stack` | Log channel (dùng custom daily) |
| `LOG_LEVEL` | `debug` | Level tối thiểu ghi log (`warning` ở production) |
| `MAIL_MAILER` | `smtp` | Mail driver |
| `MAIL_FROM_ADDRESS` | – | Email gửi + nhận thông báo lỗi |
| `MAIL_NOTIFICATION_TO` | `admin@example.com` | Email nhận thông báo hệ thống |
| `CRON_API_SECRET` | – | **Bắt buộc** – Secret cho cron/VPS gọi `/api/admin/*` |
| `SYNC_SERVICE_URL` | `http://localhost` | URL service bên ngoài cung cấp giá/risk |

### Config files quan trọng

**`config/logging.php`**
```php
'stack' => ['driver' => 'stack', 'channels' => ['daily_custom']],
'daily_custom' => [
    'driver' => 'custom',
    'via' => App\Logging\CustomDailyLogger::class,
    'days' => 30,              // Retention 30 ngày
    'send_email_on_error' => true,
    'email_throttle' => 300,   // 5 phút throttle cho WARNING/ERROR
],
```

**`config/cache.php`**
- Driver: `file`, path: `storage/framework/cache/data/`
- TTL mặc định: 86400 giây (1 ngày)

**`config/sanctum.php`**
- Token-based API authentication
- Dùng cho `/api/user` endpoint

### Bảo mật API Sync
Mọi request tới `/api/admin/*` và `/api/cache/*` phải gửi:
```
X-Cron-Secret: {CRON_API_SECRET}
```
hoặc:
```
Authorization: Bearer {CRON_API_SECRET}
```
Sai secret → HTTP 403 Forbidden.

---

## 12. Conventions & Patterns

### 1. Service Layer Pattern
```
Controller → Service → Model (+ CacheService) → DB
```
- **Controller:** Chỉ nhận request, validate cơ bản, gọi service, trả response
- **Service:** Toàn bộ business logic, không truy cập DB trực tiếp
- **Model:** Query DB, có thêm cache layer

❌ Không viết DB queries trong Controller  
❌ Không viết business logic trong Model

### 2. Cache Convention
- Mọi query nặng (list, join) đều cache qua `CacheService::remember()`
- Key pattern: `{table}_{scope}_{id}` (VD: `user_portfolio_profile_1`)
- Phải xóa cache sau mỗi write operation trong Service

### 3. FIFO Portfolio Logic
- Mỗi lần mua tạo 1 lot riêng trong `user_portfolios`
- Khi bán: lấy các lot có `session_closed_flag=0`, sort `buy_date` ASC
- Trừ dần số lượng từ lot cũ nhất, set `session_closed_flag=1` khi hết

### 4. Logging Convention
```php
// ✅ Đúng
Log::info('User bought stock', ['user_id' => $id, 'code' => $code]);
Log::error('Sync failed', ['error' => $e->getMessage()]);

// ❌ Sai – không dùng trong production
dd($variable);
var_dump($data);
echo "debug";
```

### 5. Email Mã CK
- Mã cổ phiếu luôn uppercase: `strtoupper($code)` trước khi query
- VD: `Stock::getByCode('vnm')` → sẽ tự uppercase thành `'VNM'`

### 6. Blade Template
- Dùng `@vite()` để include assets, không dùng `<link>` hay `<script>` trực tiếp
- SEO: mỗi trang set `$title`, `$description` trước khi `@extends`
- Xem chi tiết: [docs/blade-seo.md](docs/blade-seo.md)

---

## 13. Development Guide

### Setup môi trường local từ đầu

```bash
# 1. Clone và cài dependencies
git clone https://github.com/HuuPhuoc0196/invest.git
cd invest
composer install
npm install

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 3. Cấu hình .env:
#    DB_DATABASE=invest (tạo DB này trước)
#    DB_USERNAME=root
#    DB_PASSWORD=
#    MAIL_* (cấu hình Gmail SMTP)
#    CRON_API_SECRET= (generate: openssl rand -hex 32)

# 4. Migrate database
php artisan migrate

# 5. Khởi động (dùng XAMPP: đặt project vào htdocs/invest)
npm run dev  # hoặc npm run build
```

### Cấu hình XAMPP (Windows)
- Project path: `C:\xampp\htdocs\invest`
- URL: `http://localhost/invest/public`
- PHP: 8.0+ (kiểm tra trong XAMPP Control Panel)

### Debug routes có sẵn (chỉ khi `APP_DEBUG=true`)
```
GET /__debug/logo            → Kiểm tra logo config
GET /__debug/test-log        → Test tạo log
GET /__debug/test-log-warning → Test WARNING + email
GET /__debug/test-log-error  → Test ERROR + email
GET /__debug/test-log-critical → Test CRITICAL + email (no throttle)
```

### Test API Cache (PowerShell)
```powershell
$headers = @{ "X-Cron-Secret" = $env:CRON_API_SECRET }

# Xem thông tin cache
Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/info" -Method GET -Headers $headers

# Clear cache stocks
Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-table" `
    -Method POST -Headers $headers `
    -Body (@{table="stocks"} | ConvertTo-Json) `
    -ContentType "application/json"

# Clear toàn bộ cache
Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-all" -Method POST -Headers $headers
```

### Troubleshooting thường gặp

**Log không được tạo:**
```bash
php artisan config:clear   # Clear config cache
icacls storage/logs        # Kiểm tra quyền thư mục (Windows)
```

**Cache không hoạt động:**
```bash
php artisan cache:clear
ls storage/framework/cache/data/   # Kiểm tra thư mục tồn tại
```

**Email không gửi được:**
1. Kiểm tra `MAIL_*` trong `.env`
2. Gmail: bật "2-Step Verification" → tạo "App Password"
3. Test: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@email.com'))`
4. Kiểm tra spam folder

**Giá cổ phiếu không cập nhật:**
1. Kiểm tra `SYNC_SERVICE_URL` trong `.env`
2. Test thủ công: `GET /api/admin/collect` với `X-Cron-Secret` header
3. Xem log: `storage/logs/laravel_YYYYMMDD.log`

### Quy trình thêm tính năng mới
1. Tạo route trong `routes/web.php` hoặc `routes/api.php`
2. Thêm method vào Controller (chỉ gọi service)
3. Viết logic trong Service mới hoặc hiện có
4. Nếu cần query DB: thêm static method vào Model + cache
5. Nếu cần view: tạo Blade file trong `resources/views/`
6. Nếu cần assets: thêm vào `resources/js/pages/` hoặc `resources/css/pages/`
7. Đăng ký assets mới trong `vite.config.js`
