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
        // Arrange
        $message = 'Test runtime exception message';
        $code = 500;

        // Act
        $sut = new TestRuntimeException($message, $code);

        // Assert
        $this->assertInstanceOf(\RuntimeException::class, $sut);
        $this->assertInstanceOf(BaseRuntimeException::class, $sut);
        $this->assertSame($message, $sut->getMessage());
        $this->assertSame($code, $sut->getCode());
    }

    public function test_logs_exception_automatically_on_creation(): void
    {
        // Arrange
        $message = 'Runtime error occurred';

        // Act
        new TestRuntimeException($message);

        // Assert
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
        // Arrange
        $previous = new \RuntimeException('Previous runtime exception');

        // Act
        $sut = new TestRuntimeException('New runtime exception', 0, $previous);

        // Assert
        $this->assertSame($previous, $sut->getPrevious());
    }

    public function test_exception_type_is_runtime(): void
    {
        // Act
        new TestRuntimeException('Test');

        // Assert
        $this->assertTrue($this->logger->hasLogWithContext('exception_type', 'runtime'));

        $runtimeLogs = $this->logger->getLogsByExceptionType('runtime');
        $this->assertCount(1, $runtimeLogs);
    }

    public function test_context_includes_file_and_line_info(): void
    {
        // Arrange
        $message = 'Test with location info';

        // Act
        new TestRuntimeException($message);

        // Assert
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

    public function test_default_code_is_zero_when_not_specified(): void
    {
        // Arrange
        $message = 'Test exception';

        // Act
        $sut = new TestRuntimeException($message);

        // Assert
        $this->assertSame(0, $sut->getCode());
    }
}
