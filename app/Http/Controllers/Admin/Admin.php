<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StatusSync;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Database\QueryException;

class Admin extends Controller
{
    public function show()
    {
        $stocks = Stock::getAllStocks();
        $statusSync = StatusSync::getStatusSync();
        return view('Admin.AdminView', compact('stocks', 'statusSync'));
    }

    public function insert(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'buyPrice' => 'required|numeric|gt:0',
                    'currentPrice' => 'required|numeric|gt:0',
                    'risk' => 'required|integer|min:1|max:5',
                ]);

                // Kiểm tra code đã tồn tại chưa
                $stock = new Stock();
                if ($stock->getByCode($validated['code'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Mã code đã tồn tại.'
                    ]);
                }
                // Mapping data vào model
                $stock->code = $validated['code'];
                $stock->recommended_buy_price = $validated['buyPrice'];
                $stock->current_price = $validated['currentPrice'];
                $stock->risk_level = $validated['risk'];
                // Lưu vào database (ví dụ bảng stocks)
                $stock->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Insert thành công.',
                    'data' => $stock
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            return view('Admin.AdminInsert');
        }
    }

    public function update(Request $request, $code)
    {
        if ($request->isMethod('PUT')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'currentPrice' => 'required|numeric|gt:0',
                    'risk' => 'required|integer|min:1|max:5',
                    'priceAvg' => 'nullable|numeric|min:0',
                    'buyPrice' => 'nullable|numeric|min:0',
                    'sellPrice' => 'nullable|numeric|min:0',
                    'percentBuy' => 'nullable|numeric|min:0',
                    'percentSell' => 'nullable|numeric|min:0',
                    'ratingStocks' => 'nullable|numeric',
                    'stocksVn' => 'nullable|numeric|min:0',
                ]);
                // Kiểm tra code đã tồn tại chưa
                $stock = Stock::getByCode(strtoupper($code));
                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Mã code không tồn tại.'
                    ]);
                }
                // Mapping data vào model
                $stock->code = $validated['code'];
                $stock->current_price = $validated['currentPrice'];
                $stock->risk_level = $validated['risk'];
                $stock->price_avg = $validated['priceAvg'] ?? $stock->price_avg;
                $stock->recommended_buy_price = $validated['buyPrice'] ?? $stock->recommended_buy_price;
                $stock->recommended_sell_price = $validated['sellPrice'] ?? $stock->recommended_sell_price;
                $stock->percent_buy = $validated['percentBuy'] ?? $stock->percent_buy;
                $stock->percent_sell = $validated['percentSell'] ?? $stock->percent_sell;
                $stock->rating_stocks = $validated['ratingStocks'] ?? $stock->rating_stocks;
                $stock->stocks_vn = $validated['stocksVn'] ?? $stock->stocks_vn;
                // Lưu vào database
                $stock->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Update thành công.',
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            $stock = Stock::getByCode(strtoupper($code));
            return view('Admin.AdminUpdate', compact('stock'));
        }
    }

    public function delete($code)
    {
        Stock::deleteByCode(strtoupper($code));
        return redirect('/admin/stocks');
    }

    // ========== Quản lý cổ phiếu ==========

    public function stockManagement()
    {
        $stocks = Stock::getAllStocks();
        return view('Admin.AdminStockManagement', compact('stocks'));
    }

    public function exportStocksCsv()
    {
        $stocks = Stock::getAllStocks();
        $date = now()->format('Y-m-d');
        $fileName = "csv_{$date}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $columns = [
            'code',
            'prive_avg',
            'percent_buy',
            'percent_sell',
            'recommended_buy_price',
            'recommended_sell_price',
            'ratting_stocks',
            'risk_level',
        ];

        $callback = function () use ($stocks, $columns) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->code,
                    $stock->price_avg,
                    $stock->percent_buy,
                    $stock->percent_sell,
                    $stock->recommended_buy_price,
                    $stock->recommended_sell_price,
                    $stock->rating_stocks,
                    $stock->risk_level,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    public function importStocksCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (count($lines) < 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'File CSV phải có ít nhất 2 dòng (header + data).'
            ]);
        }

        // Validate header
$expectedHeaders = ['code', 'prive_avg', 'percent_buy', 'percent_sell', 'recommended_buy_price', 'recommended_sell_price', 'ratting_stocks', 'risk_level'];
        $actualHeaders = array_map('trim', array_map('strtolower', str_getcsv($lines[0])));

        if ($actualHeaders !== $expectedHeaders) {
            return response()->json([
                'status' => 'error',
                'message' => 'Header CSV không đúng cấu trúc.'
            ]);
        }

        $updated = 0;
        $created = 0;
        $errors = [];

        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (count($row) < 8) {
                $errors[] = "Dòng " . ($i + 1) . ": thiếu dữ liệu.";
                continue;
            }

            $code = strtoupper(trim($row[0], " '\"")); 
            if (empty($code)) {
                $errors[] = "Dòng " . ($i + 1) . ": mã cổ phiếu rỗng.";
                continue;
            }

            // Parse CSV values
            $priceAvg = is_numeric(trim($row[1])) ? floatval(trim($row[1])) : null;
            $percentBuy = is_numeric(trim($row[2])) ? floatval(trim($row[2])) : null;
            $percentSell = is_numeric(trim($row[3])) ? floatval(trim($row[3])) : null;
            $recommendedBuyPrice = is_numeric(trim($row[4])) ? floatval(trim($row[4])) : null;
            $recommendedSellPrice = is_numeric(trim($row[5])) ? floatval(trim($row[5])) : null;
            $ratingStocks = is_numeric(trim($row[6])) ? floatval(trim($row[6])) : null;
            $riskLevel = is_numeric(trim($row[7])) ? intval(trim($row[7])) : null;

            // Logic: nếu prive_avg khác rỗng thì tính recommended_buy/sell_price
            if ($priceAvg !== null && $priceAvg > 0) {
                if ($percentBuy !== null) {
                    $recommendedBuyPrice = $priceAvg * $percentBuy / 100;
                }
                if ($percentSell !== null) {
                    $recommendedSellPrice = $priceAvg * $percentSell / 100;
                }
            }

            try {
                $stock = Stock::getByCode($code);
                if ($stock) {
                    // Update existing
                    if ($priceAvg !== null) $stock->price_avg = $priceAvg;
                    if ($percentBuy !== null) $stock->percent_buy = $percentBuy;
                    if ($percentSell !== null) $stock->percent_sell = $percentSell;
                    if ($recommendedBuyPrice !== null) $stock->recommended_buy_price = $recommendedBuyPrice;
                    if ($recommendedSellPrice !== null) $stock->recommended_sell_price = $recommendedSellPrice;
                    if ($ratingStocks !== null) $stock->rating_stocks = $ratingStocks;
                    if ($riskLevel !== null) $stock->risk_level = $riskLevel;
                    $stock->save();
                    $updated++;
                } else {
                    // Create new
                    $stock = new Stock();
                    $stock->code = $code;
                    $stock->current_price = 0;
                    $stock->price_avg = $priceAvg ?? 0;
                    $stock->percent_buy = $percentBuy ?? 100;
                    $stock->percent_sell = $percentSell ?? 100;
                    $stock->recommended_buy_price = $recommendedBuyPrice ?? 0;
                    $stock->recommended_sell_price = $recommendedSellPrice ?? 0;
                    $stock->risk_level = $riskLevel ?? 1;
                    $stock->rating_stocks = $ratingStocks ?? 0;
                    $stock->save();
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = "Dòng " . ($i + 1) . " ($code): " . $e->getMessage();
            }
        }

        $details = "Cập nhật: $updated, Thêm mới: $created.";
        if (count($errors) > 0) {
            $details .= '<br>Lỗi:<br>' . implode('<br>', $errors);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Import CSV hoàn tất.',
            'details' => $details,
        ]);
    }

    public function stockInsert(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'currentPrice' => 'required|numeric|gt:0',
                    'priceAvg' => 'nullable|numeric|min:0',
                    'buyPrice' => 'nullable|numeric|min:0',
                    'sellPrice' => 'nullable|numeric|min:0',
                    'percentBuy' => 'nullable|numeric|min:0',
                    'percentSell' => 'nullable|numeric|min:0',
                    'risk' => 'required|integer|min:1|max:5',
                    'ratingStocks' => 'nullable|numeric|min:0|max:10',
                    'stocksVn' => 'nullable|numeric|min:0',
                ]);

                // Kiểm tra code đã tồn tại chưa
                if (Stock::getByCode($validated['code'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Mã code đã tồn tại.'
                    ]);
                }

                $stock = new Stock();
                $stock->code = strtoupper($validated['code']);
                $stock->current_price = $validated['currentPrice'];
                $stock->price_avg = $validated['priceAvg'] ?? null;
                $stock->recommended_buy_price = $validated['buyPrice'] ?? null;
                $stock->recommended_sell_price = $validated['sellPrice'] ?? null;
                $stock->percent_buy = $validated['percentBuy'] ?? 100.00;
                $stock->percent_sell = $validated['percentSell'] ?? 100.00;
                $stock->risk_level = $validated['risk'];
                $stock->rating_stocks = $validated['ratingStocks'] ?? 0;
                $stock->stocks_vn = $validated['stocksVn'] ?? 1000;
                $stock->volume = 0;
                $stock->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Thêm cổ phiếu thành công.',
                    'data' => $stock
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            return view('Admin.AdminStockInsert');
        }
    }
}
