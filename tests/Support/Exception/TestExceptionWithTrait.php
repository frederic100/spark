<?php

declare(strict_types=1);

namespace Tests\Support\Exception;

use Exception;
use Psr\Log\LogLevel;
use Spark\Domain\Shared\Exception\LoggableExceptionTrait;

/**
 * Exception qui hérite directement de Exception pour tester le fallback 'exception'
 */
final class TestExceptionWithTrait extends Exception
{
    use LoggableExceptionTrait;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        string $logLevel = LogLevel::ERROR
    ) {
        parent::__construct($message, $code, $previous);

        // Configurer le contexte et niveau AVANT de logger
        if (!empty($context)) {
            $this->withContext($context);
        }

        if ($logLevel !== LogLevel::ERROR) {
            $this->withLogLevel($logLevel);
        }

        $this->logException();
    }

    // Méthodes pour tester le chaînage (après construction)
    /**
     * @param array<string, mixed> $context
     */
    public function updateContext(array $context): static
    {
        return $this->withContext($context);
    }

    public function updateLogLevel(string $level): static
    {
        return $this->withLogLevel($level);
    }

    // Méthode pour déclencher un nouveau log (pour tester les modifications)
    public function logAgain(): void
    {
        $this->logException();
    }
}
