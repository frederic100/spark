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

final class CreateTenantServiceTest extends TestCase
{
    public function test_can_execute_create_tenant_command(): void
    {
        $service = new CreateTenantService();
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

    public function test_throws_exception_when_getting_response_without_execution(): void
    {
        $service = new CreateTenantService();

        $this->expectException(CreateTenantServiceException::class);
        $this->expectExceptionMessage('No response available. Execute the command first.');

        $service->getResponse();
    }
}
