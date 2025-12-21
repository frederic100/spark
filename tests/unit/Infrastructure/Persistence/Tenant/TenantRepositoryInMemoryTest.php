<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Tenant;

use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantId;
use Spark\Domain\Tenant\TenantRepository;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryInMemory;

final class TenantRepositoryInMemoryTest extends TenantRepositoryTestContract
{
    protected function createRepository(): TenantRepository
    {
        return new TenantRepositoryInMemory();
    }

    public function test_saved_tenant_can_be_retrieved(): void
    {
        // Arrange
        $sut = new TenantRepositoryInMemory();
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $tenant = new Tenant($tenantId, $tenantName);

        // Act
        $sut->save($tenant);
        $retrievedTenant = $sut->findById();

        // Assert
        $this->assertNotNull($retrievedTenant);
        $this->assertSame($tenant, $retrievedTenant);
        $this->assertTrue($tenantId->equals($retrievedTenant->getId()));
        $this->assertSame($tenantName, $retrievedTenant->getName());
    }

    public function test_findById_returns_null_when_no_tenant_exists(): void
    {
        // Arrange
        $sut = new TenantRepositoryInMemory();

        // Act
        $retrievedTenant = $sut->findById();

        // Assert
        $this->assertNull($retrievedTenant);
    }

    public function test_saving_second_tenant_throws_exception(): void
    {
        // Arrange
        $sut = new TenantRepositoryInMemory();
        $tenant1 = new Tenant(new TenantId('tenant-1'), 'Corp 1');
        $tenant2 = new Tenant(new TenantId('tenant-2'), 'Corp 2');

        $sut->save($tenant1);

        // Act & Assert
        $this->expectException(\Spark\Domain\Tenant\Exception\TenantAlreadyExistsException::class);
        $sut->save($tenant2);
    }
}
