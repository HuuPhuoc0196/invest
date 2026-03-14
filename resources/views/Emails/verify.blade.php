@extends('Emails.layout')

@section('content')
<p>Xin chào,</p>
<p>Bạn vừa đăng ký tài khoản tại <strong>Hệ thống đầu tư cá nhân</strong>. Để kích hoạt tài khoản, vui lòng xác nhận địa chỉ email bằng cách bấm vào nút bên dưới.</p>
<p>
    <a href="{{ $verificationUrl }}" class="btn">Xác nhận địa chỉ email</a>
</p>
<p>Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email.</p>
@endsection
