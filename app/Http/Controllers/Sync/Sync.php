<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StatusSync;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use App\Services\EmailService;
use Illuminate\Support\Facades\Http;

class Sync extends Controller
{
    public function getNewPrice()
    {
        $stocks = Stock::getAllStocks();
        try {
            set_time_limit(0);
            $statusSync = StatusSync::getStatusSync();
            $statusSync->status_sync_price = 1;
            $statusSync->save();
            foreach ($stocks as $stock) {
                $newPrice = $this->colectPrice($stock->code);
                Log::info("Call lấy giá của mã chứng khoán {$stock->code} từ {$stock->current_price} thành " . round($newPrice, 2));
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

    public function getNewRisk()
    {
        $stocks = Stock::getAllStocks();
        try {
            set_time_limit(0);
            $statusSync = StatusSync::getStatusSync();
            $statusSync->status_sync_risk = 1;
            $statusSync->save();
            foreach ($stocks as $stock) {
                $newRisk = $this->colectRisk($stock->code);
                Log::info("Call mức độ rủi ro của mã chứng khoán {$stock->code} từ {$stock->risk_level} thành {$newRisk}");
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

    public function suggestInvestment()
    {
        $stocks = Stock::getAllStocks();
        try {
            foreach ($stocks as $stock) {
                $recommended_buy_price = $stock->recommended_buy_price;
                $current_price = $stock->current_price;
                $result = EmailService::sendSuggestInvestment($stock->code, $current_price, $recommended_buy_price);
                if ($result) {
                    sleep(1);
                    Log::info("Send mail Suggest cổ phiếu: " . $stock->code);
                    Log::info("Có giá hiện tại là: {$current_price} và Giá đề xuất là {$recommended_buy_price}");
                }
            }
            // Trả kết quả JSON
            return response()->json([
                'status' => 'success',
                'message' => 'Suggest Price Code Success.',
                // 'data' => $stock
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function colectRisk($symbol)
    {

        $newRisk = null;
        $maxAttempts = 5; // giới hạn số lần thử để tránh vòng lặp vô hạn
        $attempt = 0;

        while (!is_numeric($newRisk) && $attempt < $maxAttempts) {
            try {
                $attempt++;
                // $response = Http::timeout(120)->get("http://127.0.0.1:5000/getRiskFromHTML", [
                //     'symbol' => $symbol,
                // ]);
                $response = Http::timeout(120)->get("http://163.61.182.174/getRiskFromHTML", [
                    'symbol' => $symbol,
                ]);
                sleep(1);
            } catch (\Exception $e) {
                Log::error("Request error colectRisk for symbol {$symbol}: " . $e->getMessage());
                continue;
            }
            $data = $response->json();
            $newRisk = floatval($data);
        }
        return $newRisk;
    }

    public function colectPrice($symbol)
    {
        $finalPrice = null;
        $maxAttempts = 5; // tránh lặp vô hạn
        $attempt = 0;

        while (!is_numeric($finalPrice) && $attempt < $maxAttempts) {
            try {
                $attempt++;
                // $response = Http::timeout(120)->get("http://127.0.0.1:5000/getPriceFromHTML", [
                //     'symbol' => $symbol,
                // ]);
                $response = Http::timeout(120)->get("http://163.61.182.174/getPriceFromHTML", [
                    'symbol' => $symbol,
                ]);
                sleep(1);
            } catch (\Exception $e) {
                Log::error("Request error colectPrice for symbol {$symbol}: " . $e->getMessage());
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

                // Nhân 1000 nếu là số hợp lệ
                if (is_numeric($finalPrice)) {
                    $finalPrice = floatval($finalPrice) * 1000;
                    break;
                } else {
                    $finalPrice = null; // reset để tiếp tục lặp
                }
            }
        }
        return $finalPrice;
    }
}
