<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Models\StatusSync;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Database\QueryException;
use App\Http\Requests\InsertStockBasicRequest;
use App\Http\Requests\StockInsertRequest;
use App\Http\Requests\StockUpdateRequest;
use App\Http\Requests\ImportStocksCsvRequest;

class Admin extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function show()
    {
        $stocks     = Stock::getAllStocks();
        $statusSync = StatusSync::getStatusSync();
        return view('Admin.AdminView', compact('stocks', 'statusSync'));
    }

    public function insert(InsertStockBasicRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->stockService->insertStockBasic($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminInsert');
    }

    public function update(StockUpdateRequest $request, $code)
    {
        if ($request->isMethod('PUT')) {
            try {
                $result = $this->stockService->updateStock($code, $request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        $stock = Stock::getByCode(strtoupper($code));
        if (!$stock) {
            return redirect()->route('admin.stocks')->with('error', 'Mã cổ phiếu không tồn tại.');
        }
        return view('Admin.AdminUpdate', compact('stock'));
    }

    public function delete(string $code)
    {
        $code = strtoupper($code);
        $stock = Stock::getByCode($code);
        if (!$stock) {
            return redirect('/admin/stocks')->with('error', "Không tìm thấy mã cổ phiếu {$code}.");
        }

        $deps = Stock::getDeleteDependencyCounts($code);
        $totalRefs = array_sum($deps);
        if ($totalRefs > 0) {
            $message = "Không thể xoá mã {$code} vì đang có dữ liệu liên quan. "
                . "Danh mục mua: {$deps['user_portfolios']}, "
                . "Lịch sử bán: {$deps['user_portfolios_sell']}, "
                . "Danh sách theo dõi: {$deps['user_follows']}.";

            return redirect('/admin/stocks')->with('error', $message);
        }

        try {
            $deleted = Stock::deleteByCode($code);
            if ($deleted) {
                return redirect('/admin/stocks')->with('success', "Đã xoá mã cổ phiếu {$code}.");
            }

            return redirect('/admin/stocks')->with('error', "Không thể xoá mã cổ phiếu {$code}.");
        } catch (QueryException $e) {
            return redirect('/admin/stocks')->with('error', "Không thể xoá mã {$code}: dữ liệu đang được sử dụng.");
        }
    }

    public function stockManagement()
    {
        $stocks = Stock::getAllStocks();
        return view('Admin.AdminStockManagement', compact('stocks'));
    }

    public function exportStocksCsv()
    {
        $stocks   = Stock::getAllStocks();
        $date     = now()->format('Y-m-d');
        $fileName = "csv_{$date}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $columns = [
            'code', 'prive_avg', 'percent_buy', 'percent_sell',
            'recommended_buy_price', 'recommended_sell_price', 'ratting_stocks', 'risk_level',
        ];

        $callback = function () use ($stocks, $columns) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);
            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->code,
                    $stock->price_avg,
                    $stock->percent_buy,
                    $stock->percent_sell,
                    $stock->recommended_buy_price,
                    $stock->recommended_sell_price,
                    $stock->rating_stocks,
                    $stock->risk_level,
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    public function importStocksCsv(ImportStocksCsvRequest $request)
    {
        $result = $this->stockService->importStocksCsv($request->file('csv_file'));
        return response()->json($result);
    }

    public function stockInsert(StockInsertRequest $request)
    {
        if ($request->isMethod('post')) {
            try {
                $result = $this->stockService->insertStock($request->validated());
                return response()->json($result);
            } catch (QueryException $e) {
                return $this->jsonServerError($e);
            }
        }
        return view('Admin.AdminStockInsert');
    }
}
