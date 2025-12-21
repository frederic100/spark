<?php

declare(strict_types=1);

namespace Spark\Domain\Tenant;

final readonly class TenantId
{
    public function __construct(
        private string $value
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(TenantId $other): bool
    {
        return $this->value === $other->value;
    }
}
