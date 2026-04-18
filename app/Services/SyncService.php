<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StatusSync;
use App\Models\UserFollow;
use App\Models\UserPortfolio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService;

class SyncService
{
    public function syncNewPrice(): array
    {
        $stocks     = Stock::getAllStocks();
        $statusSync = StatusSync::getStatusSync();

        Log::info("Start cập nhật giá của cổ phiếu");
        Log::info("Tổng số lượng cổ phiếu cần cập nhật là: " . $stocks->count());

        $statusSync->status_sync_price = 1;
        $statusSync->save();
        CacheService::forget('status_sync');

        foreach ($stocks as $stock) {
            $newPrice = $this->collectPrice($stock->code);
            Log::info("Call api lấy giá của mã chứng khoán {$stock->code} từ {$stock->current_price} thành " . round($newPrice, 2));
            if (!is_numeric($newPrice)) {
                Log::error("Không thể lấy giá mới sau khi run getNewPrice với {$stock->code}");
                continue;
            }
            if ($stock->current_price != $newPrice) {
                $stock->current_price = $newPrice;
                $stock->save();
            }
        }

        $statusSync->status_sync_price = 0;
        $statusSync->save();
        CacheService::forget('status_sync');
        Log::info("End cập nhật giá cổ phiếu");

        return ['status' => 'success', 'message' => 'Update thành công.'];
    }

    public function syncNewRisk(): array
    {
        $stocks     = Stock::getAllStocks();
        $statusSync = StatusSync::getStatusSync();

        Log::info("Start cập nhật mức độ rủi ro của cổ phiếu");
        Log::info("Tổng số lượng cổ phiếu cần cập nhật là: " . $stocks->count());

        $statusSync->status_sync_risk = 1;
        $statusSync->save();
        CacheService::forget('status_sync');

        foreach ($stocks as $stock) {
            $newRisk = $this->collectRisk($stock->code);
            Log::info("Call api mức độ rủi ro của mã chứng khoán {$stock->code} từ {$stock->risk_level} thành {$newRisk}");
            if (!is_numeric($newRisk)) {
                Log::error("Không thể lấy risk mới sau khi run getNewRisk với {$stock->code}");
                continue;
            }
            if ($stock->risk_level != $newRisk) {
                $result = EmailService::sendRiskChangeNotification($stock->code, $stock->risk_level, $newRisk);
                Log::info("Send mail: " . $result);
                Log::info("cập nhật mức độ rủi ro của mã chứng khoán {$stock->code} từ {$stock->risk_level} thành {$newRisk}");
                $stock->risk_level = $newRisk;
                $stock->save();
            }
        }

        $statusSync->status_sync_risk = 0;
        $statusSync->save();
        CacheService::forget('status_sync');
        Log::info("End cập nhật mức độ rủi ro của cổ phiếu");

        return ['status' => 'success', 'message' => 'Update thành công.'];
    }

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

    public function suggestInvestment(): array
    {
        $stocks = Stock::getAllStocks();
        foreach ($stocks as $stock) {
            $result = EmailService::sendSuggestInvestment(
                $stock->code,
                $stock->current_price,
                $stock->recommended_buy_price,
                $stock->risk_level
            );
            if ($result) {
                Log::info("Send mail Suggest cổ phiếu: " . $stock->code);
                Log::info("Có giá hiện tại là: {$stock->current_price} và Giá đề xuất là {$stock->recommended_buy_price}");
            }
        }
        return ['status' => 'success', 'message' => 'Suggest Price Code Success.'];
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

    public function collectPrice(string $symbol): mixed
    {
        $finalPrice  = null;
        $maxAttempts = 5;
        $attempt     = 0;

        while (!is_numeric($finalPrice) && $attempt < $maxAttempts) {
            try {
                $attempt++;
                $baseUrl  = config('services.sync.base_url');
                $response = Http::timeout(120)->get($baseUrl . "/getPriceFromHTML", ['symbol' => $symbol]);
                Log::info($response);
                sleep(1);
            } catch (\Exception $e) {
                Log::error("Request error collectPrice for symbol {$symbol}: " . $e->getMessage());
                continue;
            }

            $data = $response->json();
            if (isset($data)) {
                $price1 = $data['owner_priceClose_1'] ?? null;
                $price2 = $data['owner_priceClose_2'] ?? null;

                if ($price1 != null && $price1 != '0' && $price1 != 0 && $price1 != '--') {
                    $finalPrice = $price1;
                } else {
                    $finalPrice = $price2;
                }

                if (is_numeric($finalPrice)) {
                    $finalPrice = floatval($finalPrice) * 1000;
                    break;
                } else {
                    $finalPrice = null;
                }
            }
        }
        return $finalPrice;
    }
}
