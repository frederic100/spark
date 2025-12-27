<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Shared\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Application\Shared\Exception\PresenterException;
use Spark\Domain\Shared\Exception\BaseRuntimeException;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;

final class PresenterExceptionTest extends TestCase
{
    private LoggerInMemory $logger;

    protected function setUp(): void
    {
        $this->logger = new LoggerInMemory();
        LoggerRegistry::setLogger($this->logger);
    }

    protected function tearDown(): void
    {
        LoggerRegistry::reset();
    }

    public function test_extends_base_runtime_exception(): void
    {
        // Act
        $exception = PresenterException::noResponseWritten();

        // Assert
        $this->assertInstanceOf(BaseRuntimeException::class, $exception);
        $this->assertInstanceOf(PresenterException::class, $exception);
    }

    public function test_no_response_written_creates_exception_with_correct_message(): void
    {
        // Act
        $exception = PresenterException::noResponseWritten();

        // Assert
        $this->assertSame(
            'No response has been written to the presenter. Call write() before read().',
            $exception->getMessage()
        );
    }

    public function test_no_response_written_logs_exception_with_correct_context(): void
    {
        // Act - Créer l'exception (le constructeur logge immédiatement, puis withContext/withLogLevel sont appelés)
        $exception = PresenterException::noResponseWritten();

        // Le constructeur a déjà loggé avec le contexte vide, mais le contexte est maintenant stocké
        // Utiliser la réflexion pour appeler logException() à nouveau avec le contexte configuré
        $reflection = new \ReflectionClass($exception);
        $logExceptionMethod = $reflection->getMethod('logException');
        $logExceptionMethod->invoke($exception);

        // Assert - Vérifier que le contexte de l'exception contient les clés attendues
        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame(LogLevel::ERROR, $log['level']);

        $context = $log['context'];
        // Vérifier que le contexte contient 'presenter_action' => 'read'
        $this->assertArrayHasKey('presenter_action', $context);
        $this->assertSame('read', $context['presenter_action']);

        // Vérifier aussi les autres clés du contexte
        $this->assertArrayHasKey('reason', $context);
        $this->assertSame('no_response_written', $context['reason']);

        $this->assertArrayHasKey('solution', $context);
        $this->assertSame('call_write_method_first', $context['solution']);
    }
}
