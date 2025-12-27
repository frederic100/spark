<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tenant\Event;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\Event\DomainEvent;
use Spark\Domain\Tenant\Event\TenantCreated;
use Spark\Domain\Tenant\TenantId;

final class TenantCreatedTest extends TestCase
{
    public function test_can_create_tenantCreated_event(): void
    {
        // Arrange
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $occurredOn = new \DateTimeImmutable('2024-01-15 10:30:00');

        // Act
        $sut = new TenantCreated($tenantId, $tenantName, $occurredOn);

        // Assert
        $this->assertInstanceOf(DomainEvent::class, $sut);
        $this->assertTrue($tenantId->equals($sut->tenantId));
        $this->assertSame($tenantName, $sut->tenantName);
        $this->assertSame($occurredOn, $sut->occurredOn());
    }

    public function test_can_create_tenantCreated_event_with_default_occurred_on(): void
    {
        // Arrange
        $tenantId = new TenantId('tenant-456');
        $tenantName = 'Another Corp';

        // Act
        $sut = new TenantCreated($tenantId, $tenantName);

        // Assert
        $this->assertInstanceOf(DomainEvent::class, $sut);
        $this->assertTrue($tenantId->equals($sut->tenantId));
        $this->assertSame($tenantName, $sut->tenantName);
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->occurredOn());
    }
}
