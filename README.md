# 📈 Invest App – Quản lý Danh mục Cổ phiếu Cá nhân

> Ứng dụng web theo dõi và quản lý danh mục cổ phiếu cá nhân trên thị trường chứng khoán Việt Nam.
> Hỗ trợ mua/bán ảo, quản lý tiền ảo, đặt cảnh báo giá, và nhận email thông báo tự động.

**URL Local:** `http://localhost/invest/public`  
**GitHub:** `https://github.com/HuuPhuoc0196/invest.git`  
**Status:** Production-ready

---

## 🎯 Tính năng chính

### Cho nhà đầu tư (`role=0`)
- ✅ Xây dựng danh mục cổ phiếu (mua/bán ảo với tiền ảo)
- 📊 Theo dõi hiệu suất đầu tư (P&L, ROI)
- 🔔 Đặt cảnh báo giá (khi đạt giá mua/bán mục tiêu)
- 💰 Quản lý ví tiền ảo (nạp/rút tiền)
- 📧 Email thông báo tự động theo dõi giá hàng ngày

### Cho quản trị viên (`role=1`)
- 🔐 Quản lý danh sách cổ phiếu (CRUD)
- 📥 Import/Export CSV cổ phiếu hàng loạt
- 👥 Quản lý tài khoản users
- 📈 Cập nhật giá, mức độ rủi ro tự động từ service bên ngoài
- 📋 Xem logs hệ thống chi tiết (error/warning/info)
- 💡 Tạo gợi ý đầu tư cho users

---

## 🚀 Công nghệ sử dụng

| Tầng | Công nghệ |
|------|-----------|
| **Backend** | Laravel 9, PHP 8.0+, MySQL/PostgreSQL |
| **Frontend** | Vite, Axios, Blade templates, CSS custom |
| **Caching** | File-based cache (86400s TTL) |
| **Authentication** | Laravel Sanctum (API tokens) |
| **Email** | SMTP (Gmail, SendGrid, v.v.) |
| **Logging** | Daily rotating logs + email on errors |
| **Dev tools** | PHPUnit, Laravel Pint, Spatie Ignition |

---

## ⚡ Quick Start

### 1. Clone & Setup
```bash
git clone https://github.com/HuuPhuoc0196/invest.git
cd invest
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Cấu hình Database
Chỉnh sửa `.env`:
```env
DB_DATABASE=invest
DB_USERNAME=root
DB_PASSWORD=
```

Chạy migrations:
```bash
php artisan migrate
```

### 3. Dev Mode
```bash
# Terminal 1: Build assets với hot reload
npm run dev

# Terminal 2: (Nếu không dùng XAMPP) Start server
php artisan serve  # Hoặc sử dụng XAMPP trực tiếp
```

Truy cập: `http://localhost/invest/public`

### 4. Production Build
```bash
npm run build
php artisan config:cache
php artisan route:cache
```

---

## 📚 Tài liệu

| File | Mục đích |
|------|---------|
| **[CLAUDE.md](CLAUDE.md)** ⭐ | Tài liệu kỹ thuật chi tiết — **đọc file này trước!** Bao gồm kiến trúc, database schema, routes, services, conventions, troubleshooting |
| **[docs/blade-seo.md](docs/blade-seo.md)** | Hướng dẫn Blade layouts, SEO, meta tags, robots.txt |

---

## 🔑 Cấu hình quan trọng

### `.env` variables
```env
# Bắt buộc cấu hình
APP_NAME=Quản lý đầu tư cá nhân
APP_ENV=local              # 'local' | 'production'
APP_DEBUG=true             # false ở production
APP_URL=http://localhost/invest/public

DB_DATABASE=invest
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp           # Gmail SMTP hoặc dịch vụ khác
MAIL_FROM_ADDRESS=your@email.com
MAIL_NOTIFICATION_TO=admin@example.com

# Bắt buộc tạo giá trị ngẫu nhiên (openssl rand -hex 32)
CRON_API_SECRET=your_secret_key_here

# URL service cung cấp giá/risk (ngoài)
SYNC_SERVICE_URL=http://localhost

CACHE_DRIVER=file          # File-based cache
LOG_CHANNEL=stack
```

### Cron / API Authentication
Mọi request tới `/api/admin/*` (trừ `/api/user` Sanctum) phải gửi:
```
X-Cron-Secret: {CRON_API_SECRET}
```
hoặc:
```
Authorization: Bearer {CRON_API_SECRET}
```

---

## 📋 Database Schema (tóm tắt)

| Bảng | Mô tả |
|------|-------|
| `users` | Tài khoản (role: 0=User, 1=Admin) |
| `stocks` | Danh sách cổ phiếu |
| `user_portfolios` | Lô cổ phiếu đang giữ (FIFO) |
| `user_portfolio_sells` | Lịch sử bán cổ phiếu |
| `user_follows` | Cảnh báo giá theo dõi |
| `cash_follow` | Số dư ví tiền ảo |
| `cash_in` / `cash_out` | Lịch sử nạp/rút tiền |
| `status_sync` | Thời điểm sync giá/risk gần nhất |

**Chi tiết đầy đủ:** xem [CLAUDE.md — Database Schema](CLAUDE.md#6-database-schema)

---

## 🏗️ Kiến trúc hệ thống

```
HTTP Request
    ↓
Route (routes/web.php | routes/api.php)
    ↓
Middleware (auth, admin, user, cron.secret, ...)
    ↓
Controller (nhận request → gọi Service)
    ↓
Service (business logic)
    ↓
Model + CacheService → Database
    ↓
Response (Blade view | JSON)
```

**Chi tiết:** xem [CLAUDE.md — Kiến trúc hệ thống](CLAUDE.md#4-kiến-trúc-hệ-thống)

---

## 📁 Cấu trúc thư mục chính

```
invest/
├── app/
│   ├── Http/Controllers/      # 4 controllers: Admin, User, Login, Sync
│   ├── Services/              # 8 services: Auth, Stock, Portfolio, Follow, Cash, Email, Sync, Cache
│   ├── Models/                # 12 models Eloquent
│   ├── Http/Middleware/       # 14 middlewares
│   ├── Logging/               # Custom logger + email handler
│   └── Mail/                  # Email classes
├── config/                    # Configuration files
├── database/migrations/       # 10 migration files
├── resources/
│   ├── views/                 # 50+ Blade templates (3 layouts)
│   ├── js/                    # 30+ JavaScript files (Vite)
│   └── css/                   # 27+ CSS files
├── routes/                    # web.php, api.php
├── storage/logs/              # Daily rotating logs
└── CLAUDE.md                  # 👈 Tài liệu kỹ thuật chi tiết
```

---

## 🔧 Thêm tính năng mới

1. **Tạo Route** → `routes/web.php` hoặc `routes/api.php`
2. **Thêm Controller** → gọi Service
3. **Viết Service** → business logic (không query DB trực tiếp)
4. **Model queries** → thêm method static, có cache
5. **Blade view** → sử dụng `@vite()` để include assets
6. **Assets** → tạo trong `resources/js/pages/` hoặc `resources/css/pages/`

**Chi tiết:** xem [CLAUDE.md — Development Guide](CLAUDE.md#13-development-guide)

---

## 🐛 Troubleshooting

| Vấn đề | Giải pháp |
|--------|----------|
| Log không được tạo | `php artisan config:clear` + kiểm tra quyền `storage/logs/` |
| Cache không hoạt động | `php artisan cache:clear` + kiểm tra `storage/framework/cache/data/` tồn tại |
| Email không gửi | Cấu hình `MAIL_*` đúng, test: `php artisan tinker` |
| Giá không cập nhật | Kiểm tra `SYNC_SERVICE_URL` trong `.env` + logs |

**Chi tiết:** xem [CLAUDE.md — Troubleshooting](CLAUDE.md#troubleshooting-thường-gặp)

---

## 📞 Liên hệ

- **GitHub:** https://github.com/HuuPhuoc0196/invest
- **Issues:** Báo cáo bugs trên GitHub Issues

---

## 📄 License

MIT License — xem [LICENSE](LICENSE) file.
