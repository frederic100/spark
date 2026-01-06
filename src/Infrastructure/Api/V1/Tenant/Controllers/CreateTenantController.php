<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Api\V1\Tenant\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use Phariscope\Event\EventDispatcher;
use Spark\Application\Tenant\CreateTenant\CreateTenantRequest;
use Spark\Application\Tenant\CreateTenant\CreateTenantService;
use Spark\Application\Tenant\CreateTenant\PresenterCreateTenant;
use Spark\Domain\Tenant\TenantId;
use Spark\Domain\Tenant\TenantRepository;
use Spark\Infrastructure\Api\V1\AbstractController\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class CreateTenantController extends AbstractController
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/tenant', name: 'api_tenant-create', methods: ['POST'])]
    public function createTenant(Request $request): Response
    {
        try {
            $presenter = new PresenterCreateTenant();
            $service = new CreateTenantService(
                $this->tenantRepository,
                $presenter
            );

            $createTenantRequest = $this->convertToServiceRequest($request);
            $service->execute($createTenantRequest);

            $this->entityManager->flush();
            EventDispatcher::instance()->distribute();

            return $this->writeSuccessfulResponse($presenter->read(), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->writeUnsuccessfulResponse($e, Response::HTTP_BAD_REQUEST);
        }
    }

    private function convertToServiceRequest(Request $request): CreateTenantRequest
    {
        /** @var array<string, mixed>|null $content */
        $content = json_decode($request->getContent(), true);
        if (!is_array($content)) {
            throw new \InvalidArgumentException('Invalid JSON content');
        }
        if (!isset($content['id']) || !is_string($content['id'])) {
            throw new \InvalidArgumentException('Missing or invalid "id" field');
        }
        if (!isset($content['name']) || !is_string($content['name']) || empty($content['name'])) {
            throw new \InvalidArgumentException('Missing or empty "name" field');
        }

        return new CreateTenantRequest(
            new TenantId($content['id']),
            $content['name']
        );
    }
}
