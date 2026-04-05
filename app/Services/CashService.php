<?php

namespace App\Services;

use App\Models\UserCashFollow;
use App\Models\UserCashIn;
use App\Models\UserCashOut;

class CashService
{
    public function cashIn(array $data, int $userId): array
    {
        $cashFollow = UserCashFollow::getCashFollow($userId);
        $cashin     = $data['cashIn'];

        if ($cashFollow) {
            $cashFollow->cash += $cashin;
            $cashFollow->save();
        } else {
            $userCashFollow           = new UserCashFollow();
            $userCashFollow->user_id  = $userId;
            $userCashFollow->cash     = $cashin;
            $userCashFollow->save();
        }

        $userCashIn            = new UserCashIn();
        $userCashIn->user_id   = $userId;
        $userCashIn->cash_in   = $cashin;
        $userCashIn->cash_date = $data['cashDate'];
        $userCashIn->save();

        return ['status' => 'success', 'message' => 'Nap tiền thành công.', 'data' => $userCashIn];
    }

    public function cashOut(array $data, int $userId): array
    {
        $cashFollow = UserCashFollow::getCashFollow($userId);
        $cashout    = $data['cashOut'];

        if ($cashFollow) {
            if ($cashFollow->cash < $cashout) {
                return ['status' => 'error', 'message' => 'Số dư không đủ'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Bạn chưa nạp tiền vào tài khoản'];
        }

        $cashFollow->cash -= $cashout;
        $cashFollow->save();

        $userCashout            = new UserCashOut();
        $userCashout->user_id   = $userId;
        $userCashout->cash_out  = $cashout;
        $userCashout->cash_date = $data['cashDate'];
        $userCashout->save();

        return ['status' => 'success', 'message' => 'Rút tiền thành công.', 'data' => $userCashout];
    }
}
