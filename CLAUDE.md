# CLAUDE.md — Tài liệu kỹ thuật: Invest App

> Tài liệu này được viết để AI (Claude Code) đọc và hiểu toàn bộ project ngay từ đầu session.
> Cập nhật khi có thay đổi lớn về kiến trúc, database hoặc tính năng mới.

---

## Mục lục

1. [Tổng quan project](#1-tổng-quan-project)
2. [Công nghệ sử dụng](#2-công-nghệ-sử-dụng)
3. [Commands nhanh](#3-commands-nhanh)
4. [Kiến trúc hệ thống](#4-kiến-trúc-hệ-thống)
5. [Cấu trúc thư mục](#5-cấu-trúc-thư-mục) — tất cả file, mô tả từng file
6. [Database Schema](#6-database-schema) — 12 bảng + cache keys + model methods
7. [Routes & API Reference](#7-routes--api-reference) — tất cả routes
8. [Modules & Features](#8-modules--features)
   - 8.1 Authentication | 8.2 Admin | 8.3 Portfolio | 8.4 Follow | 8.5 Cash | 8.6 Sync
   - 8.7 Cache System | 8.8 Logging | 8.9 Crontab
   - 8.10 User Home Modals | 8.11 Public Pages
   - 8.12 Middleware | 8.13 Mail Classes
   - 8.16 VPS Python Scripts (server-side)
9. [Services](#9-services) — methods chi tiết từng service
10. [Frontend Structure](#10-frontend-structure) — theme, CSS, JS, Blade file list
11. [Configuration & Environment](#11-configuration--environment) — env vars, config files
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
│   │   │   ├── Admin/Admin.php            # Admin panel: CRUD stocks, users, logs, sync, suggest, follow
│   │   │   ├── User/User.php              # User: home, buy, sell, follow, cash, profile, email settings
│   │   │   ├── Login/Login.php            # Auth: login, register, forgot/reset password, email verify
│   │   │   ├── Pages/PagesController.php  # Public pages: about (GET), contact (GET+POST → ContactMail)
│   │   │   ├── Sync/Sync.php              # Sync/VPS proxy: risk, email jobs, crontab management
│   │   │   └── Api/CacheController.php   # REST API: cache info, clear-all/table/user/stock/keys
│   │   ├── Middleware/
│   │   │   ├── AdminMiddleware.php        # Auth::check() && role==1, redirect / nếu không
│   │   │   ├── UserMiddleware.php         # Auth::check() && role==0, redirect /admin nếu không
│   │   │   ├── VerifyCronSecret.php       # Header X-Cron-Secret hoặc Bearer == CRON_API_SECRET
│   │   │   ├── SecurityHeaders.php        # X-Frame-Options, CSP, HSTS, nosniff, referrer-policy
│   │   │   └── ThrottleAuthPosts.php      # Rate limit 5 req/60s cho các path auth
│   │   └── Requests/                      # Form Request validation (InsertStockBasicRequest, BuyStockRequest, ...)
│   ├── Logging/
│   │   ├── CustomDailyLogger.php          # Tạo daily log, auto-cleanup files cũ hơn 30 ngày
│   │   └── EmailErrorHandler.php          # Gửi LogErrorNotificationMail khi WARNING+ với throttle
│   ├── Mail/
│   │   ├── LogErrorNotificationMail.php   # Log error alert (level-color, icon, context)
│   │   ├── ContactMail.php                # Form liên hệ → lehuuphuoc0196@gmail.com (reply-to = sender)
│   │   ├── VerifyEmailMail.php            # Xác thực email (signed URL 60 phút)
│   │   ├── ResetPasswordMail.php          # Đặt lại mật khẩu (token + expiry)
│   │   └── NotifyUserMail.php             # Thông báo chung (risk, VN-Index, daily follow)
│   ├── Models/
│   │   ├── User.php                       # users — auth, role, active flag
│   │   ├── Stock.php                      # stocks — giá, risk, rating, volume
│   │   ├── UserPortfolio.php              # user_portfolios — FIFO lots
│   │   ├── UserPortfolioSell.php          # user_portfolios_sell — lịch sử bán
│   │   ├── UserFollow.php                 # user_follows — cảnh báo giá
│   │   ├── UserCashFollow.php             # cash_follow — số dư ví ảo
│   │   ├── UserCashIn.php                 # cash_in — lịch sử nạp
│   │   ├── UserCashOut.php                # cash_out — lịch sử rút
│   │   ├── StockStatusLog.php             # stock_status_logs — audit sync
│   │   ├── StatusSync.php                 # status_sync — trạng thái lần sync cuối
│   │   ├── AdminFollow.php                # admin_follow — mã admin đang theo dõi
│   │   └── AdminSuggest.php               # admin_suggest — mã admin gợi ý mua
│   ├── Services/
│   │   ├── AuthService.php                # Đăng ký, đăng nhập, reset password, update tên
│   │   ├── StockService.php               # CRUD stock, import/export CSV
│   │   ├── PortfolioService.php           # Mua/bán FIFO, P&L, ROI
│   │   ├── FollowService.php              # CRUD follow, batch add/delete
│   │   ├── CashService.php                # Nạp/rút tiền ảo
│   │   ├── EmailService.php               # Gửi mọi loại email thông báo
│   │   ├── SyncService.php                # Đồng bộ giá/risk từ VPS API
│   │   └── CacheService.php               # Cache với lock, clear theo pattern
│   └── Providers/
│       ├── AppServiceProvider.php         # (placeholder, hiện rỗng)
│       ├── AuthServiceProvider.php        # Override VerifyEmail email → tiếng Việt
│       ├── BroadcastServiceProvider.php   # (Laravel default, không dùng)
│       ├── EventServiceProvider.php       # Registered → SendEmailVerificationNotification
│       └── RouteServiceProvider.php       # HOME='/trang-chu', rate limit 60req/min
├── config/
│   ├── logging.php                        # stack→daily_custom, CustomDailyLogger, throttle email
│   ├── services.php                       # sync.base_url, sync.api_key, cron.secret
│   ├── sanctum.php                        # Token-based API auth (/api/user)
│   └── cache.php                          # driver=file, path=storage/framework/cache/data/
├── database/
│   ├── migrations/                        # Migration files (không có migration cho admin_follow, admin_suggest, cash_follow, status_sync)
│   ├── factories/
│   └── seeders/
├── docs/
│   └── blade-seo.md                       # Blade template & SEO guidelines
├── resources/
│   ├── css/                               # 30 CSS files tổng cộng
│   │   ├── app.css                        # Global styles (table reset, button, layout base)
│   │   ├── theme-invest-app.css           # Theme chính: màu, nav PC, grid, dark finance UI
│   │   ├── theme-drawer-shared.css        # Mobile drawer: Layout + LayoutAdmin dùng chung
│   │   ├── adminInsert.css                # Form thêm stock cơ bản
│   │   ├── adminView.css                  # Admin dashboard
│   │   ├── adminStockManagement.css       # Quản lý stock (table, sticky header, filter)
│   │   ├── adminStockInsert.css           # Form thêm stock đầy đủ
│   │   ├── adminUserManagement.css        # Quản lý users
│   │   ├── login.css                      # Login form
│   │   ├── loginRegister.css              # Register form
│   │   ├── userFollow.css                 # Danh sách follow (table)
│   │   ├── footer.css                     # Footer chung
│   │   └── pages/                         # CSS riêng từng trang (18 files)
│   │       ├── user-home.css              # Trang chủ user: bảng cổ phiếu, filter, modals (suggest + buy suggest)
│   │       ├── user-profile.css           # Profile: danh mục tổng hợp
│   │       ├── user-follow-form.css       # Form thêm/sửa follow
│   │       ├── user-email-settings.css    # Cài đặt email thông báo
│   │       ├── investment-performance.css # Trang hiệu suất đầu tư
│   │       ├── user-buy.css               # Form mua cổ phiếu
│   │       ├── user-sell.css              # Form bán cổ phiếu
│   │       ├── user-follow.css            # Trang follow list
│   │       ├── user-update-info-profile.css # Cập nhật thông tin cá nhân
│   │       ├── user-change-password.css   # Đổi mật khẩu
│   │       ├── public-profile.css         # Profile công khai
│   │       ├── admin-stock-update.css     # Form sửa stock
│   │       ├── admin-user-update.css      # Form sửa user
│   │       ├── admin-logs.css             # Log viewer
│   │       ├── admin-logs-vps.css         # VPS logs
│   │       ├── admin-crontab.css          # Crontab manager
│   │       ├── about.css                  # Trang giới thiệu (hero, mockups, animations)
│   │       └── contact.css               # Trang liên hệ (split layout, form, FAQ)
│   ├── js/                                # 36 JS files tổng cộng
│   │   ├── app.js                         # Base bundle: Axios, CSRF token, global helpers
│   │   ├── Admin.js                       # Admin bundle: stock CRUD, import/export
│   │   ├── AdminStockManagement.js        # Quản lý stocks: filter, sort, batch
│   │   ├── AdminUserManagement.js         # Quản lý users: filter, role toggle
│   │   ├── AdminStockInsert.js            # Form thêm stock đầy đủ
│   │   ├── AdminStockUpdate.js            # Form sửa stock
│   │   ├── User.js                        # User bundle: common helpers
│   │   └── pages/                         # JS riêng từng trang (29 files)
│   │       ├── user-home.js               # Trang chủ: bảng giá, filter, suggest modals, sticky clone
│   │       ├── user-profile.js            # Profile: danh mục FIFO, P&L
│   │       ├── user-follow-form.js        # Form thêm/sửa follow với autocomplete
│   │       ├── user-email-settings.js     # Toggle thông báo email từng mã
│   │       ├── investment-performance.js  # Biểu đồ hiệu suất, sort, filter
│   │       ├── user-buy.js                # Form mua: validate, submit AJAX
│   │       ├── user-sell.js               # Form bán FIFO: validate, preview
│   │       ├── user-follow.js             # Danh sách follow: batch delete, toggle notice
│   │       ├── user-insert-follow.js      # Thêm follow đơn
│   │       ├── user-cash-in.js            # Form nạp tiền
│   │       ├── user-cash-out.js           # Form rút tiền
│   │       ├── user-update-info-profile.js# Cập nhật tên
│   │       ├── user-change-password.js    # Đổi mật khẩu với show/hide
│   │       ├── login.js                   # Form login: show/hide password
│   │       ├── register.js                # Form register: validation
│   │       ├── forgot-password.js         # Quên mật khẩu
│   │       ├── reset-password.js          # Đặt lại mật khẩu
│   │       ├── admin-home.js              # Admin dashboard: sync status, quick actions
│   │       ├── admin-insert.js            # Form thêm stock cơ bản
│   │       ├── admin-stock-management.js  # Quản lý stocks: filter, sort, CSV
│   │       ├── admin-stock-follow.js      # Admin follow: batch add/delete
│   │       ├── admin-stock-suggest.js     # Admin suggest: batch add/delete
│   │       ├── admin-stock-update.js      # Form sửa stock inline
│   │       ├── admin-user-management.js   # Quản lý users: search, sort
│   │       ├── admin-upload-file.js       # Upload file VPS
│   │       ├── admin-update-risk.js       # Sync risk cho 1 mã
│   │       ├── admin-logs-vps.js          # Xem logs từ VPS
│   │       ├── admin-crontab.js           # Crontab CRUD UI
│   │       └── contact.js                 # Form liên hệ AJAX + FAQ accordion
│   └── views/                             # 50+ Blade templates
│       ├── Layout/
│       │   ├── Layout.blade.php           # Layout user/public: mobile drawer, SEO meta, topbar
│       │   ├── LayoutAdmin.blade.php      # Layout admin: sidebar, mobile drawer
│       │   └── LayoutLogin.blade.php      # Layout auth: minimal, centered card
│       ├── Admin/                         # 16 admin views
│       │   ├── AdminView.blade.php        # Dashboard: stocks table, sync status
│       │   ├── AdminLogs.blade.php        # Log viewer (opcodesio/log-viewer)
│       │   ├── AdminLogsVPS.blade.php     # Logs VPS realtime
│       │   ├── AdminCrontab.blade.php     # Crontab manager UI
│       │   ├── AdminStockManagement.blade.php # CRUD stocks với import/export CSV
│       │   ├── AdminStockInsert.blade.php # Form thêm stock đầy đủ
│       │   ├── AdminStockUpdate.blade.php # Form sửa stock
│       │   ├── AdminStockFollow.blade.php # Danh sách admin follow
│       │   ├── AdminStockSuggest.blade.php# Danh sách admin suggest
│       │   ├── AdminUserManagement.blade.php # Quản lý users
│       │   ├── AdminUserUpdate.blade.php  # Form sửa user (role, active, email)
│       │   ├── AdminUploadFile.blade.php  # Upload file lên VPS
│       │   ├── AdminUpdateRiskForCode.blade.php # Sync risk 1 mã
│       │   ├── AdminInfoProfile.blade.php # Thông tin admin
│       │   ├── AdminUpdateInfoProfile.blade.php # Cập nhật tên admin
│       │   └── AdminChangePassword.blade.php # Đổi mật khẩu admin
│       ├── User/                          # 14 user views
│       │   ├── UserView.blade.php         # Trang chủ: bảng giá, suggest modals, notif modal
│       │   ├── UserProfile.blade.php      # Portfolio tổng hợp FIFO
│       │   ├── UserInvestmentPerformance.blade.php # Hiệu suất P&L, ROI
│       │   ├── UserFollow.blade.php       # Danh sách follow
│       │   ├── UserBuy.blade.php          # Form mua cổ phiếu
│       │   ├── UserSell.blade.php         # Form bán cổ phiếu (FIFO preview)
│       │   ├── UserInsertFollow.blade.php # Form thêm follow
│       │   ├── UserUpdateFollow.blade.php # Form sửa follow (giá mục tiêu)
│       │   ├── UserCashIn.blade.php       # Form nạp tiền ảo
│       │   ├── UserCashOut.blade.php      # Form rút tiền ảo
│       │   ├── UserInfoProfile.blade.php  # Thông tin cá nhân + danh mục chi tiết
│       │   ├── UserUpdateInfoProfile.blade.php # Cập nhật tên
│       │   ├── UserChangePassword.blade.php # Đổi mật khẩu
│       │   └── UserEmailSettings.blade.php # Cài đặt email: session-closed, follow alerts
│       ├── Login/                         # 4 auth views
│       │   ├── Login.blade.php
│       │   ├── Register.blade.php
│       │   ├── ForgotPassword.blade.php
│       │   └── ResetPassword.blade.php
│       ├── Pages/                         # 2 public views
│       │   ├── AboutView.blade.php        # Giới thiệu: hero, features, how-it-works, CTA
│       │   └── ContactView.blade.php      # Liên hệ: split layout, form, FAQ accordion
│       ├── PDF/                           # 1 PDF template
│       │   └── PortfolioPdf.blade.php     # PDF xuất danh mục: header, KPI cards, bảng holdings (rating badge màu), P&L bar, footer. Không có logo (dompdf 2.0 không hỗ trợ SVG, XAMPP không có Imagick/GD). Không có FIFO timeline (đã bỏ).
│       ├── Emails/                        # 5 email templates
│       │   ├── verify.blade.php           # Xác thực email
│       │   ├── reset-password.blade.php   # Đặt lại mật khẩu
│       │   ├── Notify.blade.php           # Thông báo chung (risk, VN-Index, follow, liên hệ)
│       │   ├── layout.blade.php           # Email layout wrapper
│       │   └── log-error-simple.blade.php # Error log alert (level color, context)
│       └── partials/                      # Reusable components
│           ├── user-nav-primary.blade.php # Nav user đã đăng nhập
│           ├── admin-nav-primary.blade.php# Nav admin
│           ├── guest-nav-actions.blade.php# Nav guest: Trang chủ, Đăng nhập, Đăng ký + Giới thiệu, Liên hệ
│           ├── footer-invest.blade.php    # Footer chung (3 area: guest/user/admin)
│           ├── notify-modal.blade.php     # Modal thông báo dùng chung
│           ├── page-title-invest.blade.php# Component tiêu đề trang
│           ├── seo-public.blade.php       # SEO metadata cho trang public
│           └── favicon.blade.php          # Favicon link tags
├── routes/
│   ├── web.php                            # Web routes
│   └── api.php                            # API routes (cron.secret protected)
├── storage/
│   ├── framework/cache/data/              # File cache storage
│   └── logs/                             # laravel_YYYYMMDD.log (daily rotating, 30 ngày)
├── .env                                   # Environment config (không commit)
├── .env.example                           # Template
├── vite.config.js                         # 30 CSS + 36 JS entry points
├── composer.json                          # PHP dependencies
└── package.json                           # Node dependencies
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
| `code` | varchar | Mã cổ phiếu (VD: VNM, FPT) |
| `current_price` | decimal | Giá hiện tại × 1000 (VCI trả về giá nghìn, sync × 1000) |
| `price_avg` | decimal | Giá trung bình 1008 phiên giao dịch |
| `percent_buy` | decimal (default 100) | % của price_avg để tính recommended_buy_price |
| `percent_sell` | decimal (default 100) | % của price_avg để tính recommended_sell_price |
| `recommended_buy_price` | decimal | `price_avg × percent_buy / 100` |
| `recommended_sell_price` | decimal | `price_avg × percent_sell / 100` |
| `percent_stock` | decimal | % thay đổi giá so với phiên trước |
| `risk_level` | int | 1=An toàn, 2=Cảnh báo, 3=Hạn chế, 4=Đình chỉ |
| `event_date` | date | Ngày sự kiện risk hiện tại (từ VietStock, default curdate()) |
| `recommended_date` | date | Ngày tính lại recommended prices |
| `stocks_vn` | int (default 1000) | 30=VN30, 100=VN100, 1000=không thuộc VN30/VN100 |
| `rating_stocks` | int (default 0) | Điểm đánh giá 1–10 (syncRatingPrice tính theo risk × volume × price) |
| `volume` | decimal (default 0) | Khối lượng phiên giao dịch gần nhất |
| `volume_avg` | decimal (default 0) | Khối lượng giao dịch trung bình 1008 phiên |
| `created_at`, `updated_at` | datetime | Timestamps |

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

### Bảng `user_portfolios_sell` (lịch sử bán)
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
| `id` | bigint (PK) | Auto increment — chỉ có 1 row |
| `status_sync_price` | int | Legacy, không được dùng — VPS không update field này. Luôn = 0 |
| `status_sync_risk` | int | 0=idle, 1=running — VPS set=1 khi syncRisk bắt đầu, set=0 khi xong |
| `created_at`, `updated_at` | datetime | Timestamps |

### Bảng `admin_follow` (admin theo dõi mã CK)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint | ID admin |
| `stock_id` | bigint (FK → stocks) | Mã đang theo dõi |
| `created_at`, `updated_at` | timestamp | Timestamps |

> Không có migration — bảng được tạo thủ công. Model tự động xóa cache khi create/delete.

### Bảng `admin_suggest` (gợi ý mua từ admin)
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint (PK) | Auto increment |
| `user_id` | bigint | ID admin |
| `stock_id` | bigint (FK → stocks) | Mã đang gợi ý |
| `created_at`, `updated_at` | timestamp | Timestamps |

> Không có migration — bảng được tạo thủ công. Model tự động xóa cache khi create/delete.

### Bảng `stock_status_logs`
Audit log cho các thao tác sync stock. Hiện chỉ có `id` + timestamps (mở rộng sau).

### Bảng hệ thống Laravel
- **`personal_access_tokens`** – Sanctum API tokens
- **`password_resets`** – Token đặt lại mật khẩu
- **`failed_jobs`** – Queue jobs thất bại

### Quan hệ giữa các bảng
```
users (1) ─────── (many) user_portfolios
users (1) ─────── (many) user_portfolios_sell
users (1) ─────── (many) user_follows
users (1) ─────── (1)    cash_follow
users (1) ─────── (many) cash_in
users (1) ─────── (many) cash_out

stocks (1) ──────── (many) user_portfolios
stocks (1) ──────── (many) user_portfolios_sell
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
| admin_follow | `admin_follow_stock_ids`, `admin_follow_stocks` | Insert/Delete admin follow |
| admin_suggest | `admin_suggest_stock_ids`, `admin_suggest_stocks` | Insert/Delete admin suggest |

### Model Key Methods

**`Stock`**
- `getAllStocks()` — cached `stocks_all`; trả array đã sort
- `getByCode($code)` — cached `stock_code_{CODE}`; `strtoupper` tự động
- `getRiskLevelFromCode($code)` — cached `stock_risk_{CODE}`
- `deleteByCode($code)` — xóa + clear cache
- `getDeleteDependencyCounts($code)` — đếm FK liên quan trước khi xóa
- Constants: `BUY_PRICE_MAX`, `QUANTITY_MAX` dùng trong validation

**`UserPortfolio`** (table: `user_portfolios`)
- `getProfileUser($userId)` — FIFO tổng hợp theo mã (giá mua TB, tổng qty)
- `getPortfolioWithStockInfo($userId)` — join với stocks để lấy giá hiện tại
- `getPortfolioWithUserBuy($userId)` — chi tiết từng lô FIFO
- `getSessionClosedByUser($userId)` — cached `user_portfolio_session_{USER_ID}`
- `getStockHolding($userId, $stockId)` — tổng qty còn lại
- `getEarliestRemainingBuyDateYmdForCode($userId, $code)` — ngày mua cũ nhất còn open

**`UserPortfolioSell`** (table: `user_portfolios_sell`)
- `getPortfolioSellWithStockInfo($userId)` — lịch sử bán join stocks

**`UserFollow`** (table: `user_follows`)
- `getUserFollow($userId)` — cached `user_follow_{USER_ID}`
- `getFollowNoticeByUser($userId)` — cached `user_follow_notice_{USER_ID}` (dùng cho email settings)
- `deleteByCodeAndUser()`, `deleteByCodesAndUser()`, `deleteAllByUserId()` — xóa + clear cache
- `updateNoticeFlag()`, `updateNoticeBuySell()` — cập nhật alert flags

**`AdminFollow`** (table: `admin_follow`)
- `getFollowedStockIds()` — cached `admin_follow_stock_ids`
- Boot: tự động `CacheService::forget('admin_follow_*')` khi create/delete

**`AdminSuggest`** (table: `admin_suggest`)
- `getSuggestedStockIds()` — cached `admin_suggest_stock_ids`
- Boot: tự động clear cache khi create/delete

**`StatusSync`** (table: `status_sync`)
- `getStatusSync()` — cached `status_sync` 1 ngày

**`User`**
- `getUserById($id)` — cached `user_{USER_ID}`
- `getUserByEmail($email)` — dùng cho auth, không cache
- Implements `MustVerifyEmail` — gửi `VerifyEmailMail` khi đăng ký

---

## 7. Routes & API Reference

### Public / SEO
| Method | URL | Mô tả |
|--------|-----|-------|
| GET | `/robots.txt` | SEO robots file |
| GET | `/sitemap.xml` | XML sitemap (cached 1 ngày) |
| GET | `/logo.svg` | Logo SVG (served qua Laravel, không phải file tĩnh) |
| GET | `/trang-chu` | Trang chủ (không cần đăng nhập) – `name: home` |
| GET | `/gioi-thieu` | Trang giới thiệu nền tảng – `name: about` |
| GET/POST | `/lien-he` | Trang liên hệ + form gửi email – `name: contact` |
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
| GET | `/admin/crontab` | `Sync@getCrontab` | Trang quản lý crontab VPS |
| GET | `/admin/crontab/list` | `Sync@getCrontabList` | Lấy danh sách cron jobs từ VPS |
| POST | `/admin/crontab/add` | `Sync@addCrontab` | Thêm cron job mới |
| PUT | `/admin/crontab/update/{lineIdx}` | `Sync@updateCrontab` | Sửa schedule/endpoint |
| DELETE | `/admin/crontab/delete/{lineIdx}` | `Sync@deleteCrontab` | Xóa cron job |
| POST | `/admin/crontab/toggle/{lineIdx}` | `Sync@toggleCrontab` | Bật/tắt cron job |
| POST | `/admin/crontab/run/{lineIdx}` | `Sync@runCrontab` | Chạy ngay cron job |

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
| GET | `/user/portfolio/export-pdf` | `User@exportPortfolioPdf` | Xuất danh mục PDF – `name: user.portfolio.exportPdf` |
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
| POST | `/api/cache/clear-table` | `{"table": "stocks"}` | Xóa cache theo bảng (hỗ trợ: stocks, user_portfolios, user_portfolios_sell, user_follows, cash_follow, users, status_sync, admin_follow, admin_suggest) |
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
- **Crontab Management:** Xem, thêm, sửa, xóa, bật/tắt, chạy ngay các cron job trên VPS — proxy qua `Sync.php` tới VPS API (xem mục 8.9).

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

**Xuất PDF (`exportPortfolioPdf`):**
- Route: `GET /user/portfolio/export-pdf` — dùng `barryvdh/laravel-dompdf`, trả về `streamDownload`
- Dữ liệu: `getProfileUser($userId)` (FIFO tổng hợp theo mã) + enrich với `rating_stocks`, `stocks_vn`, `current_price` từ `stocks` table
- Cấu trúc PDF hiện tại: header branding (text only, không logo) → user info strip → 4 KPI cards (tổng mã, tổng vốn, giá trị thị trường, P&L) → bảng danh mục holdings (cột Điểm dùng badge màu: ≥7 xanh, 5–6 vàng, <5 đỏ) → P&L bar chart (CSS `width:%`) → footer
- **Giới hạn dompdf 2.0 trên XAMPP này:** Không render SVG qua `<img>` data-URI. Inline SVG `<text>` bị leak ra ngoài SVG bounds. XAMPP không có Imagick/GD nên không convert SVG→PNG runtime. Logo đã bỏ hoàn toàn.
- FIFO Timeline đã bỏ (quá phức tạp để đọc).

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
CacheService::remember('cache_key', CacheService::TTL_ONE_DAY, fn() => Model::query());
CacheService::forget('cache_key');
CacheService::forgetMany(['key1', 'key2']);
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
| ERROR | 1 phút (60s) | Runtime errors |
| CRITICAL | Không throttle | Lỗi nghiêm trọng |
| ALERT | Không throttle | Cần hành động ngay |
| EMERGENCY | Không throttle | Hệ thống không khả dụng |

**Recipient:** `MAIL_NOTIFICATION_TO` trong `.env` (config trong `config/logging.php`).

```php
Log::info('Thông tin thường');
Log::warning('Cảnh báo', ['context' => 'data']);
Log::error('Lỗi', ['exception' => $e->getMessage()]);
Log::critical('Lỗi nghiêm trọng');  // Gửi email ngay, không throttle
```

---

### 8.9 Crontab Management
**Files:** [app/Http/Controllers/Sync/Sync.php](app/Http/Controllers/Sync/Sync.php), [resources/views/Admin/AdminCrontab.blade.php](resources/views/Admin/AdminCrontab.blade.php), [resources/js/pages/admin-crontab.js](resources/js/pages/admin-crontab.js)

**Mục đích:** Admin quản lý cron jobs trên VPS (`SYNC_SERVICE_URL`) trực tiếp từ trình duyệt, không cần SSH.

**Kiến trúc proxy:**
```
Admin browser → Laravel /admin/crontab/* → Sync.php (proxy) → VPS FastAPI /crontab/* (X-API-Key)
```

**VPS API (base: `SYNC_SERVICE_URL`):**

| Endpoint VPS | Auth | Mô tả |
|---|---|---|
| `GET /crontab/list` | Không cần | Lấy tất cả entries |
| `POST /crontab/add` | X-API-Key | Thêm entry mới |
| `PUT /crontab/update/{line_idx}` | X-API-Key | Sửa schedule/endpoint |
| `DELETE /crontab/delete/{line_idx}` | X-API-Key | Xóa entry |
| `POST /crontab/toggle/{line_idx}` | X-API-Key | Bật/tắt |
| `POST /crontab/run/{line_idx}` | X-API-Key | Chạy ngay |

**Quy tắc quan trọng:**
- VPS có 2 loại entries: `is_stocks_api=true` (API — có thể sửa/xóa/chạy) và `is_stocks_api=false` (System — chỉ bật/tắt)
- Sau mỗi delete, `line_idx` của các entry thay đổi → frontend luôn re-fetch list sau mỗi mutation
- `SYNC_API_KEY` trong `.env` là API key gửi header `X-API-Key` khi gọi VPS (add/update/delete/toggle/run)

**Config:**
```php
// config/services.php
'sync' => [
    'base_url' => env('SYNC_SERVICE_URL'),
    'api_key'  => env('SYNC_API_KEY', ''),   // dùng cho crontab + run-sync endpoints
]
```

---

### 8.10 User Home – Modals (Gợi ý theo dõi & Gợi ý mua hôm nay)
**Files:** [resources/views/User/UserView.blade.php](resources/views/User/UserView.blade.php), [resources/js/pages/user-home.js](resources/js/pages/user-home.js), [resources/css/pages/user-home.css](resources/css/pages/user-home.css)

#### Modal "Danh sách gợi ý theo dõi" (`#home-suggest-modal`)
- Trigger: nút **💡 Gợi ý theo dõi** trên trang chủ (chỉ hiện khi đăng nhập)
- Hiển thị danh sách mã `admin_suggest` (từ `window.__pageData.adminSuggestedStocks`)
- Cho phép chọn hàng loạt → thêm vào follow
- Sticky clone header: dùng `setupSuggestStickyHeader()` — clone theo `position: sticky; left: 0` truyền thống (không cần translateX)

#### Modal "Gợi ý mua hôm nay" (`#home-buy-suggest-modal`)
- Trigger 1 (manual): nút **💰 Gợi ý mua hôm nay** trên trang chủ
- Trigger 2 (auto): tự mở khi đăng nhập nếu có mã `admin_suggest` có `% Định giá < 0` — chỉ 1 lần/ngày (gate: `sessionStorage` key `buy_suggest_shown_YYYY-MM-DD`)
- Chỉ đọc (không có checkbox chọn), sort theo `% Định giá` tăng dần
- Design amber (vàng) để phân biệt với modal xanh của gợi ý theo dõi
- Sticky clone header: dùng `setupBuySuggestStickyHeader()` — dùng `translateX(scrollLeft)` thay vì `position: sticky` (wrapper là `overflow: hidden` nên sticky không tạo scroll context)

**Công thức `% Định giá`:**
```js
((currentPrice / buyPrice) * 100 - 100)   // âm = giá hiện tại thấp hơn giá mua tốt → tín hiệu mua
```

**Lưu ý sticky clone z-index:**
- `adminStockManagement.css` khai báo `.sticky-header th { z-index: 1001 }` — rule này apply cho mọi clone có class `sticky-header`
- `th.col-code-sticky` trong clone phải có `z-index: 1002` (cao hơn 1001) mới đè lên các cột bên cạnh khi `translateX` dịch chuyển

**`isLoggedIn` guard:**
- Cả 2 trigger (manual button + auto-open) đều kiểm tra `window.__pageData.isLoggedIn` trước khi mở modal
- Guest click → `showNotifyModal('Vui lòng đăng nhập để sử dụng tính năng này.', 'error')`, không mở modal

**Scroll reset khi đóng/mở:**
- `closeBuySuggestModal()` reset `container.scrollLeft = 0` và `scrollHost.scrollTop = 0` **TRƯỚC** khi set `aria-hidden="true"` — phải reset khi element còn `display:flex` thì browser mới xử lý được
- `openBuySuggestModal()` cũng reset trong `requestAnimationFrame` trước khi `setupBuySuggestStickyHeader()` — safety net

**Gotcha `margin-top` bảng:**
- Global `app.css` có `table { margin-top: 20px }` — override bằng `margin-top: 0` trực tiếp trên `#home-buy-suggest-table` trong `user-home.css`

---

### 8.11 Public Pages (Giới thiệu & Liên hệ)
**Files:** [app/Http/Controllers/Pages/PagesController.php](app/Http/Controllers/Pages/PagesController.php), [app/Mail/ContactMail.php](app/Mail/ContactMail.php), [resources/views/Pages/AboutView.blade.php](resources/views/Pages/AboutView.blade.php), [resources/views/Pages/ContactView.blade.php](resources/views/Pages/ContactView.blade.php), [resources/css/pages/about.css](resources/css/pages/about.css), [resources/css/pages/contact.css](resources/css/pages/contact.css), [resources/js/pages/contact.js](resources/js/pages/contact.js)

**Trang Giới thiệu `/gioi-thieu` (route `about`):**
- 4 section: Hero (animated orbs + gradient chips) → Feature Cards (4 tính năng, grid 2×2 PC / 1 col mobile) → Cách hoạt động (3 bước timeline) → CTA cuối trang
- Feature cards dùng CSS-only UI mockups minh hoạ: bảng FIFO, email inbox, bar chart P&L, danh sách gợi ý
- Assets: `about.css` (CSS animations, mockups) — không có JS riêng

**Trang Liên hệ `/lien-he` (route `contact`):**
- Layout PC: 2 cột 40/60 (thông tin + FAQ bên trái, form bên phải); Mobile: 1 cột
- Form AJAX: POST → validate server → `ContactMail` → JSON response → inline success/error (không redirect)
- `ContactMail` dùng lại view `Emails/Notify.blade.php`; Reply-To = email người gửi → admin reply thẳng
- `contact.js`: validate client-side (required, email format), Axios POST với CSRF, FAQ accordion (click → toggle height), disabled button khi đang gửi

**Nav guest (`guest-nav-actions.blade.php`):**
- 3 link chính (Trang chủ, Đăng nhập, Đăng ký) nằm trong `user-nav-primary__mid`
- Giới thiệu + Liên hệ nằm trong `user-nav-guest-info` **ngoài** `__mid`:
  - PC: `grid-column: 2; grid-row: 1` → hiển thị góc phải cùng hàng với 3 link chính
  - Mobile: `flex-shrink: 0` → ghim đáy drawer (cùng cơ chế với nút Đăng xuất của user)

**CSS `:not()` chain — quan trọng khi thêm link guest mới:**
- `theme-invest-app.css` và `theme-drawer-shared.css` đều có fallback rule muted cho `.user-nav-link--guest:not(...)`. Khi thêm modifier class mới, phải thêm vào chain `:not()` trong **cả 2 file** và thêm vào rule surface tile.
- Hiện tại chain gồm: `:not(.user-nav-link--guest-login):not(.user-nav-link--guest-register):not(.user-nav-link--guest-home):not(.user-nav-link--guest-about):not(.user-nav-link--guest-contact):not(.user-nav-link--active)`

---

### 8.12 Middleware
**Đăng ký trong:** `app/Http/Kernel.php`

| Class | Alias | Mô tả |
|-------|-------|-------|
| `AdminMiddleware` | `admin` | `Auth::check() && role==1`; sai → redirect `/` |
| `UserMiddleware` | `user` | `Auth::check() && role==0`; sai → redirect `/admin` |
| `VerifyCronSecret` | `cron.secret` | Header `X-Cron-Secret` hoặc `Authorization: Bearer` phải khớp `CRON_API_SECRET`; sai → 403 JSON |
| `SecurityHeaders` | (global) | Thêm headers bảo mật vào mọi response |
| `ThrottleAuthPosts` | (global auth) | Rate limit 5 requests/60s per IP cho các path auth |

**SecurityHeaders** thêm vào response:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: (whitelist jQuery CDN, Sweetalert2 CDN)
Strict-Transport-Security: max-age=31536000 (chỉ production)
```

**ThrottleAuthPosts** áp dụng cho paths: `dang-nhap`, `dang-ky`, `quen-mat-khau`, `dat-lai-mat-khau`.

**RouteServiceProvider** (không phải middleware nhưng liên quan):
- `HOME = '/trang-chu'` — redirect mặc định sau login
- Rate limiter API: 60 requests/phút per user/IP

---

### 8.13 Mail Classes
**Thư mục:** `app/Mail/` | **View templates:** `resources/views/Emails/`

| Class | Subject | View | Gửi tới | Ghi chú |
|-------|---------|------|---------|---------|
| `VerifyEmailMail` | `[Hệ thống đầu tư cá nhân] Xác nhận địa chỉ email` | `Emails.verify` | User mới đăng ký | Signed URL 60 phút |
| `ResetPasswordMail` | `[Hệ thống đầu tư cá nhân] Đặt lại mật khẩu` | `Emails.reset-password` | User quên mật khẩu | `$resetUrl`, `$expiryMinutes` |
| `LogErrorNotificationMail` | `[{APP_URL}] Log {LEVEL}` | `emails.log-error-simple` | `MAIL_NOTIFICATION_TO` | `$levelColor`, `$levelIcon`, `$logContext` |
| `NotifyUserMail` | (custom subject) | `Emails.Notify` | `MAIL_NOTIFICATION_TO` | Dùng cho risk, VN-Index, daily follow |
| `ContactMail` | `[Liên hệ] {subject} — {name}` | `Emails.Notify` | `lehuuphuoc0196@gmail.com` | Reply-To = email người gửi |

**`LogErrorNotificationMail` color mapping:**
- WARNING → `#ff9800` (cam)
- ERROR → `#f44336` (đỏ)
- CRITICAL/ALERT/EMERGENCY → `#d32f2f` (đỏ đậm)

---

### 8.14 Form Request Validation
**Thư mục:** `app/Http/Requests/` — 19 classes, tất cả extend `FormRequest`

`ApiFormRequest` — base class cho các API request (chỉ authorize, không có rules riêng).

| Class | Fields & Rules |
|-------|----------------|
| `LoginRequest` | `email`: required\|email\|max:255 · `password`: required\|string\|min:6 |
| `RegisterRequest` | `name`: required\|string\|max:100 · `email`: required\|email\|max:255 · `password`: required\|min:6\|confirmed |
| `ForgotPasswordRequest` | `email`: required\|email\|max:255 |
| `ResetPasswordRequest` | `token`: required\|string · `email`: required\|email · `password`: required\|min:6\|confirmed |
| `UpdateInfoProfileRequest` | `name`: required\|string\|max:100 |
| `ChangePasswordRequest` | `password`: required\|string\|min:6 · `newPassword`: required\|string\|min:6 |
| `BuyStockRequest` | `code`: required\|string\|max:10 · `buy_price`: required\|numeric\|gt:0\|max:BUY_PRICE_MAX · `quantity`: required\|integer\|min:1\|max:QUANTITY_MAX · `buy_date`: required\|date_format:Y-m-d\|before_or_equal:today |
| `SellStockRequest` | `code`: required\|string\|max:10 · `sell_price`: required\|numeric\|gt:0 · `quantity`: required\|numeric\|gt:0 · `sell_date`: required\|date_format:Y-m-d\|before_or_equal:today |
| `CashInRequest` | `cashIn`: required\|numeric\|gt:0 · `cashDate`: required\|date_format:Y-m-d\|before_or_equal:today |
| `CashOutRequest` | `cashOut`: required\|numeric\|gt:0 · `cashDate`: required\|date_format:Y-m-d\|before_or_equal:today |
| `InsertFollowRequest` | `code`: required\|string\|max:10 (+ optional follow prices) |
| `UpdateFollowRequest` | `code`: required\|string\|max:10 · `followPriceBuy`: required\|numeric\|gt:0 · `autoSync`: required\|in:0,1 |
| `AddFollowBatchRequest` | `codes`: required\|array · `codes.*`: required\|string\|max:10 |
| `InsertStockBasicRequest` | `code`: required\|string\|max:10 · `buyPrice`: required\|numeric\|gt:0 · `currentPrice`: required\|numeric\|gt:0 · `risk`: required\|integer\|min:1\|max:5 |
| `StockInsertRequest` | `code`: required\|string\|max:10 · `currentPrice`: required\|numeric\|gt:0 · `risk`: required\|integer\|min:1\|max:5 (+ nhiều fields tùy chọn) |
| `StockUpdateRequest` | `currentPrice`: required\|numeric\|gt:0 · `risk`: required\|integer\|min:1\|max:5 (+ optional fields) |
| `ImportStocksCsvRequest` | `csv_file`: required\|file\|max:2048 |
| `UpdateRiskForCodeRequest` | `code`: required\|string\|max:10 |

> `BUY_PRICE_MAX` và `QUANTITY_MAX` là constants trong `UserPortfolio` model. `risk` luôn validate `min:1|max:5`.

---

### 8.15 Service Providers

| Provider | Vai trò |
|----------|---------|
| `AppServiceProvider` | Placeholder, hiện rỗng |
| `AuthServiceProvider` | Override `VerifyEmail::toMailUsing()` → gửi email xác thực bằng tiếng Việt (subject: `"Xác thực địa chỉ email - {app.name}"`) |
| `EventServiceProvider` | Map `Registered` event → `SendEmailVerificationNotification` listener (kích hoạt khi đăng ký) |
| `BroadcastServiceProvider` | Laravel default, chưa dùng |
| `RouteServiceProvider` | `HOME = '/trang-chu'`; rate limiter API: 60 req/phút per user/IP |

---

### 8.16 VPS Python Scripts (server-side)
**Thư mục VPS:** `/root/stocks/` | **Server:** `root@180.93.42.13`

Các Python script chạy trên VPS để đồng bộ dữ liệu về webapp (giá, risk, dividend, admin_follow) và gửi email thông báo. Mỗi script được gọi bởi cron job quản lý qua [8.9 Crontab Management](#89-crontab-management).

#### Các script chính

| File | Mô tả |
|------|-------|
| `syncPrice.py` | Đồng bộ `current_price`, `percent_stock`, `volume` cho tất cả mã CK từ VCI API. Sau khi sync: gọi `cacheHelper.clear_stocks()` + `clear_user_follows()` + `flush()` |
| `syncRisk.py` | Đồng bộ `risk_level`, `event_date` từ VietStock. Sau khi sync: `cacheHelper.clear_stocks()` + `clear_status_sync()` + `flush()` |
| `syncRatingPrice.py` | Tính lại `rating_stocks`, `price_avg`, `recommended_buy_price`, `recommended_sell_price`. Sau khi sync: `cacheHelper.clear_stocks()` + `flush()` |
| `syncDividend.py` | Phát hiện sự kiện cổ tức hôm nay (GDKHQ = today), điều chỉnh `current_price` + `buy_price`/`quantity` trong portfolios. Sau khi sync: `clear_stocks()` + `clear_user_portfolios()` + `clear_user_portfolios_sell()` + `flush()` |
| `syncStocks10M.py` | Kiểm tra GTGD (giá trị giao dịch) 10 phút để tự động thêm mã vào `admin_follow` nếu đủ điều kiện (GTGD > threshold + risk An toàn). Sau khi insert: `cacheHelper.clear_admin_follow()` |
| `noticeUserFollow.py` | Gửi email tín hiệu mua/bán cho users có follow. Chạy sáng T2–T6 trước phiên giao dịch |
| `noticeStock.py` | Gửi email thông báo risk thay đổi |
| `AdminFollowDB.py` | Helper module: `checkFollowExists(user_id, stock_id)`, `insertFollow(user_id, stock_id)`, `getFollowsByUser(user_id)` — CRUD bảng `admin_follow` |
| `stocksDB.py` | Helper module: CRUD bảng `stocks` |
| `commonDB.py` | DB connection pool, helpers `fetch_one()`, `execute()` |
| `sendEmail.py` | Gửi email qua SMTP: `sendEmailWarning()`, `sendEmailError()`, `sendEmailInfo()` |
| `getRiskFromAPI.py` | Lấy risk level cho 1 mã từ VietStock API |
| `getDividendFromAPI.py` | Lấy dữ liệu cổ tức/thưởng từ API |
| `logg.py` | Logging wrapper: `logg.info()`, `logg.warning()`, `logg.error()` |
| `cacheHelper.py` | Gọi webapp `/api/cache/*` để invalidate cache sau khi VPS cập nhật DB |
| `api.py` | FastAPI server phục vụ các endpoint cron + crontab management |
| `crontabManager.py` | Quản lý danh sách cron jobs (đọc/ghi file crontab) |
| `deleteLogs.py` | Xóa log files cũ hơn 30 ngày |

#### cacheHelper.py — tất cả hàm

| Hàm | API webapp gọi | Mô tả |
|-----|---------------|-------|
| `clear_stocks()` | `POST /api/cache/clear-table` `{"table":"stocks"}` | Xóa cache `stocks_all`, `stock_code_*`, `stock_risk_*` |
| `clear_user_follows()` | `POST /api/cache/clear-table` `{"table":"user_follows"}` | Xóa cache `user_follow_*`, `user_follow_notice_*` |
| `clear_user_portfolios()` | `POST /api/cache/clear-table` `{"table":"user_portfolios"}` | Xóa cache `user_portfolio_*` |
| `clear_user_portfolios_sell()` | `POST /api/cache/clear-table` `{"table":"user_portfolios_sell"}` | Xóa cache `user_portfolio_sell_*` |
| `clear_status_sync()` | `POST /api/cache/clear-keys` `{"keys":["status_sync"]}` | Xóa cache key `status_sync` |
| `clear_stock(code)` | `POST /api/cache/clear-stock` `{"code":"..."}` | Xóa cache 1 mã cụ thể |
| `clear_admin_follow()` | `POST /api/cache/clear-table` `{"table":"admin_follow"}` | Xóa cache `admin_follow_stock_ids`, `admin_follow_stocks` |
| `flush(caller_file)` | (gọi sendEmail nội bộ) | Nếu có lỗi trong run, gửi email tổng hợp rồi clear `_errors` list |

Auth gọi webapp: header `X-Cron-Secret: {WEBAPP_CACHE_SECRET}` (env var trên VPS).

#### syncStocks10M.py — luồng xử lý admin_follow

```
1. Lấy danh sách tất cả mã CK từ DB
2. Với mỗi mã: fetch GTGD 10 phút từ VCI API
3. Nếu GTGD > threshold_add → gửi email cảnh báo (chưa insert)
4. Nếu GTGD > threshold_follow + risk == An toàn + chưa có trong admin_follow:
   → AdminFollowDB.insertFollow(user_id, stock_id)
   → cacheHelper.clear_admin_follow()   ← invalidate webapp cache ngay lập tức
   → sendEmail.sendEmailWarning(...)
5. Cuối run: _notify_stale_admin_follows() → kiểm tra các mã follow cũ có GTGD xuống thấp
6. sendEmail tổng hợp + _send_accumulated_errors()
```

**Config env trên VPS:** `WEBAPP_CACHE_URL` (URL webapp), `WEBAPP_CACHE_SECRET` (phải khớp `CRON_API_SECRET` trong `.env` Laravel).

---

## 9. Services

### AuthService
**File:** [app/Services/AuthService.php](app/Services/AuthService.php) | Cache: xóa `user_{id}` khi update tên

| Method | Mô tả |
|--------|-------|
| `login($data)` | `Auth::attempt()`, kiểm tra `active=1`, session start |
| `register($data)` | Tạo user (`active=0, role=0`), gửi `VerifyEmailMail` |
| `forgotPassword($data)` | Tạo token `password_resets`, gửi `ResetPasswordMail` |
| `validateResetToken($email, $token)` | Kiểm tra token còn hạn |
| `resetPassword($data)` | Hash password mới, xóa token |
| `updateUserName($userId, $name)` | Update name, clear `user_{id}` cache |
| `changePassword($userId, $old, $new)` | Verify old password, bcrypt new |

### StockService
**File:** [app/Services/StockService.php](app/Services/StockService.php) | Cache: clear `stocks_all`, `stock_code_{CODE}` sau mỗi write

| Method | Mô tả |
|--------|-------|
| `insertStockBasic($data)` | Insert tối giản (code, price, stocks_vn) |
| `insertStock($data)` | Insert đầy đủ tất cả fields |
| `updateStock($code, $data)` | Update + clear cache |
| `importStocksCsv($file)` | Batch upsert từ CSV (hỗ trợ format cũ và mới) |

### PortfolioService
**File:** [app/Services/PortfolioService.php](app/Services/PortfolioService.php) | Cache: clear `user_portfolio_*`, `user_cash_*`

| Method | Mô tả |
|--------|-------|
| `buyStock($data, $userId)` | Kiểm tra số dư, tạo FIFO lot, trừ cash |
| `sellStock($data, $userId)` | FIFO: trừ từ lot cũ nhất, tạo sell record, cộng cash |
| `calcUserInvestCash($userId)` | Tính tổng tiền đã đầu tư (sum buy_price × qty) |
| `saveSessionClosedFlags($userId, $items)` | Batch update `session_closed_flag` |

### FollowService
**File:** [app/Services/FollowService.php](app/Services/FollowService.php) | Cache: clear `user_follow_*`

| Method | Mô tả |
|--------|-------|
| `insertFollow($data, $userId)` | Thêm 1 follow, set default prices từ stock |
| `addFollowBatch($data, $userId)` | Batch add, trả về `{added, skipped, invalid}` |
| `updateFollow($data, $userId)` | Cập nhật follow prices + notice flags |

### CashService
**File:** [app/Services/CashService.php](app/Services/CashService.php) | Cache: clear `user_cash_{id}`

| Method | Mô tả |
|--------|-------|
| `cashIn($data, $userId)` | Cộng `cash_follow.cash`, tạo `cash_in` record |
| `cashOut($data, $userId)` | Kiểm tra số dư, trừ `cash_follow.cash`, tạo `cash_out` record |

### EmailService
**File:** [app/Services/EmailService.php](app/Services/EmailService.php) | Cache: không dùng | Gửi đến `config('mail.notification_to')`

| Method | Mô tả |
|--------|-------|
| `sendRiskChangeNotification($code, $old, $new, $date)` | Thông báo thay đổi risk level |
| `sendVnindexChangeNotification($current, $suggest)` | Báo cáo VN-Index |
| `sendErrorNotification($file, $function, $message)` | Thông báo lỗi hệ thống |
| `sendFollowStocksEveryDay($stock, $avgBuyPrice)` | Email daily follow cho user ID 1 |

### SyncService
**File:** [app/Services/SyncService.php](app/Services/SyncService.php) | Cache: clear `stocks_all`, `stock_*` sau sync

| Method | Mô tả |
|--------|-------|
| `syncRiskForCode($code)` | Gọi VPS API lấy risk, update DB, gửi email nếu thay đổi |
| `followStocksEveryDay()` | Email daily portfolio summary cho user ID 1 |
| `collectRisk($code)` | HTTP GET tới `SYNC_SERVICE_URL` lấy risk data |
| `addStocksFollowFromFile($content)` | Parse file text lấy danh sách mã CK |

### CacheService
**File:** [app/Services/CacheService.php](app/Services/CacheService.php) — **Cache layer dùng chung toàn bộ hệ thống**

| Method | Mô tả |
|--------|-------|
| `remember($key, $ttl, $callback)` | Cache với distributed lock (10s timeout) chống stampede |
| `forget($key)` | Xóa 1 key |
| `forgetMany($keys)` | Xóa nhiều keys |
| `clearUserCache($userId)` | Xóa tất cả cache liên quan đến user (portfolio, follow, cash) |
| `clearStockCache($code)` | Xóa `stock_code_*`, `stock_risk_*` |
| `clearTableCache($table)` | Xóa cache theo tên bảng (smart mapping) |
| `clearAll()` | Flush toàn bộ cache |
| `getCacheInfo()` | Stats: driver, số file, dung lượng, sample keys |

- **TTL mặc định:** `CacheService::TTL_ONE_DAY = 86400` giây
- **Driver:** File, lưu tại `storage/framework/cache/data/`

---

## 10. Frontend Structure

### Blade Layouts (`resources/views/Layout/`)
| Layout | File | Dùng cho |
|--------|------|---------|
| User/Public | `Layout.blade.php` | Trang chủ, tất cả user pages, trang giới thiệu/liên hệ |
| Auth | `LayoutLogin.blade.php` | Login, register, forgot/reset password |
| Admin | `LayoutAdmin.blade.php` | Toàn bộ admin panel |

`Layout.blade.php` chứa: mobile topbar, mobile drawer, overlay, logo route (`/logo.svg`), SEO slot, Vite asset slot, body class `theme-invest-app`.

### Theme & Design System
| Variable | Giá trị | Dùng cho |
|----------|---------|---------|
| `--inv-bg` | `#0f172a` | Background chính |
| `--inv-accent` | `#38bdf8` | Sky blue — accent chính |
| `--inv-accent2` | `#818cf8` | Indigo — accent phụ |
| `--inv-text` | `#e2e8f0` | Text chính |
| `--inv-muted` | `#64748b` | Text mờ |
| `--inv-surface` | `#1e293b` | Card/surface |
| `--inv-border` | `rgba(99,179,237,0.14)` | Border |

Font chính: **"Be Vietnam Pro"** (Google Fonts, import trong `app.css`).

### Mobile Drawer Architecture
Drawer menu dùng chung giữa `Layout.blade.php` (user/public) và `LayoutAdmin.blade.php` (admin):
- CSS chung: `theme-drawer-shared.css`
- CSS theming PC (nav grid, guest-info positioning): `theme-invest-app.css` — section `@media (min-width: 769px)`
- Cấu trúc flex: `mobile-menu-drawer` (100vh, flex col) → `mobile-menu-header` (shrink:0) → `user-nav-primary` (flex:1) → `user-nav-primary__inner` (flex col) → `user-nav-primary__mid` (flex:1, overflow-y:auto) → cluster links
- Nav guest: 3 link chính trong `__mid`, Giới thiệu+Liên hệ trong `user-nav-guest-info` ngoài `__mid` → ghim đáy mobile, grid-col-2 trên PC

### CSS files
| File | Trang/Component |
|------|----------------|
| `app.css` | Global resets, typography, buttons, `table {margin-top:20px}` global |
| `theme-invest-app.css` | Dark theme vars, nav PC grid, guest-info positioning, table styles |
| `theme-drawer-shared.css` | Mobile drawer: topbar, drawer, guest link colors |
| `adminInsert.css` | Form thêm stock cơ bản |
| `adminView.css` | Admin dashboard layout |
| `adminStockManagement.css` | Stock CRUD table, sticky header, `.sticky-header th {z-index:1001}` |
| `adminStockInsert.css` | Form thêm stock đầy đủ |
| `adminUserManagement.css` | User management table |
| `login.css` | Login form card |
| `loginRegister.css` | Register form |
| `userFollow.css` | Follow table |
| `footer.css` | Footer 3-area layout |
| `pages/user-home.css` | Bảng giá, filter chips, suggest modal (blue), buy-suggest modal (amber), sticky clone |
| `pages/user-profile.css` | Portfolio FIFO summary cards |
| `pages/user-follow-form.css` | Form follow inline |
| `pages/user-email-settings.css` | Email settings toggles |
| `pages/investment-performance.css` | Performance chart table |
| `pages/user-buy.css` | Form mua |
| `pages/user-sell.css` | Form bán (FIFO preview) |
| `pages/user-follow.css` | Follow list page |
| `pages/user-update-info-profile.css` | Profile edit form |
| `pages/user-change-password.css` | Password change form |
| `pages/public-profile.css` | Public profile |
| `pages/admin-stock-update.css` | Form sửa stock |
| `pages/admin-user-update.css` | Form sửa user |
| `pages/admin-logs.css` | Log viewer |
| `pages/admin-logs-vps.css` | VPS logs realtime |
| `pages/admin-crontab.css` | Crontab manager UI |
| `pages/about.css` | Hero orbs, feature cards, CSS-only UI mockups, animations |
| `pages/contact.css` | Split layout, FAQ accordion |

### JavaScript files
| File | Tính năng chính |
|------|----------------|
| `app.js` | Axios default, CSRF header `X-CSRF-TOKEN`, global `window.axios` |
| `Admin.js` | Admin form helpers, delete confirm, quick actions |
| `AdminStockManagement.js` | Filter/sort bảng stock, import/export CSV trigger |
| `AdminUserManagement.js` | Filter/search users, role toggle |
| `AdminStockInsert.js` | Multi-field form stock insert với live preview |
| `AdminStockUpdate.js` | Inline stock update form |
| `User.js` | User helper: format số, common AJAX |
| `pages/user-home.js` | **Core**: bảng giá realtime-filter, filter chips, suggest modal (`#home-suggest-modal`), buy-suggest modal (`#home-buy-suggest-modal`), sticky clone header, `isLoggedIn` guard, `window.__pageData` |
| `pages/user-profile.js` | FIFO portfolio charts, P&L display |
| `pages/user-follow-form.js` | Follow form: stock code autocomplete/validate |
| `pages/user-email-settings.js` | Toggle email alerts per mã, batch save |
| `pages/investment-performance.js` | Sort table, ROI filter, chart |
| `pages/user-buy.js` | Buy form: validate, preview cost, AJAX submit |
| `pages/user-sell.js` | Sell form: FIFO lot preview, quantity check |
| `pages/user-follow.js` | Follow list: batch select, delete, notice toggle |
| `pages/user-insert-follow.js` | Add follow: code lookup AJAX |
| `pages/user-cash-in.js` | Nạp tiền form |
| `pages/user-cash-out.js` | Rút tiền form với balance check |
| `pages/user-update-info-profile.js` | Edit name form |
| `pages/user-change-password.js` | Password form: show/hide toggle |
| `pages/login.js` | Login: show/hide password |
| `pages/register.js` | Register: validation, show/hide |
| `pages/forgot-password.js` | Forgot password form |
| `pages/reset-password.js` | Reset password form |
| `pages/admin-home.js` | Dashboard: sync status polling, quick sync buttons |
| `pages/admin-insert.js` | Basic stock insert form |
| `pages/admin-stock-management.js` | Advanced stock management (filter/sort/batch/CSV) |
| `pages/admin-stock-follow.js` | Admin follow: batch add/delete |
| `pages/admin-stock-suggest.js` | Admin suggest: batch add/delete |
| `pages/admin-stock-update.js` | Stock update form inline |
| `pages/admin-user-management.js` | User management: search/sort |
| `pages/admin-upload-file.js` | File upload to VPS |
| `pages/admin-update-risk.js` | Sync risk 1 mã |
| `pages/admin-logs-vps.js` | VPS logs: fetch + display |
| `pages/admin-crontab.js` | Crontab CRUD: add/edit/delete/toggle/run, re-fetch after mutation |
| `pages/contact.js` | Contact form: client validate, Axios POST AJAX, FAQ accordion |

### Cách include assets (Vite)
```blade
{{-- Trong Blade template --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
@vite(['resources/css/pages/user-home.css', 'resources/js/pages/user-home.js'])
```

### window.__pageData (user-home)
Object được PHP inject vào HTML, dùng bởi `user-home.js`:
```js
window.__pageData = {
    isLoggedIn: true/false,
    stocks: [...],               // tất cả stocks cho bảng giá
    adminFollowStockIds: [...],  // IDs mà admin đang follow
    adminSuggestedStocks: [...], // objects {code, buy_price, current_price, ...} cho suggest modal
    userPortfolioStockIds: [...],// IDs user đang giữ
}
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
| `SYNC_API_KEY` | – | API key gửi header `X-API-Key` khi gọi VPS (crontab add/update/delete/toggle/run, run-sync) |

### Config files quan trọng

**`config/logging.php`**
```php
'stack' => ['driver' => 'stack', 'channels' => ['daily_custom']],
'daily_custom' => [
    'driver' => 'custom',
    'via' => App\Logging\CustomDailyLogger::class,
    'days' => 30,              // Retention 30 ngày
    'send_email_on_error' => true,
    'email_throttle' => 300,   // 5 phút throttle cho WARNING; ERROR throttle 60s
],
```

**`config/services.php`**
```php
'sync' => [
    'base_url'               => env('SYNC_SERVICE_URL'),
    'run_update_stock_path'  => env('SYNC_RUN_UPDATE_STOCK_PATH', '/run-sync-update-stocks'),
    'api_key'                => env('SYNC_API_KEY', ''),
],
'cron' => [
    'secret' => env('CRON_API_SECRET'),
],
```

**`config/cache.php`**
- Driver: `file`, path: `storage/framework/cache/data/`
- TTL mặc định: 86400 giây (1 ngày)
- Cache key prefix: `{APP_NAME}_cache_`

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

### 6. `route()` vs `url()` trong Blade

**Luôn dùng `route('name')` khi named route tồn tại** — `route()` throw ngay nếu tên sai, `url()` hardcode path và âm thầm break khi path thay đổi.

**Dùng `url()` chỉ trong 3 trường hợp hợp lệ:**
1. **Không có named route** — VD: `/user/profile`, `/user/follow`, `/user/infoProfile`, `/user/investment-performance` (chỉ có alias `Route::get(...)->name(...)` cho 1 số route, không phải tất cả)
2. **JS dynamic base URL** — VD: `url('/admin/crontab')` trong Blade truyền sang JS, vì JS sẽ append `/{lineIdx}` — không có named route cho parameterized path này
3. **Third-party package URL** — VD: `/admin/log-viewer` (opcodesio/log-viewer), không có named route trong app

❌ Không dùng `url('/admin')` khi có thể dùng `route('admin.home')`  
❌ Không hardcode path khi named route đã có

### 7. Blade Template & SEO
Chi tiết đầy đủ tại [docs/blade-seo.md](docs/blade-seo.md). Tóm tắt quan trọng:

**Chọn layout & section content:**
| Layout | `@extends` | Section nội dung |
|--------|-----------|-----------------|
| User / Public | `Layout.Layout` | `@section('user-body-content')` |
| Auth (guest) | `Layout.LayoutLogin` | `@section('body-content')` |
| Admin | `Layout.LayoutAdmin` | `@section('admin-body-content')` |

**Các `@section` dùng chung:**
- `title` — `<title>` trình duyệt (bắt buộc)
- `csrf-token` — meta CSRF cho AJAX
- `header-css` / `header-js` — `@vite(...)` theo trang
- `actions-left` — nav: `@include('partials.user-nav-primary')` hoặc `@include('partials.guest-nav-actions')`
- `seo` — SEO meta (xem dưới)

**SEO logic tự động trong `Layout.Layout`:**
1. Nếu view có `@section('seo')` → dùng section đó (ghi đè hoàn toàn)
2. Nếu route là `home` → layout tự gắn `seo-public` + JSON-LD `WebSite`
3. Nếu user đã đăng nhập → tự động `<meta name="robots" content="noindex, follow">`

**Dùng `partials.seo-public` cho trang public:**
```blade
@section('seo')
    @include('partials.seo-public', [
        'pageTitle'   => 'Tiêu đề — ' . config('app.name'),
        'description' => 'Mô tả ngắn 1-2 câu.',
        // 'canonical' => url('/...'),   // tuỳ chọn
        // 'ogImage'   => url('/img/og.jpg'),
    ])
@endsection
```

**URL trong AJAX:** Truyền từ Blade qua `window.__pageData` hoặc `@json(route('...'))` — tránh hardcode URL trong JS để đúng khi `APP_URL` có subfolder.

**Heading:** Mỗi trang có đúng 1 `<h1>`. Dùng `@include('partials.page-title-invest', ['title' => '...', 'level' => 1])`.

**Thêm trang public mới:** Nếu muốn Google index → thêm URL vào route `site.sitemap` trong `routes/web.php`.

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
