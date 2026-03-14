<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;

    public function __construct(User $user)
    {
        $this->verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Hệ thống đầu tư cá nhân] Xác nhận địa chỉ email của bạn',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Emails.verify',
        );
    }
}
