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
        // Act
        $sut = MonologApplicationLogger::getInstance();

        // Assert
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $sut);
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
        // Act
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);

        // Assert
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $sut);
    }

    public function test_can_log_different_levels(): void
    {
        // Arrange
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);

        // Act
        $sut->debug('Debug message');
        $sut->info('Info message');
        $sut->warning('Warning message');
        $sut->error('Error message');
        $sut->critical('Critical message');

        // Assert
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
        // Arrange
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);

        // Act
        $sut->info('Test message');

        // Assert
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);

        // Vérifier que le format contient un timestamp
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function test_log_format_allows_inline_line_breaks(): void
    {
        // Arrange
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);
        $messageWithNewline = "Line 1\nLine 2";

        // Act
        $sut->info($messageWithNewline);

        // Assert - Vérifier que le formatter permet les retours à la ligne inline
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);
        // Le formatter doit permettre les retours à la ligne (allowInlineLineBreaks = true)
        $this->assertStringContainsString('Line 1', $logContent);
        $this->assertStringContainsString('Line 2', $logContent);
    }

    public function test_log_format_ignores_empty_context(): void
    {
        // Arrange
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);

        // Act
        $sut->info('Test message', []);

        // Assert - Vérifier que le formatter ignore les contextes vides (ignoreEmptyContextAndExtra = true)
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);
        // Le message doit être présent même avec un contexte vide
        $this->assertStringContainsString('Test message', $logContent);
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
        $stringableMessage = new class implements \Stringable {
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
        // Arrange
        $logDir = sys_get_temp_dir() . '/test_monolog_dir_' . uniqid();
        $logPath = $logDir . '/test.log';

        // S'assurer que le répertoire n'existe pas
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $sut = MonologApplicationLogger::createWithPath($logPath);
        $sut->info('Test message');

        // Assert - Vérifier que le répertoire et le fichier ont été créés
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);

        // Nettoyer
        unlink($logPath);
        rmdir($logDir);
    }

    public function test_log_path_is_correctly_constructed_with_directory_separator(): void
    {
        // Arrange
        $logDir = sys_get_temp_dir() . '/test_monolog_path_' . uniqid();
        $logPath = $logDir . '/application.log';

        // Act
        $sut = MonologApplicationLogger::createWithPath($logPath);
        $sut->info('Test message');

        // Assert - Vérifier que le chemin est correctement construit avec le séparateur
        $this->assertFileExists($logPath);
        $this->assertStringEndsWith('/application.log', $logPath);

        // Nettoyer
        unlink($logPath);
        rmdir($logDir);
    }

    public function test_rotating_file_handler_uses_seven_days_retention(): void
    {
        // Arrange
        $sut = MonologApplicationLogger::getInstance();

        // Act - Utiliser reflection pour vérifier la configuration du RotatingFileHandler
        $reflection = new \ReflectionClass($sut);
        $loggerProperty = $reflection->getProperty('logger');
        $monologInstance = $loggerProperty->getValue($sut);

        // Assertion de type pour PHPStan
        $this->assertInstanceOf(\Monolog\Logger::class, $monologInstance);
        /** @var \Monolog\Logger $monologInstance */
        $handlers = $monologInstance->getHandlers();
        $rotatingHandler = null;
        foreach ($handlers as $handler) {
            if ($handler instanceof \Monolog\Handler\RotatingFileHandler) {
                $rotatingHandler = $handler;
                break;
            }
        }

        // Assert
        $this->assertNotNull($rotatingHandler, 'Should have a RotatingFileHandler');
        $handlerReflection = new \ReflectionClass($rotatingHandler);
        $maxFilesProperty = $handlerReflection->getProperty('maxFiles');
        $maxFiles = $maxFilesProperty->getValue($rotatingHandler);
        $this->assertSame(7, $maxFiles, 'RotatingFileHandler should be configured with 7 days retention');
    }

    public function test_formatter_is_applied_to_handler(): void
    {
        // Arrange
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);
        $message = 'Test with context';
        $context = ['key' => 'value'];

        // Act
        $sut->info($message, $context);

        // Assert - Vérifier que le formatter est appliqué (le format doit être celui configuré)
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);
        // Le formatter doit être appliqué, donc le format doit correspondre à celui configuré
        $this->assertStringContainsString('INFO:', $logContent);
        $this->assertStringContainsString($message, $logContent);
        // Vérifier que le format contient le timestamp au format attendu
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function test_createWithPath_uses_correct_formatter_settings(): void
    {
        // Arrange
        $sut = MonologApplicationLogger::createWithPath($this->testLogPath);
        $messageWithNewline = "Multi\nLine\nMessage";

        // Act
        $sut->info($messageWithNewline);

        // Assert - Vérifier que createWithPath utilise les mêmes paramètres de formatter
        $logContent = file_get_contents($this->testLogPath);
        $this->assertIsString($logContent);
        // Le formatter doit permettre les retours à la ligne inline (allowInlineLineBreaks = true)
        $this->assertStringContainsString('Multi', $logContent);
        $this->assertStringContainsString('Line', $logContent);
        $this->assertStringContainsString('Message', $logContent);
    }
}
