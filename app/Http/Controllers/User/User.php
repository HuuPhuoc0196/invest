<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\UserPortfolio;
use App\Models\UserPortfolioSell;
use Illuminate\Database\QueryException;

class User extends Controller
{
    public function show()
    {
        $stocks = Stock::getAllStocks();
        return view('User.UserView', compact('stocks'));
    }

    public function profile()
    {
        $userId = 1;
        $userPortfolios = UserPortfolio::getProfileUser($userId);

        return view('User.UserProfile', compact('userPortfolios'));
    }

    public function buy(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'buy_price' => 'required|numeric',
                    'quantity' => 'required|numeric',
                    'buy_date' => 'required|date|before_or_equal:today',
                ]);

                // Kiểm tra code đã tồn tại chưa
                $userPortfolio = new UserPortfolio();
                $stock = Stock::getByCode(strtoupper($validated['code']));

                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng liên hệ Admin insert Mã cổ phiếu ' . $validated['code'] . '.'
                    ]);
                }
                // Mapping data vào model
                $userPortfolio->user_id = 1;
                $userPortfolio->stock_id = $stock->id;
                $userPortfolio->buy_price = $validated['buy_price'];
                $userPortfolio->quantity = $validated['quantity'];
                $userPortfolio->buy_date = $validated['buy_date'];

                // Lưu vào database (ví dụ bảng stocks)
                $userPortfolio->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mua thành công.',
                    'data' => $userPortfolio
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            return view('User.UserBuy');
        }
    }

    public function sell(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'sell_price' => 'required|numeric',
                    'quantity' => 'required|numeric',
                    'sell_date' => 'required|date|before_or_equal:today',
                ]);
                $user_id = 1;

                // Kiểm tra code đã tồn tại chưa
                $stock = Stock::getByCode(strtoupper($validated['code']));
                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng liên hệ Admin insert Mã cổ phiếu ' . $validated['code'] . '.'
                    ]);
                }

                // Kiểm tra code đã tồn tại chưa
                $userPortfolio = UserPortfolio::getStockHolding($user_id, $stock->id);

                if (!$userPortfolio) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cổ phiếu ' . $validated['code'] . ' chưa được mua.'
                    ]);
                }

                // Kiểm tra quantity trước khi sell
                $quantity = $userPortfolio['total_quantity'];
                if ($quantity < $validated['quantity']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hiện tại: ' . $quantity . '. Số lượng cổ phiếu ' . $validated['code'] . ' không đủ.'
                    ]);
                }

                $userPortfolio_sell = new UserPortfolioSell();

                // Mapping data vào model
                $userPortfolio_sell->user_id = 1;
                $userPortfolio_sell->stock_id = $stock->id;
                $userPortfolio_sell->sell_price = $validated['sell_price'];
                $userPortfolio_sell->quantity = $validated['quantity'];
                $userPortfolio_sell->sell_date = $validated['sell_date'];

                // Lưu vào database (ví dụ bảng stocks)
                $userPortfolio_sell->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Bán thành công.',
                    'data' => $userPortfolio
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            return view('User.UserSell');
        }
    }
}
