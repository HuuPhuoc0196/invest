@extends('Layout.LayoutAdmin')

@section('title', 'Quản lý truy cập')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/pages/admin-access-management.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.admin-nav-primary')
@endsection

@section('admin-body-content')
<div class="admin-access-page">

    @include('partials.page-title-invest', ['title' => 'Quản lý truy cập', 'level' => 1])

    {{-- Period selector --}}
    <div class="am-toolbar">
        <span class="am-toolbar__label">Kỳ:</span>
        <a href="{{ route('admin.accessManagement', ['period' => 'today']) }}"
           class="am-period-btn {{ $period === 'today' ? 'active' : '' }}">Hôm nay</a>
        <a href="{{ route('admin.accessManagement', ['period' => 'week']) }}"
           class="am-period-btn {{ $period === 'week'  ? 'active' : '' }}">Tuần này</a>
        <a href="{{ route('admin.accessManagement', ['period' => 'month']) }}"
           class="am-period-btn {{ $period === 'month' ? 'active' : '' }}">Tháng này</a>
        <a href="{{ route('admin.accessManagement', ['period' => 'year']) }}"
           class="am-period-btn {{ $period === 'year'  ? 'active' : '' }}">Năm nay</a>
        <span class="am-toolbar__from">từ {{ $from->format('H:i d/m/Y') }}</span>
    </div>

    {{-- KPI row --}}
    <div class="am-kpi-row">
        <div class="am-kpi-card am-kpi-card--auth">
            <div class="am-kpi-label">Phiên đăng nhập</div>
            <div class="am-kpi-value">{{ number_format($uniqueAuthSessions) }}</div>
        </div>
        <div class="am-kpi-card am-kpi-card--anon">
            <div class="am-kpi-label">Phiên ẩn danh</div>
            <div class="am-kpi-value">{{ number_format($uniqueAnonSessions) }}</div>
        </div>
        <div class="am-kpi-card am-kpi-card--total">
            <div class="am-kpi-label">Tổng lượt xem</div>
            <div class="am-kpi-value">{{ number_format($totalPageviews) }}</div>
        </div>
        <div class="am-kpi-card am-kpi-card--users">
            <div class="am-kpi-label">User riêng biệt</div>
            <div class="am-kpi-value">{{ number_format($uniqueUsers) }}</div>
        </div>
    </div>

    {{-- Two-column grid --}}
    <div class="am-grid">

        {{-- Top Users --}}
        <div class="am-section">
            <div class="am-section-header">
                <span class="am-section-title">Top Users</span>
                @if($topUsers->isNotEmpty())
                    <span class="am-section-count">{{ $topUsers->count() }}</span>
                @endif
            </div>
            <div class="table-container">
                <table class="am-table table-wide">
                    <thead class="sticky-header">
                        <tr>
                            <th class="am-th--rank">#</th>
                            <th>Người dùng</th>
                            <th class="am-th--count">Phiên</th>
                            <th class="am-th--count">Trang</th>
                            <th class="am-th--time">Lần cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topUsers as $i => $u)
                        <tr>
                            <td class="am-td--rank">{{ $i + 1 }}</td>
                            <td>
                                <div class="am-cell-name">{{ $u->name }}</div>
                                <div class="am-cell-sub">{{ $u->email }}</div>
                            </td>
                            <td class="am-td--count">
                                <span class="am-count-badge">{{ number_format($u->session_count) }}</span>
                            </td>
                            <td class="am-td--count">
                                <span class="am-count-badge am-count-badge--muted">{{ number_format($u->pageview_count) }}</span>
                            </td>
                            <td class="am-td--time">
                                {{ $u->last_visit ? \Carbon\Carbon::parse($u->last_visit)->format('H:i d/m') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr class="am-empty-row">
                            <td colspan="5">Chưa có dữ liệu trong kỳ này.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Pages --}}
        <div class="am-section">
            <div class="am-section-header">
                <span class="am-section-title">Trang xem nhiều nhất</span>
                @if($topPages->isNotEmpty())
                    <span class="am-section-count">{{ $topPages->count() }}</span>
                @endif
            </div>
            <div class="table-container">
                <table class="am-table table-wide">
                    <thead class="sticky-header">
                        <tr>
                            <th class="am-th--rank">#</th>
                            <th>Trang</th>
                            <th class="am-th--count">Lượt</th>
                            <th class="am-th--count">Phiên</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topPages as $i => $p)
                        <tr>
                            <td class="am-td--rank">{{ $i + 1 }}</td>
                            <td>
                                <div class="am-cell-name">{{ $p->page_title ?: 'Trang khác' }}</div>
                                <div class="am-cell-sub">{{ $p->page }}</div>
                            </td>
                            <td class="am-td--count">
                                <span class="am-count-badge">{{ number_format($p->view_count) }}</span>
                            </td>
                            <td class="am-td--count">
                                <span class="am-count-badge am-count-badge--muted">{{ number_format($p->unique_sessions) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr class="am-empty-row">
                            <td colspan="4">Chưa có dữ liệu trong kỳ này.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /.am-grid --}}
</div>{{-- /.admin-access-page --}}
@endsection
