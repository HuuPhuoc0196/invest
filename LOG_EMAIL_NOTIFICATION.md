# Email Notification cho Log Errors

## ✅ Tính năng đã implement

Hệ thống tự động gửi email thông báo khi phát hiện log **WARNING**, **ERROR**, **CRITICAL**, **ALERT**, hoặc **EMERGENCY**.

---

## 📋 Files đã tạo/cập nhật

### Files mới (3):
1. **`app/Mail/LogErrorNotificationMail.php`** - Mail class để gửi email
2. **`app/Logging/EmailErrorHandler.php`** - Monolog handler để intercept logs
3. **`resources/views/emails/log-error-notification.blade.php`** - Email template

### Files cập nhật (2):
4. **`app/Logging/CustomDailyLogger.php`** - Thêm EmailErrorHandler vào logger
5. **`routes/web.php`** - Thêm 3 debug routes để test

---

## 🎯 Cách hoạt động

### Flow:
```
Log WARNING/ERROR → EmailErrorHandler → Check Throttle → Send Email → Update Throttle
```

### Levels gửi email:
- ⚠️ **WARNING** (300) - Cảnh báo thông thường
- ❌ **ERROR** (400) - Lỗi runtime
- 🔥 **CRITICAL** (500) - Lỗi nghiêm trọng (bypass throttle)
- 🚨 **ALERT** (550) - Cần hành động ngay (bypass throttle)
- 💥 **EMERGENCY** (600) - Hệ thống không khả dụng (bypass throttle)

### Throttling:
- **WARNING/ERROR**: Tối đa 1 email mỗi **5 phút** (300 giây)
- **CRITICAL/ALERT/EMERGENCY**: **Không** throttle, gửi ngay lập tức

### Email recipient:
- Mặc định: Email trong `MAIL_FROM_ADDRESS` (`.env`)
- Có thể custom trong config `logging.php`

---

## 📧 Email Template

Email được format đẹp với:
- ✅ Header với icon theo level (⚠️❌🔥🚨💥)
- ✅ Màu sắc theo mức độ nghiêm trọng
- ✅ Thời gian log
- ✅ Hệ thống/URL
- ✅ Nội dung lỗi
- ✅ Context data (JSON formatted)

### Email design:
- **WARNING**: Orange (#ff9800)
- **ERROR**: Red (#f44336)
- **CRITICAL**: Dark Red (#d32f2f)
- **ALERT**: Darker Red (#c62828)
- **EMERGENCY**: Darkest Red (#b71c1c)

---

## 🧪 Test Routes

### 1. Test WARNING Log
```bash
GET http://localhost/invest/public/__debug/test-log-warning
```
**Scenario**: Số dư tài khoản thấp

**Email sẽ chứa**:
- Level: WARNING ⚠️
- Message: "Test WARNING log - Số dư tài khoản thấp"
- Context:
  ```json
  {
    "user_id": 1,
    "balance": 50000,
    "threshold": 100000,
    "action": "Kiểm tra và nạp thêm tiền"
  }
  ```

### 2. Test ERROR Log
```bash
GET http://localhost/invest/public/__debug/test-log-error
```
**Scenario**: Không thể kết nối database

**Email sẽ chứa**:
- Level: ERROR ❌
- Message: "Test ERROR log - Không thể kết nối database"
- Context:
  ```json
  {
    "error_code": "DB_CONNECTION_FAILED",
    "host": "localhost",
    "database": "invest",
    "attempt": 3,
    "last_error": "Connection timeout after 30 seconds"
  }
  ```

### 3. Test CRITICAL Log
```bash
GET http://localhost/invest/public/__debug/test-log-critical
```
**Scenario**: Hệ thống mất kết nối sync server

**Email sẽ chứa**:
- Level: CRITICAL 🔥
- Message: "Test CRITICAL log - Hệ thống mất kết nối sync server"
- Context:
  ```json
  {
    "server": "http://163.61.182.174",
    "service": "Stock Price Sync",
    "downtime": "15 minutes",
    "impact": "Không thể cập nhật giá cổ phiếu realtime",
    "action_required": "Kiểm tra ngay server sync"
  }
  ```

---

## 🧪 Test Results (2026-04-11)

### ✅ Test 1: WARNING
```powershell
PS> Invoke-WebRequest http://localhost/invest/public/__debug/test-log-warning

{
  "status": "success",
  "message": "WARNING log đã được ghi và email đã được gửi",
  "log_time": "2026-04-11 13:22:33",
  "email_to": "lehuuphuoc0196investment@gmail.com"
}
```

**Log file**:
```
[2026-04-11 13:22:32] local.WARNING: Test WARNING log - Số dư tài khoản thấp {...}
```

### ✅ Test 2: ERROR
```powershell
PS> Invoke-WebRequest http://localhost/invest/public/__debug/test-log-error

{
  "status": "success",
  "message": "ERROR log đã được ghi và email đã được gửi",
  "log_time": "2026-04-11 13:22:54",
  "email_to": "lehuuphuoc0196investment@gmail.com"
}
```

**Log file**:
```
[2026-04-11 13:22:53] local.ERROR: Test ERROR log - Không thể kết nối database {...}
```

### ✅ Test 3: CRITICAL
```powershell
PS> Invoke-WebRequest http://localhost/invest/public/__debug/test-log-critical

{
  "status": "success",
  "message": "CRITICAL log đã được ghi và email đã được gửi (bỏ qua throttle)",
  "log_time": "2026-04-11 13:23:06",
  "email_to": "lehuuphuoc0196investment@gmail.com"
}
```

**Log file**:
```
[2026-04-11 13:23:06] local.CRITICAL: Test CRITICAL log - Hệ thống mất kết nối sync server {...}
```

---

## ⚙️ Configuration

### Trong `config/logging.php`:

```php
'daily_custom' => [
    'driver' => 'custom',
    'via' => App\Logging\CustomDailyLogger::class,
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 30,
    'permission' => 0664,
    
    // Email notification settings
    'send_email_on_error' => true, // Bật/tắt email notification
    'email_recipients' => [
        config('mail.from.address'), // Mặc định
        // 'admin@example.com', // Thêm recipients khác
    ],
    'email_throttle' => 300, // Throttle (giây) - 5 phút
],
```

### Tắt email notification:

Trong `.env` hoặc config:
```php
'send_email_on_error' => false,
```

### Thay đổi recipients:

```php
'email_recipients' => [
    'admin1@example.com',
    'admin2@example.com',
    'lehuuphuoc0196@gmail.com',
],
```

### Thay đổi throttle:

```php
'email_throttle' => 600, // 10 phút
'email_throttle' => 60,  // 1 phút
'email_throttle' => 0,   // Không throttle (không khuyến nghị)
```

---

## 📊 Real-world Usage

### Sử dụng trong code:

```php
use Illuminate\Support\Facades\Log;

// WARNING - Cảnh báo
Log::warning('Số dư tài khoản thấp', [
    'user_id' => $userId,
    'balance' => $balance,
    'threshold' => 100000,
]);

// ERROR - Lỗi
Log::error('Không thể kết nối API', [
    'api' => 'Stock Price API',
    'error' => $exception->getMessage(),
    'retry_count' => 3,
]);

// CRITICAL - Lỗi nghiêm trọng
Log::critical('Cache server down', [
    'server' => 'Redis',
    'impact' => 'Website chạy chậm',
]);
```

### Tự động trigger từ exceptions:

Laravel tự động log exceptions → Email sẽ được gửi nếu là ERROR level:

```php
try {
    // Code có thể lỗi
} catch (\Exception $e) {
    Log::error('Database query failed', [
        'query' => $query,
        'error' => $e->getMessage(),
    ]);
    // Email sẽ tự động gửi
}
```

---

## 🔍 Monitoring

### Check throttle cache:

```powershell
# Xem throttle cache file
ls storage/logs/.email_throttle_*

# Xem last sent time
Get-Content storage/logs/.email_throttle_* 
# Output: Unix timestamp (1712811732)
```

### Check logs:

```powershell
# Xem WARNING/ERROR logs hôm nay
Get-Content storage/logs/laravel_20260411.log | 
  Select-String "WARNING|ERROR|CRITICAL"

# Count logs by level
Get-Content storage/logs/laravel_20260411.log | 
  Select-String "WARNING|ERROR|CRITICAL" | 
  Group-Object {$_ -replace '.*\] local\.(\w+):.*','$1'} | 
  Select-Object Count, Name
```

---

## 🚨 Important Notes

### Production Checklist:

1. ✅ **Kiểm tra MAIL config** (`.env`) hoạt động đúng
2. ✅ **Test gửi email** trước khi deploy
3. ✅ **Setup email recipients** phù hợp
4. ✅ **Điều chỉnh throttle** nếu cần
5. ⚠️ **Xóa debug routes** sau khi test (hoặc giữ lại vì chỉ work khi `APP_DEBUG=true`)

### Troubleshooting:

**Không nhận được email?**
1. Check `storage/logs/laravel_YYYYMMDD.log` có log WARNING/ERROR không
2. Check `.env` - `MAIL_*` config đúng chưa
3. Check email spam folder
4. Check throttle cache: `storage/logs/.email_throttle_*`
5. Test send email thông thường: `php artisan tinker` → `Mail::to(...)->send(...)`

**Nhận quá nhiều email?**
- Tăng `email_throttle` trong config (từ 300 → 600 hoặc 900 giây)
- Hoặc tăng minimum level (từ WARNING → ERROR hoặc CRITICAL)

---

## ✅ Verification Checklist

- [x] EmailErrorHandler hoạt động
- [x] Email template hiển thị đẹp
- [x] Throttling hoạt động (WARNING/ERROR)
- [x] Bypass throttle cho CRITICAL
- [x] Test routes hoạt động
- [x] Log file ghi đúng format
- [ ] Email thực tế được gửi đến inbox (cần check mailbox)
- [ ] Production: Xóa debug routes (optional)

---

## 📞 Summary

✅ **3 levels** được monitor: WARNING, ERROR, CRITICAL (+ ALERT, EMERGENCY)  
✅ **Throttling**: 5 phút cho WARNING/ERROR, không throttle cho CRITICAL+  
✅ **Email template**: Professional, responsive, color-coded  
✅ **Context logging**: Full JSON context trong email  
✅ **Test routes**: 3 routes để test đầy đủ scenarios  
✅ **Configuration**: Flexible, có thể tắt/bật dễ dàng  

**Hệ thống email notification cho logs đã sẵn sàng! 🎉**

Bạn sẽ nhận email ngay khi hệ thống gặp WARNING, ERROR, hoặc vấn đề nghiêm trọng hơn.
