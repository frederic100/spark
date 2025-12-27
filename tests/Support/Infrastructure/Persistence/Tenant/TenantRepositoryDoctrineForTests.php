<?php

declare(strict_types=1);

namespace Tests\Support\Infrastructure\Persistence\Tenant;

use Doctrine\ORM\EntityManagerInterface;
use Spark\Domain\Tenant\Tenant;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryDoctrine;

/**
 * Extension de TenantRepositoryDoctrine pour les tests
 *
 * Cette classe surcharge save() pour appeler automatiquement flush(),
 * permettant d'utiliser les tests du contrat sans modification.
 */
final class TenantRepositoryDoctrineForTests extends TenantRepositoryDoctrine
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function save(Tenant $tenant): void
    {
        parent::save($tenant);
        $this->getEntityManager()->flush();
    }
}
