@extends('Layout.LayoutAdmin')

@section('title', 'Logs VPS')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/admin-logs-vps.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
    <div class="invest-narrow-wrap-full">

        <div class="back-bar">
            <a href="{{ route('admin.logs') }}" class="back-btn">← Quay lại</a>
        </div>

        @include('partials.page-title-invest', ['title' => 'Logs VPS', 'level' => 1])

        {{-- Toolbar --}}
        <div class="lvps-toolbar profile-detail-card">
            <div class="lvps-toolbar__left">
                <select id="lvps-date" class="lvps-select" aria-label="Chọn ngày">
                    <option value="">Hôm nay</option>
                </select>
                <select id="lvps-level" class="lvps-select" aria-label="Lọc theo level">
                    <option value="">Tất cả level</option>
                    <option value="INFO">INFO</option>
                    <option value="WARNING">WARNING</option>
                    <option value="ERROR">ERROR</option>
                </select>
                <input id="lvps-search" class="lvps-input" type="search" placeholder="Tìm trong log…" aria-label="Tìm kiếm">
            </div>
            <div class="lvps-toolbar__right">
                <span id="lvps-status" class="lvps-status" aria-live="polite"></span>
                <button id="lvps-refresh" class="lvps-btn" type="button" title="Làm mới">↻ Làm mới</button>
            </div>
        </div>

        {{-- Log table --}}
        <div class="profile-detail-card lvps-table-wrap" role="region" aria-label="Bảng log VPS">
            <div id="lvps-error" class="lvps-error" style="display:none;"></div>
            <table class="lvps-table" aria-label="Log entries">
                <thead>
                    <tr>
                        <th class="lvps-th lvps-th--time">Thời gian</th>
                        <th class="lvps-th lvps-th--level">Level</th>
                        <th class="lvps-th lvps-th--msg">Nội dung</th>
                    </tr>
                </thead>
                <tbody id="lvps-tbody">
                    <tr><td colspan="3" class="lvps-empty">Đang tải…</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="lvps-pagination" id="lvps-pagination" role="navigation" aria-label="Phân trang log"></div>

    </div>
@endsection

@section('admin-script')
    <script>
        window.LVPS_DATA_URL = '{{ route('admin.logsVPS.data') }}';
    </script>
    @vite('resources/js/pages/admin-logs-vps.js')
@endsection
