<?php

declare(strict_types=1);

namespace Spark\Domain\Tenant;

use Spark\Domain\Tenant\Exception\TenantAlreadyExistsException;

interface TenantRepository
{
    /**
     * @throws TenantAlreadyExistsException When a tenant already exists
     */
    public function save(Tenant $tenant): void;

    public function findById(): ?Tenant;
}
