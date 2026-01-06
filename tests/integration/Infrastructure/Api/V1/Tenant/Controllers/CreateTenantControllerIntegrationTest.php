<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Api\V1\Tenant\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Phariscope\Event\EventDispatcher;
use Spark\Domain\Shared\BaseDir;
use Spark\Domain\Tenant\TenantRepository;
use Spark\Infrastructure\Api\V1\Tenant\Controllers\CreateTenantController;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryDoctrine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\Infrastructure\Persistence\Doctrine\EntityManagerFactory;

use function Safe\json_decode;

final class CreateTenantControllerIntegrationTest extends TestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private ?string $originalDatabaseUrl = null;
    private const DATABASE_URL_PATTERN = 'sqlite:///%s/test-integration.db';

    protected function setUp(): void
    {
        EventDispatcher::tearDown();

        // Sauvegarder la valeur existante de DATABASE_URL
        $this->saveOriginalDatabaseUrl();

        $databaseUrl = $this->getDatabaseUrl();
        $this->setDatabaseUrl($databaseUrl);

        // Créer l'EntityManager avec la vraie base de données
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

        EventDispatcher::tearDown();
    }

    private function createController(): CreateTenantController
    {
        if ($this->entityManager === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        $repository = new TenantRepositoryDoctrine($this->entityManager);
        return new CreateTenantController($repository, $this->entityManager);
    }

    public function test_create_tenant_returns_successful_response(): void
    {
        // Arrange
        $controller = $this->createController();
        $requestContent = json_encode(['id' => 'integration-tenant-1', 'name' => 'Integration Test Corp']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        // Act
        $response = $controller->createTenant($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertTrue($decoded['success']);
        $this->assertIsArray($decoded['data']);
        /** @var array<string, mixed> $data */
        $data = $decoded['data'];
        $this->assertSame('integration-tenant-1', $data['tenantId']);

        // Vérifier que le tenant a été persisté
        if ($this->entityManager === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }
        $repository = new TenantRepositoryDoctrine($this->entityManager);
        $savedTenant = $repository->findById();
        $this->assertNotNull($savedTenant);
        $this->assertSame('integration-tenant-1', (string) $savedTenant->getId());
        $this->assertSame('Integration Test Corp', $savedTenant->getName());
    }

    public function test_create_tenant_returns_bad_request_when_tenant_already_exists(): void
    {
        // Arrange - Create first tenant
        $controller = $this->createController();
        $firstRequestContent = json_encode(['id' => 'integration-tenant-2', 'name' => 'First Corp']);
        $this->assertNotFalse($firstRequestContent);
        $firstRequest = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $firstRequestContent);
        $controller->createTenant($firstRequest);

        // Try to create second tenant (should fail)
        $secondRequestContent = json_encode(['id' => 'integration-tenant-3', 'name' => 'Second Corp']);
        $this->assertNotFalse($secondRequestContent);
        $secondRequest = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $secondRequestContent);

        // Act
        $response = $controller->createTenant($secondRequest);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertFalse($decoded['success']);
        $this->assertSame('TenantAlreadyExistsException', $decoded['error']);
    }

    public function test_create_tenant_returns_bad_request_when_name_is_missing(): void
    {
        // Arrange
        $controller = $this->createController();
        $requestContent = json_encode(['id' => 'integration-tenant-4']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        // Act
        $response = $controller->createTenant($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertFalse($decoded['success']);
        $this->assertArrayHasKey('error', $decoded);
    }
}
