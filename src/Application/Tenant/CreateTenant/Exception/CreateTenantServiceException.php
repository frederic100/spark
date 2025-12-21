<?php

declare(strict_types=1);

namespace Spark\Application\Tenant\CreateTenant\Exception;

use Spark\Domain\Shared\Exception\BaseRuntimeException;
use Psr\Log\LogLevel;

final class CreateTenantServiceException extends BaseRuntimeException
{
    public static function noResponseAvailable(): self
    {
        return (new self('No response available. Execute the command first.'))
            ->withLogLevel(LogLevel::WARNING)
            ->withContext([
                'service' => 'CreateTenantService',
                'action' => 'getResponse',
                'reason' => 'execute_not_called'
            ]);
    }
}
