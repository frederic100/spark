<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Spark\Domain\Tenant\TenantId;

final class TenantIdType extends Type
{
    public function getName(): string
    {
        return 'tenant_id';
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof TenantId) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new \InvalidArgumentException('Expected TenantId or string, got ' . gettype($value));
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?TenantId
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Expected string, got ' . gettype($value));
        }

        return new TenantId($value);
    }
}
