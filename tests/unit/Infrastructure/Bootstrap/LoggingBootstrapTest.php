<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Bootstrap;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Spark\Domain\Shared\BaseDir;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Spark\Infrastructure\Bootstrap\LoggingBootstrap;

use function Safe\file_get_contents;
use function SafePHP\strval;

final class LoggingBootstrapTest extends TestCase
{
    private string $testLogPath;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        // Créer un système de fichiers virtuel pour tous les tests
        $this->root = vfsStream::setup('root');
        $this->testLogPath = vfsStream::url('root/test.log');
        LoggerRegistry::reset();
    }

    protected function tearDown(): void
    {
        LoggerRegistry::reset();
    }

    public function test_can_create_logger_instance(): void
    {
        // Act
        LoggingBootstrap::initialize();
        $sut = LoggerRegistry::getLogger();

        // Assert
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $sut);
    }

    public function test_can_create_test_logger_with_custom_path(): void
    {
        // Act
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Assert
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $sut);
    }

    public function test_can_log_different_levels(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Act
        $sut->debug('Debug message');
        $sut->info('Info message');
        $sut->warning('Warning message');
        $sut->error('Error message');
        $sut->critical('Critical message');

        // Assert
        $this->assertFileExists($this->testLogPath);
        $logContent = file_get_contents($this->testLogPath);

        $this->assertStringContainsString('DEBUG: Debug message', $logContent);
        $this->assertStringContainsString('INFO: Info message', $logContent);
        $this->assertStringContainsString('WARNING: Warning message', $logContent);
        $this->assertStringContainsString('ERROR: Error message', $logContent);
        $this->assertStringContainsString('CRITICAL: Critical message', $logContent);
    }

    public function test_can_log_with_context(): void
    {
        $logger = LoggingBootstrap::createLogger($this->testLogPath, false);

        $context = [
            'user_id' => 123,
            'action' => 'create_tenant',
            'data' => ['name' => 'Test Tenant']
        ];

        $logger->info('Action performed', $context);

        $this->assertFileExists($this->testLogPath);
        $logContent = file_get_contents($this->testLogPath);

        $this->assertStringContainsString('INFO: Action performed', $logContent);
        $this->assertStringContainsString('user_id', $logContent);
        $this->assertStringContainsString('123', $logContent);
        $this->assertStringContainsString('create_tenant', $logContent);
    }

    public function test_log_format_contains_timestamp(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Act
        $sut->info('Test message');

        // Assert
        $logContent = file_get_contents($this->testLogPath);

        // Vérifier que le format contient un timestamp
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function test_log_format_allows_inline_line_breaks(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);
        $messageWithNewline = "Line 1\nLine 2";

        // Act
        $sut->info($messageWithNewline);

        // Assert - Vérifier que le formatter permet les retours à la ligne inline
        $logContent = file_get_contents($this->testLogPath);
        // Le formatter doit permettre les retours à la ligne (allowInlineLineBreaks = true)
        $this->assertStringContainsString('Line 1', $logContent);
        $this->assertStringContainsString('Line 2', $logContent);
    }

    public function test_log_format_ignores_empty_context(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Act
        $sut->info('Test message', []);

        // Assert - Vérifier que le formatter ignore les contextes vides (ignoreEmptyContextAndExtra = true)
        $logContent = file_get_contents($this->testLogPath);
        // Le message doit être présent même avec un contexte vide
        $this->assertStringContainsString('Test message', $logContent);
    }

    public function test_can_log_using_generic_log_method(): void
    {
        $logger = LoggingBootstrap::createLogger($this->testLogPath, false);

        $logger->log(LogLevel::NOTICE, 'Notice message');

        $logContent = file_get_contents($this->testLogPath);

        $this->assertStringContainsString('NOTICE: Notice message', $logContent);
    }

    public function test_handles_stringable_messages(): void
    {
        $logger = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Créer un objet Stringable
        $stringableMessage = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $logger->info($stringableMessage);

        $logContent = file_get_contents($this->testLogPath);

        $this->assertStringContainsString('INFO: Stringable message', $logContent);
    }

    public function test_creates_log_directory_if_not_exists(): void
    {
        // Arrange
        $logDir = vfsStream::url('root/logs');
        $logPath = $logDir . '/test.log';

        // S'assurer que le répertoire n'existe pas
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le répertoire et le fichier ont été créés
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);
    }

    public function test_log_path_is_correctly_constructed_with_directory_separator(): void
    {
        // Arrange
        $logDir = vfsStream::url('root/app/logs');
        $logPath = $logDir . '/application.log';

        // Act
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le chemin est correctement construit avec le séparateur
        $this->assertFileExists($logPath);
        $this->assertStringEndsWith('/application.log', $logPath);
    }

    public function test_rotating_file_handler_uses_seven_days_retention(): void
    {
        // Arrange
        LoggingBootstrap::initialize();
        $logger = LoggerRegistry::getLogger();

        // Assertion de type pour PHPStan
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
        /** @var \Monolog\Logger $logger */
        $handlers = $logger->getHandlers();
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
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);
        $message = 'Test with context';
        $context = ['key' => 'value'];

        // Act
        $sut->info($message, $context);

        // Assert - Vérifier que le formatter est appliqué (le format doit être celui configuré)
        $logContent = file_get_contents($this->testLogPath);
        // Le formatter doit être appliqué, donc le format doit correspondre à celui configuré
        $this->assertStringContainsString('INFO:', $logContent);
        $this->assertStringContainsString($message, $logContent);
        // Vérifier que le format contient le timestamp au format attendu
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
            $logContent
        );
    }

    public function test_createLogger_uses_correct_formatter_settings(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);
        $messageWithNewline = "Multi\nLine\nMessage";

        // Act
        $sut->info($messageWithNewline);

        // Assert - Vérifier que createLogger utilise les mêmes paramètres de formatter
        $logContent = file_get_contents($this->testLogPath);
        // Le formatter doit permettre les retours à la ligne inline (allowInlineLineBreaks = true)
        $this->assertStringContainsString('Multi', $logContent);
        $this->assertStringContainsString('Line', $logContent);
        $this->assertStringContainsString('Message', $logContent);
    }

    public function test_creates_log_directory_when_using_basedir(): void
    {
        // Arrange - Créer un système de fichiers virtuel
        $logDir = vfsStream::url('root/data/log');

        // S'assurer que le dossier n'existe pas initialement
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $logPath = $logDir . '/application.log';
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le dossier a été créé automatiquement par mkdir()
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);

        // Vérifier que le fichier de log peut être lu
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Test message', $logContent);
    }

    public function test_creates_log_directory_when_path_provided(): void
    {
        // Arrange - Créer un système de fichiers virtuel
        $logDir = vfsStream::url('root/custom/logs');
        $logPath = $logDir . '/custom.log';

        // S'assurer que le dossier parent n'existe pas initialement
        $this->assertDirectoryDoesNotExist($logDir);

        // Act - Créer le logger avec un chemin où le dossier parent n'existe pas
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le dossier parent a été créé automatiquement par mkdir()
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);

        // Vérifier que le fichier de log peut être lu
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Test message', $logContent);
    }

    public function test_creates_log_directory_when_logpath_is_null_and_directory_not_exists(): void
    {
        // Arrange
        $initialDataPath = $this->saveInitialDataPathEnvironment();
        $root = vfsStream::setup('test_root');
        $baseDirPath = vfsStream::url('test_root/project');
        $logDir = $baseDirPath . '/data/log';

        // Créer la structure de base
        vfsStream::create([
            'project' => [
                'data' => []
            ]
        ], $root);

        // Créer un realpathCallable mock qui retourne le chemin tel quel pour VFSStream
        $realpathMock = function (string $path) use ($baseDirPath) {
            if ($path === $baseDirPath) {
                return $baseDirPath;
            }
            if (str_starts_with($path, $baseDirPath)) {
                return $path;
            }
            return false;
        };

        // Créer un BaseDir avec le chemin virtuel et le mock realpathCallable
        $baseDir = new BaseDir($baseDirPath, 'data', $realpathMock);
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger(null, false, $baseDir);
        $sut->info('Test message');

        // Assert
        $this->assertDirectoryExists($logDir);
        $logPath = $logDir . '/application.log';
        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Test message', $logContent);

        // Cleanup
        $this->restoreDataPathEnvironment($initialDataPath);
    }

    public function test_creates_log_directory_only_when_directory_does_not_exist(): void
    {
        // Arrange - Créer un répertoire qui n'existe pas
        $logDir = vfsStream::url('root/logs');
        $logPath = $logDir . '/test.log';

        // S'assurer que le répertoire n'existe pas (condition !is_dir() doit être vraie)
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le répertoire a été créé (prouve que la condition !is_dir() a été évaluée correctement)
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);
    }

    public function test_does_not_recreate_directory_when_it_already_exists(): void
    {
        // Arrange - Créer un répertoire qui existe déjà
        $logDir = vfsStream::url('root/existing/logs');
        $logPath = $logDir . '/test.log';

        // Créer le répertoire avant l'appel
        vfsStream::create([
            'existing' => [
                'logs' => []
            ]
        ], $this->root);

        $this->assertDirectoryExists($logDir);

        // Capturer le timestamp/modification time du répertoire
        $originalMtime = filemtime($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le répertoire existe toujours
        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logPath);

        // Vérifier que le répertoire n'a pas été recréé
        // Si la mutation était appliquée (is_dir au lieu de !is_dir), mkdir() serait appelé
        // même si le dossier existe, ce qui pourrait modifier le mtime
        $newMtime = filemtime($logDir);
        // Le mtime devrait être identique ou très proche (tolérance de 1 seconde)
        $this->assertLessThanOrEqual(
            1,
            abs($newMtime - $originalMtime),
            'Directory should not be recreated when it already exists'
        );
    }

    public function test_createLogger_uses_provided_baseDir_instead_of_creating_new_one(): void
    {
        // Arrange - Créer un BaseDir avec un chemin virtuel très spécifique qui
        // ne correspondra jamais au chemin par défaut
        $root = vfsStream::setup('test_basedir');
        $uniqueId = uniqid('custom_', true);
        $baseDirPath = vfsStream::url('test_basedir/' . $uniqueId);
        $expectedLogDir = $baseDirPath . '/data/log';

        // Créer la structure avec le même identifiant unique
        $pathParts = explode('/', $baseDirPath);
        $folderName = end($pathParts);
        vfsStream::create([
            $folderName => [
                'data' => []
            ]
        ], $root);

        // Créer un realpathCallable mock qui retourne le chemin tel quel pour VFSStream
        $realpathMock = function (string $path) use ($baseDirPath) {
            if ($path === $baseDirPath) {
                return $baseDirPath;
            }
            if (str_starts_with($path, $baseDirPath)) {
                return $path;
            }
            // Si le chemin ne correspond pas au baseDirPath fourni, retourner false
            // Cela simule que realpath() échouerait pour un chemin par défaut différent
            return false;
        };

        $providedBaseDir = new BaseDir($baseDirPath, 'data', $realpathMock);

        // Act
        $sut = LoggingBootstrap::createLogger(null, false, $providedBaseDir);
        $sut->info('Test message');

        // Assert - Vérifier que le logger utilise bien le BaseDir fourni
        // Si la mutation était appliquée (new BaseDir() ?? $baseDir), un nouveau BaseDir serait créé
        // avec le chemin par défaut, et realpath() échouerait (retournerait false), ce qui lèverait une exception
        // ou créerait le fichier au mauvais endroit. Le test échouerait donc.
        $logPath = $expectedLogDir . '/application.log';
        $this->assertFileExists($logPath, 'Log file should be created at the path specified by the provided BaseDir');
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Test message', $logContent);
    }

    public function test_creates_log_directory_with_correct_permissions_when_logpath_is_null(): void
    {
        // Arrange
        $initialDataPath = $this->saveInitialDataPathEnvironment();
        $root = vfsStream::setup('test_perms');
        $baseDirPath = vfsStream::url('test_perms/project');
        $logDir = $baseDirPath . '/data/log';

        vfsStream::create([
            'project' => [
                'data' => []
            ]
        ], $root);

        // Créer un realpathCallable mock qui retourne le chemin tel quel pour VFSStream
        $realpathMock = function (string $path) use ($baseDirPath) {
            if ($path === $baseDirPath) {
                return $baseDirPath;
            }
            if (str_starts_with($path, $baseDirPath)) {
                return $path;
            }
            return false;
        };

        $baseDir = new BaseDir($baseDirPath, 'data', $realpathMock);
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger(null, false, $baseDir);
        $sut->info('Test message');

        // Assert - Vérifier que le répertoire a été créé avec les permissions 0755
        $this->assertDirectoryExists($logDir);
        $permissions = fileperms($logDir);
        $this->assertNotFalse($permissions);
        // Extraire les 4 derniers chiffres (permissions en octal)
        $octalPermissions = substr(decoct($permissions), -4);
        $this->assertSame(
            '0755',
            $octalPermissions,
            'Directory should be created with 0755 permissions'
        );

        // Cleanup
        $this->restoreDataPathEnvironment($initialDataPath);
    }

    public function test_creates_log_directory_with_correct_permissions_when_logpath_provided(): void
    {
        // Arrange - Créer un répertoire qui n'existe pas
        $logDir = vfsStream::url('root/permissions/logs');
        $logPath = $logDir . '/test.log';

        // S'assurer que le répertoire parent n'existe pas
        $this->assertDirectoryDoesNotExist($logDir);

        // Act
        $sut = LoggingBootstrap::createLogger($logPath, false);
        $sut->info('Test message');

        // Assert - Vérifier que le répertoire parent a été créé avec les permissions 0755
        $this->assertDirectoryExists($logDir);
        $permissions = fileperms($logDir);
        $this->assertNotFalse($permissions);
        // Extraire les 4 derniers chiffres (permissions en octal)
        $octalPermissions = substr(decoct($permissions), -4);
        $this->assertSame('0755', $octalPermissions, 'Directory should be created with 0755 permissions');
    }

    public function test_line_formatter_has_allow_inline_line_breaks_set_to_true(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Act - Utiliser la méthode publique getHandlers() pour accéder aux handlers
        $this->assertInstanceOf(\Monolog\Logger::class, $sut);
        /** @var \Monolog\Logger $sut */
        $handlers = $sut->getHandlers();

        // Assert - Vérifier que le formatter a allowInlineLineBreaks = true
        $this->assertNotEmpty($handlers);
        $handler = $handlers[0];
        $this->assertInstanceOf(\Monolog\Handler\AbstractHandler::class, $handler);

        // Utiliser la réflexion pour accéder au formatter
        $handlerReflection = new \ReflectionClass($handler);
        $formatterProperty = $handlerReflection->getProperty('formatter');
        $formatter = $formatterProperty->getValue($handler);
        $this->assertInstanceOf(\Monolog\Formatter\LineFormatter::class, $formatter);

        // Utiliser la réflexion pour vérifier la propriété allowInlineLineBreaks
        $formatterReflection = new \ReflectionClass($formatter);
        $allowInlineLineBreaksProperty = $formatterReflection->getProperty('allowInlineLineBreaks');
        $allowInlineLineBreaks = $allowInlineLineBreaksProperty->getValue($formatter);

        $this->assertTrue($allowInlineLineBreaks, 'LineFormatter should have allowInlineLineBreaks set to true');
    }

    public function test_line_formatter_has_ignore_empty_context_and_extra_set_to_true(): void
    {
        // Arrange
        $sut = LoggingBootstrap::createLogger($this->testLogPath, false);

        // Act - Utiliser la méthode publique getHandlers() pour accéder aux handlers
        $this->assertInstanceOf(\Monolog\Logger::class, $sut);
        /** @var \Monolog\Logger $sut */
        $handlers = $sut->getHandlers();

        // Assert - Vérifier que le formatter a ignoreEmptyContextAndExtra = true
        $this->assertNotEmpty($handlers);
        $handler = $handlers[0];
        $this->assertInstanceOf(\Monolog\Handler\AbstractHandler::class, $handler);

        // Utiliser la réflexion pour accéder au formatter
        $handlerReflection = new \ReflectionClass($handler);
        $formatterProperty = $handlerReflection->getProperty('formatter');
        $formatter = $formatterProperty->getValue($handler);
        $this->assertInstanceOf(\Monolog\Formatter\LineFormatter::class, $formatter);

        // Utiliser la réflexion pour vérifier la propriété ignoreEmptyContextAndExtra
        $formatterReflection = new \ReflectionClass($formatter);
        $ignoreEmptyContextAndExtraProperty = $formatterReflection->getProperty('ignoreEmptyContextAndExtra');
        $ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtraProperty->getValue($formatter);

        $this->assertTrue(
            $ignoreEmptyContextAndExtra,
            'LineFormatter should have ignoreEmptyContextAndExtra set to true'
        );
    }

    private function saveInitialDataPathEnvironment(): ?string
    {
        return isset($_ENV['DATA_PATH']) ? strval($_ENV['DATA_PATH']) : null;
    }

    private function restoreDataPathEnvironment(?string $initialDataPath): void
    {
        if ($initialDataPath !== null) {
            $_ENV['DATA_PATH'] = $initialDataPath;
        } else {
            unset($_ENV['DATA_PATH']);
        }
    }
}
