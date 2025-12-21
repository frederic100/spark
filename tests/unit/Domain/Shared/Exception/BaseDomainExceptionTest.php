<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Domain\Shared\Exception\BaseDomainException;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;
use Tests\Support\Exception\TestDomainException;

final class BaseDomainExceptionTest extends TestCase
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

    public function test_can_create_domain_exception(): void
    {
        $message = 'Test domain exception message';
        $code = 42;

        $exception = new TestDomainException($message, $code);

        $this->assertInstanceOf(\DomainException::class, $exception);
        $this->assertInstanceOf(BaseDomainException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function test_logs_exception_automatically_on_creation(): void
    {
        $message = 'Domain violation occurred';

        new TestDomainException($message);

        $this->assertSame(1, $this->logger->getLogCount());
        $log = $this->logger->getLastLog();

        $this->assertNotNull($log);
        $this->assertSame(LogLevel::ERROR, $log['level']);
        $this->assertSame($message, $log['message']);
        $this->assertSame('domain', $log['context']['exception_type']);
        $exceptionClass = $log['context']['exception_class'];
        $this->assertIsString($exceptionClass);
        $this->assertStringContainsString('TestDomainException', $exceptionClass);
    }

    public function test_includes_stack_trace_in_context(): void
    {
        $message = 'Exception with trace';

        new TestDomainException($message);

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        $context = $log['context'];
        $this->assertArrayHasKey('file', $context);
        $this->assertArrayHasKey('line', $context);
        $this->assertArrayHasKey('trace', $context);
        $file = $context['file'];
        $this->assertIsString($file);
        $this->assertStringContainsString('BaseDomainExceptionTest.php', $file);
    }

    public function test_preserves_previous_exception(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new TestDomainException('New exception', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_exception_type_is_domain(): void
    {
        new TestDomainException('Test');

        $this->assertTrue($this->logger->hasLogWithContext('exception_type', 'domain'));

        $domainLogs = $this->logger->getLogsByExceptionType('domain');
        $this->assertCount(1, $domainLogs);
    }
}
