<?php

declare(strict_types=1);

namespace Spark\Domain\Tenant\Exception;

use Spark\Domain\Shared\Exception\BaseDomainException;

final class TenantAlreadyExistsException extends BaseDomainException
{
    public static function tenantAlreadyExists(): self
    {
        return new self('A tenant already exists in the application. Only one tenant is allowed.');
    }
}
