<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StatusSync;
use App\Models\UserPortfolio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService;

class SyncService
{
    public function syncRiskForCode(string $code): array
    {
        $stock = Stock::getByCode($code);
        if (!$stock) {
            return ['status' => 'error', 'message' => 'Mã ' . $code . ' không tồn tại.'];
        }

        Log::info("Start cập nhật mức độ rủi ro của cổ phiếu: " . $code);

        $statusSync = StatusSync::getStatusSync();
        $statusSync->status_sync_risk = 1;
        $statusSync->save();
        CacheService::forget('status_sync');

        $newRisk = $this->collectRisk($stock->code);
        Log::info("Call api mức độ rủi ro của mã chứng khoán {$stock->code} từ {$stock->risk_level} thành {$newRisk}");

        if (!is_numeric($newRisk)) {
            Log::error("Không thể lấy risk mới sau khi run getNewRisk với {$stock->code}");
            $statusSync->status_sync_risk = 0;
            $statusSync->save();
            CacheService::forget('status_sync');
            return ['status' => 'error', 'message' => 'Cập nhật rủi ro ' . $stock->code . ' thất bại.'];
        }

        if ($stock->risk_level != $newRisk) {
            $result = EmailService::sendRiskChangeNotification($stock->code, $stock->risk_level, $newRisk);
            Log::info("Send mail: " . $result);
            Log::info("cập nhật mức độ rủi ro của mã chứng khoán {$stock->code} từ {$stock->risk_level} thành {$newRisk}");
            $stock->risk_level = $newRisk;
            $stock->save();
        }

        $statusSync->status_sync_risk = 0;
        $statusSync->save();
        CacheService::forget('status_sync');
        Log::info("End cập nhật mức độ rủi ro của cổ phiếu");

        return ['status' => 'success', 'message' => 'Cập nhật rủi ro ' . $stock->code . ' thành công.'];
    }

    public function followStocksEveryDay(): array
    {
        $listUserProfile = UserPortfolio::getProfileUser(1);
        foreach ($listUserProfile as $item) {
            $stock  = Stock::getByCode($item['code']);
            $result = EmailService::sendFollowStocksEveryDay($stock, $item['avg_buy_price']);
            Log::info("Send mail: " . $result);
        }
        return ['status' => 'success', 'message' => 'Send mail follow every day thành công.'];
    }

    public function addStocksFollowFromFile(string $content): array
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode(':', $line);
            if (count($parts) == 2) {
                $code        = trim($parts[0]);
                $priceInvest = (int) trim($parts[1]) * 1000;
                $stock       = Stock::getByCode(strtoupper($code));
                if (!$stock) {
                    $stock                        = new Stock();
                    $stock->code                  = strtoupper($code);
                    $stock->recommended_buy_price = $priceInvest;
                    $stock->current_price         = $priceInvest;
                    $stock->risk_level            = 4;
                    $stock->save();
                } else {
                    $stock->recommended_buy_price = $priceInvest;
                    $stock->save();
                }
            }
        }

        CacheService::clearTableCache('stocks');

        return ['status' => 'success', 'message' => 'Upload thành công.'];
    }

    public function collectRisk(string $symbol): mixed
    {
        $newRisk     = null;
        $maxAttempts = 5;
        $attempt     = 0;
        $data        = null;

        while (!is_numeric($data) && $attempt < $maxAttempts) {
            try {
                $attempt++;
                $baseUrl  = config('services.sync.base_url');
                $response = Http::timeout(120)->get($baseUrl . "/getRiskFromHTML", ['symbol' => $symbol]);
                Log::info($response);
                sleep(1);
            } catch (\Exception $e) {
                Log::error("Request error collectRisk for symbol {$symbol}: " . $e->getMessage());
                continue;
            }
            $data    = $response->json();
            $newRisk = floatval($data);
        }
        return $newRisk;
    }

}
