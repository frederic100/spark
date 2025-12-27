<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Tenant;

use Doctrine\ORM\EntityManagerInterface;
use Spark\Domain\Shared\BaseDir;
use Spark\Domain\Tenant\TenantRepository;
use Tests\Support\Infrastructure\Persistence\Doctrine\EntityManagerFactory;
use Tests\Support\Infrastructure\Persistence\Tenant\TenantRepositoryDoctrineForTests;
use Tests\Unit\Infrastructure\Persistence\Tenant\TenantRepositoryTestContract;

final class TenantRepositoryDoctrineIntegrationTest extends TenantRepositoryTestContract
{
    private ?EntityManagerInterface $entityManager = null;
    private ?string $originalDatabaseUrl = null;
    private const DATABASE_URL_PATTERN = 'sqlite:///%s/test-integration.db';

    protected function setUp(): void
    {
        parent::setUp();

        // Sauvegarder la valeur existante de DATABASE_URL
        $this->saveOriginalDatabaseUrl();

        $databaseUrl = $this->getDatabaseUrl();
        $this->setDatabaseUrl($databaseUrl);

        // Créer l'EntityManager avec la vraie base de données en passant directement 'url'
        // ConnectionFactory dans EntityManagerFactory parse automatiquement 'url'
        $this->entityManager = EntityManagerFactory::create(
            connectionParams: ['url' => $databaseUrl],
            createSchema: false
        );

        // Supprimer et recréer le schéma pour chaque test
        EntityManagerFactory::dropSchema($this->entityManager);
        EntityManagerFactory::createSchema($this->entityManager);
    }


    private function saveOriginalDatabaseUrl(): void
    {
        $envValue = getenv('DATABASE_URL');
        $originalUrl = ($envValue !== false ? $envValue : null)
        ?? (is_string($_ENV['DATABASE_URL'] ?? null) ? $_ENV['DATABASE_URL'] : null);
        $this->originalDatabaseUrl = $originalUrl;
    }

    private function getDatabaseUrl(): string
    {
        return sprintf(self::DATABASE_URL_PATTERN, (new BaseDir())->getRootPath('var'));
    }

    private function setDatabaseUrl(string $databaseUrl): void
    {
        putenv('DATABASE_URL=' . $databaseUrl);
        $_ENV['DATABASE_URL'] = $databaseUrl;
    }

    protected function tearDown(): void
    {
        // Nettoyer les données après chaque test
        if ($this->entityManager !== null) {
            // Supprimer tous les tenants
            $this->entityManager->createQuery('DELETE FROM Spark\Domain\Tenant\Tenant')->execute();
            $this->entityManager->flush();
            $this->entityManager->clear();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        // Restaurer la valeur originale de DATABASE_URL
        if ($this->originalDatabaseUrl !== null) {
            putenv('DATABASE_URL=' . $this->originalDatabaseUrl);
            $_ENV['DATABASE_URL'] = $this->originalDatabaseUrl;
        } else {
            putenv('DATABASE_URL');
            unset($_ENV['DATABASE_URL']);
        }

        parent::tearDown();
    }

    protected function createRepository(): TenantRepository
    {
        if ($this->entityManager === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        return new TenantRepositoryDoctrineForTests($this->entityManager);
    }

    public function test_tenant_persistence_across_transactions(): void
    {
        // Arrange
        $sut = $this->createRepository();
        $tenantId = new \Spark\Domain\Tenant\TenantId('integration-tenant-4');
        $tenant = new \Spark\Domain\Tenant\Tenant($tenantId, 'Transaction Test Corp');

        // Act - Sauvegarder dans une transaction (flush automatique via TenantRepositoryDoctrineForTests)
        $sut->save($tenant);

        // Fermer et rouvrir l'EntityManager pour simuler un nouveau cycle de vie
        if ($this->entityManager !== null) {
            $this->entityManager->clear();
            $this->entityManager->close();
        }

        // Recréer l'EntityManager avec la même connexion
        $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? $this->getDatabaseUrl());
        $this->entityManager = EntityManagerFactory::create(
            connectionParams: ['url' => $databaseUrl],
            createSchema: false
        );
        $sut = $this->createRepository();

        // Assert - Vérifier que le tenant persiste
        $foundTenant = $sut->findById();
        $this->assertNotNull($foundTenant);
        $this->assertTrue($tenantId->equals($foundTenant->getId()));
    }
}
