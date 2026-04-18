<?php

namespace App\Services;

use App\Models\Stock;
use App\Services\CacheService;

class StockService
{
    public function insertStockBasic(array $data): array
    {
        if (Stock::getByCode($data['code'])) {
            return ['status' => 'error', 'message' => 'Mã cổ phiếu đã tồn tại.'];
        }
        $stock                        = new Stock();
        $stock->code                  = $data['code'];
        $stock->recommended_buy_price = $data['buyPrice'];
        $stock->current_price         = $data['currentPrice'];
        $stock->risk_level            = $data['risk'];
        $stock->save();
        
        // Clear cache sau khi insert
        CacheService::clearTableCache('stocks');
        
        return ['status' => 'success', 'message' => 'Insert thành công.', 'data' => $stock];
    }

    public function insertStock(array $data): array
    {
        if (Stock::getByCode($data['code'])) {
            return ['status' => 'error', 'message' => 'Mã cổ phiếu đã tồn tại.'];
        }

        $stock                         = new Stock();
        $stock->code                   = strtoupper($data['code']);
        $stock->current_price          = $data['currentPrice'];
        $stock->price_avg              = $data['priceAvg'] ?? null;
        $stock->recommended_buy_price  = $data['buyPrice'] ?? null;
        $stock->recommended_sell_price = $data['sellPrice'] ?? null;
        $stock->percent_buy            = $data['percentBuy'] ?? 100.00;
        $stock->percent_sell           = $data['percentSell'] ?? 100.00;
        $stock->risk_level             = $data['risk'];
        $stock->rating_stocks          = $data['ratingStocks'] ?? 0;
        $stock->stocks_vn              = $data['stocksVn'] ?? 1000;
        $stock->volume                 = 0;
        $stock->save();

        // Clear cache sau khi insert
        CacheService::clearTableCache('stocks');

        return ['status' => 'success', 'message' => 'Thêm cổ phiếu thành công.', 'data' => $stock];
    }

    public function updateStock(string $code, array $data): array
    {
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return ['status' => 'error', 'message' => 'Mã code không tồn tại.'];
        }

        $stock->current_price          = $data['currentPrice'];
        $stock->risk_level             = $data['risk'];
        $stock->price_avg              = $data['priceAvg'] ?? $stock->price_avg;
        $stock->recommended_buy_price  = $data['buyPrice'] ?? $stock->recommended_buy_price;
        $stock->recommended_sell_price = $data['sellPrice'] ?? $stock->recommended_sell_price;
        $stock->percent_buy            = $data['percentBuy'] ?? $stock->percent_buy;
        $stock->percent_sell           = $data['percentSell'] ?? $stock->percent_sell;
        $stock->rating_stocks          = $data['ratingStocks'] ?? $stock->rating_stocks;
        $stock->stocks_vn              = $data['stocksVn'] ?? $stock->stocks_vn;
        $stock->save();

        // Clear cache sau khi update
        CacheService::clearStockCache($code);
        CacheService::forget('stocks_all');

        return ['status' => 'success', 'message' => 'Update thành công.'];
    }

    public function importStocksCsv(\Illuminate\Http\UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines   = array_filter(array_map('trim', explode("\n", $content)));

        if (count($lines) < 2) {
            return ['status' => 'error', 'message' => 'File CSV phải có ít nhất 2 dòng (header + data).'];
        }

        $expectedHeaders = ['code', 'prive_avg', 'percent_buy', 'percent_sell', 'recommended_buy_price', 'recommended_sell_price', 'ratting_stocks', 'risk_level', 'current_price', 'percent_stock', 'stocks_vn', 'volume', 'volume_avg', 'recommended_date', 'event_date'];
        $actualHeaders   = array_map('trim', array_map('strtolower', str_getcsv($lines[0])));

        // Chấp nhận cả file export cũ (8 cột) và mới (15 cột)
        $oldHeaders = array_slice($expectedHeaders, 0, 8);
        if ($actualHeaders !== $expectedHeaders && $actualHeaders !== $oldHeaders) {
            return ['status' => 'error', 'message' => 'Header CSV không đúng cấu trúc.'];
        }
        $columnCount = count($actualHeaders);

        $updated = 0;
        $created = 0;
        $errors  = [];

        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (count($row) < 8) {
                $errors[] = "Dòng " . ($i + 1) . ": thiếu dữ liệu.";
                continue;
            }

            $code = strtoupper(trim($row[0], " '\""));
            if (empty($code)) {
                $errors[] = "Dòng " . ($i + 1) . ": mã cổ phiếu rỗng.";
                continue;
            }

            $priceAvg             = is_numeric(trim($row[1])) ? floatval(trim($row[1])) : null;
            $percentBuy           = is_numeric(trim($row[2])) ? floatval(trim($row[2])) : null;
            $percentSell          = is_numeric(trim($row[3])) ? floatval(trim($row[3])) : null;
            $recommendedBuyPrice  = is_numeric(trim($row[4])) ? floatval(trim($row[4])) : null;
            $recommendedSellPrice = is_numeric(trim($row[5])) ? floatval(trim($row[5])) : null;
            $ratingStocks         = is_numeric(trim($row[6])) ? floatval(trim($row[6])) : null;
            $riskLevel            = is_numeric(trim($row[7])) ? intval(trim($row[7])) : null;

            // Các field mở rộng (cột 8-14, chỉ có khi file 15 cột)
            $currentPrice     = ($columnCount > 8 && isset($row[8])  && is_numeric(trim($row[8])))  ? floatval(trim($row[8]))  : null;
            $percentStock     = ($columnCount > 8 && isset($row[9])  && is_numeric(trim($row[9])))  ? floatval(trim($row[9]))  : null;
            $stocksVn         = ($columnCount > 8 && isset($row[10]) && is_numeric(trim($row[10]))) ? intval(trim($row[10]))   : null;
            $volume           = ($columnCount > 8 && isset($row[11]) && is_numeric(trim($row[11]))) ? floatval(trim($row[11])) : null;
            $volumeAvg        = ($columnCount > 8 && isset($row[12]) && is_numeric(trim($row[12]))) ? floatval(trim($row[12])) : null;
            $recommendedDate  = ($columnCount > 8 && isset($row[13]) && !empty(trim($row[13])))     ? trim($row[13])           : null;
            $eventDate        = ($columnCount > 8 && isset($row[14]) && !empty(trim($row[14])))     ? trim($row[14])           : null;

            if ($priceAvg !== null && $priceAvg > 0) {
                if ($percentBuy !== null) {
                    $recommendedBuyPrice = $priceAvg * $percentBuy / 100;
                }
                if ($percentSell !== null) {
                    $recommendedSellPrice = $priceAvg * $percentSell / 100;
                }
            }

            try {
                $stock = Stock::getByCode($code);
                if ($stock) {
                    if ($priceAvg !== null)             $stock->price_avg              = $priceAvg;
                    if ($percentBuy !== null)           $stock->percent_buy            = $percentBuy;
                    if ($percentSell !== null)          $stock->percent_sell           = $percentSell;
                    if ($recommendedBuyPrice !== null)  $stock->recommended_buy_price  = $recommendedBuyPrice;
                    if ($recommendedSellPrice !== null) $stock->recommended_sell_price = $recommendedSellPrice;
                    if ($ratingStocks !== null)         $stock->rating_stocks          = $ratingStocks;
                    if ($riskLevel !== null)            $stock->risk_level             = $riskLevel;
                    if ($currentPrice !== null)         $stock->current_price          = $currentPrice;
                    if ($percentStock !== null)         $stock->percent_stock          = $percentStock;
                    if ($stocksVn !== null)             $stock->stocks_vn              = $stocksVn;
                    if ($volume !== null)               $stock->volume                 = $volume;
                    if ($volumeAvg !== null)            $stock->volume_avg             = $volumeAvg;
                    if ($recommendedDate !== null)      $stock->recommended_date       = $recommendedDate;
                    if ($eventDate !== null)            $stock->event_date             = $eventDate;
                    $stock->save();
                    $updated++;
                } else {
                    $stock                         = new Stock();
                    $stock->code                   = $code;
                    $stock->current_price          = 0;
                    $stock->price_avg              = $priceAvg ?? 0;
                    $stock->percent_buy            = $percentBuy ?? 100;
                    $stock->percent_sell           = $percentSell ?? 100;
                    $stock->recommended_buy_price  = $recommendedBuyPrice ?? 0;
                    $stock->recommended_sell_price = $recommendedSellPrice ?? 0;
                    $stock->risk_level             = $riskLevel ?? 1;
                    $stock->rating_stocks          = $ratingStocks ?? 0;
                    $stock->current_price          = $currentPrice ?? 0;
                    $stock->percent_stock          = $percentStock;
                    $stock->stocks_vn              = $stocksVn;
                    $stock->volume                 = $volume ?? 0;
                    $stock->volume_avg             = $volumeAvg ?? 0;
                    $stock->recommended_date       = $recommendedDate;
                    $stock->event_date             = $eventDate;
                    $stock->save();
                    $created++;
                }
            } catch (\Exception $e) {
                report($e);
                $errors[] = 'Dòng ' . ($i + 1) . " ($code): " . (config('app.debug') ? $e->getMessage() : 'Lỗi xử lý dòng.');
            }
        }

        $details = "Cập nhật: $updated, Thêm mới: $created.";
        if (count($errors) > 0) {
            $details .= '<br>Lỗi:<br>' . implode('<br>', $errors);
        }

        // Clear cache sau khi import
        CacheService::clearTableCache('stocks');

        return ['status' => 'success', 'message' => 'Import CSV hoàn tất.', 'details' => $details];
    }
}
