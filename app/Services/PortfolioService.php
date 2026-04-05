<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\UserCashFollow;
use App\Models\UserPortfolio;
use App\Models\UserPortfolioSell;

class PortfolioService
{
    public function buyStock(array $data, int $userId): array
    {
        $cashFollow = UserCashFollow::getCashFollow($userId);
        $cashBuy    = floatval($data['buy_price']) * floatval($data['quantity']);

        if ($cashFollow->cash < $cashBuy) {
            return ['status' => 'error', 'message' => 'Số dư không đủ'];
        }

        $stock = Stock::getByCode(strtoupper($data['code']));
        if (!$stock) {
            return ['status' => 'error', 'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $data['code'] . '.'];
        }

        $userPortfolio                       = new UserPortfolio();
        $userPortfolio->user_id              = $userId;
        $userPortfolio->stock_id             = $stock->id;
        $userPortfolio->buy_price            = $data['buy_price'];
        $userPortfolio->quantity             = $data['quantity'];
        $userPortfolio->buy_date             = $data['buy_date'];
        $userPortfolio->session_closed_flag  = 1;
        $userPortfolio->save();

        $cashFollow->cash -= $cashBuy;
        $cashFollow->save();

        return ['status' => 'success', 'message' => 'Mua thành công.', 'data' => $userPortfolio];
    }

    public function sellStock(array $data, int $userId): array
    {
        $cashFollow = UserCashFollow::getCashFollow($userId);
        $cashSell   = floatval($data['sell_price']) * floatval($data['quantity']);
        $cashFollow->cash += $cashSell;
        $cashFollow->save();

        $stock = Stock::getByCode(strtoupper($data['code']));
        if (!$stock) {
            return ['status' => 'error', 'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $data['code'] . '.'];
        }

        $userPortfolio = UserPortfolio::getStockHolding($userId, $stock->id);
        if (!$userPortfolio) {
            return ['status' => 'error', 'message' => 'Cổ phiếu ' . $data['code'] . ' chưa được mua.'];
        }

        $quantity = $userPortfolio['total_quantity'];
        if ($quantity < $data['quantity']) {
            return [
                'status'  => 'error',
                'message' => 'Hiện tại: ' . $quantity . '. Số lượng cổ phiếu ' . $data['code'] . ' không đủ.',
            ];
        }

        $userPortfolioSell            = new UserPortfolioSell();
        $userPortfolioSell->user_id   = $userId;
        $userPortfolioSell->stock_id  = $stock->id;
        $userPortfolioSell->sell_price = $data['sell_price'];
        $userPortfolioSell->quantity  = $data['quantity'];
        $userPortfolioSell->sell_date = $data['sell_date'];
        $userPortfolioSell->save();

        return ['status' => 'success', 'message' => 'Bán thành công.', 'data' => $userPortfolio];
    }

    /**
     * Lưu session_closed_flag hàng loạt cho user.
     */
    public function saveSessionClosedFlags(int $userId, array $items): void
    {
        foreach ($items as $item) {
            UserPortfolio::updateSessionClosedFlag(
                $userId,
                $item['stock_id'],
                $item['session_closed_flag'] ? 1 : 0
            );
        }
    }

    /**
     * Tính tổng tài sản hiện tại của user (tiền mặt + giá trị cổ phiếu).
     */
    public function calcUserInvestCash(int $userId): array
    {
        $userPortfolios = UserPortfolio::getProfileUser($userId);
        $cashFollow     = UserCashFollow::getCashFollow($userId);
        $cash           = $cashFollow->cash ?? 0;

        foreach ($userPortfolios as $item) {
            $cash += (int) $item['total_quantity'] * (float) $item['current_price'];
        }

        $cashIn = \App\Models\UserCashIn::getCashIn($userId);
        $cashOut = \App\Models\UserCashOut::getCashOut($userId);
        $cashInFinal = floatval($cashIn) - floatval($cashOut);

        return [
            'cash'    => $cash,
            'cash_in' => $cashInFinal,
        ];
    }
}
