<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Logging;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Spark\Infrastructure\Bootstrap\LoggingBootstrap;
use Spark\Infrastructure\Logging\MonologApplicationLogger;
use Tests\Support\Exception\TestDomainException;

final class MonologIntegrationTest extends TestCase
{
    private string $testLogPath;

    protected function setUp(): void
    {
        $this->testLogPath = sys_get_temp_dir() . '/test_integration_' . uniqid() . '.log';

        // Reset le registry pour un état propre
        LoggerRegistry::reset();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogPath)) {
            unlink($this->testLogPath);
        }

        LoggerRegistry::reset();
        MonologApplicationLogger::resetInstance();
    }

    public function test_logging_bootstrap_configures_monolog(): void
    {
        LoggingBootstrap::initialize();

        $logger = LoggerRegistry::getLogger();

        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
        $this->assertTrue(LoggerRegistry::isConfigured());
    }

    public function test_exceptions_are_logged_with_monolog(): void
    {
        // Configurer un logger de test
        $testLogger = MonologApplicationLogger::createWithPath($this->testLogPath);
        LoggerRegistry::setLogger($testLogger);

        // Créer une exception qui devrait être loggée automatiquement
        new TestDomainException('Test exception with Monolog');

        // Vérifier que l'exception a été loggée
        $this->assertFileExists($this->testLogPath);
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        $this->assertStringContainsString('ERROR: Test exception with Monolog', $logContent);
        $this->assertStringContainsString('exception_class', $logContent);
        $this->assertStringContainsString('TestDomainException', $logContent);
        $this->assertStringContainsString('exception_type', $logContent);
        $this->assertStringContainsString('domain', $logContent);
    }

    public function test_monolog_handles_complex_context(): void
    {
        $testLogger = MonologApplicationLogger::createWithPath($this->testLogPath);
        LoggerRegistry::setLogger($testLogger);

        // Créer une exception avec un contexte complexe
        $exception = new TestDomainException('Complex context test');

        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        // Vérifier que Monolog gère correctement le contexte complexe
        $this->assertStringContainsString('file', $logContent);
        $this->assertStringContainsString('line', $logContent);
        $this->assertStringContainsString('trace', $logContent);
    }

    public function test_monolog_preserves_log_levels(): void
    {
        $testLogger = MonologApplicationLogger::createWithPath($this->testLogPath);

        // Tester différents niveaux
        $testLogger->debug('Debug level test');
        $testLogger->info('Info level test');
        $testLogger->warning('Warning level test');
        $testLogger->error('Error level test');

        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        $this->assertStringContainsString('DEBUG:', $logContent);
        $this->assertStringContainsString('INFO:', $logContent);
        $this->assertStringContainsString('WARNING:', $logContent);
        $this->assertStringContainsString('ERROR:', $logContent);
    }

    public function test_monolog_rotation_configuration(): void
    {
        // Tester que l'instance par défaut utilise bien RotatingFileHandler
        $logger = MonologApplicationLogger::getInstance();

        // Utiliser reflection pour vérifier la configuration interne
        $reflection = new \ReflectionClass($logger);
        $loggerProperty = $reflection->getProperty('logger');
        // Note: setAccessible() is deprecated since PHP 8.5 and has no effect since PHP 8.1
        $monologInstance = $loggerProperty->getValue($logger);

        $this->assertInstanceOf(\Monolog\Logger::class, $monologInstance);

        $handlers = $monologInstance->getHandlers();
        $this->assertNotEmpty($handlers);

        // Vérifier qu'au moins un handler est un RotatingFileHandler
        $hasRotatingHandler = false;
        foreach ($handlers as $handler) {
            if ($handler instanceof \Monolog\Handler\RotatingFileHandler) {
                $hasRotatingHandler = true;
                break;
            }
        }

        $this->assertTrue($hasRotatingHandler, 'Should have at least one RotatingFileHandler');
    }
}
