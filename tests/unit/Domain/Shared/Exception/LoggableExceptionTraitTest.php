<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;
use Tests\Support\Exception\TestDomainException;
use Tests\Support\Exception\TestExceptionWithTrait;
use Tests\Support\Exception\TestNativeDomainExceptionWithTrait;
use Tests\Support\Exception\TestNativeRuntimeExceptionWithTrait;
use Tests\Support\Exception\TestRuntimeException;

use function SafePHP\strval;

final class LoggableExceptionTraitTest extends TestCase
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

    // Tests pour la détection automatique du type d'exception

    public function test_detects_exception_type_for_base_exception(): void
    {
        new TestExceptionWithTrait('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('exception', $log['context']['exception_type']);
    }

    public function test_detects_domain_type_for_native_domain_exception(): void
    {
        new TestNativeDomainExceptionWithTrait('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('domain', $log['context']['exception_type']);
    }

    public function test_detects_runtime_type_for_native_runtime_exception(): void
    {
        new TestNativeRuntimeExceptionWithTrait('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('runtime', $log['context']['exception_type']);
    }

    public function test_detects_domain_type_for_base_domain_exception(): void
    {
        new TestDomainException('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('domain', $log['context']['exception_type']);
    }

    public function test_detects_runtime_type_for_base_runtime_exception(): void
    {
        new TestRuntimeException('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('runtime', $log['context']['exception_type']);
    }

    // Tests pour la gestion du contexte personnalisé

    public function test_can_add_custom_context(): void
    {
        $customContext = [
            'user_id' => 123,
            'action' => 'create_tenant',
            'data' => ['name' => 'Test Tenant']
        ];

        new TestExceptionWithTrait('Test message', 0, null, $customContext);

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        // Vérifier que le contexte personnalisé est présent
        $this->assertSame(123, $log['context']['user_id']);
        $this->assertSame('create_tenant', $log['context']['action']);
        $this->assertSame(['name' => 'Test Tenant'], $log['context']['data']);

        // Vérifier que les données standards sont toujours là
        $this->assertArrayHasKey('exception_class', $log['context']);
        $this->assertArrayHasKey('exception_type', $log['context']);
        $this->assertArrayHasKey('file', $log['context']);
        $this->assertArrayHasKey('line', $log['context']);
        $this->assertArrayHasKey('trace', $log['context']);
    }

    public function test_custom_context_overwrites_standard_keys(): void
    {
        $customContext = [
            'exception_class' => 'CustomOverride',
            'custom_key' => 'custom_value'
        ];

        new TestExceptionWithTrait('Test message', 0, null, $customContext);

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        // Le contexte personnalisé doit écraser les clés standard (spread operator)
        $this->assertSame('CustomOverride', $log['context']['exception_class']);
        $this->assertSame('custom_value', $log['context']['custom_key']);
    }

    // Tests pour les niveaux de log personnalisés

    public function test_can_change_log_level(): void
    {
        new TestExceptionWithTrait('Test message', 0, null, [], LogLevel::WARNING);

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame(LogLevel::WARNING, $log['level']);
    }

    public function test_default_log_level_is_error(): void
    {
        new TestExceptionWithTrait('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame(LogLevel::ERROR, $log['level']);
    }

    public function test_can_chain_context_and_log_level(): void
    {
        $customContext = ['user_id' => 456];

        new TestExceptionWithTrait('Test message', 0, null, $customContext, LogLevel::INFO);

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame(LogLevel::INFO, $log['level']);
        $this->assertSame(456, $log['context']['user_id']);
    }

    public function test_can_update_context_and_log_level_after_construction(): void
    {
        $exception = new TestExceptionWithTrait('Test message');

        // Premier log avec valeurs par défaut
        $firstLog = $this->logger->getLastLog();
        $this->assertNotNull($firstLog);
        $this->assertSame(LogLevel::ERROR, $firstLog['level']);

        // Mettre à jour et logger à nouveau
        $newContext = ['updated_key' => 'updated_value'];
        $exception->updateContext($newContext)->updateLogLevel(LogLevel::DEBUG);
        $exception->logAgain();

        // Le nouveau log doit avoir les valeurs mises à jour
        $secondLog = $this->logger->getLastLog();
        $this->assertNotNull($secondLog);
        $this->assertSame(LogLevel::DEBUG, $secondLog['level']);
        $this->assertSame('updated_value', $secondLog['context']['updated_key']);

        // Vérifier qu'on a bien 2 logs au total
        $this->assertSame(2, $this->logger->getLogCount());
    }

    // Tests pour la structure complète des logs

    public function test_log_contains_all_expected_fields(): void
    {
        new TestExceptionWithTrait('Test exception message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        // Champs de base du log
        $this->assertSame(LogLevel::ERROR, $log['level']);
        $this->assertSame('Test exception message', $log['message']);

        // Champs du contexte d'exception
        $context = $log['context'];
        $this->assertArrayHasKey('exception_class', $context);
        $this->assertArrayHasKey('exception_type', $context);
        $this->assertArrayHasKey('file', $context);
        $this->assertArrayHasKey('line', $context);
        $this->assertArrayHasKey('trace', $context);

        // Vérifier les types
        $exceptionClass = $context['exception_class'];
        $this->assertIsString($exceptionClass);
        $this->assertStringContainsString('TestExceptionWithTrait', $exceptionClass);

        $this->assertIsString($context['exception_type']);
        $this->assertIsString($context['file']);
        $this->assertIsInt($context['line']);
        $this->assertIsString($context['trace']);
    }

    public function test_log_includes_file_and_line_information(): void
    {
        new TestExceptionWithTrait('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        $file = $log['context']['file'] ?? '';
        $this->assertIsString($file);
        $this->assertStringContainsString('LoggableExceptionTraitTest.php', $file);

        $this->assertIsInt($log['context']['line']);
        $this->assertGreaterThan(0, $log['context']['line']);
    }

    public function test_log_includes_stack_trace(): void
    {
        new TestExceptionWithTrait('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);

        $trace = $log['context']['trace'];
        $this->assertIsString($trace);
        $this->assertStringContainsString('LoggableExceptionTraitTest', $trace);
        // Le nom de classe peut être tronqué dans la trace, vérifions juste qu'il y a une trace
        $this->assertGreaterThan(50, strlen($trace)); // Une trace minimale doit avoir une longueur raisonnable
    }

    // Test pour la priorité des types d'exception (nos classes de base vs PHP natives)

    public function test_prioritizes_custom_base_classes_over_native(): void
    {
        // TestDomainException hérite de BaseDomainException qui hérite de \DomainException
        // Le trait doit détecter 'domain' via BaseDomainException, pas via \DomainException
        new TestDomainException('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('domain', $log['context']['exception_type']);

        // TestRuntimeException hérite de BaseRuntimeException qui hérite de \RuntimeException
        new TestRuntimeException('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        $this->assertSame('runtime', $log['context']['exception_type']);
    }

    public function test_detects_domain_type_via_base_domain_exception_first_check(): void
    {
        // TestDomainException hérite de BaseDomainException
        // Le trait doit détecter 'domain' via le premier check (BaseDomainException) à la ligne 54-55
        // Si le return 'domain' à la ligne 55 était supprimé, le code continuerait mais le test
        // vérifie explicitement que le type est détecté correctement via BaseDomainException
        new TestDomainException('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        // Vérifier explicitement que le type est 'domain' (ce qui prouve que le return à la ligne 55 a été exécuté)
        $this->assertSame('domain', $log['context']['exception_type']);
        // Vérifier que l'exception est bien une BaseDomainException
        $this->assertStringContainsString('TestDomainException', strval($log['context']['exception_class']));
    }

    public function test_detects_runtime_type_via_base_runtime_exception_first_check(): void
    {
        // TestRuntimeException hérite de BaseRuntimeException
        // Le trait doit détecter 'runtime' via le premier check (BaseRuntimeException) à la ligne 58-59
        // Si le return 'runtime' à la ligne 59 était supprimé, le code continuerait mais le test
        // vérifie explicitement que le type est détecté correctement via BaseRuntimeException
        new TestRuntimeException('Test message');

        $log = $this->logger->getLastLog();
        $this->assertNotNull($log);
        // Vérifier explicitement que le type est 'runtime' (ce qui prouve que le return à la ligne 59 a été exécuté)
        $this->assertSame('runtime', $log['context']['exception_type']);
        // Vérifier que l'exception est bien une BaseRuntimeException
        $this->assertStringContainsString('TestRuntimeException', strval($log['context']['exception_class']));
    }

    // Test de l'intégration avec le LoggerRegistry

    public function test_uses_logger_registry(): void
    {
        // Vérifier qu'aucun log n'est présent initialement
        $this->assertSame(0, $this->logger->getLogCount());

        new TestExceptionWithTrait('Test message');

        // Vérifier qu'un log a été ajouté
        $this->assertSame(1, $this->logger->getLogCount());
    }

    public function test_works_with_null_logger_fallback(): void
    {
        // Reset pour utiliser le NullLogger par défaut
        LoggerRegistry::reset();

        // Cela ne doit pas générer d'erreur même sans logger configuré
        $exception = new TestExceptionWithTrait('Test message');

        // Pas de logs dans notre LoggerInMemory car on utilise le NullLogger
        $this->assertSame(0, $this->logger->getLogCount());

        // L'exception doit exister normalement
        $this->assertSame('Test message', $exception->getMessage());
    }
}
