<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Phariscope\Event\EventDispatcher;
use Phariscope\Event\Tools\SpyListener;
use Spark\Domain\Tenant\Event\TenantCreated;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantId;

final class TenantTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private SpyListener $eventSpy;

    protected function setUp(): void
    {
        // Reset dispatcher for each test
        EventDispatcher::tearDown();
        $this->dispatcher = EventDispatcher::instance();

        // Setup spy to capture events
        $this->eventSpy = new SpyListener();
        $this->dispatcher->subscribe($this->eventSpy);
    }

    protected function tearDown(): void
    {
        EventDispatcher::tearDown();
    }

    public function test_can_create_tenant_with_id_and_name(): void
    {
        // Arrange
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';

        // Act
        $sut = new Tenant($tenantId, $tenantName);

        // Assert
        $this->assertTrue($tenantId->equals($sut->getId()));
        $this->assertSame($tenantName, $sut->getName());
    }

    public function test_dispatches_tenant_created_event_on_creation(): void
    {
        // Arrange
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';

        // Act
        new Tenant($tenantId, $tenantName);
        $this->dispatcher->distribute();

        // Assert
        $this->assertEquals(1, $this->eventSpy->handleCallCount);
        $this->assertInstanceOf(TenantCreated::class, $this->eventSpy->domainEvent);

        /** @var TenantCreated $event */
        $event = $this->eventSpy->domainEvent;
        $this->assertTrue($tenantId->equals($event->tenantId));
        $this->assertSame($tenantName, $event->tenantName);
    }
}
