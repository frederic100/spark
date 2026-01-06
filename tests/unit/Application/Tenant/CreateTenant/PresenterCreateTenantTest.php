<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Tenant\CreateTenant;

use PHPUnit\Framework\TestCase;
use Spark\Application\Shared\Exception\PresenterException;
use Spark\Application\Tenant\CreateTenant\CreateTenantResponse;
use Spark\Application\Tenant\CreateTenant\PresenterCreateTenant;
use Spark\Domain\Tenant\TenantId;

final class PresenterCreateTenantTest extends TestCase
{
    public function test_can_write_and_read_response(): void
    {
        // Arrange
        $presenter = new PresenterCreateTenant();
        $tenantId = new TenantId('tenant-123');
        $response = new CreateTenantResponse($tenantId);

        // Act
        $presenter->write($response);
        $result = $presenter->read();

        // Assert
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('tenant-123', $result->tenantId);
    }

    public function test_throws_exception_when_reading_without_writing(): void
    {
        // Arrange
        $presenter = new PresenterCreateTenant();

        // Act & Assert
        $this->expectException(PresenterException::class);
        $this->expectExceptionMessage('No response has been written to the presenter. Call write() before read().');

        $presenter->read();
    }

    public function test_can_overwrite_response(): void
    {
        // Arrange
        $presenter = new PresenterCreateTenant();
        $firstTenantId = new TenantId('tenant-123');
        $secondTenantId = new TenantId('tenant-456');
        $firstResponse = new CreateTenantResponse($firstTenantId);
        $secondResponse = new CreateTenantResponse($secondTenantId);

        // Act
        $presenter->write($firstResponse);
        $firstResult = $presenter->read();

        $presenter->write($secondResponse);
        $secondResult = $presenter->read();

        // Assert
        $this->assertSame('tenant-123', $firstResult->tenantId);
        $this->assertSame('tenant-456', $secondResult->tenantId);
        $this->assertNotSame($firstResult->tenantId, $secondResult->tenantId);
    }
}
