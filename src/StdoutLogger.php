<?php

declare(strict_types=1);

namespace App;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class StdoutLogger extends AbstractLogger
{
    public const COLORIZE = [
        'emergency' => "\033[1;41;97m" . 'EMERGENCY' . "\033[0m",
        'alert' => "\033[1;43;97m" . 'ALERT' . "\033[0m",
        'critical' => "\033[1;45;97m" . 'CRITICAL' . "\033[0m",
        'error' => "\033[1;31m" . 'ERROR' . "\033[0m",
        'warning' => "\033[1;33m" . 'WARNING' . "\033[0m",
        'notice' => "\033[1;32m" . 'NOTICE' . "\033[0m",
        'info' => "\033[1;34m" . 'INFO' . "\033[0m",
        'debug' => "\033[1;37m" . 'DEBUG' . "\033[0m",
    ];

    private bool $colorize = true;

    private array $logLevels = [
        LogLevel::EMERGENCY => true,
        LogLevel::ALERT => true,
        LogLevel::CRITICAL => true,
        LogLevel::ERROR => true,
        LogLevel::WARNING => true,
        LogLevel::NOTICE => true,
        LogLevel::INFO => true,
        LogLevel::DEBUG => false,
    ];

    /**
     * @var null|bool|resource
     */
    private $handle;

    /**
     * Конструктор класса.
     *
     * @param null|array $levels
     * @param bool       $colorize
     * @param resource   $handle
     */
    public function __construct(array $levels = null, bool $colorize = true, $handle = null)
    {
        if (null !== $levels) {
            $this->logLevels = [];
            foreach ($levels as $level) {
                $this->logLevels[$level] = true;
            }
        }
        $this->colorize = $colorize;
        $this->handle = $handle ?? \STDOUT;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (! ($this->logLevels[$level] ?? false)) {
            return;
        }
        $string = $this->formatMessage($level, $message, $context);
        fwrite($this->handle, $string);
    }

    private function formatMessage(string $level, string $message, array $context = []): string
    {
        $date = ( new \DateTime() )->format('Y-m-dTH:i:s.vO');
        $level = $this->colorize ? self::COLORIZE[$level] : \strtoupper($level);
        $ctx = empty($context)
            ?
            '' : (' (' . \json_encode($context, \JSON_UNESCAPED_UNICODE | \JSON_BIGINT_AS_STRING) . ')');

        return "[{$date}] [{$level}] {$message}{$ctx}" . \PHP_EOL;
    }
}