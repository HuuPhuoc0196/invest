@extends('Emails.layout')

@section('content')
<p>Xin chào,</p>
<p>Bạn (hoặc ai đó) đã yêu cầu đặt lại mật khẩu cho tài khoản tại <strong>Hệ thống đầu tư cá nhân</strong>. Để đặt lại mật khẩu, vui lòng bấm vào nút bên dưới.</p>
<p>
    <a href="{{ $resetUrl }}" class="btn">Đặt lại mật khẩu</a>
</p>
<p>Link có hiệu lực trong <strong>{{ $expiryMinutes }} phút</strong>. Sau thời gian đó bạn cần gửi yêu cầu mới từ trang Quên mật khẩu.</p>
<p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này và giữ nguyên mật khẩu hiện tại.</p>
@endsection
