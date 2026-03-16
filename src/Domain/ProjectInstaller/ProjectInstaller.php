<?php

declare(strict_types=1);

namespace Spark\Domain\ProjectInstaller;

use InvalidArgumentException;

final class ProjectInstaller
{
    private const SOURCE_PROJECT_NAME = 'spark';
    private const MIN_PORT = 1024;
    private const MAX_PORT = 65535;

    public function __construct(
        private readonly string $projectName
    ) {
        $this->validateProjectName();
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function generateNamespace(): string
    {
        $parts = explode('-', $this->projectName);
        $namespace = '';

        foreach ($parts as $part) {
            $namespace .= ucfirst($part);
        }

        return $namespace;
    }

    public function isSourceProjectSpark(): bool
    {
        return $this->projectName === self::SOURCE_PROJECT_NAME;
    }

    public function validatePort(int $port): void
    {
        if ($port < 1) {
            throw new InvalidArgumentException('Port must be a positive integer');
        }

        if ($port < self::MIN_PORT || $port > self::MAX_PORT) {
            throw new InvalidArgumentException('Port must be between ' . self::MIN_PORT . ' and ' . self::MAX_PORT);
        }
    }

    private function validateProjectName(): void
    {
        if (empty($this->projectName)) {
            throw new InvalidArgumentException('Project name cannot be empty');
        }

        if (!preg_match('/^[a-zA-Z0-9-]+$/', $this->projectName)) {
            throw new InvalidArgumentException('Project name contains invalid characters');
        }
    }
}

