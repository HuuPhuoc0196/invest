@extends('Layout.Layout')

@section('title', 'Thông tin cá nhân')

@section('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/adminView.css')
@endsection

@section('header-js')
    @vite('resources/js/app.js')
@endsection

@section('user-info')
    <div class="user-info">
        {{-- <img src="{{ asset('images/default-avatar.png') }}" alt="User Avatar" class="avatar"> --}}
        <div class="user-details">
            <p class="user-name">👤 {{ Auth::user()->name }}</p>
            <p class="user-email">📧 {{ Auth::user()->email }}</p>
        </div>
    </div>
@endsection

@section('actions-left')
    <a href="{{ url('/') }}" class="button-link">🏠 Trang chủ</a>
    <a href="{{ url('/user/updateInfoProfile') }}" class="button-link">👤✏️ Cập nhật thông tin cá nhân</a>
    <a href="{{ url('/user/changePassword') }}" class="button-link">👤⚙️ Thay đổi mật khẩu</a>
@endsection

{{-- @section('user-body-content')
<h1>Danh sách cổ phiếu</h1>

<div class="table-container">
    <table id="user-info-profile-table">
        <thead>
            <tr>
                <th>Mã cổ phiếu</th>
                <th>Khối lượng giao dịch</th>
                <th>Giá trung bình</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="userInfoTableBody">
        </tbody>
    </table>
</div>
<!-- Modal xác nhận xoá -->
<div id="confirmModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                                                                                            background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
        <p>Bạn có chắc chắn muốn xoá?</p>
        <button id="confirmYes">Có</button>
        <button id="confirmNo">Không</button>
    </div>
    @endsection --}}

    @section('user-script')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            const baseUrl = "{{ url('') }}";
            // var userPortfolios = @json($userPortfolios);
            // let deleteUrl = "";

            // document.addEventListener("DOMContentLoaded", function () {
            //     user = new User();
            //     user.renderTableUserInfoProfile(userPortfolios);
            // });

            // function confirmDelete(code) {
            //     deleteUrl = `${baseUrl}/user/deleteUserProfileCode/${code}`;
            //     document.getElementById("confirmModal").style.display = "flex";
            // }

            // document.getElementById("confirmYes").onclick = function () {
            //     window.location.href = deleteUrl;
            // };

            // document.getElementById("confirmNo").onclick = function () {
            //     document.getElementById("confirmModal").style.display = "none";
            //     deleteUrl = "";
            // };
        </script>
    @endsection