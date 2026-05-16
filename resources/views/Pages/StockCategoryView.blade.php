@extends('Layout.Layout')

@section('title', $title . ' — ' . config('app.name'))

@section('seo')
    <meta name="robots" content="index, follow">
    @include('partials.seo-public', [
        'pageTitle'   => $title . ' — ' . config('app.name'),
        'description' => $subtitle . ' Xem giá hiện tại, mức rủi ro và thông tin chi tiết từng mã cổ phiếu.',
    ])
    <script type="application/ld+json">
    {!! json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/stock-category.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @auth
        @include('partials.user-nav-primary')
    @else
        @include('partials.guest-nav-actions')
    @endauth
@endsection

@section('user-body-content')
@php
    $riskLabels  = [1 => 'An toàn', 2 => 'Cảnh báo', 3 => 'Hạn chế', 4 => 'Đình chỉ'];
    $riskClasses = [1 => 'risk-safe', 2 => 'risk-warn', 3 => 'risk-limit', 4 => 'risk-halt'];
    $countSafe   = $stocks->where('risk_level', 1)->count();
    $countWarn   = $stocks->where('risk_level', 2)->count();
    $countOther  = $stocks->whereIn('risk_level', [3, 4])->count();
@endphp

<div class="sc-page">

    {{-- ─── HEADER ─── --}}
    <div class="sc-header">
        <div class="sc-header__left">
            <p class="sc-header__eyebrow">Sàn HOSE · Cập nhật hàng ngày</p>
            <h1 class="sc-header__title">{{ $title }}</h1>
            <p class="sc-header__sub">{{ $subtitle }}</p>
        </div>
        <div class="sc-header__right">
            <div class="sc-stat-group">
                <div class="sc-stat-item">
                    <span class="sc-stat-num">{{ $stocks->count() }}</span>
                    <span class="sc-stat-label">Mã CK</span>
                </div>
                <div class="sc-stat-divider"></div>
                <div class="sc-stat-item">
                    <span class="sc-stat-num sc-stat-num--safe">{{ $countSafe }}</span>
                    <span class="sc-stat-label">An toàn</span>
                </div>
                @if ($countWarn > 0)
                <div class="sc-stat-divider"></div>
                <div class="sc-stat-item">
                    <span class="sc-stat-num sc-stat-num--warn">{{ $countWarn }}</span>
                    <span class="sc-stat-label">Cảnh báo</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── TAB SWITCH VN30 / VN100 ─── --}}
    <div class="sc-tabs">
        <a href="{{ route('stocks.vn30') }}"
           class="sc-tab {{ $categoryKey === 'vn30' ? 'is-active' : '' }}">
            VN30
            <span class="sc-tab__count">30</span>
        </a>
        <a href="{{ route('stocks.vn100') }}"
           class="sc-tab {{ $categoryKey === 'vn100' ? 'is-active' : '' }}">
            VN100
            <span class="sc-tab__count">~95</span>
        </a>
        <a href="{{ route('home') }}" class="sc-tab">
            Bảng giá đầy đủ
        </a>
    </div>

    {{-- ─── TABLE ─── --}}
    <div class="sc-table-wrap">
        <table class="sc-table" aria-label="{{ $title }}">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Mã CK</th>
                    <th scope="col">Giá hiện tại</th>
                    <th scope="col">% Thay đổi</th>
                    <th scope="col">Rủi ro</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $index => $stock)
                    @php
                        $riskLevel = (int) $stock->risk_level;
                        $riskLabel = $riskLabels[$riskLevel] ?? 'Không rõ';
                        $riskClass = $riskClasses[$riskLevel] ?? 'risk-safe';
                        $pct       = (float) $stock->percent_stock;
                        $pctClass  = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
                        $pctPrefix = $pct > 0 ? '+' : '';
                        $detailUrl = url('/co-phieu/' . strtoupper($stock->code));
                    @endphp
                    <tr
                        class="sc-row"
                        role="link"
                        tabindex="0"
                        onclick="window.location.href='{{ $detailUrl }}'"
                        onkeydown="if(event.key==='Enter'||event.key===' ')window.location.href='{{ $detailUrl }}'"
                        aria-label="Xem chi tiết {{ strtoupper($stock->code) }}"
                    >
                        <td class="sc-idx">{{ $index + 1 }}</td>
                        <td>
                            <a href="{{ $detailUrl }}" class="sc-code" tabindex="-1">
                                {{ strtoupper($stock->code) }}
                            </a>
                        </td>
                        <td class="sc-price">
                            {{ number_format($stock->current_price, 0, ',', '.') }}&nbsp;đ
                        </td>
                        <td>
                            <span class="sc-change {{ $pctClass }}">
                                {{ $pctPrefix }}{{ number_format($pct, 2) }}%
                            </span>
                        </td>
                        <td>
                            <span class="sc-risk-badge {{ $riskClass }}">{{ $riskLabel }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="sc-empty">Chưa có dữ liệu cổ phiếu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ─── CTA ─── --}}
    <div class="sc-cta">
        @guest
            <div class="sc-cta__inner">
                <div class="sc-cta__icon">📊</div>
                <div class="sc-cta__text">
                    <p class="sc-cta__title">Theo dõi và quản lý danh mục cá nhân</p>
                    <p class="sc-cta__body">Đăng ký miễn phí để xem giá khuyến nghị, lịch sử rủi ro, điểm đánh giá và quản lý danh mục đầu tư của bạn.</p>
                </div>
                <div class="sc-cta__actions">
                    <a href="{{ route('register') }}" class="sc-cta__btn sc-cta__btn--primary">Đăng ký miễn phí →</a>
                    <a href="{{ route('login') }}" class="sc-cta__btn sc-cta__btn--ghost">Đã có tài khoản</a>
                </div>
            </div>
        @else
            <div class="sc-cta__inner">
                <div class="sc-cta__icon">🔔</div>
                <div class="sc-cta__text">
                    <p class="sc-cta__title">Đặt cảnh báo giá tự động</p>
                    <p class="sc-cta__body">Thêm các mã bạn quan tâm vào danh sách theo dõi và nhận thông báo qua email khi đến vùng giá mục tiêu.</p>
                </div>
                <div class="sc-cta__actions">
                    <a href="{{ url('/user/follow') }}" class="sc-cta__btn sc-cta__btn--primary">Danh sách theo dõi →</a>
                    <a href="{{ route('home') }}" class="sc-cta__btn sc-cta__btn--ghost">Xem bảng giá</a>
                </div>
            </div>
        @endguest
    </div>

</div>
@endsection
