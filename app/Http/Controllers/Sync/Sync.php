<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\LOG;
use App\Services\EmailService;
use Illuminate\Support\Facades\Http;

class Sync extends Controller
{
    public function getNewPrice()
    {
        $stocks = Stock::getAllStocks();
        try {
            set_time_limit(0);
            foreach ($stocks as $stock) {
                $newPrice = $this->colectPrice($stock->code);
                if (!is_numeric($newPrice)) {
                    LOG::error("Không thể lấy giá mới sau khi run getNewPrice với {$stock->code}");
                    continue;
                }
                $stock->current_price = $newPrice;
                $stock->save();
            }
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
            foreach ($stocks as $stock) {
                $newRisk = $this->colectRisk($stock->code);
                if (!is_numeric($newRisk)) {
                    LOG::error("Không thể lấy risk mới sau khi run getNewRisk với {$stock->code}");
                    continue;
                }
                if ($stock->risk_level != $newRisk) {
                    $result = EmailService::sendRiskChangeNotification($stock->code, $stock->risk_level, $newRisk);
                    LOG::error("Send mail: " . $result);
                    $stock->risk_level = $newRisk;
                    $stock->save();
                }
            }
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

    public function colectRisk($symbol)
    {

        $newRisk = null;
        $maxAttempts = 10; // giới hạn số lần thử để tránh vòng lặp vô hạn
        $attempt = 0;

        while (!is_numeric($newRisk) && $attempt < $maxAttempts) {
            $response = Http::timeout(60)->get("http://163.61.182.174:5000/getRiskFromHTML", [
                'symbol' => $symbol,
            ]);
            $attempt++;
            if (!$response->successful()) {
                continue;
            }
            $data = $response->json();
            $newRisk = floatval($data['risk_level']);
        }
        return $newRisk;
    }

    public function colectPrice($symbol)
    {
        $finalPrice = null;
        $maxAttempts = 10; // tránh lặp vô hạn
        $attempt = 0;

        while (!is_numeric($finalPrice) && $attempt < $maxAttempts) {
            $response = Http::timeout(60)->get("http://163.61.182.174:5000/getPriceFromHTML", [
                'symbol' => $symbol,
            ]);
            $attempt++;
            if (!$response->successful()) {
                continue;
            }

            $data = $response->json();
            $inner = $data['data'];
            if (isset($inner)) {
                $price1 = $inner['owner_priceClose_1'] ?? null;
                $price2 = $inner['owner_priceClose_2'] ?? null;

                if ($price1 !== null && $price1 !== '0' && $price1 !== 0) {
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
