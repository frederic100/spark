<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Tenant;

use Doctrine\ORM\EntityManagerInterface;
use Spark\Domain\Tenant\TenantRepository;
use Tests\Support\Infrastructure\Persistence\Doctrine\EntityManagerFactory;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryDoctrine;

final class TenantRepositoryDoctrineTest extends TenantRepositoryTestContract
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = EntityManagerFactory::createForTests();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            EntityManagerFactory::dropSchema($this->entityManager);
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }

    protected function createRepository(): TenantRepository
    {
        if ($this->entityManager === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        return new TenantRepositoryDoctrine($this->entityManager);
    }

    public function test_can_save_tenant(): void
    {
        // Arrange
        $sut = $this->createRepository();
        $tenantId = new \Spark\Domain\Tenant\TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $tenant = new \Spark\Domain\Tenant\Tenant($tenantId, $tenantName);

        // Act
        $this->saveAndFlush($sut, $tenant);

        // Assert - Vérifier que le tenant peut être sauvegardé sans exception
        $this->addToAssertionCount(1);
    }

    /**
     * Helper pour appeler flush() après save() dans les tests Doctrine
     */
    private function saveAndFlush(TenantRepository $repository, \Spark\Domain\Tenant\Tenant $tenant): void
    {
        $repository->save($tenant);
        if ($this->entityManager !== null) {
            $this->entityManager->flush();
        }
    }

    public function test_save_throws_exception_when_tenant_already_exists(): void
    {
        // Arrange
        $sut = $this->createRepository();
        $tenant1 = new \Spark\Domain\Tenant\Tenant(new \Spark\Domain\Tenant\TenantId('tenant-1'), 'Corp 1');
        $tenant2 = new \Spark\Domain\Tenant\Tenant(new \Spark\Domain\Tenant\TenantId('tenant-2'), 'Corp 2');

        $this->saveAndFlush($sut, $tenant1);

        // Act & Assert
        $this->expectException(\Spark\Domain\Tenant\Exception\TenantAlreadyExistsException::class);
        $sut->save($tenant2);
    }

    public function test_can_find_saved_tenant(): void
    {
        // Arrange
        $sut = $this->createRepository();
        $tenantId = new \Spark\Domain\Tenant\TenantId('tenant-123');
        $tenantName = 'Acme Corporation';
        $tenant = new \Spark\Domain\Tenant\Tenant($tenantId, $tenantName);

        $this->saveAndFlush($sut, $tenant);

        // Act
        $foundTenant = $sut->findById();

        // Assert
        $this->assertNotNull($foundTenant);
        $this->assertTrue($tenantId->equals($foundTenant->getId()));
        $this->assertSame($tenantName, $foundTenant->getName());
    }
}
