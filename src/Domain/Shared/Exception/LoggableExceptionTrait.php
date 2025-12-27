<?php

declare(strict_types=1);

namespace Spark\Domain\Shared\Exception;

use Psr\Log\LogLevel;
use Spark\Domain\Shared\Logging\LoggerRegistry;

trait LoggableExceptionTrait
{
    /** @var array<string, mixed> */
    private array $context = [];
    private string $logLevel = LogLevel::ERROR;

    /**
     * @param array<string, mixed> $context
     */
    protected function withContext(array $context): static
    {
        $this->context = $context;
        return $this;
    }

    protected function withLogLevel(string $level): static
    {
        $this->logLevel = $level;
        return $this;
    }

    private function logException(): void
    {
        $logger = LoggerRegistry::getLogger();

        $logger->log($this->logLevel, $this->getMessage(), [
            'exception_class' => static::class,
            'exception_type' => $this->getExceptionType(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            ...$this->context
        ]);
    }

    private function getExceptionType(): string
    {
        // Détection basée sur l'héritage réel plutôt que le nom de classe
        $parentClasses = class_parents($this);

        // Fallback sur les classes PHP natives
        if (in_array(\DomainException::class, $parentClasses, true)) {
            return 'domain';
        }

        if (in_array(\RuntimeException::class, $parentClasses, true)) {
            return 'runtime';
        }

        return 'exception';
    }
}
