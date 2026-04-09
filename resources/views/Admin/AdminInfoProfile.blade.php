@extends('Layout.Layout')

@section('title', 'Thông tin cá nhân')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.admin-nav-primary')
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Thông tin cá nhân', 'level' => 1])

    <div class="profile-detail-wrap">
        <section class="profile-detail-card" aria-labelledby="profile-account-heading">
            <h3 id="profile-account-heading" class="profile-detail-card__title">Tài khoản</h3>
            <dl class="profile-detail-list">
                <div class="profile-detail-row">
                    <dt>Họ và tên</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Email</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Xác thực email</dt>
                    <dd>
                        @if ($user->hasVerifiedEmail())
                            <span class="profile-badge profile-badge--ok">Đã xác thực</span>
                        @else
                            <span class="profile-badge profile-badge--warn">Chưa xác thực</span>
                        @endif
                    </dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Vai trò</dt>
                    <dd>{{ (int) ($user->role ?? 0) === 1 ? 'Quản trị viên' : 'Nhà đầu tư' }}</dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Ngày tham gia</dt>
                    <dd>{{ $user->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="profile-detail-row">
                    <dt>Cập nhật lần cuối</dt>
                    <dd>{{ $user->updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </section>

        <section class="profile-detail-card profile-detail-card--actions" aria-label="Thao tác nhanh">
            <a href="{{ url('/admin/updateInfoProfile') }}" class="button-link profile-detail-cta">✏️ Cập nhật thông tin</a>
            <a href="{{ url('/admin/changePassword') }}" class="button-link profile-detail-cta profile-detail-cta--secondary">🔐 Đổi mật khẩu</a>
        </section>
    </div>
@endsection
