<?php

declare(strict_types=1);

namespace Spark\Domain\Tenant;

final class Tenant
{
    public function __construct(
        private readonly TenantId $id,
        private readonly string $name
    ) {
    }

    public function getId(): TenantId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
