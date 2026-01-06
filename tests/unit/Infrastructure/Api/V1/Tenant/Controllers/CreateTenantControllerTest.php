<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api\V1\Tenant\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Phariscope\Event\EventDispatcher;
use Phariscope\Event\Tools\SpyListener;
use Spark\Domain\Tenant\Event\TenantCreated;
use Spark\Domain\Tenant\TenantRepository;
use Spark\Infrastructure\Api\V1\Tenant\Controllers\CreateTenantController;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryInMemory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\Infrastructure\Persistence\Doctrine\EntityManagerFactory;

final class CreateTenantControllerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private TenantRepository $repository;
    private CreateTenantController $controller;

    protected function setUp(): void
    {
        EventDispatcher::tearDown();
        $this->repository = new TenantRepositoryInMemory();
        $this->entityManager = EntityManagerFactory::createForTests();
        $this->controller = new CreateTenantController(
            $this->repository,
            $this->entityManager
        );
    }

    protected function tearDown(): void
    {
        EventDispatcher::tearDown();
    }

    public function test_create_tenant_returns_successful_response(): void
    {
        // Arrange
        $requestContent = json_encode(['id' => 'tenant-123', 'name' => 'Acme Corp']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        // Act
        $response = $this->controller->createTenant($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertTrue($decoded['success']);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertIsArray($decoded['data']);
        $this->assertArrayHasKey('tenantId', $decoded['data']);
        $this->assertSame('tenant-123', $decoded['data']['tenantId']);
    }

    public function test_create_tenant_returns_bad_request_when_name_is_missing(): void
    {
        // Arrange
        $requestContent = json_encode(['id' => 'tenant-123']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        // Act
        $response = $this->controller->createTenant($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertFalse($decoded['success']);
        $this->assertArrayHasKey('error', $decoded);
    }

    public function test_create_tenant_returns_bad_request_when_name_is_empty(): void
    {
        // Arrange
        $requestContent = json_encode(['id' => 'tenant-123', 'name' => '']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        // Act
        $response = $this->controller->createTenant($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertFalse($decoded['success']);
    }

    public function test_create_tenant_returns_bad_request_when_id_is_missing(): void
    {
        // Arrange
        $requestContent = json_encode(['name' => 'Acme Corp']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        // Act
        $response = $this->controller->createTenant($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertFalse($decoded['success']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_create_tenant_handles_tenant_already_exists_exception(): void
    {
        // Arrange - Create first tenant
        $firstRequestContent = json_encode(['id' => 'tenant-123', 'name' => 'Acme Corp']);
        $this->assertNotFalse($firstRequestContent);
        $firstRequest = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $firstRequestContent);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects($this->once())->method('flush');
        $this->controller = new CreateTenantController($this->repository, $this->entityManager);
        $this->controller->createTenant($firstRequest);

        // Try to create second tenant (should fail)
        $secondRequestContent = json_encode(['id' => 'tenant-456', 'name' => 'Another Corp']);
        $this->assertNotFalse($secondRequestContent);
        $secondRequest = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $secondRequestContent);
        // EntityManager flush is not called when exception is thrown
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects($this->never())->method('flush');
        $this->controller = new CreateTenantController(
            $this->repository,
            $this->entityManager
        );

        // Act
        $response = $this->controller->createTenant($secondRequest);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertFalse($decoded['success']);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertSame('TenantAlreadyExistsException', $decoded['error']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_create_tenant_distributes_events_after_flush(): void
    {
        // Arrange
        EventDispatcher::tearDown();
        $dispatcher = EventDispatcher::instance();
        $spy = new SpyListener();
        $dispatcher->subscribe($spy);

        $requestContent = json_encode(['id' => 'tenant-123', 'name' => 'Acme Corp']);
        $this->assertNotFalse($requestContent);
        $request = Request::create('/api/v1/tenant', 'POST', [], [], [], [], $requestContent);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        $this->controller = new CreateTenantController($this->repository, $this->entityManager);

        // Act
        $this->controller->createTenant($request);

        // Assert - EventDispatcher::distribute() should be called, which distributes the TenantCreated event
        // After distribute() is called in the controller, events should be distributed
        // We verify this by checking that the event was captured by the spy
        // Note: distribute() is called inside createTenant(), so we need to check after
        // Since distribute() is called synchronously, we can verify the event was handled
        $this->assertEquals(
            1,
            $spy->handleCallCount,
            'EventDispatcher::distribute() should be called, distributing the TenantCreated event'
        );
        $this->assertInstanceOf(TenantCreated::class, $spy->domainEvent);
    }
}
