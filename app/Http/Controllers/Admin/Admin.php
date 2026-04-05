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
        Stock::deleteByCode(strtoupper($code));
        return redirect('/admin/stocks');
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
