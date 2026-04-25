<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\CashService;
use App\Services\FollowService;
use App\Services\PortfolioService;
use Illuminate\Http\Request;
use App\Models\User as UserModel;
use App\Models\Stock;
use App\Models\UserCashFollow;
use App\Models\UserFollow;
use App\Models\UserPortfolio;
use App\Models\UserPortfolioSell;
use App\Models\AdminSuggest;
use Illuminate\Database\QueryException;
use App\Http\Requests\UpdateInfoProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\BuyStockRequest;
use App\Http\Requests\SellStockRequest;
use App\Http\Requests\InsertFollowRequest;
use App\Http\Requests\AddFollowBatchRequest;
use App\Http\Requests\UpdateFollowRequest;
use App\Http\Requests\CashInRequest;
use App\Http\Requests\CashOutRequest;

class User extends Controller
{
    public function __construct(
        private AuthService      $authService,
        private FollowService    $followService,
        private PortfolioService $portfolioService,
        private CashService      $cashService,
    ) {}

    public function show()
    {
        if (auth()->check() && auth()->user()->role == 1) {
            return redirect('/admin');
        }

        $stocks = Stock::getAllStocks();
        $adminSuggestedStocks = AdminSuggest::query()
            ->join('stocks', 'admin_suggest.stock_id', '=', 'stocks.id')
            ->select('stocks.*')
            ->distinct()
            ->get();
        if (auth()->check()) {
            $userId            = auth()->id();
            $userPortfolios    = UserPortfolio::getProfileUser($userId);
            $userFollowList    = UserFollow::getUserFollow($userId);
            $userFollowedCodes = $userFollowList->pluck('code')->map(fn ($c) => strtoupper((string) $c))->values()->toArray();
        } else {
            $userPortfolios    = [];
            $userFollowedCodes = [];
        }
        return view('User.UserView', compact('stocks', 'userPortfolios', 'userFollowedCodes', 'adminSuggestedStocks'));
    }

    public function investmentPerformance()
    {
        $user_id            = auth()->id();
        $userPortfolios     = UserPortfolio::getPortfolioWithStockInfo($user_id);
        $userPortfoliosSell = UserPortfolioSell::getPortfolioSellWithStockInfo($user_id);
        $stocks             = $userPortfolios->merge($userPortfoliosSell);
        return view('User.UserInvestmentPerformance', compact('stocks'));
    }

    public function profile()
    {
        $userId         = auth()->id();
        $userPortfolios = UserPortfolio::getProfileUser($userId);
        $userInvestCash = $this->portfolioService->calcUserInvestCash($userId);
        return view('User.UserProfile', compact('userPortfolios', 'userInvestCash'));
    }

    public function infoProfile()
    {
        $userId = auth()->id();
        $user   = UserModel::getUserById($userId);
        if (!$user) {
            abort(404);
        }
        $userPortfolios = UserPortfolio::getPortfolioWithUserBuy($userId);
        return view('User.UserInfoProfile', compact('user', 'userPortfolios'));
    }

    public function follow()
    {
        $userId     = auth()->id();
        $userFollow = UserFollow::getUserFollow($userId);
        $stocks     = Stock::getAllStocks();
        return view('User.UserFollow', compact('stocks', 'userFollow'));
    }

    public function updateInfoProfile(UpdateInfoProfileRequest $request)
    {
        if ($request->isMethod('PUT')) {
            try {
                $result     = $this->authService->updateUserName(auth()->id(), $request->validated()['name']);
                $httpStatus = ($result['code'] ?? 200);
                unset($result['code']);
                return response()->json($result, $httpStatus);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        $user = UserModel::getUserById(auth()->id());
        if (!$user) {
            return redirect()->route('login')->with('error', 'Tài khoản không tồn tại.');
        }
        return view('User.UserUpdateInfoProfile', compact('user'));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        if ($request->isMethod('PUT')) {
            try {
                $validated  = $request->validated();
                $result     = $this->authService->changePassword(auth()->id(), $validated['password'], $validated['newPassword']);
                $httpStatus = ($result['code'] ?? 200);
                unset($result['code']);
                return response()->json($result, $httpStatus);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('User.UserChangePassword');
    }

    public function deleteFollow($code)
    {
        UserFollow::deleteByCodeAndUser(strtoupper($code), auth()->id());
        return $this->follow();
    }

    public function deleteFollowBatch(Request $request)
    {
        $codes = $request->input('codes', []);
        if (!is_array($codes) || empty($codes)) {
            return response()->json(['status' => 'error', 'message' => 'Không có mã nào được cung cấp.'], 422);
        }
        $deletedCount = UserFollow::deleteByCodesAndUser($codes, auth()->id());
        return response()->json([
            'status'        => 'success',
            'message'       => $deletedCount > 0 ? "Đã xoá {$deletedCount} mã theo dõi." : 'Không có mã nào được xoá.',
            'deleted_count' => $deletedCount,
        ]);
    }

    public function deleteAllFollow(Request $request)
    {
        $deletedCount = UserFollow::deleteAllByUserId(auth()->id());
        return response()->json([
            'status'        => 'success',
            'message'       => $deletedCount > 0 ? 'Đã xoá tất cả mã theo dõi.' : 'Không có mã theo dõi để xoá.',
            'deleted_count' => $deletedCount,
        ]);
    }

    public function buy(BuyStockRequest $request)
    {
        $user_id    = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            try {
                $result = $this->portfolioService->buyStock($request->validated(), $user_id);
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('User.UserBuy', [
            'cash'        => $cashFollow->cash ?? 0,
            'buyPriceMax' => UserPortfolio::BUY_PRICE_MAX,
            'quantityMax' => UserPortfolio::QUANTITY_MAX,
        ]);
    }

    public function sell(SellStockRequest $request)
    {
        $user_id    = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            try {
                $result = $this->portfolioService->sellStock($request->validated(), $user_id);
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('User.UserSell', [
            'userPortfolios' => UserPortfolio::getProfileUser($user_id),
            'cash'           => $cashFollow->cash ?? 0,
        ]);
    }

    public function insertFollow(InsertFollowRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->followService->insertFollow($request->validated(), auth()->id());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('User.UserInsertFollow');
    }

    public function addFollowBatch(AddFollowBatchRequest $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['status' => 'error', 'message' => 'Method not allowed.'], 405);
        }
        try {
            $result = $this->followService->addFollowBatch($request->validated(), auth()->id());
            return response()->json($result);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }

    public function updateFollow(UpdateFollowRequest $request, $code)
    {
        if ($request->isMethod('PUT')) {
            try {
                $result = $this->followService->updateFollow($request->validated(), auth()->id());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return redirect()->route('user.follow')->with('error', 'Mã cổ phiếu không tồn tại.');
        }
        return view('User.UserUpdateFollow', [
            'userFollow' => UserFollow::getCodeFromUserFollow($stock->id, auth()->id()),
        ]);
    }

    public function checkStockCode($code)
    {
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return response()->json(['status' => 'error', 'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' không tồn tại trong hệ thống.']);
        }
        $stockData = [
            'code'                   => $stock->code,
            'recommended_buy_price'  => $stock->recommended_buy_price,
            'current_price'          => $stock->current_price,
            'recommended_sell_price' => $stock->recommended_sell_price,
        ];
        $userFollowExist = UserFollow::getUserFollowFirst($stock->id, auth()->id());
        if ($userFollowExist) {
            return response()->json(['status' => 'warning', 'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' đã được theo dõi rồi.', 'data' => $stockData]);
        }
        return response()->json(['status' => 'success', 'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' hợp lệ.', 'data' => $stockData]);
    }

    public function validateStockCode($code)
    {
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return response()->json(['status' => 'error', 'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' không tồn tại trong hệ thống.']);
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Mã cổ phiếu ' . strtoupper($code) . ' hợp lệ.',
            'data'    => [
                'code'                   => $stock->code,
                'recommended_buy_price'  => $stock->recommended_buy_price,
                'current_price'          => $stock->current_price,
                'recommended_sell_price' => $stock->recommended_sell_price,
            ],
        ]);
    }

    public function cashIn(CashInRequest $request)
    {
        $user_id    = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            try {
                $result = $this->cashService->cashIn($request->validated(), $user_id);
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('User.UserCashIn', ['cash' => $cashFollow->cash ?? 0]);
    }

    public function cashOut(CashOutRequest $request)
    {
        $user_id    = auth()->id();
        $cashFollow = UserCashFollow::getCashFollow($user_id);
        if ($request->isMethod('post')) {
            try {
                $result = $this->cashService->cashOut($request->validated(), $user_id);
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('User.UserCashOut', ['cash' => $cashFollow->cash ?? 0]);
    }

    public function emailSettings()
    {
        $userId             = auth()->id();
        $noticesFollow      = UserFollow::getFollowNoticeByUser($userId);
        $sessionClosedItems = UserPortfolio::getSessionClosedByUser($userId);
        return view('User.UserEmailSettings', compact('noticesFollow', 'sessionClosedItems'));
    }

    public function saveSessionClosedFlags(Request $request)
    {
        $validated = $request->validate([
            'items'                    => ['required', 'array', 'max:200'],
            'items.*.stock_id'         => ['required', 'integer', 'min:1'],
            'items.*.session_closed_flag' => ['required', 'boolean'],
        ]);

        try {
            $this->portfolioService->saveSessionClosedFlags(auth()->id(), $validated['items']);
            return response()->json(['status' => 'success', 'message' => 'Lưu cài đặt thành công.']);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }

    public function saveEmailSettingsFollow(Request $request)
    {
        $validated = $request->validate([
            'items'                => ['required', 'array', 'max:200'],
            'items.*.id'           => ['required', 'integer', 'min:1'],
            'items.*.notice_buy'   => ['required', 'boolean'],
            'items.*.notice_sell'  => ['required', 'boolean'],
        ]);

        $userId = auth()->id();
        try {
            foreach ($validated['items'] as $item) {
                UserFollow::updateNoticeBuySell((int) $item['id'], $userId, $item['notice_buy'] ? 1 : 0, $item['notice_sell'] ? 1 : 0);
            }
            return response()->json(['status' => 'success', 'message' => 'Lưu cài đặt thành công.']);
        } catch (QueryException $e) {
            return $this->jsonServerError($e);
        }
    }
}
