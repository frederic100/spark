<?php

declare(strict_types=1);

namespace Spark\Application\Tenant\CreateTenant;

use Spark\Domain\Tenant\TenantId;

final readonly class CreateTenantRequest
{
    public function __construct(
        private TenantId $tenantId,
        private string $name
    ) {
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
