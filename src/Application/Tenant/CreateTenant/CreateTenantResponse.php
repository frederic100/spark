<?php

declare(strict_types=1);

namespace Spark\Application\Tenant\CreateTenant;

use Spark\Application\Shared\Response;
use Spark\Domain\Tenant\TenantId;

final readonly class CreateTenantResponse implements Response
{
    public function __construct(
        private TenantId $tenantId
    ) {
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }
}
