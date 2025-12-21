<?php

declare(strict_types=1);

namespace Tests\Support\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class LoggerInMemory extends AbstractLogger
{
    /** @var array<int, array{level: string, message: string, context: array<string, mixed>, timestamp: float}> */
    private array $logs = [];

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // PHPStan: In our test context, level is always a string (LogLevel constants)
        // @phpstan-ignore-next-line cast.string
        $levelString = (string) $level;

        $this->logs[] = [
            'level' => $levelString,
            'message' => (string) $message,
            'context' => $context,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * @return array<int, array{level: string, message: string, context: array<string, mixed>, timestamp: float}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * @return array{level: string, message: string, context: array<string, mixed>, timestamp: float}|null
     */
    public function getLastLog(): ?array
    {
        return end($this->logs) ?: null;
    }

    /**
     * @return array<int, array{level: string, message: string, context: array<string, mixed>, timestamp: float}>
     */
    public function getLogsByLevel(string $level): array
    {
        return array_filter($this->logs, fn($log) => $log['level'] === $level);
    }

    public function hasLogWithMessage(string $message): bool
    {
        foreach ($this->logs as $log) {
            if (str_contains($log['message'], $message)) {
                return true;
            }
        }
        return false;
    }

    public function hasLogWithLevel(string $level): bool
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === $level) {
                return true;
            }
        }
        return false;
    }

    public function hasLogWithContext(string $key, mixed $value): bool
    {
        foreach ($this->logs as $log) {
            if (isset($log['context'][$key]) && $log['context'][$key] === $value) {
                return true;
            }
        }
        return false;
    }

    public function getLogCount(): int
    {
        return count($this->logs);
    }

    public function clear(): void
    {
        $this->logs = [];
    }

    /**
     * Convenience method to find logs by exception type
     * @return array<int, array{level: string, message: string, context: array<string, mixed>, timestamp: float}>
     */
    public function getLogsByExceptionType(string $type): array
    {
        return array_filter($this->logs, function ($log) use ($type) {
            return isset($log['context']['exception_type'])
                && $log['context']['exception_type'] === $type;
        });
    }

    /**
     * Debug method to dump all logs (for troubleshooting tests)
     */
    public function dump(): void
    {
        echo "=== LoggerInMemory Dump ===\n";
        echo "Total logs: " . count($this->logs) . "\n";
        foreach ($this->logs as $i => $log) {
            echo "[$i] {$log['level']}: {$log['message']}\n";
            if (!empty($log['context'])) {
                echo "    Context: " . json_encode($log['context'], JSON_PRETTY_PRINT) . "\n";
            }
        }
        echo "=== End Dump ===\n";
    }
}
