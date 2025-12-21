<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tenant;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Tenant\Exception\TenantAlreadyExistsException;

final class TenantAlreadyExistsExceptionTest extends TestCase
{
    public function test_can_create_tenant_already_exists_exception(): void
    {
        $exception = TenantAlreadyExistsException::tenantAlreadyExists();

        $this->assertInstanceOf(\DomainException::class, $exception);
        $this->assertStringContainsString('tenant already exists', $exception->getMessage());
    }
}
