<?php

declare(strict_types=1);

namespace Spark\Domain\Tenant;

use Phariscope\Event\EventDispatcher;
use Spark\Domain\Tenant\Event\TenantCreated;

final class Tenant
{
    public function __construct(
        private readonly TenantId $id,
        private readonly string $name
    ) {
        // Dispatch domain event directly as recommended by phariscope/event
        EventDispatcher::instance()->dispatch(new TenantCreated($this->id, $this->name));
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
