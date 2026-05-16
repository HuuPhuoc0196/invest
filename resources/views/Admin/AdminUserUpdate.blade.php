@extends('Layout.LayoutAdmin')

@section('title', 'Cập nhật user')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminInsert.css')
    @vite('resources/css/pages/admin-user-update.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('admin-body-content')
    <div class="back-bar">
        <a href="{{ route('admin.users') }}" class="back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Cập nhật user'])

    <div class="invest-narrow-wrap">
        <div class="profile-detail-card">
            @if ($errors->any())
                <div class="admin-user-update-flash admin-user-update-flash--error">{{ $errors->first() }}</div>
            @endif

            <form class="form-container admin-user-update-form" method="POST" action="{{ route('admin.users.update', ['id' => $user->id]) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="id">ID</label>
                    <input type="text" id="id" value="{{ $user->id }}" readonly aria-readonly="true">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="{{ $user->email }}" readonly aria-readonly="true">
                </div>

                <div class="form-group">
                    <label for="name">Tên</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="role">Vai trò</label>
                    <select id="role" name="role">
                        <option value="1" @selected((int) old('role', $user->role) === 1)>Quản trị viên</option>
                        <option value="0" @selected((int) old('role', $user->role) === 0)>Nhà đầu tư</option>
                    </select>
                    <span id="role-badge" class="user-update-select-badge"></span>
                </div>

                <div class="form-group">
                    <label for="active">Trạng thái</label>
                    <select id="active" name="active">
                        <option value="1" @selected((int) old('active', $user->active) === 1)>Đã active</option>
                        <option value="0" @selected((int) old('active', $user->active) === 0)>Chưa active</option>
                    </select>
                    <span id="active-badge" class="user-update-select-badge"></span>
                </div>

                <div class="form-group">
                    <label for="email_verified_at">Xác thực email</label>
                    <select id="email_verified_at" name="email_verified">
                        <option value="1" @selected((int) old('email_verified', $user->email_verified_at ? 1 : 0) === 1)>Đã xác thực</option>
                        <option value="0" @selected((int) old('email_verified', $user->email_verified_at ? 1 : 0) === 0)>Chưa xác thực</option>
                    </select>
                    <span id="email-verified-badge" class="user-update-select-badge"></span>
                </div>

                <button type="submit" id="btnFormSubmit">Cập nhật</button>
            </form>
        </div>
    </div>

    <div id="userUpdateNoticeModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeUserUpdateNoticeModal()">&times;</span>
            <h2 id="userUpdateNoticeTitle">Thông báo</h2>
            <div id="userUpdateNoticeMessage" class="user-update-notice-modal__message"></div>
            <div class="modal-actions">
                <button class="btn-import" type="button" onclick="closeUserUpdateNoticeModal()">Đóng</button>
            </div>
        </div>
    </div>
@endsection

@section('admin-script')
    <script>
        window.closeUserUpdateNoticeModal = function () {
            var modal = document.getElementById('userUpdateNoticeModal');
            if (!modal) return;
            modal.style.display = 'none';
        };

        (function () {
            var BADGE_CONFIGS = {
                role: function (val) {
                    return val === '1'
                        ? { cls: 'user-update-select-badge--role-admin', label: 'Quản trị viên' }
                        : { cls: 'user-update-select-badge--role-investor', label: 'Nhà đầu tư' };
                },
                active: function (val) {
                    return val === '1'
                        ? { cls: 'user-update-select-badge--status-active', label: 'Đã active' }
                        : { cls: 'user-update-select-badge--status-inactive', label: 'Chưa active' };
                },
                email_verified_at: function (val) {
                    return val === '1'
                        ? { cls: 'user-update-select-badge--status-active', label: 'Đã xác thực' }
                        : { cls: 'user-update-select-badge--status-inactive', label: 'Chưa xác thực' };
                },
            };

            function wireSelectBadge(selectId, badgeId, configFn) {
                var sel = document.getElementById(selectId);
                var badge = document.getElementById(badgeId);
                if (!sel || !badge) return;
                function refresh() {
                    var cfg = configFn(sel.value);
                    badge.className = 'user-update-select-badge ' + cfg.cls;
                    badge.textContent = cfg.label;
                }
                sel.addEventListener('change', refresh);
                refresh();
            }

            document.addEventListener('DOMContentLoaded', function () {
                wireSelectBadge('role', 'role-badge', BADGE_CONFIGS.role);
                wireSelectBadge('active', 'active-badge', BADGE_CONFIGS.active);
                wireSelectBadge('email_verified_at', 'email-verified-badge', BADGE_CONFIGS.email_verified_at);

                var modal = document.getElementById('userUpdateNoticeModal');
                if (modal) {
                    modal.addEventListener('click', function (e) {
                        if (e.target === modal) window.closeUserUpdateNoticeModal();
                    });
                }

                @if (session('success'))
                    if (modal) {
                        modal.classList.remove('is-error');
                        modal.classList.add('is-success');
                        document.getElementById('userUpdateNoticeTitle').textContent = 'Cập nhật thành công';
                        document.getElementById('userUpdateNoticeMessage').innerHTML = @json('✅ ' . session('success'));
                        modal.style.display = 'flex';
                    }
                @endif
            });
        })();
    </script>
@endsection
