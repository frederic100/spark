<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Bootstrap;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Spark\Domain\Shared\BaseDir;
use Spark\Domain\Shared\Logging\LoggerRegistry;

final class LoggingBootstrap
{
    private const APPLICATION_LOG_FILENAME = '/application.log';
    private const LOGGER_CHANNEL_NAME = 'spark_application';

    /**
     * Configure le logger pour l'application en utilisant Monolog
     */
    public static function initialize(): void
    {
        LoggerRegistry::setLogger(
            self::createLogger()
        );
    }

    /**
     * Crée un logger Monolog configuré
     *
     * @param string|null $logPath Chemin du fichier de log. Si null,
     * utilise BaseDir::getLogFolder() + APPLICATION_LOG_FILENAME
     * @param bool $useRotation Si true, utilise RotatingFileHandler, sinon StreamHandler (pour les tests)
     * @param BaseDir|null $baseDir Instance de BaseDir. Si null, utilise new BaseDir()
     */
    public static function createLogger(
        ?string $logPath = null,
        bool $useRotation = true,
        ?BaseDir $baseDir = null
    ): LoggerInterface {
        $logger = new Logger(self::LOGGER_CHANNEL_NAME);

        // Déterminer le chemin du log
        if ($logPath === null) {
            $baseDir = $baseDir ?? new BaseDir();
            $logDir = $baseDir->getLogFolder();

            // S'assurer que le dossier de logs existe
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            $logPath = $logDir . self::APPLICATION_LOG_FILENAME;
        } else {
            // S'assurer que le répertoire existe
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
        }

        // Configurer le format des logs
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context%\n\n",
            'Y-m-d H:i:s',
            true, // allowInlineLineBreaks
            true  // ignoreEmptyContextAndExtra
        );

        // Créer le handler approprié
        if ($useRotation) {
            $handler = new RotatingFileHandler(
                $logPath,
                7, // 7 jours de logs
                Level::Debug
            );
        } else {
            $handler = new StreamHandler($logPath, Logger::DEBUG);
        }

        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    }
}
