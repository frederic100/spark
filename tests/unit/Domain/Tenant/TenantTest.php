<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantId;

final class TenantTest extends TestCase
{
    public function test_can_create_tenant_with_id_and_name(): void
    {
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';

        $tenant = new Tenant($tenantId, $tenantName);

        $this->assertTrue($tenantId->equals($tenant->getId()));
        $this->assertSame($tenantName, $tenant->getName());
    }
}
