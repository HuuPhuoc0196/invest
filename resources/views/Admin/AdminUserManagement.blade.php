@extends('Layout.LayoutAdmin')

@section('title', 'Quản lý user')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminStockManagement.css')
    @vite('resources/css/adminUserManagement.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('actions-right')
    <input type="text" id="searchInput" placeholder="Nhập gmail user...">
    <button type="button" onclick="searchUser()">🔍 Tìm kiếm</button>
@endsection

@section('admin-body-content')
    <div class="admin-users-page">
        @include('partials.page-title-invest', ['title' => 'Danh sách user', 'level' => 1])

        @if (session('error'))
            <div class="admin-stock-flash admin-stock-flash--error">{{ session('error') }}</div>
        @endif

        <div class="filter-panel">
            <div class="filter-header" onclick="toggleFilter()">
                <span>🔧 Bộ lọc dữ liệu</span>
                <span id="filterToggleIcon">▼</span>
            </div>
            <div id="filterBody" class="filter-body" style="display:none;">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Xác thực email:</label>
                        <select id="filterEmailVerified">
                            <option value="">-- Tất cả --</option>
                            <option value="1">Đã xác thực</option>
                            <option value="0">Chưa xác thực</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Trạng thái:</label>
                        <select id="filterActive">
                            <option value="">-- Tất cả --</option>
                            <option value="1">Đã active</option>
                            <option value="0">Chưa active</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Vai trò:</label>
                        <select id="filterRole">
                            <option value="">-- Tất cả --</option>
                            <option value="1">Quản trị viên</option>
                            <option value="0">Nhà đầu tư</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn-filter" type="button" onclick="applyFilter()">🔍 Lọc</button>
                    <button class="btn-filter-reset" type="button" onclick="resetFilter()">🔄 Đặt lại</button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table id="user-table">
                <thead class="sticky-header">
                    <tr>
                        <th data-sort-key="id">ID <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="email">Email <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="name">Tên <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="role">Vai trò <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="active">Trạng thái <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="email_verified_at">Xác thực email <span class="sort-icon">⇅</span></th>
                        <th data-sort-key="created_at">Ngày đăng ký <span class="sort-icon">⇅</span></th>
                        <th>Cập nhật</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
            </table>
        </div>

        <div id="deleteUserModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <span class="modal-close" onclick="closeDeleteUserModal()">&times;</span>
                <h2>Xác nhận xoá user</h2>
                <div class="delete-user-modal__message">
                    Bạn có chắc chắn muốn xoá user <b id="deleteUserEmail"></b>?
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" type="button" onclick="closeDeleteUserModal()">Huỷ</button>
                    <button class="btn-delete-confirm" id="btnDeleteUserConfirm" type="button" onclick="runDeleteUser()">Xoá</button>
                </div>
            </div>
        </div>

        <div id="userNoticeModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <span class="modal-close" onclick="closeUserNoticeModal()">&times;</span>
                <h2 id="userNoticeTitle">Thông báo</h2>
                <div id="userNoticeMessage" class="delete-user-modal__message"></div>
                <div class="modal-actions">
                    <button class="btn-import" type="button" onclick="closeUserNoticeModal()">Đồng ý</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('admin-script')
    @vite('resources/js/AdminUserManagement.js')
    <script>
        window.__pageData = {
            baseUrl: @json(url('')),
            users: @json($users),
            currentAdminId: @json(auth()->id()),
            flashSuccess: @json(session('success')),
        };
    </script>
    @vite('resources/js/pages/admin-user-management.js')
@endsection
