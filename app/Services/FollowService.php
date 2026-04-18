<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\UserFollow;
use App\Services\CacheService;

class FollowService
{
    public function insertFollow(array $data, int $userId): array
    {
        $stock = Stock::getByCode(strtoupper($data['code']));
        if (!$stock) {
            return ['status' => 'error', 'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $data['code'] . '.'];
        }

        $userFollowExist = UserFollow::getUserFollowFirst($stock->id, $userId);
        if ($userFollowExist) {
            return ['status' => 'error', 'message' => 'Mã cổ phiếu ' . $data['code'] . ' đã được theo dõi.'];
        }

        $userFollow                   = new UserFollow();
        $userFollow->user_id          = $userId;
        $userFollow->stock_id         = $stock->id;
        $userFollow->follow_price_buy = !empty($data['followPriceBuy'])
            ? $data['followPriceBuy']
            : $stock->recommended_buy_price;
        $userFollow->follow_price_sell = !empty($data['followPriceSell'])
            ? $data['followPriceSell']
            : ($stock->recommended_sell_price ?? null);
        $userFollow->notice_flag      = 1;
        $userFollow->auto_sync        = 1;
        $userFollow->save();

        // Clear cache sau khi thêm follow
        CacheService::forgetMany([
            "user_follow_{$userId}",
            "user_follow_notice_{$userId}"
        ]);

        return ['status' => 'success', 'message' => 'Thêm theo dõi thành công.', 'data' => $userFollow];
    }

    public function addFollowBatch(array $data, int $userId): array
    {
        $added   = [];
        $skipped = [];
        $invalid = [];

        foreach ($data['codes'] as $code) {
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

            $userFollow                    = new UserFollow();
            $userFollow->user_id           = $userId;
            $userFollow->stock_id          = $stock->id;
            $userFollow->follow_price_buy  = $stock->recommended_buy_price ?? 0;
            $userFollow->follow_price_sell = $stock->recommended_sell_price ?? null;
            $userFollow->notice_flag       = 1;
            $userFollow->auto_sync         = 1;
            $userFollow->save();
            $added[] = $code;
        }

        // Clear cache sau khi thêm batch
        if (count($added) > 0) {
            CacheService::forgetMany([
                "user_follow_{$userId}",
                "user_follow_notice_{$userId}"
            ]);
        }

        $message = count($added) > 0
            ? 'Đã thêm ' . count($added) . ' mã vào danh mục theo dõi: ' . implode(', ', $added) . '.'
            : 'Không có mã nào được thêm.';

        if (count($skipped) > 0) {
            $message .= ' Đã theo dõi trước đó: ' . implode(', ', $skipped) . '.';
        }
        if (count($invalid) > 0) {
            $message .= ' Mã không tồn tại: ' . implode(', ', $invalid) . '.';
        }

        return [
            'status'  => 'success',
            'message' => $message,
            'added'   => $added,
            'skipped' => $skipped,
            'invalid' => $invalid,
        ];
    }

    public function updateFollow(array $data, int $userId): array
    {
        $stock = Stock::getByCode(strtoupper($data['code']));
        if (!$stock) {
            return ['status' => 'error', 'message' => 'Vui lòng liên hệ Admin thêm mã cổ phiếu ' . $data['code'] . '.'];
        }

        $userFollowExist = UserFollow::getUserFollowFirst($stock->id, $userId);
        if (!$userFollowExist) {
            return ['status' => 'error', 'message' => 'Mã cổ phiếu ' . $data['code'] . ' chưa được theo dõi.'];
        }

        $userFollowExist->follow_price_buy  = $data['followPriceBuy'];
        $userFollowExist->follow_price_sell = $data['followPriceSell'] ?? null;
        $userFollowExist->auto_sync         = (int) $data['autoSync'];
        $userFollowExist->save();

        // Clear cache sau khi update
        CacheService::forgetMany([
            "user_follow_{$userId}",
            "user_follow_notice_{$userId}"
        ]);

        return ['status' => 'success', 'message' => 'Update theo dõi thành công.', 'data' => $userFollowExist];
    }
}
