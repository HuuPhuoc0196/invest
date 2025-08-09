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

    public static function sendSuggestInvestment($code, $current_price, $recommended_buy_price)
    {
        $content = self::getMessgaeSuggest($current_price, $recommended_buy_price);
        if (!$content) return $content;
        $to = 'lehuuphuoc0196@gmail.com';
        $subject = 'Investment suggest cổ phiếu <span style="color:red;"> ' . $code . '</span>';
        $message = 'Hệ thống ghi nhận cổ phiếu <span style="color:red;">' . $code . '</span> có giá hấp dẫn.';
        $message .= '<br/>Giá hiện tại là: ' . number_format($current_price, 0, ',', '.');
        $message .= '<br/>Giá khuyến nghị mua vào là:  <span style="color:red;">' . number_format($recommended_buy_price, 0, ',', '.') . '</span>';
        $message .= $content;

        Mail::to($to)->send(new NotifyUserMail($subject, $message));

        return "Email đã được gửi!";
    }

    protected static function getMessgaeSuggest($current_price, $recommended_buy_price)
    {
        if ($current_price > $recommended_buy_price) {
            $percentDiff = (($current_price - $recommended_buy_price) / $recommended_buy_price) * 100;
            if ($percentDiff <= 10) {
                return '<br/> % chênh lệch là: <span style="color:yellow;"> > ' . round($percentDiff, 2) . '% </span>';
            } else {
                return false;
            }
        } else {
            $percentDiff = (($recommended_buy_price - $current_price) / $recommended_buy_price) * 100;
            if ($percentDiff > 20) {
                return '<br/> % chênh lệch là: <span style="color:red;"> < ' . round($percentDiff, 2) . '% </span>';
            } else if ($percentDiff > 10) {
                return '<br/> % chênh lệch là: <span style="color:purple;"> < ' . round($percentDiff, 2) . '% </span>';
            } else {
                return '<br/> % chênh lệch là: <span style="color:green;"> < ' . round($percentDiff, 2) . '% </span>';
            }
        }
    }
}
