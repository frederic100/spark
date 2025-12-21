<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Tenant\TenantId;

final class TenantIdTest extends TestCase
{
    public function test_can_create_tenant_id_from_string(): void
    {
        $idValue = 'tenant-123';

        $tenantId = new TenantId($idValue);

        $this->assertSame($idValue, (string) $tenantId);
    }

    public function test_can_use_tenant_id_in_string_context(): void
    {
        $tenantId = new TenantId('tenant-456');

        $message = "Processing tenant: $tenantId";

        $this->assertSame('Processing tenant: tenant-456', $message);
    }

    public function test_tenant_ids_with_same_value_are_equal(): void
    {
        $tenantId1 = new TenantId('tenant-123');
        $tenantId2 = new TenantId('tenant-123');

        $this->assertTrue($tenantId1->equals($tenantId2));
    }

    public function test_tenant_ids_with_different_values_are_not_equal(): void
    {
        $tenantId1 = new TenantId('tenant-123');
        $tenantId2 = new TenantId('tenant-456');

        $this->assertFalse($tenantId1->equals($tenantId2));
    }
}
