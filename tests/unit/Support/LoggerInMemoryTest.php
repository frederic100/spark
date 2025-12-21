<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Tests\Support\Logging\LoggerInMemory;

final class LoggerInMemoryTest extends TestCase
{
    private LoggerInMemory $logger;

    protected function setUp(): void
    {
        $this->logger = new LoggerInMemory();
    }

    public function test_can_log_messages(): void
    {
        $this->logger->log(LogLevel::INFO, 'Test message');

        $this->assertSame(1, $this->logger->getLogCount());
        $this->assertTrue($this->logger->hasLogWithMessage('Test message'));
        $this->assertTrue($this->logger->hasLogWithLevel(LogLevel::INFO));
    }

    public function test_can_get_last_log(): void
    {
        $this->logger->log(LogLevel::ERROR, 'Error message', ['key' => 'value']);

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame(LogLevel::ERROR, $log['level']);
        $this->assertSame('Error message', $log['message']);
        $this->assertSame(['key' => 'value'], $log['context']);
    }

    public function test_can_filter_logs_by_level(): void
    {
        $this->logger->log(LogLevel::INFO, 'Info message');
        $this->logger->log(LogLevel::ERROR, 'Error message');
        $this->logger->log(LogLevel::INFO, 'Another info');

        $infoLogs = $this->logger->getLogsByLevel(LogLevel::INFO);
        $errorLogs = $this->logger->getLogsByLevel(LogLevel::ERROR);

        $this->assertCount(2, $infoLogs);
        $this->assertCount(1, $errorLogs);
    }

    public function test_can_check_context(): void
    {
        $this->logger->log(LogLevel::DEBUG, 'Debug', ['user_id' => 123]);

        $this->assertTrue($this->logger->hasLogWithContext('user_id', 123));
        $this->assertFalse($this->logger->hasLogWithContext('user_id', 456));
    }

    public function test_can_clear_logs(): void
    {
        $this->logger->log(LogLevel::INFO, 'Message');
        $this->assertSame(1, $this->logger->getLogCount());

        $this->logger->clear();
        $this->assertSame(0, $this->logger->getLogCount());
        $this->assertNull($this->logger->getLastLog());
    }

    public function test_can_get_logs_by_exception_type(): void
    {
        $this->logger->log(LogLevel::ERROR, 'Domain error', ['exception_type' => 'domain']);
        $this->logger->log(LogLevel::ERROR, 'Runtime error', ['exception_type' => 'runtime']);

        $domainLogs = $this->logger->getLogsByExceptionType('domain');
        $runtimeLogs = $this->logger->getLogsByExceptionType('runtime');

        $this->assertCount(1, $domainLogs);
        $this->assertCount(1, $runtimeLogs);
    }
}
