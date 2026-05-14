@extends('Layout.LayoutAdmin')

@section('title', 'Quản lý Crontab')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/pages/admin-crontab.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
<div class="admin-crontab-page">

    {{-- Flash --}}
    <div id="crontab-flash" class="admin-crontab-flash" hidden></div>

    {{-- Toolbar --}}
    <div class="admin-crontab-toolbar">
        <div class="admin-crontab-toolbar__left">
            <button id="btn-add-open"    class="btn-crontab btn-crontab--add"    type="button">+ Thêm cron job</button>
            <button id="btn-clear-cache" class="btn-crontab btn-crontab--danger" type="button">🗑️ Xóa Cache</button>
        </div>
        <div class="admin-crontab-toolbar__right">
            <span id="crontab-status" class="admin-crontab-status" aria-live="polite"></span>
            <button id="btn-refresh" class="btn-crontab btn-crontab--refresh" type="button">↻ Làm mới</button>
        </div>
    </div>

    @include('partials.page-title-invest', ['title' => 'Quản lý Crontab VPS', 'level' => 1])

    {{-- Add / Edit form --}}
    <div id="crontab-form-wrap" class="profile-detail-card admin-crontab-form" hidden>
        <h2 id="crontab-form-title" class="admin-crontab-form__title">Thêm cron job</h2>
        <div class="admin-crontab-form__grid">
            <label class="admin-crontab-field">
                <span class="admin-crontab-field__label">Phút</span>
                <input id="cf-minute"  class="admin-crontab-input" type="text" value="0" placeholder="0–59 / *">
            </label>
            <label class="admin-crontab-field">
                <span class="admin-crontab-field__label">Giờ</span>
                <input id="cf-hour"    class="admin-crontab-input" type="text" value="15" placeholder="0–23 / *">
            </label>
            <label class="admin-crontab-field">
                <span class="admin-crontab-field__label">Ngày</span>
                <input id="cf-day"     class="admin-crontab-input" type="text" value="*" placeholder="1–31 / *">
            </label>
            <label class="admin-crontab-field">
                <span class="admin-crontab-field__label">Tháng</span>
                <input id="cf-month"   class="admin-crontab-input" type="text" value="*" placeholder="1–12 / *">
            </label>
            <label class="admin-crontab-field">
                <span class="admin-crontab-field__label">Thứ</span>
                <input id="cf-weekday" class="admin-crontab-input" type="text" value="1-5" placeholder="0–6 / *">
            </label>
            <label class="admin-crontab-field admin-crontab-field--endpoint">
                <span class="admin-crontab-field__label">Endpoint</span>
                <input id="cf-endpoint" class="admin-crontab-input" type="text" placeholder="/run-sync-price">
            </label>
        </div>
        <p class="admin-crontab-form__desc">
            <span class="admin-crontab-form__desc-label">Mô tả:</span>
            <span id="cf-desc" class="admin-crontab-form__desc-text">—</span>
        </p>
        <div class="admin-crontab-form__actions">
            <button id="btn-form-save"   class="btn-crontab btn-crontab--add"     type="button">✅ Lưu</button>
            <button id="btn-form-cancel" class="btn-crontab btn-crontab--refresh" type="button">❌ Hủy</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-container">
        <div id="crontab-error" class="admin-crontab-error" hidden></div>
        <table id="crontab-table">
            <thead class="sticky-header">
                <tr>
                    <th class="ct-th--idx">#</th>
                    <th class="ct-th--name">Tên / Endpoint</th>
                    <th class="ct-th--schedule">Schedule</th>
                    <th class="ct-th--type">Loại</th>
                    <th class="ct-th--status">Trạng thái</th>
                    <th class="ct-th--actions">Hành động</th>
                </tr>
            </thead>
            <tbody id="crontab-tbody">
                <tr><td colspan="6" class="ct-empty">Đang tải…</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Modal: Confirm Clear All Cache --}}
    <div id="clearCacheModal" class="ct-modal-overlay" hidden aria-modal="true" role="dialog" aria-labelledby="clearCacheModalTitle">
        <div class="ct-modal">
            <button type="button" class="ct-modal__close" id="btnClearCacheClose" aria-label="Đóng">&times;</button>
            <div class="ct-modal__icon">🗑️</div>
            <h2 class="ct-modal__title" id="clearCacheModalTitle">Xóa toàn bộ Cache</h2>
            <p class="ct-modal__body">
                Thao tác này sẽ xóa <strong>toàn bộ cache</strong> của hệ thống.<br>
                Lần tải trang tiếp theo sẽ lấy dữ liệu mới nhất trực tiếp từ database.
            </p>
            <div class="ct-modal__actions">
                <button type="button" class="btn-crontab btn-crontab--refresh" id="btnClearCacheCancel">Hủy</button>
                <button type="button" class="btn-crontab btn-crontab--danger"  id="btnClearCacheConfirm">🗑️ Xóa Cache</button>
            </div>
        </div>
    </div>

</div>
@endsection

@section('admin-script')
    <script>
        window.__crontabRoutes = {
            list:       '{{ route('admin.crontab.list') }}',
            add:        '{{ route('admin.crontab.add') }}',
            update:     '{{ url('/admin/crontab/update') }}',
            delete:     '{{ url('/admin/crontab/delete') }}',
            toggle:     '{{ url('/admin/crontab/toggle') }}',
            run:        '{{ url('/admin/crontab/run') }}',
            clearCache: '{{ route('admin.cache.clearAll') }}',
            csrf:       '{{ csrf_token() }}',
        };
    </script>
    @vite('resources/js/pages/admin-crontab.js')
@endsection
