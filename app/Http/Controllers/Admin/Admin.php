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
}
