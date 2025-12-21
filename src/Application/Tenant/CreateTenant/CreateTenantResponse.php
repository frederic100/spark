<?php

declare(strict_types=1);

namespace Spark\Application\Tenant\CreateTenant;

use Spark\Domain\Tenant\Tenant;

final readonly class CreateTenantResponse
{
    public function __construct(
        private Tenant $tenant
    ) {
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }
}
