<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PagesController extends Controller
{
    public function about()
    {
        return view('Pages.AboutView');
    }

    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name'    => 'required|string|max:100',
                'email'   => 'required|email|max:200',
                'subject' => 'required|string|max:200',
                'message' => 'required|string|max:2000',
            ], [
                'name.required'    => 'Vui lòng nhập họ tên.',
                'email.required'   => 'Vui lòng nhập email.',
                'email.email'      => 'Email không hợp lệ.',
                'subject.required' => 'Vui lòng nhập tiêu đề.',
                'message.required' => 'Vui lòng nhập nội dung.',
            ]);

            try {
                Mail::to('lehuuphuoc0196@gmail.com')->send(new ContactMail($validated));
            } catch (\Throwable $e) {
                Log::error('Contact form mail error', ['error' => $e->getMessage()]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không thể gửi tin nhắn lúc này. Vui lòng thử lại sau.',
                ], 500);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Tin nhắn đã được gửi thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.',
            ]);
        }

        return view('Pages.ContactView');
    }
}
