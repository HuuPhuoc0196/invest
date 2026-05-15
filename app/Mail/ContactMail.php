<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Liên hệ] ' . $this->data['subject'] . ' — ' . $this->data['name'],
            replyTo: [new Address($this->data['email'], $this->data['name'])],
        );
    }

    public function content(): Content
    {
        $name    = e($this->data['name']);
        $email   = e($this->data['email']);
        $subject = e($this->data['subject']);
        $message = nl2br(e($this->data['message']));

        $html = "
            <strong>Họ tên:</strong> {$name}<br>
            <strong>Email:</strong> {$email}<br>
            <strong>Tiêu đề:</strong> {$subject}<br><br>
            <strong>Nội dung:</strong><br>{$message}
        ";

        return new Content(
            view: 'Emails.Notify',
            with: ['content' => $html],
        );
    }
}
