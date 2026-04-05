@extends('Layout.Layout')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Cài đặt thông báo email')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
    @vite('resources/css/pages/user-email-settings.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('actions-left')
    @include('partials.user-nav-primary')
@endsection

@section('user-body-content')
    @include('partials.page-title-invest', ['title' => 'Cài đặt thông báo email', 'level' => 1])

    <div class="email-settings-page">
    <div class="section-panel">
        <div class="section-header" onclick="toggleSectionFollow()">
            <span>🔔 Thông báo email cổ phiếu đã theo dõi</span>
            <span id="sectionToggleIconFollow">▼</span>
        </div>
        <div id="sectionBodyFollow" class="section-body" style="display:none;">
            <div class="save-bar">
                <button class="btn-save" id="btnSaveFollow" onclick="saveFlagsFollow()">💾 Lưu</button>
            </div>

            <div class="table-container table-container-follow">
                <table id="notice-table-follow">
                    <thead>
                        <tr>
                            <th>Mã CK</th>
                            <th>Giá mua theo dõi</th>
                            <th>Giá bán theo dõi</th>
                            <th><input type="checkbox" class="checkbox-all" id="checkAllFollow" onclick="toggleAllFollow()"></th>
                        </tr>
                    </thead>
                    <tbody id="noticeTableBodyFollow"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-panel">
        <div class="section-header" onclick="toggleSectionSession()">
            <span>⏰ Thông báo email cuối phiên cho cổ phiếu đã mua</span>
            <span id="sectionToggleIconSession">▼</span>
        </div>
        <div id="sectionBodySession" class="section-body" style="display:none;">
            <div class="save-bar">
                <button class="btn-save" id="btnSaveSession" onclick="saveFlagsSession()">💾 Lưu</button>
            </div>

            <div class="table-container table-container-session">
                <table id="notice-table-session">
                    <thead>
                        <tr>
                            <th>Mã CK</th>
                            <th><input type="checkbox" class="checkbox-all" id="checkAllSession" onclick="toggleAllSession()"></th>
                        </tr>
                    </thead>
                    <tbody id="noticeTableBodySession"></tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <div id="toast" class="toast"></div>
@endsection


@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.__pageData = {
            baseUrl: "{{ url('') }}",
            noticesFollow: @json($noticesFollow),
            sessionClosedItems: @json($sessionClosedItems)
        };
    </script>
    @vite('resources/js/pages/user-email-settings.js')
@endsection
