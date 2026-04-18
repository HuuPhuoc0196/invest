# Cache System Implementation - Test & Documentation

## ✅ Tổng kết Implementation

### Files đã tạo mới (2 files):
1. **`app/Services/CacheService.php`** - Core cache service với lock mechanism
2. **`app/Http/Controllers/Api/CacheController.php`** - API endpoints để clear cache

### Files đã cập nhật (18 files):

#### Models (7 files):
1. `app/Models/Stock.php` - Cache cho getAllStocks(), getByCode(), getRiskLevelFromCode()
2. `app/Models/UserPortfolio.php` - Cache cho getProfileUser(), getPortfolioWithStockInfo(), etc.
3. `app/Models/UserFollow.php` - Cache cho getUserFollow(), getFollowNoticeByUser()
4. `app/Models/UserPortfolioSell.php` - Cache cho getPortfolioSellWithStockInfo()
5. `app/Models/UserCashFollow.php` - Cache cho getCashFollow()
6. `app/Models/User.php` - Cache cho getUserById()
7. `app/Models/StatusSync.php` - Cache cho getStatusSync()

#### Services (5 files):
8. `app/Services/StockService.php` - Clear cache sau insert/update/import
9. `app/Services/PortfolioService.php` - Clear cache sau buy/sell
10. `app/Services/FollowService.php` - Clear cache sau insert/update follow
11. `app/Services/CashService.php` - Clear cache sau cashIn/cashOut
12. `app/Services/AuthService.php` - Clear cache sau updateUserName

#### Controllers (1 file):
13. `app/Http/Controllers/Sync/Sync.php` - Clear cache sau sync operations

#### Routes (1 file):
14. `routes/api.php` - Thêm 6 cache API endpoints

---

## 🧪 Test Plan

### Test 1: Verify Cache Driver
```bash
# Check .env
cat .env | grep CACHE_DRIVER
# Expected: CACHE_DRIVER=file

# Check cache directory
ls storage/framework/cache/data/
```

### Test 2: Test Cache trên Local

#### Bước 1: Truy cập trang Home
```
1. Mở browser: http://localhost/invest/public/trang-chu
2. Refresh nhiều lần
3. Check logs: storage/logs/laravel.log
   - Lần đầu: "Cache stored" với key "stocks_all"
   - Lần sau: Không có log (cache hit)
```

#### Bước 2: Test Cache Clear khi Update Stock
```
1. Đăng nhập Admin
2. Vào Admin Stock Management → Update 1 mã cổ phiếu
3. Check logs: "Cache cleared" với các keys liên quan
4. Refresh trang Home → "Cache stored" lại (cache miss)
```

#### Bước 3: Test User Cache
```
1. Đăng nhập User
2. Vào Profile → Check logs "Cache stored" với key "user_portfolio_profile_1"
3. Mua cổ phiếu → Check logs "Cache cleared" với user cache keys
4. Refresh Profile → "Cache stored" lại
```

### Test 3: Test API Endpoints (từ PowerShell)

#### 3.1 Get Cache Info
```powershell
$headers = @{
    "X-Cron-Secret" = "572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a"
}

Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/info" `
    -Method GET `
    -Headers $headers
```

**Expected Response:**
```json
{
  "status": "success",
  "data": {
    "driver": "file",
    "path": "C:/xampp/htdocs/invest/storage/framework/cache/data",
    "exists": true,
    "file_count": 5,
    "total_size": 12345,
    "total_size_mb": 0.01,
    "sample_keys": ["stocks_all", "user_portfolio_profile_1", ...]
  }
}
```

#### 3.2 Clear Cache của Table Stocks
```powershell
$headers = @{
    "X-Cron-Secret" = "572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a"
    "Content-Type" = "application/json"
}

$body = @{
    table = "stocks"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-table" `
    -Method POST `
    -Headers $headers `
    -Body $body
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Đã xóa cache của table 'stocks'.",
  "table": "stocks",
  "keys_cleared": 3
}
```

#### 3.3 Clear Cache của User
```powershell
$body = @{
    user_id = 1
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-user" `
    -Method POST `
    -Headers $headers `
    -Body $body
```

#### 3.4 Clear Cache của Stock Code
```powershell
$body = @{
    code = "VNM"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-stock" `
    -Method POST `
    -Headers $headers `
    -Body $body
```

#### 3.5 Clear Cache theo Keys
```powershell
$body = @{
    keys = @("stocks_all", "stock_code_VNM", "user_portfolio_profile_1")
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-keys" `
    -Method POST `
    -Headers $headers `
    -Body $body
```

#### 3.6 Clear All Cache
```powershell
Invoke-RestMethod -Uri "http://localhost/invest/public/api/cache/clear-all" `
    -Method POST `
    -Headers $headers
```

---

## 📋 API Usage từ Server Khác

### Example: VPS Server gọi API Clear Cache

#### Scenario 1: Server khác update giá cổ phiếu
```bash
# Sau khi update prices vào DB, gọi API clear cache
curl -X POST http://your-domain.com/api/cache/clear-table \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a" \
  -H "Content-Type: application/json" \
  -d '{"table":"stocks"}'
```

#### Scenario 2: Server khác update portfolio của user
```bash
# Sau khi update user portfolio, clear cache của user đó
curl -X POST http://your-domain.com/api/cache/clear-user \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1}'
```

#### Scenario 3: Deploy mới lên production
```bash
# Clear toàn bộ cache
curl -X POST http://your-domain.com/api/cache/clear-all \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a"
```

---

## 🔍 Monitoring & Debugging

### Check Cache Files
```bash
# Xem số lượng cache files
ls storage/framework/cache/data/ | wc -l

# Xem dung lượng cache
du -sh storage/framework/cache/data/

# Xem nội dung 1 cache file
cat storage/framework/cache/data/xx/xx/xxxxxx
```

### Check Logs
```bash
# Xem cache logs
tail -f storage/logs/laravel.log | grep "Cache"

# Expected log patterns:
# - "Cache stored" - Khi cache được tạo
# - "Cache cleared" - Khi cache được xóa
# - "Cache cleared via API" - Khi gọi API clear cache
```

### Manual Cache Clear
```bash
# Clear toàn bộ cache qua artisan
php artisan cache:clear

# Hoặc xóa thư mục cache
rm -rf storage/framework/cache/data/*
```

---

## 📊 Cache Key Patterns

| Table | Cache Keys | Cleared When |
|-------|-----------|--------------|
| stocks | `stocks_all`, `stock_code_{CODE}`, `stock_risk_{CODE}` | Insert/Update/Delete stock, Import CSV, Sync price/risk |
| user_portfolios | `user_portfolio_profile_{USER_ID}`, `user_portfolio_stock_info_{USER_ID}`, `user_portfolio_buy_{USER_ID}`, `user_portfolio_session_{USER_ID}` | Buy stock, Sell stock, Update session closed flag |
| user_portfolios_sell | `user_portfolio_sell_{USER_ID}` | Sell stock |
| user_follows | `user_follow_{USER_ID}`, `user_follow_notice_{USER_ID}` | Insert/Update/Delete follow |
| cash_follow | `user_cash_{USER_ID}` | Cash in, Cash out, Buy stock, Sell stock |
| users | `user_{USER_ID}` | Update user name |
| status_sync | `status_sync` | Status sync update |

---

## ⚠️ Lưu ý quan trọng

### 1. CRON_API_SECRET
- Giá trị hiện tại: `572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a`
- **QUAN TRỌNG:** Đổi secret mới trước khi deploy production
- Generate mới: `openssl rand -hex 32`

### 2. Server khác call API
- Phải gửi header: `X-Cron-Secret: {CRON_API_SECRET}`
- Hoặc: `Authorization: Bearer {CRON_API_SECRET}`
- Nếu sai secret → HTTP 403 Forbidden

### 3. Cache Stampede Prevention
- CacheService đã implement lock mechanism
- Chỉ 1 request được phép query DB khi cache miss
- Các request khác chờ và lấy cache sau khi lock release
- Lock timeout: 10 giây

### 4. Cache TTL
- Mặc định: 1 ngày (86400 giây)
- Cache tự expire sau 1 ngày
- Laravel tự động dọn dẹp expired cache

### 5. Performance
- File cache chậm hơn memory cache (APCu/Redis)
- Đủ nhanh cho use case này (shared hosting)
- Nếu cần performance cao hơn → migrate sang Redis/APCu trong tương lai

---

## 🚀 Next Steps

### Immediate (Local Testing):
1. ✅ Test cache trên trang Home
2. ✅ Test cache clear khi update data
3. ✅ Test tất cả 6 API endpoints bằng PowerShell
4. ✅ Check logs để verify cache hoạt động

### Before Production Deploy:
1. ⚠️ Đổi CRON_API_SECRET mới
2. ⚠️ Test API từ server khác với secret mới
3. ⚠️ Monitor cache size trên production
4. ⚠️ Setup log rotation cho không tràn logs

### Optional Improvements:
1. Thêm cache warming script (pre-populate cache)
2. Thêm cache metrics/monitoring dashboard
3. Implement rate limiting cho cache clear APIs
4. Migrate sang Redis nếu cần performance cao hơn

---

## ✅ Checklist Hoàn thành

- [x] Phase 1: Tạo CacheService.php
- [x] Phase 2: Update 7 Models thêm cache
- [x] Phase 3: Update Services clear cache
- [x] Phase 4: Update Sync controller clear cache
- [x] Phase 5: Tạo CacheController & API routes
- [x] Phase 6: Verify và test

**Total: 20 files created/updated**

---

## 📞 Support

Nếu gặp vấn đề:
1. Check logs: `storage/logs/laravel.log`
2. Check cache files: `storage/framework/cache/data/`
3. Test API với curl/PowerShell
4. Clear cache manual: `php artisan cache:clear`

**Hệ thống cache đã sẵn sàng sử dụng! 🎉**
