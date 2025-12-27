<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Spark\Application\Shared\PresenterInterface;
use Spark\Application\Tenant\CreateTenant\CreateTenantService;
use Spark\Application\Tenant\CreateTenant\CreateTenantRequest;
use Spark\Application\Tenant\CreateTenant\CreateTenantResponse;
use Spark\Application\Tenant\CreateTenant\Exception\CreateTenantServiceException;
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

    public function test_uses_default_presenter_and_returns_standard_response(): void
    {
        // Arrange
        $sut = new CreateTenantService($this->repository);
        $tenantId = new TenantId('tenant_abc123');
        $tenantName = 'Acme Corporation';
        $request = new CreateTenantRequest($tenantId, $tenantName);

        // Act
        $sut->execute($request);


        // Assert - Vérifie que le PresenterStandardResponse par défaut est utilisé
        // (prouvé par le fait qu'on obtient une CreateTenantResponse)
        $response = $sut->getResponse();
        $this->assertInstanceOf(CreateTenantResponse::class, $response);
        /** @var CreateTenantResponse $response */
        $this->assertTrue($tenantId->equals($response->getTenantId()));
        $this->assertSame('tenant_abc123', (string) $response->getTenantId());
    }

    public function test_saves_tenant_to_repository(): void
    {
        // Arrange
        $sut = new CreateTenantService($this->repository);
        $tenantId = new TenantId('tenant_abc123');
        $tenantName = 'Acme Corporation';
        $request = new CreateTenantRequest($tenantId, $tenantName);

        // Act
        $sut->execute($request);

        // Assert
        $savedTenant = $this->repository->findById();
        $this->assertNotNull($savedTenant);
        $this->assertTrue($tenantId->equals($savedTenant->getId()));
        $this->assertSame($tenantName, $savedTenant->getName());
    }

    public function test_throws_exception_when_getting_response_without_execution(): void
    {
        // Arrange
        $sut = new CreateTenantService($this->repository);

        // Act & Assert
        $this->expectException(CreateTenantServiceException::class);
        $this->expectExceptionMessage('No response available. Execute the command first.');

        $sut->getResponse();
    }

    public function test_execute_calls_presenter_write_with_response(): void
    {
        // Arrange
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('write')
            ->with($this->isInstanceOf(CreateTenantResponse::class));

        $sut = new CreateTenantService($this->repository, $presenter);
        $tenantId = new TenantId('tenant_abc123');
        $tenantName = 'Acme Corporation';
        $request = new CreateTenantRequest($tenantId, $tenantName);

        // Act
        $sut->execute($request);

        // Assert - La vérification est faite via le mock (expects()->method('write'))
    }
}
