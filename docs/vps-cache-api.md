# VPS Cache API — Hướng dẫn invalidate cache webapp

> **Dành cho:** VPS `root@180.93.42.13` — các script Python tại `/root/stocks/`
> **Cập nhật:** 2026-05-16

---

## Thông tin kết nối Production

| Biến | Giá trị |
|------|---------|
| `WEBAPP_CACHE_URL` | `https://investment-manager.xyz` |
| `WEBAPP_CACHE_SECRET` | `572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a` |

Đặt 2 biến này trong environment của VPS (file `.env` hoặc `systemd` service):

```bash
export WEBAPP_CACHE_URL="https://investment-manager.xyz"
export WEBAPP_CACHE_SECRET="572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a"
```

---

## Xác thực

Thêm header vào **mọi** request:

```
X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a
```

hoặc dùng Bearer token:

```
Authorization: Bearer 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a
```

Sai secret → HTTP **403 Forbidden**.

---

## API Endpoints

### POST `/api/cache/clear-stock` — Clear cache 1 mã cổ phiếu

Dùng khi VPS cập nhật giá / risk / risk_history / dividend cho **1 mã cụ thể**.

**Keys bị xóa:** `stock_code_{CODE}`, `stock_risk_{CODE}`, `stock_risk_history_{CODE}`, `stock_dividend_{CODE}`

```bash
curl -X POST https://investment-manager.xyz/api/cache/clear-stock \
  -H "Content-Type: application/json" \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a" \
  -d '{"code": "VNM"}'
```

```json
{
  "status": "success",
  "message": "Đã xóa cache của mã VNM.",
  "code": "VNM",
  "keys_cleared": 4
}
```

---

### POST `/api/cache/clear-table` — Clear cache theo bảng

Dùng khi VPS cập nhật **hàng loạt** một bảng.

```bash
curl -X POST https://investment-manager.xyz/api/cache/clear-table \
  -H "Content-Type: application/json" \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a" \
  -d '{"table": "stock_risk_history"}'
```

#### Tất cả table được hỗ trợ:

| `table` | Keys bị xóa | Script VPS gọi sau |
|---------|------------|-------------------|
| `stocks` | `stocks_all`, `stock_code_*`, `stock_risk_*` | `syncPrice.py`, `syncRatingPrice.py` |
| `stock_risk_history` | `stock_risk_history_*` | `syncRisk.py` |
| `dividend_adjustments` | `stock_dividend_*` | `syncDividend.py` |
| `user_portfolios` | `user_portfolio_profile_*`, `user_portfolio_stock_info_*`, `user_portfolio_buy_*`, `user_portfolio_session_*` | `syncDividend.py` |
| `user_portfolios_sell` | `user_portfolio_sell_*` | `syncDividend.py` |
| `user_follows` | `user_follow_*`, `user_follow_notice_*` | `syncPrice.py` |
| `cash_follow` | `user_cash_*` | — |
| `users` | `user_*` | — |
| `status_sync` | `status_sync` | `syncRisk.py` |
| `admin_follow` | `admin_follow_stock_ids`, `admin_follow_stocks` | `syncStocks10M.py` |
| `admin_suggest` | `admin_suggest_stock_ids`, `admin_suggest_stocks` | — |

```json
{
  "status": "success",
  "message": "Đã xóa cache của table 'stock_risk_history'.",
  "table": "stock_risk_history",
  "keys_cleared": 12
}
```

---

### POST `/api/cache/clear-keys` — Clear danh sách keys cụ thể

```bash
curl -X POST https://investment-manager.xyz/api/cache/clear-keys \
  -H "Content-Type: application/json" \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a" \
  -d '{"keys": ["status_sync", "stocks_all"]}'
```

```json
{
  "status": "success",
  "keys_requested": ["status_sync", "stocks_all"],
  "keys_cleared": 2
}
```

---

### POST `/api/cache/clear-all` — Flush toàn bộ cache

> ⚠️ Dùng cẩn thận — gây cache miss đồng loạt, tăng tải DB tạm thời.

```bash
curl -X POST https://investment-manager.xyz/api/cache/clear-all \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a"
```

---

### GET `/api/cache/info` — Xem thông tin cache (debug)

```bash
curl https://investment-manager.xyz/api/cache/info \
  -H "X-Cron-Secret: 572db6f54e4238e465004bffe9e1296eca9a1c34df7be92eadd981f99e27f59a"
```

---

## Cập nhật `cacheHelper.py` trên VPS

Thêm 2 hàm mới vào `/root/stocks/cacheHelper.py`:

```python
def clear_stock_risk_history():
    """Xóa cache lịch sử risk của tất cả mã — gọi sau syncRisk.py"""
    _post('/api/cache/clear-table', {'table': 'stock_risk_history'})

def clear_dividend_adjustments():
    """Xóa cache cổ tức của tất cả mã — gọi sau syncDividend.py"""
    _post('/api/cache/clear-table', {'table': 'dividend_adjustments'})
```

---

## Cập nhật các script VPS

### `syncRisk.py` — thêm `clear_stock_risk_history()` vào cuối

```python
# Hiện tại (giữ nguyên):
cacheHelper.clear_stocks()
cacheHelper.clear_status_sync()

# Thêm mới — xóa cache lịch sử risk:
cacheHelper.clear_stock_risk_history()

cacheHelper.flush()
```

### `syncDividend.py` — thêm `clear_dividend_adjustments()` vào cuối

```python
# Hiện tại (giữ nguyên):
cacheHelper.clear_stocks()
cacheHelper.clear_user_portfolios()
cacheHelper.clear_user_portfolios_sell()

# Thêm mới — xóa cache cổ tức:
cacheHelper.clear_dividend_adjustments()

cacheHelper.flush()
```

### Khi cập nhật 1 mã duy nhất (ví dụ `updateRiskForCode`)

```python
# Dùng clear_stock(code) — tự động xóa cả risk_history và dividend của mã đó
cacheHelper.clear_stock('VNM')
```

---

## Bảng tổng hợp cache keys

| Key pattern | Dữ liệu | Script xóa |
|-------------|---------|-----------|
| `stocks_all` | Danh sách tất cả stocks | `clear_stocks()` |
| `stock_code_{CODE}` | Thông tin 1 mã (giá, risk, rating...) | `clear_stocks()`, `clear_stock(code)` |
| `stock_risk_{CODE}` | Risk level của 1 mã | `clear_stocks()`, `clear_stock(code)` |
| `stock_risk_history_{CODE}` | Lịch sử sự kiện risk | `clear_stock_risk_history()`, `clear_stock(code)` |
| `stock_dividend_{CODE}` | Lịch sử điều chỉnh cổ tức | `clear_dividend_adjustments()`, `clear_stock(code)` |
| `user_portfolio_profile_{UID}` | Portfolio tổng hợp theo user | `clear_user_portfolios()` |
| `user_portfolio_stock_info_{UID}` | Portfolio join stock info | `clear_user_portfolios()` |
| `user_portfolio_buy_{UID}` | Chi tiết lô mua FIFO | `clear_user_portfolios()` |
| `user_portfolio_session_{UID}` | Session closed flags | `clear_user_portfolios()` |
| `user_portfolio_sell_{UID}` | Lịch sử bán | `clear_user_portfolios_sell()` |
| `user_follow_{UID}` | Danh sách follow của user | `clear_user_follows()` |
| `user_follow_notice_{UID}` | Cài đặt thông báo follow | `clear_user_follows()` |
| `user_cash_{UID}` | Số dư ví ảo | — |
| `status_sync` | Trạng thái sync gần nhất | `clear_status_sync()` |
| `admin_follow_stock_ids` | IDs mã admin đang follow | `clear_admin_follow()` |
| `admin_follow_stocks` | Chi tiết mã admin follow | `clear_admin_follow()` |
| `admin_suggest_stock_ids` | IDs mã admin gợi ý | — |
| `admin_suggest_stocks` | Chi tiết mã admin gợi ý | — |

---

## Xử lý lỗi

| HTTP Code | Nguyên nhân | Cách fix |
|-----------|------------|---------|
| `403 Forbidden` | Sai `WEBAPP_CACHE_SECRET` | Kiểm tra env var trên VPS khớp với `CRON_API_SECRET` trong `.env` Laravel |
| `422 Unprocessable` | Body JSON sai (thiếu field, sai `table` name) | Kiểm tra request body |
| `500 Internal Error` | Lỗi phía webapp | Xem log tại `storage/logs/laravel_YYYYMMDD.log` trên server webapp |
| Timeout / Connection refused | Webapp đang down hoặc sai URL | Kiểm tra `WEBAPP_CACHE_URL` và trạng thái server |
