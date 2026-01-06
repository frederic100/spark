<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Api\V1\Tenant;

use Spark\Domain\Tenant\TenantRepository;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryDoctrine;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

use function Safe\json_decode;

final class CreateTenantTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return \Spark\Infrastructure\Symfony\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function clearDatabase(KernelBrowser $client): void
    {
        $container = $client->getContainer();

        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.default_entity_manager');

        // Supprimer tous les tenants existants
        $entityManager->createQuery('DELETE FROM Spark\Domain\Tenant\Tenant')->execute();
        $entityManager->flush();
        $entityManager->clear();
    }

    public function test_create_tenant_via_http(): void
    {
        $client = static::createClient();
        $this->clearDatabase($client);

        // Act - Faire une requête POST sur la route
        $requestContent = json_encode([
            'id' => 'integration-tenant-1',
            'name' => 'Test Organization integration'
        ]);
        $this->assertNotFalse($requestContent);
        $client->request(
            'POST',
            '/api/v1/tenant',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $requestContent
        );

        // Assert - Vérifier la réponse
        $response = $client->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        /** @var \stdClass $content */
        $content = json_decode((string) $response->getContent());
        $this->assertTrue($content->success);
        /** @var \stdClass $data */
        $data = $content->data;
        $this->assertSame('integration-tenant-1', $data->tenantId);

        // Vérifier que le tenant a été persisté en base de données
        $container = $client->getContainer();
        /** @var TenantRepository $repository */
        $repository = $container->get(TenantRepository::class);

        $this->assertInstanceOf(TenantRepositoryDoctrine::class, $repository);
        /** @var TenantRepositoryDoctrine $repository */
        $tenantFound = $repository->findById();
        $this->assertNotNull($tenantFound);
        $this->assertSame('integration-tenant-1', (string) $tenantFound->getId());
        $this->assertSame('Test Organization integration', $tenantFound->getName());
    }

    public function test_create_tenant_returns_bad_request_when_tenant_already_exists(): void
    {
        $client = static::createClient();
        $this->clearDatabase($client);

        // Créer le premier tenant
        $firstRequestContent = json_encode([
            'id' => 'integration-tenant-2',
            'name' => 'First Corp'
        ]);
        $this->assertNotFalse($firstRequestContent);
        $client->request(
            'POST',
            '/api/v1/tenant',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $firstRequestContent
        );

        $firstResponse = $client->getResponse();
        $this->assertEquals(201, $firstResponse->getStatusCode());

        // Essayer de créer un deuxième tenant (devrait échouer)
        $secondRequestContent = json_encode([
            'id' => 'integration-tenant-3',
            'name' => 'Second Corp'
        ]);
        $this->assertNotFalse($secondRequestContent);
        $client->request(
            'POST',
            '/api/v1/tenant',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $secondRequestContent
        );

        // Assert
        $response = $client->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        /** @var \stdClass $content */
        $content = json_decode((string) $response->getContent());
        $this->assertFalse($content->success);
        $this->assertSame('TenantAlreadyExistsException', $content->error);
    }

    public function test_create_tenant_returns_bad_request_when_name_is_missing(): void
    {
        $client = static::createClient();
        $this->clearDatabase($client);

        $requestContent = json_encode([
            'id' => 'integration-tenant-4'
        ]);
        $this->assertNotFalse($requestContent);
        $client->request(
            'POST',
            '/api/v1/tenant',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $requestContent
        );

        // Assert
        $response = $client->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        /** @var \stdClass $content */
        $content = json_decode((string) $response->getContent());
        $this->assertFalse($content->success);
        $this->assertObjectHasProperty('error', $content);
    }
}
