<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public int $expiryMinutes;

    public function __construct(string $resetUrl, int $expiryMinutes = 60)
    {
        $this->resetUrl = $resetUrl;
        $this->expiryMinutes = $expiryMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Hệ thống đầu tư cá nhân] Đặt lại mật khẩu',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Emails.reset-password',
        );
    }
}
