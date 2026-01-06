<?php

declare(strict_types=1);

namespace Spark\Application\Tenant\CreateTenant;

use Spark\Application\Shared\Exception\PresenterException;
use Spark\Application\Shared\PresenterInterface;
use Spark\Application\Shared\Response;

final class PresenterCreateTenant implements PresenterInterface
{
    private ?\stdClass $data = null;

    public function write(Response $response): void
    {
        if (!$response instanceof CreateTenantResponse) {
            throw new \InvalidArgumentException('PresenterCreateTenant only accepts CreateTenantResponse');
        }

        $data = new \stdClass();
        $data->tenantId = (string) $response->getTenantId();
        $this->data = $data;
    }

    public function read(): \stdClass
    {
        if ($this->data === null) {
            throw PresenterException::noResponseWritten();
        }

        return $this->data;
    }
}
