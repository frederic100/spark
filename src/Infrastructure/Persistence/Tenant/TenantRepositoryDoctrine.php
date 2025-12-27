<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Persistence\Tenant;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Spark\Domain\Tenant\Exception\TenantAlreadyExistsException;
use Spark\Domain\Tenant\Tenant;
use Spark\Domain\Tenant\TenantRepository;

/**
 * @extends EntityRepository<Tenant>
 */
class TenantRepositoryDoctrine extends EntityRepository implements TenantRepository
{
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Tenant::class));
    }

    public function save(Tenant $tenant): void
    {
        // Vérifier s'il existe déjà un tenant
        $existingTenant = $this->findById();
        if ($existingTenant !== null) {
            throw TenantAlreadyExistsException::tenantAlreadyExists();
        }

        $this->getEntityManager()->persist($tenant);
        // Note: flush() n'est pas appelé ici pour permettre la gestion transactionnelle
        // au niveau de l'application (service layer) lorsque plusieurs agrégats sont impliqués
    }

    public function findById(): ?Tenant
    {
        $repository = $this->getEntityManager()->getRepository(Tenant::class);
        $tenants = $repository->findAll();

        if (empty($tenants)) {
            return null;
        }

        return $tenants[0];
    }
}
