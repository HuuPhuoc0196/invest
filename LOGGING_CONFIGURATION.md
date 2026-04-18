# Custom Daily Logging Configuration

## ✅ Implementation Complete

Hệ thống logging đã được cấu hình để ghi log theo ngày với format tên file tùy chỉnh.

### 📋 Files đã tạo/cập nhật

#### Files mới (1):
1. **`app/Logging/CustomDailyLogger.php`** - Custom logger class

#### Files cập nhật (2):
2. **`config/logging.php`** - Thêm channel `daily_custom` và cập nhật stack channel
3. **`routes/web.php`** - Thêm debug route `/__debug/test-log` (có thể xóa sau khi test)

---

## 📝 Format Log File

### Tên file theo ngày:
- **Format**: `laravel_YYYYMMDD.log`
- **Ví dụ**: 
  - Ngày 11/04/2026 → `laravel_20260411.log`
  - Ngày 12/04/2026 → `laravel_20260412.log`
  - Ngày 01/01/2027 → `laravel_20270101.log`

### Cấu hình:
- **Location**: `storage/logs/`
- **Retention**: 30 ngày (tự động xóa log cũ hơn 30 ngày)
- **Permission**: 0664
- **Level**: debug (hoặc theo LOG_LEVEL trong .env)

---

## 🧪 Test Logging

### Test 1: Kiểm tra log file được tạo

1. **Truy cập trang web bất kỳ**:
   ```
   http://localhost/invest/public/trang-chu
   ```

2. **Kiểm tra thư mục logs**:
   ```powershell
   ls storage/logs/
   ```
   
3. **Kết quả mong đợi**: Thấy file `laravel_20260411.log`

### Test 2: Kiểm tra nội dung log

```powershell
# Xem log file hiện tại
Get-Content storage/logs/laravel_20260411.log -Tail 10

# Hoặc xem toàn bộ
cat storage/logs/laravel_20260411.log
```

### Test 3: Kiểm tra log rotation

1. Chờ đến ngày mới (hoặc thay đổi system date để test)
2. Truy cập lại website
3. Kiểm tra sẽ có file mới: `laravel_20260412.log`

### Test 4: Test từ code

Tạo log test:
```php
use Illuminate\Support\Facades\Log;

// Trong bất kỳ controller nào
Log::info('Test custom daily logging', ['date' => date('Y-m-d H:i:s')]);
Log::warning('Test warning log');
Log::error('Test error log');
```

---

## 🔧 Cấu hình

### Environment Variables (`.env`)

```env
# Log channel (đã được set tự động trong config)
LOG_CHANNEL=stack

# Log level
LOG_LEVEL=debug
```

### Config (`config/logging.php`)

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['daily_custom'], // ← Sử dụng custom daily logger
    'ignore_exceptions' => false,
],

'daily_custom' => [
    'driver' => 'monolog',
    'handler' => App\Logging\CustomDailyLogger::class,
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 30, // Giữ log 30 ngày
    'permission' => 0664,
],
```

---

## 📊 Log Retention & Cleanup

### Automatic Cleanup
- Log files cũ hơn **30 ngày** sẽ tự động bị xóa
- Cleanup chạy mỗi khi có log mới được ghi

### Manual Cleanup
Nếu muốn cleanup thủ công:

```powershell
# Xóa log cũ hơn 30 ngày (Windows PowerShell)
Get-ChildItem storage/logs/laravel_*.log | 
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } | 
    Remove-Item

# Xóa tất cả log (cẩn thận!)
Remove-Item storage/logs/laravel_*.log
```

---

## 🔍 Monitoring

### Kiểm tra kích thước log files

```powershell
# List tất cả log files với size
Get-ChildItem storage/logs/laravel_*.log | 
    Select-Object Name, Length, LastWriteTime | 
    Sort-Object LastWriteTime -Descending

# Tổng dung lượng tất cả log
(Get-ChildItem storage/logs/laravel_*.log | 
    Measure-Object -Property Length -Sum).Sum / 1MB
```

### Theo dõi log realtime

```powershell
# Windows PowerShell
Get-Content storage/logs/laravel_$(Get-Date -Format "yyyyMMdd").log -Wait -Tail 20
```

---

## 📋 Log Levels

Hệ thống hỗ trợ các log levels sau (theo thứ tự từ thấp đến cao):

1. **DEBUG** - Thông tin chi tiết cho debugging
2. **INFO** - Thông tin thông thường (cache stored, etc.)
3. **NOTICE** - Sự kiện bình thường nhưng quan trọng
4. **WARNING** - Cảnh báo (không phải lỗi)
5. **ERROR** - Runtime errors
6. **CRITICAL** - Lỗi nghiêm trọng
7. **ALERT** - Cần hành động ngay lập tức
8. **EMERGENCY** - Hệ thống không khả dụng

### Sử dụng trong code:

```php
use Illuminate\Support\Facades\Log;

Log::debug('Debug message');
Log::info('Info message');
Log::notice('Notice message');
Log::warning('Warning message');
Log::error('Error message');
Log::critical('Critical message');
Log::alert('Alert message');
Log::emergency('Emergency message');
```

---

## 🔄 Integration với Cache System

Cache system đã tự động ghi log khi:
- Cache được stored: `"Cache stored" với key`
- Cache được cleared: `"Cache cleared" với key/pattern`
- Cache cleared via API: `"Cache cleared via API"` với action, ip, etc.

Tất cả các log này sẽ được ghi vào file theo ngày hiện tại.

---

## ⚙️ Advanced Configuration

### Thay đổi retention period

Trong `config/logging.php`:
```php
'daily_custom' => [
    // ...
    'days' => 60, // Giữ log 60 ngày thay vì 30
],
```

### Thay đổi log level

Trong `.env`:
```env
# Chỉ log warning trở lên (bỏ debug, info)
LOG_LEVEL=warning

# Hoặc chỉ log error trở lên
LOG_LEVEL=error
```

### Thêm multiple channels

Có thể thêm nhiều channels khác cho các mục đích khác:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily_custom', 'slack'], // Ghi cả file và Slack
    ],
],
```

---

## 🚨 Troubleshooting

### Vấn đề: Log file không được tạo

**Kiểm tra**:
1. Permission thư mục `storage/logs/`
   ```powershell
   # Đảm bảo web server có quyền ghi
   icacls storage/logs
   ```

2. Laravel cache config
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

### Vấn đề: Log file sai format

**Kiểm tra**:
1. File `app/Logging/CustomRotatingFileHandler.php` tồn tại
2. Config `config/logging.php` đúng
3. Clear config cache: `php artisan config:clear`

### Vấn đề: Log bị duplicate

**Nguyên nhân**: Có thể do stack channel ghi vào nhiều handlers

**Giải pháp**: Kiểm tra stack channels trong config chỉ có `daily_custom`

---

## ✅ Verification Checklist

- [x] File `laravel_20260411.log` được tạo tự động
- [x] Log mới được ghi vào file theo ngày hiện tại
- [x] File cũ hơn 30 ngày tự động bị xóa
- [x] Cache logs được ghi đúng format
- [x] Test route `/__debug/test-log` hoạt động
- [ ] Production: Xóa debug route `/__debug/test-log` (optional)
- [ ] Test trên production (chưa deploy)

---

## 🧹 Cleanup (Optional)

### Xóa Debug Route

Sau khi test xong, bạn có thể xóa debug route trong `routes/web.php`:

```php
// Xóa hoặc comment block này:
Route::get('/__debug/test-log', function () {
    // ...
})->name('debug.testlog');
```

Hoặc giữ lại để debug sau này (route này chỉ hoạt động khi `APP_DEBUG=true`).

---

## 📞 Notes

- Log format theo ngày giúp dễ quản lý và tìm kiếm log
- Tự động cleanup giúp tiết kiệm disk space
- Compatible với tất cả Laravel logging features
- Có thể switch về single/daily channel bất kỳ lúc nào
- Debug route `/__debug/test-log` chỉ accessible khi `APP_DEBUG=true`

**Custom daily logging đã sẵn sàng sử dụng! 🎉**
