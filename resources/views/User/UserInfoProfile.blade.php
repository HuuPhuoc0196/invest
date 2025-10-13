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

@section('user-script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = "{{ url('') }}";
    </script>
@endsection