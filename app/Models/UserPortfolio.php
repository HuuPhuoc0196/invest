<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserPortfolio extends Model
{
    protected $table = 'user_portfolios';
    protected $fillable = ['id', 'user_id', 'stock_id', 'buy_price', 'buy_date', 'quantity', 'session_closed_flag'];

    public static function getProfileUser($userId)
    {
        // 1. Lấy danh sách các mã cổ phiếu mà user đã từng mua
        /** @var \Illuminate\Support\Collection<int, \stdClass> $stocks */
        $stocks = DB::table('user_portfolios')
            ->where('user_id', $userId)
            ->select('stock_id')
            ->groupBy('stock_id')
            ->get();

        $result = [];

        /** @var \stdClass $s */
        foreach ($stocks as $s) {
            $stockId = $s->stock_id;

            // 2. Lấy các lô mua theo FIFO
            $buys = DB::table('user_portfolios')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->orderBy('buy_date', 'asc')
                ->get();

            // 3. Lấy tổng số lượng đã bán
            $sellQty = DB::table('user_portfolios_sell')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->orderBy('sell_date', 'asc')
                ->sum('quantity');

            // 4. FIFO – trừ bán từ các lô mua
            /** @var \App\Models\Portfolio $buy */
            foreach ($buys as &$buy) {
                if ($sellQty <= 0) break;

                if ($sellQty >= $buy->quantity) {
                    // Bán hết lô này
                    $sellQty -= $buy->quantity;
                    $buy->quantity = 0;
                } else {
                    // Bán 1 phần lô này
                    $buy->quantity -= $sellQty;
                    $sellQty = 0;
                }
            }

            // 5. Tính giá trung bình còn lại
            $totalQty = 0;
            $totalCost = 0;
            /** @var \App\Models\Portfolio $buy */
            foreach ($buys as $buy) {
                if ($buy->quantity > 0) {
                    $totalQty += $buy->quantity;
                    $totalCost += $buy->quantity * $buy->buy_price;
                }
            }

            // Nếu đã bán hết == bỏ qua
            if ($totalQty == 0) continue;

            // 6. Lấy thông tin mã cổ phiếu
            $stockInfo = DB::table('stocks')->where('id', $stockId)->first();

            $result[] = [
                'code' => $stockInfo->code,
                'total_quantity' => $totalQty,
                'avg_buy_price' => round($totalCost / $totalQty, 2),
                'current_price' => $stockInfo->current_price,
                'risk_level' => $stockInfo->risk_level,
            ];
        }

        return $result;
    }

    public static function getStockHolding($userId, $stockId)
    {
        // Tổng số lượng đã mua
        $totalBought = self::where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->sum('quantity');

        // Tổng số lượng đã bán
        $totalSold = DB::table('user_portfolios_sell')
            ->where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->sum('quantity');

        // Số lượng còn giữ
        $remaining = $totalBought - $totalSold;

        // Nếu không còn cổ phiếu nào -> trả về false
        if ($remaining <= 0) {
            return false;
        }

        // Lấy mã cổ phiếu từ bảng stocks
        $stock = DB::table('stocks')
            ->where('id', $stockId)
            ->value('code'); // chỉ lấy trường code

        // Trả về kết quả mong muốn
        return [
            'code' => $stock ?? 'N/A',
            'total_quantity' => $remaining
        ];
    }

    public static function getPortfolioWithStockInfo($userId)
    {
        return DB::table('user_portfolios as up')
            ->join('stocks as s', 'up.stock_id', '=', 's.id')
            ->where('up.user_id', $userId)
            ->select(
                's.code',
                'up.buy_price',
                'up.quantity',
                's.current_price',
                's.risk_level',
                'up.buy_date'
            )
            ->get();
    }

    public static function getPortfolioWithUserBuy($userId)
    {
        // 1. Lấy tất cả stock user đã mua
        /** @var \Illuminate\Support\Collection<int, \stdClass> $stocks */
        $stocks = DB::table('user_portfolios')
            ->where('user_id', $userId)
            ->select('stock_id')
            ->groupBy('stock_id')
            ->get();

        $result = [];

        /** @var \stdClass $s */
        foreach ($stocks as $s) {
            $stockId = $s->stock_id;

            // 2. Lấy danh sách các lô mua theo FIFO
            $buys = DB::table('user_portfolios')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->orderBy('buy_date', 'asc')
                ->get();

            // 3. Lấy tổng số lượng đã bán
            $sellQty = DB::table('user_portfolios_sell')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->sum('quantity');

            // 4. Áp dụng FIFO
            /** @var \App\Models\Portfolio $buy */
            foreach ($buys as &$buy) {
                if ($sellQty <= 0) break;

                if ($sellQty >= $buy->quantity) {
                    $sellQty -= $buy->quantity;
                    $buy->quantity = 0;
                } else {
                    $buy->quantity -= $sellQty;
                    $sellQty = 0;
                }
            }

            // 5. Tính tổng và giá trung bình FIFO
            $totalQty = 0;
            $totalCost = 0;
            /** @var \App\Models\Portfolio $buy */
            foreach ($buys as $buy) {
                if ($buy->quantity > 0) {
                    $totalQty += $buy->quantity;
                    $totalCost += $buy->quantity * $buy->buy_price;
                }
            }

            if ($totalQty == 0) continue;

            // 6. Lấy thông tin cổ phiếu
            $stock = DB::table('stocks')->where('id', $stockId)->first();

            // 7. Trả về format giống method dưới
            $result[] = (object)[
                'code'          => $stock->code,
                'stock_id'      => $stockId,
                'user_id'       => $userId,
                'total_quantity'=> $totalQty,
                'avg_buy_price' => round($totalCost / $totalQty, 2),
            ];
        }

        return collect($result);
    }

    /**
     * Lấy danh sách cổ phiếu đang nắm giữ kèm session_closed_flag
     * Mỗi stock chỉ xuất hiện 1 lần, lấy flag từ record mua đầu tiên (FIFO)
     */
    public static function getSessionClosedByUser(int $userId)
    {
        // Lấy danh sách mã đang nắm giữ (total > 0)
        $stocks = DB::table('user_portfolios')
            ->where('user_id', $userId)
            ->select('stock_id')
            ->groupBy('stock_id')
            ->get();

        $result = [];

        foreach ($stocks as $s) {
            $stockId = $s->stock_id;

            $totalBought = DB::table('user_portfolios')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->sum('quantity');

            $totalSold = DB::table('user_portfolios_sell')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->sum('quantity');

            if ($totalBought - $totalSold <= 0) continue;

            // Lấy session_closed_flag từ record đầu tiên (FIFO)
            $firstRecord = DB::table('user_portfolios')
                ->where('user_id', $userId)
                ->where('stock_id', $stockId)
                ->orderBy('buy_date', 'asc')
                ->first();

            $stockInfo = DB::table('stocks')->where('id', $stockId)->first();

            $result[] = (object)[
                'stock_id' => $stockId,
                'code' => $stockInfo->code,
                'session_closed_flag' => $firstRecord->session_closed_flag ?? 0,
            ];
        }

        return collect($result)->sortBy('code')->values();
    }

    /**
     * Update session_closed_flag cho tất cả records của user + stock
     */
    public static function updateSessionClosedFlag(int $userId, int $stockId, int $flag)
    {
        return DB::table('user_portfolios')
            ->where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->update(['session_closed_flag' => $flag]);
    }
}
