<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

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
            foreach ($stocks as $stock) {
                $newPrice = $this->colect($stock->code);
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

    public function colect($symbol)
    {
        $symbol = strtoupper($symbol);
        $scriptPath = base_path('node-scripts/cafef-scraper.js');

        // Gọi lệnh node
        $output = shell_exec("node {$scriptPath} {$symbol}");
        if (!$output) {
            return response()->json(['error' => 'Không thể lấy dữ liệu'], 500);
        }
        $data = json_decode($output, true);
        // Kiểm tra dữ liệu
        if (is_array($data)) {
            // $ownerTime = $data['owner_time'] ?? null;
            $price1 = $data['owner_priceClose_1'] ?? null;
            $price2 = $data['owner_priceClose_2'] ?? null;

            $finalPrice = null;

            if ($price1 !== null && $price1 !== '0' && $price1 !== 0) {
                $finalPrice = $price1;
            } else {
                $finalPrice = $price2;
            }

            // Nhân 1000 (chuyển thành float trước để đảm bảo phép toán chính xác)
            $finalPrice = is_numeric($finalPrice) ? floatval($finalPrice) * 1000 : null;
            return $finalPrice;
        } else {
            // Thử gọi lại lần 2
            $output2 = shell_exec("node {$scriptPath} {$symbol}");
            $data2 = json_decode($output2, true);

            if (is_array($data2)) {
                // $ownerTime = $data2['owner_time'] ?? null;
                $price1 = $data2['owner_priceClose_1'] ?? null;
                $price2 = $data2['owner_priceClose_2'] ?? null;

                // Xử lý final_price
                $finalPrice = ($price1 !== null && $price1 !== '0' && $price1 !== 0) ? $price1 : $price2;
                $finalPrice = is_numeric($finalPrice) ? floatval($finalPrice) * 1000 : null;

                return $finalPrice;
            }
        }
    }
}
