<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Logging;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Spark\Infrastructure\Bootstrap\LoggingBootstrap;
use Tests\Support\Exception\TestDomainException;

final class MonologIntegrationTest extends TestCase
{
    private string $testLogPath;

    protected function setUp(): void
    {
        // Créer un système de fichiers virtuel
        $root = vfsStream::setup('root');
        $this->testLogPath = vfsStream::url('root/test_integration.log');

        // Reset le registry pour un état propre
        LoggerRegistry::reset();
    }

    protected function tearDown(): void
    {
        LoggerRegistry::reset();
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
        $testLogger = LoggingBootstrap::createLogger($this->testLogPath, false);
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
        $testLogger = LoggingBootstrap::createLogger($this->testLogPath, false);
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
        $testLogger = LoggingBootstrap::createLogger($this->testLogPath, false);

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
        LoggingBootstrap::initialize();
        $logger = LoggerRegistry::getLogger();

        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
        /** @var \Monolog\Logger $logger */
        $handlers = $logger->getHandlers();
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

    public function test_creates_log_directory_when_not_exists(): void
    {
        // Arrange
        $logDir = $this->createVirtualFileSystemWithNonExistentLogDirectory();
        $this->assertDirectoryDoesNotExist($logDir);
        $logPath = $this->getApplicationLogFilePath($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert
        $this->assertLogDirectoryWasCreatedAutomatically($logDir);
        $this->assertLogFileWasCreatedAndContainsMessage($logPath, 'Test message');
    }

    private function createVirtualFileSystemWithNonExistentLogDirectory(): string
    {
        vfsStream::setup('root');
        return vfsStream::url('root/data/log');
    }

    private function getApplicationLogFilePath(string $logDir): string
    {
        return $logDir . '/application.log';
    }

    private function assertLogDirectoryWasCreatedAutomatically(string $logDir): void
    {
        $this->assertDirectoryExists($logDir);
    }

    private function assertLogFileWasCreatedAndContainsMessage(string $logPath, string $expectedMessage): void
    {
        $this->assertFileExists($logPath);

        $logContent = file_get_contents($logPath);
        $this->assertIsString($logContent);
        $this->assertStringContainsString($expectedMessage, $logContent);
    }
}
