<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Domain\Shared\Exception\BaseRuntimeException;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;
use Tests\Support\Exception\TestRuntimeException;

final class BaseRuntimeExceptionTest extends TestCase
{
    private LoggerInMemory $logger;

    protected function setUp(): void
    {
        LoggerRegistry::reset();
        $this->logger = new LoggerInMemory();
        LoggerRegistry::setLogger($this->logger);
    }

    protected function tearDown(): void
    {
        LoggerRegistry::reset();
    }

    public function test_can_create_runtime_exception(): void
    {
        $message = 'Test runtime exception message';
        $code = 500;

        $exception = new TestRuntimeException($message, $code);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(BaseRuntimeException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function test_logs_exception_automatically_on_creation(): void
    {
        $message = 'Runtime error occurred';

        new TestRuntimeException($message);

        $this->assertSame(1, $this->logger->getLogCount());
        $log = $this->logger->getLastLog();

        $this->assertNotNull($log);
        $this->assertSame(LogLevel::ERROR, $log['level']);
        $this->assertSame($message, $log['message']);
        $this->assertSame('runtime', $log['context']['exception_type']);
        $exceptionClass = $log['context']['exception_class'];
        $this->assertIsString($exceptionClass);
        $this->assertStringContainsString('TestRuntimeException', $exceptionClass);
    }


    public function test_preserves_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous runtime exception');
        $exception = new TestRuntimeException('New runtime exception', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_exception_type_is_runtime(): void
    {
        new TestRuntimeException('Test');

        $this->assertTrue($this->logger->hasLogWithContext('exception_type', 'runtime'));

        $runtimeLogs = $this->logger->getLogsByExceptionType('runtime');
        $this->assertCount(1, $runtimeLogs);
    }

    public function test_context_includes_file_and_line_info(): void
    {
        new TestRuntimeException('Test with location info');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        $context = $log['context'];
        $this->assertArrayHasKey('file', $context);
        $this->assertArrayHasKey('line', $context);
        $this->assertArrayHasKey('trace', $context);
        $file = $context['file'];
        $this->assertIsString($file);
        $this->assertStringContainsString('BaseRuntimeExceptionTest.php', $file);
    }
}
