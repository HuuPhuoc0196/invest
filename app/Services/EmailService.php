<?php

namespace App\Services;

use App\Mail\NotifyUserMail;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public static function sendRiskChangeNotification($code, $oldRisk, $newRisk)
    {
        $oldRiskText = self::getRisk($oldRisk);
        $newRiskText = self::getRisk($newRisk);

        $to = 'lehuuphuoc0196@gmail.com';
        $subject = 'Investment cá nhân thông báo cổ phiếu <span style="color:red;"> ' . $code . '</span>';
        $message = 'Hệ thống ghi nhận cổ phiếu <span style="color:red;">' . $code . '</span> có thay đổi mức độ rủi ro.';
        $message .= '<br/>Chuyển từ ' . $oldRiskText;
        $message .= '<br/> Thành ' . $newRiskText;


        Mail::to($to)->send(new NotifyUserMail($subject, $message));

        return "Email đã được gửi!";
    }

    protected static function getRisk($rating)
    {
        switch (intval($rating)) {
            case 1:
                return '<span style="color:green;">1: An toàn</span>';
            case 2:
                return '<span style="color:goldenrod;">2: Tốt</span>';
            case 3:
                return '<span style="color:orange;">3: Nguy hiểm</span>';
            case 4:
                return '<span style="color:red;">4: Cực kỳ xấu</span>';
            default:
                return 'Không xác định';
        }
    }
}
