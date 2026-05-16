<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
/* ── Reset ── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; background: #fff; }

/* ── Header ── */
.pdf-header { background: #0f172a; color: #fff; }
.pdf-header-inner { padding: 14px 20px; }
.pdf-header-logo { font-size: 17px; font-weight: bold; color: #38bdf8; letter-spacing: -0.5px; }
.pdf-header-logo span { color: #818cf8; }
.pdf-header-title { font-size: 10px; font-weight: bold; color: #cbd5e1; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.06em; }
.pdf-header-bar { background: #1e40af; height: 3px; background: linear-gradient(90deg,#38bdf8,#818cf8,#34d399); }

/* ── User info strip ── */
.user-strip { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 7px 20px; }
.uname { font-size: 10px; font-weight: bold; color: #0f172a; }
.umeta { font-size: 8px; color: #64748b; }

/* ── KPI ── */
.kpi-section { padding: 10px 20px 0; }
.kpi-table { width: 100%; border-collapse: separate; border-spacing: 5px 0; }
.kpi-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px; padding: 9px 10px; text-align: center; vertical-align: top; }
.kpi-box.acc-blue   { border-top: 3px solid #38bdf8; }
.kpi-box.acc-indigo { border-top: 3px solid #818cf8; }
.kpi-box.acc-green  { border-top: 3px solid #16a34a; }
.kpi-box.acc-red    { border-top: 3px solid #dc2626; }
.kpi-box.acc-amber  { border-top: 3px solid #d97706; }
.kpi-label { font-size: 7px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
.kpi-value { font-size: 13px; font-weight: bold; color: #0f172a; line-height: 1.2; }
.kpi-value.pos { color: #15803d; }
.kpi-value.neg { color: #b91c1c; }
.kpi-sub { font-size: 7px; color: #94a3b8; margin-top: 2px; }

/* ── Section header ── */
.section-hd { margin: 12px 20px 6px; padding: 5px 10px; background: #f1f5f9; border-left: 3px solid #38bdf8; border-radius: 0 3px 3px 0; }
.section-hd-title { font-size: 8.5px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.06em; }
.section-hd-sub { font-size: 7px; color: #64748b; margin-top: 1px; }

/* ── Holdings table ── */
.main-table { width: calc(100% - 40px); border-collapse: collapse; margin: 0 20px; }
.main-table thead th {
    background: #0f172a; color: #94a3b8; font-size: 7px; font-weight: bold;
    text-transform: uppercase; letter-spacing: 0.05em;
    padding: 6px 7px; border-bottom: 2px solid #38bdf8; text-align: left;
}
.main-table thead th.r { text-align: right; }
.main-table tbody td { padding: 6px 7px; font-size: 8.5px; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.main-table tbody td.r { text-align: right; }
.main-table tbody tr:nth-child(even) td { background: #f8fafc; }
.main-table tfoot td { background: #0f172a; color: #e2e8f0; font-size: 8px; font-weight: bold; padding: 6px 7px; border-top: 2px solid #38bdf8; }
.main-table tfoot td.r { text-align: right; }

/* ── Badges ── */
.code-cell { font-weight: bold; font-size: 10px; color: #0369a1; }
.vn-badge { font-size: 6px; font-weight: bold; padding: 1px 3px; border-radius: 2px; margin-left: 2px; }
.vn30  { background: #fef3c7; color: #92400e; }
.vn100 { background: #e0f2fe; color: #0369a1; }
.risk  { font-size: 7px; font-weight: bold; padding: 2px 5px; border-radius: 8px; }
.risk-1 { background: #dcfce7; color: #15803d; }
.risk-2 { background: #fef9c3; color: #a16207; }
.risk-3 { background: #fee2e2; color: #b91c1c; }
.risk-4 { background: #1e293b; color: #f87171; }

/* ── Rating dots ── */
.rating-dot { display: inline-block; width: 5px; height: 5px; border-radius: 50%; margin-right: 1px; background: #e2e8f0; }
.rating-dot.on { background: #f59e0b; }

/* ── Chart ── */
.chart-section { margin: 0 20px; }
.chart-table { width: 100%; border-collapse: collapse; }
.chart-table td { padding: 2px 3px; vertical-align: middle; }
.bar-label { font-size: 8px; font-weight: bold; color: #374151; width: 50px; text-align: right; padding-right: 6px; }
.bar-neg-cell { width: 195px; text-align: right; }
.bar-pos-cell { width: 195px; }
.bar-divider  { width: 1px; background: #cbd5e1; padding: 0; }
.bar-val      { width: 100px; font-size: 7.5px; padding-left: 5px; }
.bar-fill     { display: inline-block; height: 10px; border-radius: 2px; vertical-align: middle; }
.bar-fill.pos { background: #16a34a; }
.bar-fill.neg { background: #dc2626; }

/* ── Lot / Sell tables ── */
.sub-table { width: calc(100% - 40px); border-collapse: collapse; margin: 0 20px; }
.sub-table thead th {
    background: #1e293b; color: #94a3b8; font-size: 7px; font-weight: bold;
    text-transform: uppercase; letter-spacing: 0.05em; padding: 5px 7px;
    border-bottom: 1px solid #334155; text-align: left;
}
.sub-table thead th.r { text-align: right; }
.sub-table tbody td { padding: 5px 7px; font-size: 8px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
.sub-table tbody td.r { text-align: right; }
.sub-table tbody tr:nth-child(even) td { background: #f8fafc; }

/* ── Helpers ── */
.r    { text-align: right; }
.c    { text-align: center; }
.pos  { color: #15803d; }
.neg  { color: #b91c1c; }
.bold { font-weight: bold; }
.muted { color: #64748b; }

/* ── Footer ── */
.pdf-footer { margin-top: 14px; border-top: 2px solid #38bdf8; padding: 7px 20px; background: #0f172a; }
.pdf-footer-name { font-size: 8px; color: #38bdf8; font-weight: bold; }
.pdf-footer-url  { font-size: 7px; color: #64748b; margin-top: 1px; }
.pdf-footer-disc { font-size: 6.5px; color: #475569; margin-top: 3px; }
</style>
</head>
<body>
{{-- ══════════════ HEADER ══════════════ --}}
<div class="pdf-header">
    <div class="pdf-header-inner">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <div class="pdf-header-logo">Quản lý <span>Đầu tư</span></div>
                    <div class="pdf-header-title">Báo cáo danh mục đầu tư cổ phiếu cá nhân</div>
                </td>
                <td style="text-align:right;vertical-align:top;">
                    <div style="font-size:8.5px;color:#38bdf8;font-weight:bold;">{{ config('app.name') }}</div>
                    <div style="font-size:7.5px;color:#94a3b8;margin-top:2px;">{{ config('app.url') }}</div>
                    <div style="font-size:7.5px;color:#94a3b8;margin-top:2px;">Xuất lúc: {{ now()->format('H:i — d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="pdf-header-bar"></div>

{{-- ══════════════ USER STRIP ══════════════ --}}
<div class="user-strip">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td>
                <span class="uname">{{ $user->name }}</span>
                <span class="umeta" style="margin-left:8px;">{{ $user->email }}</span>
            </td>
            <td style="text-align:right;">
                <span class="umeta">Ngày báo cáo: <strong>{{ now()->format('d/m/Y') }}</strong></span>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════════ KPI SUMMARY ══════════════ --}}
@php
    $pnlClass  = $totalPnl >= 0 ? 'pos' : 'neg';
    $roiClass  = $totalRoi >= 0 ? 'pos' : 'neg';
    $pnlAcc    = $totalPnl >= 0 ? 'acc-green' : 'acc-red';
    $roiAcc    = $totalRoi >= 0 ? 'acc-green' : 'acc-red';
    $netAsset  = $totalValue + $cashBalance;
@endphp
<div class="kpi-section">
    <table class="kpi-table">
        <tr>
            <td class="kpi-box acc-blue">
                <div class="kpi-label">Tổng vốn đầu tư</div>
                <div class="kpi-value">{{ number_format($totalCost) }}&#x20AB;</div>
                <div class="kpi-sub">Giá mua trung bình × SL</div>
            </td>
            <td class="kpi-box acc-indigo">
                <div class="kpi-label">Giá trị thị trường</div>
                <div class="kpi-value">{{ number_format($totalValue) }}&#x20AB;</div>
                <div class="kpi-sub">Giá hiện tại × SL nắm giữ</div>
            </td>
            <td class="kpi-box {{ $pnlAcc }}">
                <div class="kpi-label">Tiền lãi</div>
                <div class="kpi-value {{ $pnlClass }}">{{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl) }}&#x20AB;</div>
                <div class="kpi-sub">Giá trị TT − Vốn đầu tư</div>
            </td>
            <td class="kpi-box {{ $roiAcc }}">
                <div class="kpi-label">% Lãi</div>
                <div class="kpi-value {{ $roiClass }}">{{ $totalRoi >= 0 ? '+' : '' }}{{ $totalRoi }}%</div>
                <div class="kpi-sub">Lợi nhuận / Vốn × 100</div>
            </td>
            <td class="kpi-box acc-amber">
                <div class="kpi-label">Số dư</div>
                <div class="kpi-value">{{ number_format($cashBalance) }}&#x20AB;</div>
                <div class="kpi-sub">Tổng tài sản: {{ number_format($netAsset) }}&#x20AB;</div>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════════ HOLDINGS TABLE ══════════════ --}}
<div class="section-hd" style="margin-top:14px;">
    <div class="section-hd-title">Chi tiết danh mục đang nắm giữ</div>
    <div class="section-hd-sub">{{ $userPortfolios->count() }} mã — tính theo phương pháp FIFO</div>
</div>

<table class="main-table">
    <thead>
        <tr>
            <th style="width:16px;" class="c">#</th>
            <th style="width:58px;">Mã CK</th>
            <th style="width:38px;" class="r">SL</th>
            <th style="width:68px;" class="r">Giá vốn TB</th>
            <th style="width:68px;" class="r">Giá hiện tại</th>
            <th style="width:74px;" class="r">Vốn đầu tư</th>
            <th style="width:74px;" class="r">Giá trị TT</th>
            <th style="width:72px;" class="r">Tiền lãi</th>
            <th style="width:38px;" class="r">% Lãi</th>
            <th style="width:52px;" class="c">Rủi ro</th>
            <th style="width:48px;" class="c">Điểm</th>
            <th style="width:54px;">Ngày mua</th>
        </tr>
    </thead>
    <tbody>
    @php
        $gCost = 0; $gVal = 0; $gPnl = 0; $rowNum = 0;
        $riskLabels  = [1=>'An toàn',2=>'Cảnh báo',3=>'Hạn chế',4=>'Đình chỉ'];
        $riskClasses = [1=>'risk-1',2=>'risk-2',3=>'risk-3',4=>'risk-4'];
    @endphp
    @foreach($userPortfolios as $p)
    @php
        $rowNum++;
        $qty    = $p->total_quantity ?? 0;
        $avgBuy = $p->avg_buy_price  ?? 0;
        $curP   = $p->current_price  ?? 0;
        $cost   = $avgBuy * $qty;
        $val    = $curP * $qty;
        $pnl    = $val - $cost;
        $roi    = $cost > 0 ? round($pnl / $cost * 100, 2) : 0;
        $gCost += $cost; $gVal += $val; $gPnl += $pnl;
        $risk   = $p->risk_level ?? 1;
        $rating = min(10, max(0, (int)($p->rating_stocks ?? 0)));
        $vn     = $p->stocks_vn ?? 1000;
        $vnLabel = $vn == 30 ? 'VN30' : ($vn == 100 ? 'VN100' : '');
        $vnCls   = $vn == 30 ? 'vn30' : 'vn100';
    @endphp
    <tr>
        <td class="c muted">{{ $rowNum }}</td>
        <td>
            <span class="code-cell">{{ $p->code }}</span>
            @if($vnLabel)<span class="vn-badge {{ $vnCls }}">{{ $vnLabel }}</span>@endif
        </td>
        <td class="r bold">{{ number_format($qty) }}</td>
        <td class="r">{{ number_format($avgBuy) }}&#x20AB;</td>
        <td class="r bold">{{ number_format($curP) }}&#x20AB;</td>
        <td class="r muted">{{ number_format($cost) }}&#x20AB;</td>
        <td class="r bold">{{ number_format($val) }}&#x20AB;</td>
        <td class="r {{ $pnl >= 0 ? 'pos bold' : 'neg bold' }}">{{ $pnl >= 0 ? '+' : '' }}{{ number_format($pnl) }}&#x20AB;</td>
        <td class="r {{ $roi >= 0 ? 'pos bold' : 'neg bold' }}">{{ $roi >= 0 ? '+' : '' }}{{ $roi }}%</td>
        <td class="c"><span class="risk {{ $riskClasses[$risk] ?? 'risk-1' }}">{{ $riskLabels[$risk] ?? '—' }}</span></td>
        @php
            $ratingBg    = $rating >= 7 ? '#e6ffea' : ($rating >= 5 ? '#fff8e1' : '#fdecea');
            $ratingColor = $rating >= 7 ? '#2ecc71' : ($rating >= 5 ? '#f1c40f' : '#e74c3c');
        @endphp
        <td class="c"><span style="display:inline-block;padding:1px 5px;border-radius:8px;font-weight:bold;background:{{ $ratingBg }};color:{{ $ratingColor }};">{{ $rating }}</span></td>
        <td class="muted">{{ $p->earliest_buy_date ? \Carbon\Carbon::parse($p->earliest_buy_date)->format('d/m/Y') : '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    @php $gRoi = $gCost > 0 ? round($gPnl / $gCost * 100, 2) : 0; @endphp
    <tr>
        <td colspan="5" class="muted" style="font-size:7px;text-transform:uppercase;letter-spacing:.06em;">Tổng cộng</td>
        <td class="r">{{ number_format($gCost) }}&#x20AB;</td>
        <td class="r">{{ number_format($gVal) }}&#x20AB;</td>
        <td class="r {{ $gPnl >= 0 ? 'pos' : 'neg' }}">{{ $gPnl >= 0 ? '+' : '' }}{{ number_format($gPnl) }}&#x20AB;</td>
        <td class="r {{ $gRoi >= 0 ? 'pos' : 'neg' }}">{{ $gRoi >= 0 ? '+' : '' }}{{ $gRoi }}%</td>
        <td colspan="3"></td>
    </tr>
    </tfoot>
</table>

{{-- ══════════════ P&L BAR CHART ══════════════ --}}
@php
    $chartItems = $userPortfolios->map(function($p) {
        $qty = $p->total_quantity ?? 0;
        $avgBuy = $p->avg_buy_price ?? 0;
        $curP = $p->current_price ?? 0;
        $cost = $avgBuy * $qty;
        $val  = $curP * $qty;
        $pnl  = $val - $cost;
        $roi  = $cost > 0 ? round($pnl / $cost * 100, 1) : 0;
        return ['code' => $p->code, 'pnl' => $pnl, 'roi' => $roi];
    })->sortByDesc('roi')->values();
    $maxAbs  = max(0.01, $chartItems->map(fn($i) => abs($i['roi']))->max());
    $maxBarPx = 190;
@endphp

<div class="section-hd" style="margin-top:12px;">
    <div class="section-hd-title">Biểu đồ lãi/lỗ theo mã</div>
    <div class="section-hd-sub">Sắp xếp từ lãi nhiều nhất đến lỗ nhiều nhất — cột trái: lỗ, cột phải: lãi</div>
</div>

<div class="chart-section">
    <table class="chart-table">
        <tr>
            <td class="bar-label" style="color:#94a3b8;font-size:6.5px;padding-bottom:4px;">MÃ</td>
            <td class="bar-neg-cell" style="text-align:right;font-size:6.5px;color:#b91c1c;padding-bottom:4px;">LỖ ◄</td>
            <td class="bar-divider" style="background:#e2e8f0;">&nbsp;</td>
            <td class="bar-pos-cell" style="font-size:6.5px;color:#15803d;padding-bottom:4px;">► LÃI</td>
            <td class="bar-val" style="font-size:6.5px;color:#94a3b8;padding-bottom:4px;">Lợi nhuận</td>
        </tr>
        @foreach($chartItems as $item)
        @php
            $isPos  = $item['roi'] >= 0;
            $barPx  = max(1, (int) round(abs($item['roi']) / $maxAbs * $maxBarPx));
            $roiFmt = ($item['roi'] >= 0 ? '+' : '') . $item['roi'] . '%';
            $pnlFmt = ($item['pnl'] >= 0 ? '+' : '') . number_format($item['pnl']) . '&#x20AB;';
        @endphp
        <tr>
            <td class="bar-label">{{ $item['code'] }}</td>
            <td class="bar-neg-cell">
                @if(!$isPos)<span class="bar-fill neg" style="width:{{ $barPx }}px;"></span>@endif
            </td>
            <td class="bar-divider" style="background:#cbd5e1;">&nbsp;</td>
            <td class="bar-pos-cell">
                @if($isPos)<span class="bar-fill pos" style="width:{{ $barPx }}px;"></span>@endif
            </td>
            <td class="bar-val {{ $isPos ? 'pos' : 'neg' }}">
                <span class="bold">{{ $roiFmt }}</span>&nbsp;
                <span class="muted">{!! $pnlFmt !!}</span>
            </td>
        </tr>
        @endforeach
    </table>
</div>

{{-- FIFO Timeline section removed by user request --}}

{{-- ══════════════ FOOTER ══════════════ --}}
<div class="pdf-footer">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td>
                <div class="pdf-footer-name">{{ config('app.name') }}</div>
                <div class="pdf-footer-url">{{ config('app.url') }}</div>
                <div class="pdf-footer-disc">Tài liệu này được tạo tự động. Thông tin chỉ mang tính tham khảo, không phải tư vấn đầu tư tài chính. Giá cổ phiếu có thể biến động bất cứ lúc nào.</div>
            </td>
            <td style="text-align:right;vertical-align:bottom;">
                <div style="font-size:7px;color:#64748b;">Tạo lúc {{ now()->format('H:i:s d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
