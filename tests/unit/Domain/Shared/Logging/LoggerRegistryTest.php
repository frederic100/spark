<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;

final class LoggerRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset registry avant chaque test
        LoggerRegistry::reset();
    }

    protected function tearDown(): void
    {
        // Nettoyage après chaque test
        LoggerRegistry::reset();
    }

    public function test_can_set_and_get_logger(): void
    {
        $logger = new LoggerInMemory();

        LoggerRegistry::setLogger($logger);
        $retrievedLogger = LoggerRegistry::getLogger();

        $this->assertSame($logger, $retrievedLogger);
    }

    public function test_returns_null_logger_when_no_logger_configured(): void
    {
        $logger = LoggerRegistry::getLogger();

        $this->assertInstanceOf(NullLogger::class, $logger);
    }

    public function test_is_configured_returns_false_initially(): void
    {
        $this->assertFalse(LoggerRegistry::isConfigured());
    }

    public function test_is_configured_returns_false_with_null_logger(): void
    {
        // getLogger() va créer un NullLogger automatiquement
        LoggerRegistry::getLogger();

        $this->assertFalse(LoggerRegistry::isConfigured());
    }

    public function test_is_configured_returns_true_after_setting_logger(): void
    {
        $logger = new LoggerInMemory();

        LoggerRegistry::setLogger($logger);

        $this->assertTrue(LoggerRegistry::isConfigured());
    }

    public function test_reset_clears_logger(): void
    {
        $logger = new LoggerInMemory();
        LoggerRegistry::setLogger($logger);

        $this->assertTrue(LoggerRegistry::isConfigured());

        LoggerRegistry::reset();

        $this->assertFalse(LoggerRegistry::isConfigured());
    }

    public function test_reset_makes_get_logger_return_null_logger_again(): void
    {
        $logger = new LoggerInMemory();
        LoggerRegistry::setLogger($logger);

        LoggerRegistry::reset();
        $logger = LoggerRegistry::getLogger();

        $this->assertInstanceOf(NullLogger::class, $logger);
    }

    public function test_can_replace_logger(): void
    {
        $firstLogger = new LoggerInMemory();
        $secondLogger = new LoggerInMemory();

        LoggerRegistry::setLogger($firstLogger);
        $this->assertSame($firstLogger, LoggerRegistry::getLogger());

        LoggerRegistry::setLogger($secondLogger);
        $this->assertSame($secondLogger, LoggerRegistry::getLogger());
    }

    public function test_get_logger_returns_same_instance_on_multiple_calls(): void
    {
        $logger = new LoggerInMemory();
        LoggerRegistry::setLogger($logger);

        $logger1 = LoggerRegistry::getLogger();
        $logger2 = LoggerRegistry::getLogger();

        $this->assertSame($logger1, $logger2);
    }

    public function test_null_logger_is_same_instance_on_multiple_calls(): void
    {
        $logger1 = LoggerRegistry::getLogger();
        $logger2 = LoggerRegistry::getLogger();

        $this->assertInstanceOf(NullLogger::class, $logger1);
        $this->assertSame($logger1, $logger2);
    }
}
