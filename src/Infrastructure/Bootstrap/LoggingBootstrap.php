<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Bootstrap;

use Spark\Domain\Shared\Logging\LoggerRegistry;
use Spark\Infrastructure\Logging\MonologApplicationLogger;

final class LoggingBootstrap
{
    /**
     * Configure le logger pour l'application en utilisant Monolog
     */
    public static function initialize(): void
    {
        LoggerRegistry::setLogger(
            MonologApplicationLogger::getInstance()
        );
    }
}
