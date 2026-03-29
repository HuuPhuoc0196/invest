<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User as UserModel;
use App\Models\Stock;
use App\Models\UserCashIn;
use App\Models\UserCashOut;
use App\Models\UserCashFollow;
use App\Models\UserFollow;
use App\Models\UserPortfolio;
use App\Models\UserPortfolioSell;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class User extends Controller
{
    public function show()
    {
        $stocks = Stock::getAllStocks();
        if (auth()->check()) {
            $userId = auth()->id();
            $userPortfolios = UserPortfolio::getProfileUser($userId);
            $userFollowList = UserFollow::getUserFollow($userId);
            $userFollowedCodes = $userFollowList->pluck('code')->map(fn ($c) => strtoupper((string) $c))->values()->toArray();
        } else {
            $userPortfolios = [];
            $userFollowedCodes = [];
        }
        return view('User.UserView', compact('stocks', 'userPortfolios', 'userFollowedCodes'));
    }

    public function investmentPerformance()
    {
        $user_id = auth()->id();
        $userPortfolios = UserPortfolio::getPortfolioWithStockInfo($user_id);
        $userPortfoliosSell = UserPortfolioSell::getPortfolioSellWithStockInfo($user_id);
        $stocks = $userPortfolios->merge($userPortfoliosSell);
        return view('User.UserInvestmentPerformance', compact('stocks'));
    }

    public function profile()
    {
        $userId = auth()->id();
        $userPortfolios = UserPortfolio::getProfileUser($userId);
        $cashFollow = UserCashFollow::getCashFollow($userId);
        $cashIn = UserCashIn::getCashIn($userId);
        $cashOut = UserCashOut::getCashOut($userId);

        $cashInFinal = floatval($cashIn) - floatval($cashOut);
        $cash = $cashFollow->cash ?? 0;

        foreach ($userPortfolios as $item) {
            $quantity = (int) $item['total_quantity'];
            $price = (float) $item['current_price']; // ép về số
            $cash += $quantity * $price;
        }

        $userInvestCash = [
            'cash' => $cash,
            'cash_in' => $cashInFinal
        ];

        return view('User.UserProfile', compact('userPortfolios', 'userInvestCash'));
    }

    public function infoProfile()
    {
        $userId = auth()->id();
        $user = UserModel::getUserById($userId);
        if (!$user) {
            abort(404);
        }
        $userPortfolios = UserPortfolio::getPortfolioWithUserBuy($userId);
        return view('User.UserInfoProfile', compact('user', 'userPortfolios'));
    }

    public function follow()
    {
        $userId = auth()->id();
        $userFollow = UserFollow::getUserFollow($userId);
        $stocks = Stock::getAllStocks();
        return view('User.UserFollow', compact('stocks', 'userFollow'));
    }

    public function updateInfoProfile(Request $request)
    {
        if ($request->isMethod('PUT')) {
            // Có dữ liệu
            try {
                // Validate dữ liệu
                $validated = $request->validate([
                    'name' => 'required|string|max:100',
                ]);

                $user = UserModel::getUserById(auth()->id());
                if (!$user) {
                    return response()->json(['status' => 'error', 'message' => 'Tài khoản không tồn tại.'], 404);
                }
                $user->name = trim($validated['name']);
                $user->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Cập nhật thông tin thành công.',
                    'data' => $user
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], 422);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                ], 500);
            }
        } else {
            $user = UserModel::getUserById(auth()->id());
            if (!$user) {
                return redirect()->route('login')->with('error', 'Tài khoản không tồn tại.');
            }
            return view('User.UserUpdateInfoProfile', compact('user'));
        }
    }

    public function changePassword(Request $request)
    {
        if ($request->isMethod('PUT')) {
            // Có dữ liệu
            try {
                // Validate dữ liệu
                $validated = $request->validate([
                    'password' => 'required|string|min:6',
                    'newPassword' => 'required|string|min:6',
                ]);


                $user = UserModel::getUserById(auth()->id());
                if (!$user) {
                    return response()->json(['status' => 'error', 'message' => 'Tài khoản không tồn tại.'], 404);
                }
                // Kiểm tra user đã tồn tại chưa
                $existingUser = UserModel::getUserLogin($user->email, $validated['password']);
                if (!$existingUser) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Mật khẩu không đúng.'
                    ]);
                }

                $user->password = Hash::make($validated['newPassword']);
                $user->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Đổi mật khẩu thành công.',
                    'data' => $user
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], 422);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                ], 500);
            }
        } else {
            return view('User.UserChangePassword');
        }
    }

    public function deleteFollow($code)
    {
        UserFollow::deleteByCodeAndUser(strtoupper($code), auth()->id());
        return $this->follow();
    }

    public function deleteAllFollow(Request $request)
    {
        $userId = auth()->id();
        $deletedCount = UserFollow::deleteAllByUserId($userId);

        return response()->json([
            'status' => 'success',
            'message' => $deletedCount > 0
                ? 'Đã xoá tất cả mã theo dõi.'
                : 'Không có mã theo dõi để xoá.',
            'deleted_count' => $deletedCount,
        ]);
    }

    // public function deleteUserProfileCode($code)
    // {
    //     $stock = Stock::where('code', $code)->first();
    //     if(!empty($stock)){
    //         $user_id = auth()->id();
    //         $listUserProfile = UserPortfolio::getProfileUser($user_id);
    //         $cashFollow = UserCashFollow::getCashFollow($user_id);
    //         foreach ($listUserProfile as $item) {
    //             $cashBuy = floatval($item["total_quantity"]) * floatval($item["avg_buy_price"]);
    //             $cashFollow->cash += $cashBuy;
    //         }
    //         $cashFollow->save();
    //         $deleteUserProfile = UserPortfolio::deleteUserInfo($stock->id, $user_id);
    //         if ($deleteUserProfile) {
    //             $listUserProfileSell = UserPortfolioSell::getAllPortfolioSellByUserId($user_id);
    //             foreach ($listUserProfileSell as $item) {
    //                 $cashBuy = floatval($item->sell_price) * floatval($item->quantity);
    //                 $cashFollow->cash -= $cashBuy;
    //             }
    //             $cashFollow->save();
    //             UserPortfolioSell::deleteUserInfo($stock->id,$user_id);
    //         }
    //     }
    //     return $this->infoProfile();
    // }

    public function buy(Request $request)
    {
        $user_id = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            try {
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'buy_price' => 'required|numeric|gt:0|max:' . UserPortfolio::BUY_PRICE_MAX,
                    'quantity' => 'required|integer|min:1|max:' . UserPortfolio::QUANTITY_MAX,
                    'buy_date' => 'required|date|before_or_equal:today',
                ], [
                    'buy_price.max' => 'Giá mua không hợp lệ!',
                    'buy_price.numeric' => 'Giá mua không hợp lệ!',
                    'quantity.max' => 'Khối lượng giao dịch không hợp lệ!',
                    'quantity.integer' => 'Khối lượng giao dịch không hợp lệ!',
                    'quantity.min' => 'Khối lượng giao dịch không hợp lệ!',
                ]);

                $cashBuy = floatval($validated['buy_price']) * floatval($validated['quantity']);
                if($cashFollow->cash < $cashBuy){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Số dư không đủ',
                    ], 400);
                }

                // Kiểm tra code đã tồn tại chưa
                $userPortfolio = new UserPortfolio();
                $stock = Stock::getByCode(strtoupper($validated['code']));

                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $validated['code'] . '.'
                    ]);
                }
                // Mapping data vào model
                $userPortfolio->user_id = auth()->id();
                $userPortfolio->stock_id = $stock->id;
                $userPortfolio->buy_price = $validated['buy_price'];
                $userPortfolio->quantity = $validated['quantity'];
                $userPortfolio->buy_date = $validated['buy_date'];
                $userPortfolio->session_closed_flag = 1;

                // Lưu vào database (ví dụ bảng stocks)
                $userPortfolio->save();

                $cashFollow->cash -= $cashBuy;
                $cashFollow->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mua thành công.',
                    'data' => $userPortfolio
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => collect($e->errors())->flatten()->first() ?? 'Dữ liệu không hợp lệ.',
                ], 422);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            $cash = $cashFollow->cash ?? 0;

            return view('User.UserBuy', [
                'cash' => $cash,
                'buyPriceMax' => UserPortfolio::BUY_PRICE_MAX,
                'quantityMax' => UserPortfolio::QUANTITY_MAX,
            ]);
        }
    }

    public function sell(Request $request)
    {
        $user_id = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'sell_price' => 'required|numeric|gt:0',
                    'quantity' => 'required|numeric|gt:0',
                    'sell_date' => 'required|date|before_or_equal:today',
                ]);
                $cashSell = floatval($validated['sell_price']) * floatval($validated['quantity']);
                $cashFollow->cash += $cashSell;
                $cashFollow->save();

                // Kiểm tra code đã tồn tại chưa
                $stock = Stock::getByCode(strtoupper($validated['code']));
                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $validated['code'] . '.'
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
                $userPortfolio_sell->user_id = auth()->id();
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
            $cash = $cashFollow->cash ?? 0;
            $userId = auth()->id();
            $userPortfolios = UserPortfolio::getProfileUser($userId);
            return view('User.UserSell', compact('userPortfolios', 'cash'));
        }
    }

    public function insertFollow(Request $request)
    {
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'followPriceBuy' => 'nullable|numeric|gt:0',
                    'followPriceSell' => 'nullable|numeric|gt:0',
                ]);

                // Kiểm tra code đã tồn tại chưa
                $userFollow = new UserFollow();
                $stock = Stock::getByCode(strtoupper($validated['code']));

                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $validated['code'] . '.'
                    ]);
                }

                $userFollowExit = UserFollow::getUserFollowFirst($stock->id, auth()->id());

                if ($userFollowExit) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Mã cổ phiếu ' . $validated['code'] . ' đã được theo dõi.'
                    ]);
                }

                // Mapping data vào model
                $userFollow->user_id = auth()->id();
                $userFollow->stock_id = $stock->id;
                $followPriceBuy = !empty($validated['followPriceBuy']) ? $validated['followPriceBuy'] : $stock->recommended_buy_price;
                $followPriceSell = !empty($validated['followPriceSell']) ? $validated['followPriceSell'] : ($stock->recommended_sell_price ?? null);
                $userFollow->follow_price_buy = $followPriceBuy;
                $userFollow->follow_price_sell = $followPriceSell;
                $userFollow->notice_flag = 1;
                $userFollow->auto_sync = 1;

                // Lưu vào database
                $userFollow->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Thêm theo dõi thành công.',
                    'data' => $userFollow
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            return view('User.UserInsertFollow');
        }
    }

    public function addFollowBatch(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['status' => 'error', 'message' => 'Method not allowed.'], 405);
        }

        try {
            $validated = $request->validate([
                'codes' => 'required|array',
                'codes.*' => 'required|string|max:10',
            ]);

            $userId = auth()->id();
            $added = [];
            $skipped = [];
            $invalid = [];

            foreach ($validated['codes'] as $code) {
                $code = strtoupper(trim($code));
                if ($code === '') {
                    continue;
                }

                $stock = Stock::getByCode($code);
                if (!$stock) {
                    $invalid[] = $code;
                    continue;
                }

                $existing = UserFollow::getUserFollowFirst($stock->id, $userId);
                if ($existing) {
                    $skipped[] = $code;
                    continue;
                }

                $userFollow = new UserFollow();
                $userFollow->user_id = $userId;
                $userFollow->stock_id = $stock->id;
                $userFollow->follow_price_buy = $stock->recommended_buy_price ?? 0;
                $userFollow->follow_price_sell = $stock->recommended_sell_price ?? null;
                $userFollow->notice_flag = 1;
                $userFollow->auto_sync = 1;
                $userFollow->save();
                $added[] = $code;
            }

            $message = count($added) > 0
                ? 'Đã thêm theo dõi ' . count($added) . ' mã: ' . implode(', ', $added) . '.'
                : 'Không có mã nào được thêm.';

            if (count($skipped) > 0) {
                $message .= ' Đã theo dõi trước đó: ' . implode(', ', $skipped) . '.';
            }
            if (count($invalid) > 0) {
                $message .= ' Mã không tồn tại: ' . implode(', ', $invalid) . '.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'added' => $added,
                'skipped' => $skipped,
                'invalid' => $invalid,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateFollow(Request $request, $code)
    {
        if ($request->isMethod('PUT')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'code' => 'required|string|max:10',
                    'followPriceBuy' => 'required|numeric|gt:0',
                    'followPriceSell' => 'nullable|numeric|gt:0',
                    'autoSync' => 'required|in:0,1',
                ]);
                // Kiểm tra code đã tồn tại chưa
                $stock = Stock::getByCode(strtoupper($validated['code']));

                if (!$stock) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $validated['code'] . '.'
                    ]);
                }

                $userFollowExit = UserFollow::getUserFollowFirst($stock->id, auth()->id());

                if (!$userFollowExit) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Mã cổ phiếu ' . $validated['code'] . ' chưa được theo dõi.'
                    ]);
                }

                // Mapping data vào model
                $userFollowExit->follow_price_buy = $validated['followPriceBuy'];
                $userFollowExit->follow_price_sell = $validated['followPriceSell'] ?? null;
                $userFollowExit->auto_sync = (int) $validated['autoSync'];

                // Lưu vào database
                $userFollowExit->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Update theo dõi thành công.',
                    'data' => $userFollowExit
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], 422);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            $stock = Stock::getByCode(strtoupper($code));
            if (!$stock) {
                return redirect()->route('user.follow')->with('error', 'Mã cổ phiếu không tồn tại.');
            }
            $userFollow = UserFollow::getCodeFromUserFollow($stock->id, auth()->id());
            return view('User.UserUpdateFollow', compact('userFollow'));
        }
    }

    public function checkStockCode($code)
    {
        $stock = Stock::getByCode(strtoupper($code));
        if ($stock) {
            // Kiểm tra user đã follow mã này chưa
            $userFollowExist = UserFollow::getUserFollowFirst($stock->id, auth()->id());
            if ($userFollowExist) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' đã được theo dõi rồi.',
                    'data' => [
                        'code' => $stock->code,
                        'recommended_buy_price' => $stock->recommended_buy_price,
                        'current_price' => $stock->current_price,
                        'recommended_sell_price' => $stock->recommended_sell_price,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' hợp lệ.',
                'data' => [
                    'code' => $stock->code,
                    'recommended_buy_price' => $stock->recommended_buy_price,
                    'current_price' => $stock->current_price,
                    'recommended_sell_price' => $stock->recommended_sell_price,
                ]
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' không tồn tại trong hệ thống.'
        ]);
    }

    /**
     * Chỉ kiểm tra mã có trong bảng stocks (read-only). Dùng cho mua/bán, không liên quan theo dõi.
     */
    public function validateStockCode($code)
    {
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' không tồn tại trong hệ thống.',
            ]);
        }

        $upper = strtoupper($code);

        return response()->json([
            'status' => 'success',
            'message' => 'Mã cổ phiếu ' . $upper . ' hợp lệ.',
            'data' => [
                'code' => $stock->code,
                'recommended_buy_price' => $stock->recommended_buy_price,
                'current_price' => $stock->current_price,
                'recommended_sell_price' => $stock->recommended_sell_price,
            ],
        ]);
    }

    public function getRiskLevel($code)
    {
        $stock = Stock::getRiskLevelFromCode($code);
        return view('User.ShowRiskLevel', compact('stock'));
    }

    public function cashIn(Request $request)
    {
        $user_id = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'cashIn' => 'required|numeric|gt:0',
                    'cashDate' => 'required|date|before_or_equal:today',
                ]);
                $user_id = auth()->id();
                $cashFollow = UserCashFollow::getCashFollow($user_id);
                $cashin = $validated['cashIn'];
                if ($cashFollow) {
                    // Nếu tồn tại → cộng thêm cash_in
                    $cashFollow->cash += $cashin;
                    $cashFollow->save();
                } else {
                    $userCashFollow = new UserCashFollow();
                    $userCashFollow->user_id = $user_id;
                    $userCashFollow->cash = $cashin;
                    $userCashFollow->save();
                }
                // Mapping data vào model
                $userCashIn = new UserCashIn();
                $userCashIn->user_id = $user_id;
                $userCashIn->cash_in = $cashin;
                $userCashIn->cash_date = $validated['cashDate'];

                // Lưu vào database 
                $userCashIn->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Nap tiền thành công.',
                    'data' => $userCashIn
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            $cash = $cashFollow->cash ?? 0;
            return view('User.UserCashIn',compact('cash'));
        }
    }

    public function cashOut(Request $request)
    {
        $user_id = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            // Có dữ liệu
            try {
                // Validation dữ liệu
                $validated = $request->validate([
                    'cashOut' => 'required|numeric|gt:0',
                    'cashDate' => 'required|date|before_or_equal:today',
                ]);
                $cashout = $validated['cashOut'];
                if ($cashFollow) {
                    if($cashFollow->cash < $cashout){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Số dư không đủ',
                        ], 400);
                    }
                }else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Bạn chưa nạp tiền vào tài khoản',
                    ], 400);
                }
                
                $cashFollow->cash -= $cashout;
                $cashFollow->save();
                // Mapping data vào model
                $userCashout = new UserCashOut();
                $userCashout->user_id = auth()->id();
                $userCashout->cash_out = $cashout;
                $userCashout->cash_date = $validated['cashDate'];

                // Lưu vào database 
                $userCashout->save();

                // Trả kết quả JSON
                return response()->json([
                    'status' => 'success',
                    'message' => 'Rút tiền thành công.',
                    'data' => $userCashout
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            $cash = $cashFollow->cash ?? 0;
            return view('User.UserCashOut',compact('cash'));
        }
    }

    public function emailSettings()
    {
        $userId = auth()->id();
        $noticesFollow = UserFollow::getFollowNoticeByUser($userId);
        $sessionClosedItems = UserPortfolio::getSessionClosedByUser($userId);
        return view('User.UserEmailSettings', compact('noticesFollow', 'sessionClosedItems'));
    }

    public function saveSessionClosedFlags(Request $request)
    {
        $userId = auth()->id();
        $items = $request->input('items', []);

        try {
            foreach ($items as $item) {
                UserPortfolio::updateSessionClosedFlag(
                    $userId,
                    $item['stock_id'],
                    $item['session_closed_flag'] ? 1 : 0
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Lưu cài đặt thành công.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function saveEmailSettingsFollow(Request $request)
    {
        $userId = auth()->id();
        $items = $request->input('items', []);

        try {
            foreach ($items as $item) {
                UserFollow::where('id', $item['id'])
                    ->where('user_id', $userId)
                    ->update(['notice_flag' => $item['notice_flag'] ? 1 : 0]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Lưu cài đặt thành công.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
