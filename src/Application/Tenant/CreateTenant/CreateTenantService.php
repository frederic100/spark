<?php

declare(strict_types=1);

namespace Spark\Application\Tenant\CreateTenant;

use Spark\Application\Shared\PresenterInterface;
use Spark\Application\Shared\PresenterStandardResponse;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantRepository;
use Spark\Application\Tenant\CreateTenant\Exception\CreateTenantServiceException;

final class CreateTenantService
{
    private ?CreateTenantResponse $response = null;

    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly PresenterInterface $presenter = new PresenterStandardResponse()
    ) {
    }

    public function execute(CreateTenantRequest $request): void
    {
        $tenant = new Tenant(
            $request->getTenantId(),
            $request->getName()
        );

        $this->tenantRepository->save($tenant);

        $this->response = new CreateTenantResponse($tenant->getId());

        $this->presenter->write($this->response);
    }

    public function getResponse(): CreateTenantResponse
    {
        if ($this->response === null) {
            throw CreateTenantServiceException::noResponseAvailable();
        }

        return $this->response;
    }
}
