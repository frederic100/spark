<?php

declare(strict_types=1);

namespace Spark\Domain\Tenant\Event;

use Phariscope\Event\Psr14\Event;
use Spark\Domain\Shared\Event\DomainEvent;
use Spark\Domain\Tenant\TenantId;

final class TenantCreated extends Event implements DomainEvent
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly string $tenantName,
        \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
        parent::__construct($occurredOn);
    }
}
