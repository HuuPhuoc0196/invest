<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Models\Stock;
use App\Models\UserFollow;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PagesController extends Controller
{
    public function about()
    {
        return view('Pages.AboutView');
    }

    public function donate()
    {
        $email = Auth::check() ? Auth::user()->email : null;
        return view('Pages.DonateView', compact('email'));
    }

    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name'    => 'required|string|max:100',
                'email'   => 'required|email|max:200',
                'subject' => 'required|string|max:200',
                'message' => 'required|string|max:2000',
            ], [
                'name.required'    => 'Vui lòng nhập họ tên.',
                'email.required'   => 'Vui lòng nhập email.',
                'email.email'      => 'Email không hợp lệ.',
                'subject.required' => 'Vui lòng nhập tiêu đề.',
                'message.required' => 'Vui lòng nhập nội dung.',
            ]);

            try {
                Mail::to('lehuuphuoc0196@gmail.com')->send(new ContactMail($validated));
            } catch (\Throwable $e) {
                Log::error('Contact form mail error', ['error' => $e->getMessage()]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không thể gửi tin nhắn lúc này. Vui lòng thử lại sau.',
                ], 500);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Tin nhắn đã được gửi thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.',
            ]);
        }

        return view('Pages.ContactView');
    }

    public function privacy()
    {
        return view('Pages.PrivacyView');
    }

    public function terms()
    {
        return view('Pages.TermsView');
    }

    public function vn30()
    {
        $stocks = Stock::select('code', 'current_price', 'percent_stock', 'risk_level')
            ->where('stocks_vn', 30)
            ->orderBy('code')
            ->get();

        $itemListSchema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Danh sách cổ phiếu VN30',
            'itemListElement' => $stocks->values()->map(fn ($s, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $s->code,
                'url'      => url('/co-phieu/' . $s->code),
            ])->all(),
        ];

        return view('Pages.StockCategoryView', [
            'title'          => 'Danh sách cổ phiếu VN30',
            'subtitle'       => 'Top 30 mã cổ phiếu vốn hóa lớn nhất sàn HOSE.',
            'stocks'         => $stocks,
            'categoryKey'    => 'vn30',
            'itemListSchema' => $itemListSchema,
        ]);
    }

    public function vn100()
    {
        $stocks = Stock::select('code', 'current_price', 'percent_stock', 'risk_level')
            ->whereIn('stocks_vn', [30, 100])
            ->orderBy('code')
            ->get();

        $itemListSchema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Danh sách cổ phiếu VN100',
            'itemListElement' => $stocks->values()->map(fn ($s, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $s->code,
                'url'      => url('/co-phieu/' . $s->code),
            ])->all(),
        ];

        return view('Pages.StockCategoryView', [
            'title'          => 'Danh sách cổ phiếu VN100',
            'subtitle'       => 'Top 100 mã cổ phiếu vốn hóa lớn nhất sàn HOSE.',
            'stocks'         => $stocks,
            'categoryKey'    => 'vn100',
            'itemListSchema' => $itemListSchema,
        ]);
    }

    public function guide()
    {
        return view('Pages.GuideView');
    }

    public function faq()
    {
        return view('Pages.FaqView');
    }

    public function stockDetail(string $code)
    {
        $code  = strtoupper(trim($code));
        $stock = Stock::getByCode($code);

        if (!$stock) {
            abort(404, "Mã cổ phiếu {$code} không tồn tại.");
        }

        // Risk history — cached 1 ngày, key: stock_risk_history_{CODE}
        try {
            $riskHistory = CacheService::remember(
                "stock_risk_history_{$code}",
                CacheService::TTL_ONE_DAY,
                fn () => DB::table('stock_risk_history')
                    ->where('code', $code)
                    ->orderBy('event_date', 'desc')
                    ->limit(30)
                    ->get()
            );
        } catch (\Throwable $e) {
            Log::warning("stock_risk_history query failed for {$code}: " . $e->getMessage());
            $riskHistory = collect();
        }

        // Dividend history — cached 1 ngày, key: stock_dividend_{CODE}
        try {
            $dividendHistory = CacheService::remember(
                "stock_dividend_{$code}",
                CacheService::TTL_ONE_DAY,
                fn () => DB::table('dividend_adjustments')
                    ->join('stocks', 'stocks.id', '=', 'dividend_adjustments.stock_id')
                    ->where('stocks.code', $code)
                    ->orderBy('dividend_adjustments.gdkhq_date', 'desc')
                    ->limit(20)
                    ->select('dividend_adjustments.*')
                    ->get()
            );
        } catch (\Throwable $e) {
            Log::warning("dividend_adjustments query failed for {$code}: " . $e->getMessage());
            $dividendHistory = collect();
        }

        // Nếu đã đăng nhập là user, lấy thông tin nắm giữ
        $userHolding = null;
        $userFollow  = null;
        if (Auth::check() && Auth::user()->role === 0) {
            $userId = Auth::id();
            try {
                $userHolding = DB::table('user_portfolios')
                    ->join('stocks', 'stocks.id', '=', 'user_portfolios.stock_id')
                    ->where('stocks.code', $code)
                    ->where('user_portfolios.user_id', $userId)
                    ->where('user_portfolios.session_closed_flag', 0)
                    ->selectRaw('SUM(user_portfolios.quantity) as total_qty, SUM(user_portfolios.buy_price * user_portfolios.quantity) as total_cost')
                    ->first();
            } catch (\Throwable $e) {
                $userHolding = null;
            }
            try {
                $userFollow = DB::table('user_follows')
                    ->where('user_id', $userId)
                    ->where('stock_id', $stock->id)
                    ->first();
            } catch (\Throwable $e) {
                $userFollow = null;
            }
        }

        return view('Pages.StockDetailView', compact('stock', 'riskHistory', 'dividendHistory', 'userHolding', 'userFollow'));
    }

    public function stockRiskHistory(string $code)
    {
        $code = strtoupper(trim($code));
        try {
            $data = DB::table('stock_risk_history')
                ->where('code', $code)
                ->orderBy('event_date', 'desc')
                ->limit(50)
                ->get();
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'data' => [], 'message' => 'Không thể tải dữ liệu.'], 200);
        }
    }

    public function stockDividendHistory(string $code)
    {
        $code = strtoupper(trim($code));
        try {
            $data = DB::table('dividend_adjustments')
                ->join('stocks', 'stocks.id', '=', 'dividend_adjustments.stock_id')
                ->where('stocks.code', $code)
                ->orderBy('dividend_adjustments.gdkhq_date', 'desc')
                ->limit(30)
                ->select('dividend_adjustments.*')
                ->get();
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'data' => [], 'message' => 'Không thể tải dữ liệu.'], 200);
        }
    }
}
