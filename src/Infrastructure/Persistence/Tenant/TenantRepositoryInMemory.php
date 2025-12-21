<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Persistence\Tenant;

use Spark\Domain\Tenant\Exception\TenantAlreadyExistsException;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantRepository;

final class TenantRepositoryInMemory implements TenantRepository
{
    private ?Tenant $tenant = null;

    public function save(Tenant $tenant): void
    {
        if ($this->tenant !== null) {
            throw TenantAlreadyExistsException::tenantAlreadyExists();
        }

        $this->tenant = $tenant;
    }

    public function findById(): ?Tenant
    {
        return $this->tenant;
    }
}
