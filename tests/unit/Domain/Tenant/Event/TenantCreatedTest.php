<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tenant\Event;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\Event\DomainEvent;
use Spark\Domain\Tenant\Event\TenantCreated;
use Spark\Domain\Tenant\TenantId;

final class TenantCreatedTest extends TestCase
{
    public function test_can_create_tenant_created_event(): void
    {
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $occurredOn = new \DateTimeImmutable('2024-01-15 10:30:00');

        $event = new TenantCreated($tenantId, $tenantName, $occurredOn);

        $this->assertInstanceOf(DomainEvent::class, $event);
        $this->assertTrue($tenantId->equals($event->tenantId));
        $this->assertSame($tenantName, $event->tenantName);
        $this->assertSame($occurredOn, $event->occurredOn());
    }

    public function test_can_create_tenant_created_event_with_default_occurred_on(): void
    {
        $tenantId = new TenantId('tenant-456');
        $tenantName = 'Another Corp';

        $event = new TenantCreated($tenantId, $tenantName);

        $this->assertInstanceOf(DomainEvent::class, $event);
        $this->assertTrue($tenantId->equals($event->tenantId));
        $this->assertSame($tenantName, $event->tenantName);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredOn());
    }
}
