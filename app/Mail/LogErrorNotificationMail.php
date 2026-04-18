<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email thông báo khi có log WARNING hoặc ERROR
 */
class LogErrorNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $logLevel;
    public string $logMessage;
    public array $logContext;
    public string $logTime;
    public string $appUrl;
    public string $levelColor;
    public string $levelIcon;

    /**
     * Create a new message instance.
     */
    public function __construct(string $level, string $message, array $context, string $time)
    {
        $this->logLevel = $level;
        $this->logMessage = $message;
        $this->logContext = $context;
        $this->logTime = $time;
        $this->appUrl = config('app.url') ?? 'http://localhost';
        $this->levelColor = $this->getLevelColor($level);
        $this->levelIcon = $this->getLevelIcon($level);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = "[" . $this->appUrl . "] Log " . $this->logLevel;
        
        return $this->subject($subject)
            ->view('emails.log-error-simple');
    }

    /**
     * Get color based on log level
     */
    private function getLevelColor(string $level): string
    {
        return match($level) {
            'WARNING' => '#ff9800',
            'ERROR' => '#f44336',
            'CRITICAL' => '#d32f2f',
            'ALERT' => '#c62828',
            'EMERGENCY' => '#b71c1c',
            default => '#ff9800',
        };
    }

    /**
     * Get icon based on log level
     */
    private function getLevelIcon(string $level): string
    {
        return match($level) {
            'WARNING' => '⚠️',
            'ERROR' => '❌',
            'CRITICAL' => '🔥',
            'ALERT' => '🚨',
            'EMERGENCY' => '💥',
            default => '⚠️',
        };
    }
}
