<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Custom Daily Logger để tạo log file theo format: laravel_YYYYMMDD.log
 * Thay vì format mặc định: laravel-YYYY-MM-DD.log
 * 
 * Tính năng:
 * - Log file theo ngày với format custom
 * - Tự động cleanup log cũ hơn N ngày
 * - Gửi email tự động khi có WARNING/ERROR
 */
class CustomDailyLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger($config['name'] ?? 'daily_custom');
        
        // Tạo log filename với format chuẩn Laravel: laravel-YYYY-MM-DD.log
        $logPath = storage_path('logs');
        $logFile = $logPath . '/laravel-' . date('Y-m-d') . '.log';
        
        // Tạo stream handler với file theo ngày
        $handler = new StreamHandler(
            $logFile,
            $this->level($config),
            true, // bubble
            $config['permission'] ?? null,
            false // locking
        );
        
        // Set custom formatter với date format dễ đọc
        // Format: [Y-m-d H:i:s] level.CHANNEL: message {"context"} {"extra"}
        $dateFormat = "Y-m-d H:i:s"; // Thay vì ISO 8601
        $outputFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        
        $formatter = new LineFormatter($outputFormat, $dateFormat, true, true);
        $handler->setFormatter($formatter);
        
        $logger->pushHandler($handler);
        
        // Thêm Email Error Handler để gửi email khi có WARNING/ERROR
        if ($config['send_email_on_error'] ?? true) {
            $emailRecipients = $config['email_recipients'] ?? [config('mail.from.address')];
            $emailHandler = new EmailErrorHandler(
                $emailRecipients,
                Logger::WARNING, // Gửi email từ WARNING trở lên
                $config['email_throttle'] ?? 300 // Throttle 5 phút
            );
            $logger->pushHandler($emailHandler);
        }
        
        // Cleanup old log files
        $this->cleanupOldLogs($logPath, $config['days'] ?? 30);
        
        return $logger;
    }
    
    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  array  $config
     * @return int
     */
    protected function level(array $config): int
    {
        $level = $config['level'] ?? 'debug';
        
        return Logger::toMonologLevel($level);
    }
    
    /**
     * Cleanup old log files.
     *
     * @param string $logPath
     * @param int $days
     * @return void
     */
    protected function cleanupOldLogs(string $logPath, int $days): void
    {
        try {
            $files = glob($logPath . '/laravel-*.log');
            $cutoffTime = time() - ($days * 86400);
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    @unlink($file);
                }
            }
        } catch (\Throwable $e) {
            // Ignore cleanup errors
        }
    }
}
