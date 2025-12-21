<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Tenant;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantId;
use Spark\Domain\Tenant\TenantRepository;

/**
 * Contract de test réutilisable pour toutes les implémentations de TenantRepository
 *
 * Cette classe abstraite définit les tests communs que toutes les implémentations
 * du repository doivent satisfaire, permettant de réutiliser les mêmes tests
 * pour différentes implémentations (InMemory, Doctrine, etc.)
 */
abstract class TenantRepositoryTestContract extends TestCase
{
    /**
     * Crée une instance du repository à tester
     * Chaque implémentation doit fournir sa propre instance
     */
    abstract protected function createRepository(): TenantRepository;

    public function test_can_save_tenant(): void
    {
        $repository = $this->createRepository();
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $tenant = new Tenant($tenantId, $tenantName);

        // Act
        $repository->save($tenant);

        // Assert - Vérifier que le tenant peut être sauvegardé sans exception
        $this->addToAssertionCount(1);
    }

    public function test_save_throws_exception_when_tenant_already_exists(): void
    {
        $repository = $this->createRepository();
        $tenant1 = new Tenant(new TenantId('tenant-1'), 'Corp 1');
        $tenant2 = new Tenant(new TenantId('tenant-2'), 'Corp 2');

        $repository->save($tenant1);

        // Act & Assert - Essayer de sauvegarder un deuxième tenant doit lever une exception
        $this->expectException(\Spark\Domain\Tenant\Exception\TenantAlreadyExistsException::class);
        $repository->save($tenant2);
    }

    public function test_can_find_saved_tenant(): void
    {
        $repository = $this->createRepository();
        $tenantId = new TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $tenant = new Tenant($tenantId, $tenantName);

        $repository->save($tenant);

        $foundTenant = $repository->findById();

        $this->assertNotNull($foundTenant);
        $this->assertTrue($tenantId->equals($foundTenant->getId()));
        $this->assertSame($tenantName, $foundTenant->getName());
    }

    public function test_findById_returns_null_when_no_tenant_exists(): void
    {
        $repository = $this->createRepository();

        $foundTenant = $repository->findById();

        $this->assertNull($foundTenant);
    }
}
