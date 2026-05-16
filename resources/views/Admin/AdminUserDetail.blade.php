@extends('Layout.LayoutAdmin')

@section('title', 'Tài sản — ' . $user->name)

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/adminStockManagement.css')
    @vite('resources/css/pages/user-profile.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('admin-body-content')
    <div class="back-bar">
        <a href="{{ route('admin.users') }}" class="back-btn">← Quay lại</a>
    </div>

    @include('partials.page-title-invest', ['title' => 'Tài sản — ' . $user->name, 'level' => 1])

    <div class="profile-detail-wrap" style="margin-bottom:1.5rem;">
        <section class="profile-detail-card" aria-label="Thông tin user">
            <dl class="profile-detail-list">
                <div class="profile-detail-row">
                    <dt>Email</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Vai trò</dt>
                    <dd>{{ (int) ($user->role ?? 0) === 1 ? 'Quản trị viên' : 'Nhà đầu tư' }}</dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Số mã đang giữ</dt>
                    <dd>{{ count($userPortfolios) }}</dd>
                </div>
            </dl>
        </section>
    </div>

    @include('partials.page-title-invest', ['title' => 'Danh sách mã cổ phiếu đang giữ', 'level' => 2])

    <div class="table-container">
        <table id="stock-table" class="table-wide">
            <thead class="sticky-header">
                <tr>
                    <th class="col-code-sticky">Mã CK</th>
                    <th>Khối lượng nắm giữ</th>
                    <th>Giá vốn</th>
                    <th>Giá hiện tại</th>
                    <th>Giá trị vốn</th>
                    <th>Giá trị thị trường</th>
                    <th>Tiền lãi</th>
                    <th>% lãi</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
            </tbody>
        </table>
        <table id="invest-table" class="table-wide">
            <thead class="sticky-header">
                <tr>
                    <th class="col-code-sticky">Danh mục</th>
                    <th>Vốn đầu tư</th>
                    <th>Giá trị hiện tại</th>
                    <th>Tiền lãi</th>
                    <th>% lãi</th>
                </tr>
            </thead>
            <tbody id="investTableBody">
            </tbody>
        </table>
    </div>
@endsection

@section('admin-script')
    <script>
        window.__pageData = {
            userPortfolios: @json($userPortfolios),
            userInvestCash: @json($userInvestCash)
        };
    </script>
    @vite('resources/js/pages/user-profile.js')
@endsection
