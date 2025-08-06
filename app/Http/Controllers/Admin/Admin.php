<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\LOG;
use App\Services\EmailService;

class Admin extends Controller
{
    public function show()
    {
        $stocks = Stock::getAllStocks();
        return view('Admin.AdminView', compact('stocks'));
    }

    public function insert(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'buyPrice' => 'required|numeric',
                    'currentPrice' => 'required|numeric',
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
                    'buyPrice' => 'required|numeric',
                    'currentPrice' => 'required|numeric',
                    'risk' => 'required|integer|min:1|max:5',
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
                $stock->recommended_buy_price = $validated['buyPrice'];
                $stock->current_price = $validated['currentPrice'];
                $stock->risk_level = $validated['risk'];
                // Lưu vào database (ví dụ bảng stocks)
                $stock->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Update thành công.',
                    // 'data' => $stock
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
        return $this->show();
    }

    public function getNewPrice()
    {
        $stocks = Stock::getAllStocks();
        try {
            set_time_limit(0);
            foreach ($stocks as $stock) {
                $newPrice = $this->colect($stock->code);
                if (!is_numeric($newPrice)) {
                    LOG::error("Không thể lấy giá mới sau khi run getNewPrice với {$stock->code}");
                    continue;
                }
                $stock->current_price = $newPrice;
                $stock->save();
            }
            // Trả kết quả JSON
            return response()->json([
                'status' => 'success',
                'message' => 'Update thành công.',
                // 'data' => $stock
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getNewRisk()
    {
        $stocks = Stock::getAllStocks();
        try {
            set_time_limit(0);
            foreach ($stocks as $stock) {
                $newRisk = $this->colectRisk($stock->code);
                if (!is_numeric($newRisk)) {
                    LOG::error("Không thể lấy risk mới sau khi run getNewRisk với {$stock->code}");
                    continue;
                }
                if ($stock->risk_level != $newRisk) {
                    $result = EmailService::sendRiskChangeNotification($stock->code, $stock->risk_level, $newRisk);
                    LOG::error("Send mail: " . $result);
                    $stock->risk_level = $newRisk;
                    $stock->save();
                }
            }
            // Trả kết quả JSON
            return response()->json([
                'status' => 'success',
                'message' => 'Update thành công.',
                // 'data' => $stock
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function colectRisk($symbol)
    {
        $symbol = strtoupper($symbol);
        $scriptPath = base_path('node-scripts/cafef-risk.js');

        $data = null;
        $maxAttempts = 10; // giới hạn số lần thử để tránh vòng lặp vô hạn
        $attempt = 0;

        while (!is_numeric($data) && $attempt < $maxAttempts) {
            $output = shell_exec("node {$scriptPath} {$symbol}");
            $attempt++;
            if (!$output) {
                continue; // nếu lệnh node không chạy được thì thoát luôn
            }

            $data = json_decode($output, true);
        }

        return $data;
    }

    public function colect($symbol)
    {
        $symbol = strtoupper($symbol);
        $scriptPath = base_path('node-scripts/cafef-scraper.js');

        $finalPrice = null;
        $maxAttempts = 10; // tránh lặp vô hạn
        $attempt = 0;

        while (!is_numeric($finalPrice) && $attempt < $maxAttempts) {
            $output = shell_exec("node {$scriptPath} {$symbol}");
            $attempt++;
            if (!$output) {
                continue;
            }

            $data = json_decode($output, true);
            if (is_array($data)) {
                $price1 = $data['owner_priceClose_1'] ?? null;
                $price2 = $data['owner_priceClose_2'] ?? null;

                if ($price1 !== null && $price1 !== '0' && $price1 !== 0) {
                    $finalPrice = $price1;
                } else {
                    $finalPrice = $price2;
                }

                // Nhân 1000 nếu là số hợp lệ
                if (is_numeric($finalPrice)) {
                    $finalPrice = floatval($finalPrice) * 1000;
                    break;
                } else {
                    $finalPrice = null; // reset để tiếp tục lặp
                }
            }
        }

        return $finalPrice;
    }
}
