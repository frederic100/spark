<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Spark\Application\Tenant\CreateTenant\CreateTenantService;
use Spark\Application\Tenant\CreateTenant\CreateTenantRequest;
use Spark\Application\Tenant\CreateTenant\CreateTenantResponse;
use Spark\Application\Tenant\CreateTenant\Exception\CreateTenantServiceException;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantId;
use Spark\Domain\Tenant\TenantRepository;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryInMemory;

final class CreateTenantServiceTest extends TestCase
{
    private TenantRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TenantRepositoryInMemory();
    }

    public function test_can_execute_create_tenant_command(): void
    {
        $service = new CreateTenantService($this->repository);
        $tenantId = new TenantId('tenant_abc123');
        $tenantName = 'Acme Corporation';
        $request = new CreateTenantRequest($tenantId, $tenantName);

        $service->execute($request);
        $response = $service->getResponse();

        $this->assertInstanceOf(CreateTenantResponse::class, $response);
        $tenant = $response->getTenant();
        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertTrue($tenantId->equals($tenant->getId()));
        $this->assertSame($tenantName, $tenant->getName());
    }

    public function test_saves_tenant_to_repository(): void
    {
        $service = new CreateTenantService($this->repository);
        $tenantId = new TenantId('tenant_abc123');
        $tenantName = 'Acme Corporation';
        $request = new CreateTenantRequest($tenantId, $tenantName);

        $service->execute($request);

        // Vérifier que le tenant a été sauvegardé dans le repository
        $savedTenant = $this->repository->findById();

        $this->assertNotNull($savedTenant);
        $this->assertTrue($tenantId->equals($savedTenant->getId()));
        $this->assertSame($tenantName, $savedTenant->getName());
    }

    public function test_throws_exception_when_tenant_already_exists(): void
    {
        $service = new CreateTenantService($this->repository);
        $tenantId1 = new TenantId('tenant_1');
        $tenantId2 = new TenantId('tenant_2');

        $request1 = new CreateTenantRequest($tenantId1, 'First Corp');
        $request2 = new CreateTenantRequest($tenantId2, 'Second Corp');

        $service->execute($request1);

        // Act & Assert - Essayer de créer un deuxième tenant doit lever une exception
        $this->expectException(\Spark\Domain\Tenant\Exception\TenantAlreadyExistsException::class);
        $service->execute($request2);
    }

    public function test_throws_exception_when_getting_response_without_execution(): void
    {
        $service = new CreateTenantService($this->repository);

        $this->expectException(CreateTenantServiceException::class);
        $this->expectExceptionMessage('No response available. Execute the command first.');

        $service->getResponse();
    }
}
