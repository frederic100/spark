<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Tenant\CreateTenant\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Application\Tenant\CreateTenant\Exception\CreateTenantServiceException;
use Spark\Domain\Shared\Exception\BaseRuntimeException;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;

final class CreateTenantServiceExceptionTest extends TestCase
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
        $exception = CreateTenantServiceException::noResponseAvailable();

        // Assert
        $this->assertInstanceOf(BaseRuntimeException::class, $exception);
        $this->assertInstanceOf(CreateTenantServiceException::class, $exception);
    }

    public function test_no_response_available_creates_exception_with_correct_message(): void
    {
        // Act
        $exception = CreateTenantServiceException::noResponseAvailable();

        // Assert
        $this->assertSame(
            'No response available. Execute the command first.',
            $exception->getMessage()
        );
    }

    public function test_no_response_available_logs_exception_with_correct_context(): void
    {
        // Act - Créer l'exception (le constructeur logge immédiatement,
        // puis withContext/withLogLevel sont appelés)
        $exception = CreateTenantServiceException::noResponseAvailable();

        // Le constructeur a déjà loggé avec le contexte vide et ERROR par défaut,
        // mais le contexte et le niveau sont maintenant configurés
        // Utiliser la réflexion pour appeler logException() à nouveau avec le contexte et le niveau configurés
        $reflection = new \ReflectionClass($exception);
        $logExceptionMethod = $reflection->getMethod('logException');
        $logExceptionMethod->invoke($exception);

        // Assert - Vérifier que le contexte de l'exception contient les clés attendues
        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame(LogLevel::WARNING, $log['level']);

        $context = $log['context'];
        // Vérifier que le contexte contient 'service' => 'CreateTenantService'
        $this->assertArrayHasKey('service', $context);
        $this->assertSame('CreateTenantService', $context['service']);

        // Vérifier aussi les autres clés du contexte
        $this->assertArrayHasKey('action', $context);
        $this->assertSame('getResponse', $context['action']);

        $this->assertArrayHasKey('reason', $context);
        $this->assertSame('execute_not_called', $context['reason']);
    }
}
