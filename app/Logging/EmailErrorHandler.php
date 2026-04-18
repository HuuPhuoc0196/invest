<?php

namespace App\Logging;

use App\Mail\LogErrorNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Custom Monolog Handler để gửi email khi có log WARNING hoặc ERROR
 */
class EmailErrorHandler extends AbstractProcessingHandler
{
    /**
     * Email recipients
     */
    private array $recipients;

    /**
     * Minimum level to send email (WARNING = 300)
     */
    private int $minLevel;

    /**
     * Throttle: số giây giữa các email (để tránh spam)
     */
    private int $throttleSeconds;

    /**
     * Cache file để track last email sent
     */
    private string $cacheFile;

    /**
     * Constructor
     */
    public function __construct(
        array $recipients,
        int $level = Logger::WARNING,
        int $throttleSeconds = 300, // 5 minutes
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        
        $this->recipients = $recipients;
        $this->minLevel = $level;
        $this->throttleSeconds = $throttleSeconds;
        $this->cacheFile = storage_path('logs/.email_throttle_' . md5(implode(',', $recipients)));
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        // Check throttle
        if (!$this->shouldSendEmail($record)) {
            return;
        }

        try {
            $level = $record['level_name'] ?? 'UNKNOWN';
            $message = $record['message'] ?? '';
            $context = $record['context'] ?? [];
            $time = isset($record['datetime']) 
                ? ($record['datetime'] instanceof \DateTimeInterface 
                    ? $record['datetime']->format('Y-m-d H:i:s')
                    : $record['datetime'])
                : date('Y-m-d H:i:s');

            // Send email
            foreach ($this->recipients as $recipient) {
                Mail::to($recipient)->send(
                    new LogErrorNotificationMail($level, $message, $context, $time)
                );
            }

            // Update throttle cache
            $this->updateThrottleCache($record);

        } catch (\Throwable $e) {
            // Không throw exception để không ảnh hưởng app
            // Ghi log vào file thông thường
            Log::channel('single')->error('Failed to send log email notification', [
                'error' => $e->getMessage(),
                'original_log' => $record['message'] ?? '',
            ]);
        }
    }

    /**
     * Check if should send email (throttling)
     */
    private function shouldSendEmail(array $record): bool
    {
        $level = $record['level'] ?? 0;
        $levelName = $record['level_name'] ?? '';
        
        // CRITICAL, ALERT, EMERGENCY (>= 500): Luôn gửi ngay, KHÔNG throttle
        if ($level >= Logger::CRITICAL) {
            return true;
        }

        // ERROR (400): Throttle 1 phút (60 giây) - ưu tiên cao hơn WARNING
        // WARNING (300): Throttle 5 phút (300 giây) - tránh spam
        $throttleTime = ($level >= Logger::ERROR) ? 60 : $this->throttleSeconds;

        // Check throttle cache
        if (!file_exists($this->cacheFile)) {
            return true;
        }

        $lastSent = (int) file_get_contents($this->cacheFile);
        $now = time();

        // If last email was sent less than throttle seconds ago, skip
        return ($now - $lastSent) >= $throttleTime;
    }

    /**
     * Update throttle cache
     */
    private function updateThrottleCache(array $record): void
    {
        file_put_contents($this->cacheFile, time());
    }
}
