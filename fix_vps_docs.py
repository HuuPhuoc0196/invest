#!/usr/bin/env python3
"""Fix 3 SKIP items in VPS documentation files."""

# === FIX SKIP 4b: Add resolved note to section 3 of cache_missing_api_doc.md ===
with open('/root/stocks/cache_missing_api_doc.md', 'r', encoding='utf-8') as f:
    content = f.read()

old_4b = '> **Lưu ý:** Nếu `/risk-history/{code}` không có cache, không cần API này.\n\n---\n\n## Tóm Tắt'
new_4b = ('> **Lưu ý:** Nếu `/risk-history/{code}` không có cache, không cần API này.\n\n'
          '> **✅ Đã xử lý (2026-05-16):** `cacheHelper.py` đã có function `clear_stock_risk_history()`. '
          '`syncRisk.py` đã gọi `cacheHelper.clear_stock_risk_history()` sau `clear_status_sync()` và trước `flush()` '
          '— đảm bảo cache `stock_risk_history_*` được xoá sau mỗi lần syncRisk cập nhật DB.\n\n---\n\n## Tóm Tắt')

if old_4b in content:
    content = content.replace(old_4b, new_4b, 1)
    with open('/root/stocks/cache_missing_api_doc.md', 'w', encoding='utf-8') as f:
        f.write(content)
    print('OK 4b: resolved note added to section 3 of cache_missing_api_doc.md')
else:
    print('SKIP 4b: pattern not found')
    i = content.find('risk-history')
    if i >= 0:
        print('  context:', repr(content[i:i+400]))


# === FIX SKIP 5b and 5c: DOCUMENTATION.md ===
with open('/root/stocks/DOCUMENTATION.md', 'r', encoding='utf-8') as f:
    doc = f.read()

# 5b: Add dividend_adjustments to syncDividend.py cache row in integration table
old_5b = '| `syncDividend.py` | Khi co co tuc xu ly | `clear-table: stocks`, `user_portfolios`, `user_portfolios_sell` |'
new_5b = '| `syncDividend.py` | Khi co co tuc xu ly | `clear-table: stocks`, `user_portfolios`, `user_portfolios_sell`, `dividend_adjustments` |'

if old_5b in doc:
    doc = doc.replace(old_5b, new_5b, 1)
    print('OK 5b: syncDividend row updated with dividend_adjustments in cache table')
else:
    print('SKIP 5b: old pattern not found')
    i = doc.find('syncDividend.py')
    while i >= 0 and i < len(doc):
        line_end = doc.find('\n', i)
        print('  line:', repr(doc[i:line_end]))
        i = doc.find('syncDividend.py', i + 1)

# 5c: Add clear_dividend_adjustments() to syncDividend cache clear block description
old_5c = ("- `cacheHelper.clear_user_portfolios_sell()` -- sell_price va quantity da thay doi\n"
          "- `cacheHelper.flush('syncDividend.py')`")
new_5c = ("- `cacheHelper.clear_user_portfolios_sell()` -- sell_price va quantity da thay doi\n"
          "- `cacheHelper.clear_dividend_adjustments()` -- lich su co tuc cua tat ca ma (stock_dividend_*)\n"
          "- `cacheHelper.flush('syncDividend.py')`")

if old_5c in doc:
    doc = doc.replace(old_5c, new_5c, 1)
    print('OK 5c: clear_dividend_adjustments() added to syncDividend cache block in DOCUMENTATION.md')
else:
    print('SKIP 5c: old pattern not found')
    i = doc.find('clear_user_portfolios_sell')
    if i >= 0:
        print('  context:', repr(doc[i:i+300]))

# 5e: Update "Chua co API" table to mark both as resolved
old_5e = ('| `dividend_adjustments` | `syncDividend.py` | Can xac nhan webapp co cache bang nay khong |\n'
          '| `stock_risk_history` | `syncRisk.py` | Can xac nhan `/risk-history/{code}` co cache khong |')
new_5e = ('| `dividend_adjustments` | `syncDividend.py` | ✅ Da xu ly — webapp khong cache bang nay, khong can API |\n'
          '| `stock_risk_history` | `syncRisk.py` | ✅ Da xu ly — `clear_stock_risk_history()` da them vao syncRisk.py |')

if old_5e in doc:
    doc = doc.replace(old_5e, new_5e, 1)
    print('OK 5e: "Chua co API" table updated to resolved')
else:
    print('SKIP 5e: pattern not found')
    i = doc.find('Can xac nhan')
    if i >= 0:
        print('  context:', repr(doc[i-50:i+300]))

with open('/root/stocks/DOCUMENTATION.md', 'w', encoding='utf-8') as f:
    f.write(doc)

print('Done.')
