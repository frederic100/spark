<?php

declare(strict_types=1);

namespace Spark\Domain\Shared\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoggerRegistry
{
    private static ?LoggerInterface $logger = null;

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            // Fallback vers NullLogger si aucun logger n'est configuré
            // Évite les erreurs si le Registry n'est pas initialisé
            self::$logger = new NullLogger();
        }

        return self::$logger;
    }

    /**
     * Pour les tests : permet de reset le registry
     */
    public static function reset(): void
    {
        self::$logger = null;
    }

    /**
     * Vérifie si un logger a été configuré
     */
    public static function isConfigured(): bool
    {
        return self::$logger !== null && !self::$logger instanceof NullLogger;
    }
}
