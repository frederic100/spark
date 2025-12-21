<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Infrastructure\Logging\MonologApplicationLogger;

final class MonologApplicationLoggerTest extends TestCase
{
    private string $testLogPath;

    protected function setUp(): void
    {
        // Créer un fichier de log temporaire pour les tests
        $this->testLogPath = sys_get_temp_dir() . '/test_monolog_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        // Nettoyer le fichier de log de test
        if (file_exists($this->testLogPath)) {
            unlink($this->testLogPath);
        }

        // Reset l'instance singleton
        MonologApplicationLogger::resetInstance();
    }

    public function test_can_create_logger_instance(): void
    {
        $logger = MonologApplicationLogger::getInstance();

        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $logger1 = MonologApplicationLogger::getInstance();
        $logger2 = MonologApplicationLogger::getInstance();

        $this->assertSame($logger1, $logger2);
    }

    public function test_can_reset_instance(): void
    {
        $logger1 = MonologApplicationLogger::getInstance();
        MonologApplicationLogger::resetInstance();
        $logger2 = MonologApplicationLogger::getInstance();

        $this->assertNotSame($logger1, $logger2);
    }

    public function test_can_create_logger_with_custom_path(): void
    {
        $logger = MonologApplicationLogger::createWithPath($this->testLogPath);

        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function test_can_log_different_levels(): void
    {
        $logger = MonologApplicationLogger::createWithPath($this->testLogPath);

        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->warning('Warning message');
        $logger->error('Error message');
        $logger->critical('Critical message');

        $this->assertFileExists($this->testLogPath);
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        $this->assertStringContainsString('DEBUG: Debug message', $logContent);
        $this->assertStringContainsString('INFO: Info message', $logContent);
        $this->assertStringContainsString('WARNING: Warning message', $logContent);
        $this->assertStringContainsString('ERROR: Error message', $logContent);
        $this->assertStringContainsString('CRITICAL: Critical message', $logContent);
    }

    public function test_can_log_with_context(): void
    {
        $logger = MonologApplicationLogger::createWithPath($this->testLogPath);

        $context = [
            'user_id' => 123,
            'action' => 'create_tenant',
            'data' => ['name' => 'Test Tenant']
        ];

        $logger->info('Action performed', $context);

        $this->assertFileExists($this->testLogPath);
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        $this->assertStringContainsString('INFO: Action performed', $logContent);
        $this->assertStringContainsString('user_id', $logContent);
        $this->assertStringContainsString('123', $logContent);
        $this->assertStringContainsString('create_tenant', $logContent);
    }

    public function test_log_format_contains_timestamp(): void
    {
        $logger = MonologApplicationLogger::createWithPath($this->testLogPath);

        $logger->info('Test message');

        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        // Vérifier que le format contient un timestamp
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function test_can_log_using_generic_log_method(): void
    {
        $logger = MonologApplicationLogger::createWithPath($this->testLogPath);

        $logger->log(LogLevel::NOTICE, 'Notice message');

        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        $this->assertStringContainsString('NOTICE: Notice message', $logContent);
    }

    public function test_handles_stringable_messages(): void
    {
        $logger = MonologApplicationLogger::createWithPath($this->testLogPath);

        // Créer un objet Stringable
        $stringableMessage = new class {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $logger->info($stringableMessage);

        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        $this->assertStringContainsString('INFO: Stringable message', $logContent);
    }

    public function test_creates_log_directory_if_not_exists(): void
    {
        $logDir = sys_get_temp_dir() . '/test_monolog_dir_' . uniqid();
        $logPath = $logDir . '/test.log';

        // S'assurer que le répertoire n'existe pas
        $this->assertDirectoryDoesNotExist($logDir);

        $logger = MonologApplicationLogger::createWithPath($logPath);
        $logger->info('Test message');

        // Vérifier que le répertoire et le fichier ont été créés
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);

        // Nettoyer
        unlink($logPath);
        rmdir($logDir);
    }
}
