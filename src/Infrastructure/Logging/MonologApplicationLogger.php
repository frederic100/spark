<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
use Spark\Domain\Shared\BaseDir;

final class MonologApplicationLogger implements LoggerInterface
{
    private static ?self $instance = null;
    private Logger $logger;

    private function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function getInstance(): LoggerInterface
    {
        if (self::$instance === null) {
            self::$instance = new self(self::createLogger());
        }

        return self::$instance;
    }

    /**
     * Réinitialise l'instance singleton (utile pour les tests)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    private static function createLogger(): Logger
    {
        // Créer le logger principal
        $logger = new Logger('spark_application');

        // Utiliser BaseDir pour obtenir le chemin des logs avec fallback
        try {
            $logDir = BaseDir::getLogFolder();

            // S'assurer que le dossier de logs existe
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            $logPath = $logDir . '/application.log';
        } catch (\Exception $e) {
            // Fallback vers le dossier var/logs si BaseDir échoue
            $fallbackDir = __DIR__ . '/../../../var/logs';
            if (!is_dir($fallbackDir)) {
                @mkdir($fallbackDir, 0755, true);
            }
            $logPath = $fallbackDir . '/application.log';
        }

        // Créer un handler pour les fichiers avec rotation
        $rotatingHandler = new RotatingFileHandler(
            $logPath,
            7, // 7 jours de logs
            Logger::DEBUG
        );

        // Configurer le format des logs
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context%\n\n",
            'Y-m-d H:i:s',
            true, // allowInlineLineBreaks
            true  // ignoreEmptyContextAndExtra
        );

        $rotatingHandler->setFormatter($formatter);

        // Ajouter le handler au logger
        $logger->pushHandler($rotatingHandler);

        return $logger;
    }

    // Implémentation de LoggerInterface (délégation vers Monolog)
    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        // Pour notre usage, level est toujours un string (LogLevel constants)
        // @phpstan-ignore-next-line argument.type
        $this->logger->log($level, $message, $context);
    }

    // Méthodes pour les tests (compatibilité avec l'ancienne interface)
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public static function createWithPath(string $logPath): LoggerInterface
    {
        $logger = new Logger('spark_application_test');

        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context%\n\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return new self($logger);
    }
}
