<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Spark\Domain\Tenant\TenantId;
use Spark\Infrastructure\Persistence\Doctrine\Type\TenantIdType;

final class TenantIdTypeTest extends TestCase
{
    private TenantIdType $sut;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->sut = new TenantIdType();
        $this->platform = new SQLitePlatform();
    }

    public function test_getName_returns_tenant_id(): void
    {
        // Act
        $result = $this->sut->getName();

        // Assert
        $this->assertSame('tenant_id', $result);
    }

    public function test_getSQLDeclaration_returns_string_type_declaration(): void
    {
        // Arrange
        $column = ['length' => 255];

        // Act
        $result = $this->sut->getSQLDeclaration($column, $this->platform);

        // Assert
        $this->assertNotEmpty($result);
    }

    public function test_convertToDatabaseValue_with_null_returns_null(): void
    {
        // Act
        $result = $this->sut->convertToDatabaseValue(null, $this->platform);

        // Assert
        $this->assertNull($result);
    }

    public function test_convertToDatabaseValue_with_tenantId_returns_string(): void
    {
        // Arrange
        $tenantId = new TenantId('tenant-123');

        // Act
        $result = $this->sut->convertToDatabaseValue($tenantId, $this->platform);

        // Assert
        $this->assertIsString($result);
        $this->assertSame('tenant-123', $result);
    }

    public function test_convertToDatabaseValue_with_string_returns_string(): void
    {
        // Arrange
        $value = 'tenant-456';

        // Act
        $result = $this->sut->convertToDatabaseValue($value, $this->platform);

        // Assert
        $this->assertIsString($result);
        $this->assertSame('tenant-456', $result);
    }

    public function test_convertToDatabaseValue_with_invalid_type_throws_exception(): void
    {
        // Arrange
        $invalidValue = 123;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected TenantId or string, got integer');
        $this->sut->convertToDatabaseValue($invalidValue, $this->platform);
    }

    public function test_convertToPHPValue_with_null_returns_null(): void
    {
        // Act
        $result = $this->sut->convertToPHPValue(null, $this->platform);

        // Assert
        $this->assertNull($result);
    }

    public function test_convertToPHPValue_with_string_returns_tenantId(): void
    {
        // Arrange
        $value = 'tenant-789';

        // Act
        $result = $this->sut->convertToPHPValue($value, $this->platform);

        // Assert
        $this->assertInstanceOf(TenantId::class, $result);
        $this->assertSame('tenant-789', (string) $result);
    }

    public function test_convertToPHPValue_with_invalid_type_throws_exception(): void
    {
        // Arrange
        $invalidValue = 123;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string, got integer');
        $this->sut->convertToPHPValue($invalidValue, $this->platform);
    }
}
