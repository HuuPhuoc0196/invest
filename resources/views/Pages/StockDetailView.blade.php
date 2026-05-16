@extends('Layout.Layout')

@php
    $riskLabels = [1 => 'An toàn', 2 => 'Cảnh báo', 3 => 'Hạn chế', 4 => 'Đình chỉ'];
    $riskIcons  = [1 => '✅', 2 => '⚠️', 3 => '🚫', 4 => '⛔'];
    $rl         = $stock->risk_level ?? 1;
    $pct        = $stock->percent_stock ?? 0;
    $changeClass = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
    $changeSign  = $pct > 0 ? '+' : '';
@endphp

@section('title', $stock->code . ' — ' . config('app.name'))

@section('seo')
    @include('partials.seo-public', [
        'pageTitle'   => $stock->code . ' — Giá cổ phiếu hôm nay — ' . config('app.name'),
        'description' => 'Giá cổ phiếu ' . $stock->code . ' hôm nay: ' . number_format($stock->current_price) . '₫. Xem mức rủi ro, giá mua/bán khuyến nghị, lịch sử risk và cổ tức.',
        'canonical'   => url('/co-phieu/' . $stock->code),
    ])
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'FinancialProduct',
        'name'     => $stock->code,
        'url'      => url('/co-phieu/' . $stock->code),
        'offers'   => ['@type' => 'Offer', 'price' => $stock->current_price, 'priceCurrency' => 'VND'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/stock-detail.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @auth
        @if(Auth::user()->role === 0)
            @include('partials.user-nav-primary')
        @endif
    @else
        @include('partials.guest-nav-actions')
    @endauth
@endsection

@section('user-body-content')
<div class="stock-detail-wrap">

    {{-- Breadcrumb --}}
    <nav style="font-size:0.82rem;color:#64748b;margin-bottom:16px;">
        <a href="{{ route('home') }}" style="color:#38bdf8;text-decoration:none;">Trang chủ</a>
        <span style="margin:0 6px;">/</span>
        <span>{{ $stock->code }}</span>
    </nav>

    {{-- Hero card --}}
    <div class="stock-detail-hero">
        <div style="flex:1;min-width:200px;">
            <div class="stock-detail-code">{{ $stock->code }}</div>
            <div style="margin-top:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span class="stock-detail-price">{{ number_format($stock->current_price) }}₫</span>
                <span class="stock-detail-change {{ $changeClass }}">{{ $changeSign }}{{ number_format($pct, 2) }}%</span>
                <span class="risk-badge risk-{{ $rl }}">
                    {{ $riskIcons[$rl] ?? '' }} {{ $riskLabels[$rl] ?? 'Không xác định' }}
                </span>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            @auth
                @if(Auth::user()->role === 0)
                    <a href="{{ route('buy') }}?code={{ $stock->code }}" class="sd-btn-primary">➕ Mua</a>
                    <a href="{{ route('sell') }}?code={{ $stock->code }}" class="sd-btn-secondary">❌ Bán</a>
                    @if(!$userFollow)
                        <a href="{{ route('insertFollow') }}?code={{ $stock->code }}" class="sd-btn-secondary">🔔 Theo dõi</a>
                    @else
                        <span class="sd-btn-secondary" style="cursor:default;opacity:0.6;">✓ Đang theo dõi</span>
                    @endif
                @endif
            @else
                <a href="{{ route('register') }}" class="sd-btn-primary">📝 Đăng ký miễn phí</a>
                <a href="{{ route('login') }}" class="sd-btn-secondary">🔑 Đăng nhập</a>
            @endauth
        </div>
    </div>

    {{-- Thông tin cơ bản (ẩn price_avg với user) --}}
    <div class="sd-section">
        <div class="sd-section-title">📊 Thông tin giao dịch</div>
        <div class="sd-info-grid">
            <div class="sd-info-item">
                <span class="sd-info-label">Giá mua khuyến nghị</span>
                <span class="sd-info-value" style="color:#10b981;">{{ number_format($stock->recommended_buy_price) }}₫</span>
            </div>
            <div class="sd-info-item">
                <span class="sd-info-label">Giá bán khuyến nghị</span>
                <span class="sd-info-value" style="color:#f59e0b;">{{ number_format($stock->recommended_sell_price) }}₫</span>
            </div>
            <div class="sd-info-item">
                <span class="sd-info-label">Khối lượng phiên</span>
                <span class="sd-info-value">{{ number_format($stock->volume) }}</span>
            </div>
            <div class="sd-info-item">
                <span class="sd-info-label">Chỉ số</span>
                <span class="sd-info-value">
                    @if($stock->stocks_vn == 30) VN30
                    @elseif($stock->stocks_vn == 100) VN100
                    @else Ngoài chỉ số
                    @endif
                </span>
            </div>
            <div class="sd-info-item">
                <span class="sd-info-label">Điểm đánh giá</span>
                <span class="sd-info-value">{{ $stock->rating_stocks ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Danh mục của user (chỉ hiện khi đăng nhập và có nắm giữ) --}}
    @auth
        @if(Auth::user()->role === 0 && $userHolding && $userHolding->total_qty > 0)
        @php
            $avgBuy  = $userHolding->total_qty > 0 ? $userHolding->total_cost / $userHolding->total_qty : 0;
            $curVal  = $stock->current_price * $userHolding->total_qty;
            $pnl     = $curVal - $userHolding->total_cost;
            $roi     = $userHolding->total_cost > 0 ? round($pnl / $userHolding->total_cost * 100, 2) : 0;
        @endphp
        <div class="sd-section">
            <div class="sd-section-title">💼 Danh mục của bạn với {{ $stock->code }}</div>
            <div class="user-holding-card">
                <div class="uhc-item">
                    <span class="uhc-label">Số lượng nắm giữ</span>
                    <span class="uhc-value">{{ number_format($userHolding->total_qty) }} CP</span>
                </div>
                <div class="uhc-item">
                    <span class="uhc-label">Giá mua trung bình</span>
                    <span class="uhc-value">{{ number_format($avgBuy) }}₫</span>
                </div>
                <div class="uhc-item">
                    <span class="uhc-label">Giá trị thị trường</span>
                    <span class="uhc-value">{{ number_format($curVal) }}₫</span>
                </div>
                <div class="uhc-item">
                    <span class="uhc-label">Lãi/Lỗ</span>
                    <span class="uhc-value {{ $pnl >= 0 ? 'positive' : 'negative' }}">
                        {{ $pnl >= 0 ? '+' : '' }}{{ number_format($pnl) }}₫
                    </span>
                </div>
                <div class="uhc-item">
                    <span class="uhc-label">ROI</span>
                    <span class="uhc-value {{ $roi >= 0 ? 'positive' : 'negative' }}">
                        {{ $roi >= 0 ? '+' : '' }}{{ $roi }}%
                    </span>
                </div>
            </div>
        </div>
        @endif
    @endauth

    {{-- Lịch sử risk --}}
    <div class="sd-section">
        <div class="sd-section-title">📋 Lịch sử mức độ rủi ro</div>
        @if($riskHistory->isNotEmpty())
            <div class="risk-timeline">
                @foreach($riskHistory as $r)
                @php
                    $noteText  = $r->note ?? '';
                    $channelId = $r->channel_id ?? 0;
                    $noteL     = mb_strtolower($noteText);
                    if (str_contains($noteL, 'hủy niêm yết') || str_contains($noteL, 'huỷ niêm yết') || str_contains($noteL, 'đình chỉ')) {
                        $rlh = 4;
                    } elseif (str_contains($noteL, 'ra khỏi') || str_contains($noteL, 'giao dịch trở lại')) {
                        $rlh = 1;
                    } elseif ($channelId == 56) {
                        $rlh = 4;
                    } elseif (in_array($channelId, [22, 50, 51, 52, 53])) {
                        $rlh = 3;
                    } elseif ($channelId == 19) {
                        $rlh = 2;
                    } else {
                        $rlh = 1;
                    }
                @endphp
                <div class="risk-timeline-item">
                    <span class="risk-timeline-date">{{ \Carbon\Carbon::parse($r->event_date)->format('d/m/Y') }}</span>
                    <span class="risk-timeline-badge">
                        <span class="risk-badge risk-{{ $rlh }}">{{ $riskIcons[$rlh] ?? '' }} {{ $noteText ?: ($riskLabels[$rlh] ?? 'Không xác định') }}</span>
                    </span>
                </div>
                @endforeach
            </div>
        @else
            <p class="empty-history">Chưa có dữ liệu lịch sử rủi ro.</p>
        @endif
    </div>

    {{-- Lịch sử cổ tức --}}
    <div class="sd-section">
        <div class="sd-section-title">💰 Lịch sử điều chỉnh cổ tức</div>
        @if($dividendHistory->isNotEmpty())
            <div class="sd-table-wrap">
                <table class="sd-table" aria-label="Lịch sử điều chỉnh cổ tức {{ $stock->code }}">
                    <thead>
                        <tr>
                            <th scope="col">Ngày GDKHQ</th>
                            <th scope="col">Loại</th>
                            <th scope="col">Hệ số điều chỉnh</th>
                            <th scope="col">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dividendHistory as $d)
                        @php
                            [$adjLabel, $adjClass] = match($d->adj_type ?? '') {
                                'cash_dividend'  => ['💵 Tiền mặt',  'sd-adj-badge--cash'],
                                'stock_dividend' => ['📈 Cổ phiếu',  'sd-adj-badge--stock'],
                                'bonus_shares'   => ['🎁 Thưởng CP', 'sd-adj-badge--bonus'],
                                default          => [$d->adj_type ?: '—', 'sd-adj-badge--other'],
                            };
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d->gdkhq_date)->format('d/m/Y') }}</td>
                            <td><span class="sd-adj-badge {{ $adjClass }}">{{ $adjLabel }}</span></td>
                            <td>{{ $d->adj_factor ? number_format((float)$d->adj_factor, 4) : '—' }}</td>
                            <td>{{ $d->note_raw ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="empty-history">Chưa có dữ liệu cổ tức.</p>
        @endif
    </div>

    {{-- CTA --}}
    @guest
    <div class="sd-cta-row">
        <a href="{{ route('register') }}" class="sd-btn-primary">🚀 Đăng ký miễn phí — Theo dõi {{ $stock->code }}</a>
        <a href="{{ route('home') }}" class="sd-btn-secondary">📊 Xem bảng giá</a>
    </div>
    @endguest

</div>
@endsection
