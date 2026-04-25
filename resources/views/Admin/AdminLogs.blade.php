@extends('Layout.LayoutAdmin')

@section('title', 'Quản lý Log')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/admin-logs.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
    @include('partials.page-title-invest', ['title' => 'Quản lý Log', 'level' => 1])

    <div class="invest-narrow-wrap-full">
        <div class="admin-logs-grid">

            {{-- Card Logs Hosting --}}
            <section class="profile-detail-card admin-logs-card" aria-labelledby="log-hosting-title">
                <div class="admin-logs-card__icon">🖥️</div>
                <h2 id="log-hosting-title" class="profile-detail-card__title">Logs Hosting</h2>
                <p class="admin-logs-card__desc">
                    Log của server hosting — nơi ứng dụng Laravel đang chạy.<br>
                    Ghi nhận lỗi, exception, cache, email và các sự kiện hệ thống.
                    File log được tạo mỗi ngày, lưu tối đa <strong>30 ngày</strong>.
                </p>
                <div class="admin-logs-card__meta">
                    <span class="admin-logs-badge admin-logs-badge--active">● Hosting server</span>
                    <span class="admin-logs-card__retention">storage/logs/laravel-YYYY-MM-DD.log</span>
                </div>
                <a href="{{ url('/admin/log-viewer') }}" rel="noopener noreferrer"
                   class="admin-logs-card__btn">
                    Mở Log Viewer →
                </a>
            </section>

            {{-- Card Logs VPS --}}
            <section class="profile-detail-card admin-logs-card" aria-labelledby="log-vps-title">
                <div class="admin-logs-card__icon">⚡</div>
                <h2 id="log-vps-title" class="profile-detail-card__title">Logs VPS</h2>
                <p class="admin-logs-card__desc">
                    Log của VPS bên ngoài — server chạy script sync giá và mức độ rủi ro cổ phiếu.<br>
                    Lấy trực tiếp từ service VPS qua API, ghi nhận hoạt động sync hàng ngày, lỗi kết nối, và các sự kiện liên quan đến dữ liệu cổ phiếu.<br>
                    {{-- <code style="font-size:0.8em; opacity:0.75;">{{ config('services.sync.base_url') }}</code>. --}}
                </p>
                <div class="admin-logs-card__meta">
                    <span class="admin-logs-badge admin-logs-badge--vps">● Service VPS</span>
                </div>
                <a href="{{ url('/admin/logsVPS') }}" rel="noopener noreferrer"
                   class="admin-logs-card__btn admin-logs-card__btn--secondary">
                    Mở Logs VPS →
                </a>
            </section>

        </div>
    </div>
@endsection
