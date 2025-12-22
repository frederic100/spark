<?php

declare(strict_types=1);

/**
 * Exemple simple d'utilisation des événements avec phariscope/event
 * 
 * Cet exemple montre l'approche simplifiée recommandée par phariscope/event
 * pour l'émission et la capture d'événements de domaine.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Phariscope\Event\EventDispatcher;
use Phariscope\Event\Tools\SpyListener;
use Spark\Application\Tenant\CreateTenant\CreateTenantRequest;
use Spark\Application\Tenant\CreateTenant\CreateTenantService;
use Spark\Domain\Tenant\Event\TenantCreated;
use Spark\Domain\Tenant\TenantId;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryInMemory;

echo "=== Exemple d'événements avec phariscope/event ===\n\n";

// 1. Configuration du dispatcher
$dispatcher = EventDispatcher::instance();

// 2. Ajout d'un spy pour capturer les événements
$spy = new SpyListener();
$dispatcher->subscribe($spy);

// 3. Création d'un tenant via le service applicatif
$repository = new TenantRepositoryInMemory();
$service = new CreateTenantService($repository);
$request = new CreateTenantRequest(
    new TenantId('tenant-example'),
    'Exemple Corporation'
);

echo "Création du tenant...\n";
// Le service se contente de la logique métier
$service->execute($request);

// La couche appelante gère la distribution (responsabilité infrastructure)
$dispatcher->distribute();

$response = $service->getResponse();
$tenant = $response->getTenant();

echo "✅ Tenant créé: {$tenant->getId()} - {$tenant->getName()}\n\n";

// 4. Vérification des événements capturés
echo "=== Événements capturés ===\n";
echo "Nombre d'événements: {$spy->handleCallCount}\n";

if ($spy->domainEvent instanceof TenantCreated) {
    echo "Type d'événement: " . get_class($spy->domainEvent) . "\n";
    echo "ID du tenant: {$spy->domainEvent->tenantId}\n";
    echo "Nom du tenant: {$spy->domainEvent->tenantName}\n";
    echo "Date de l'événement: {$spy->domainEvent->occurredOn()->format('Y-m-d H:i:s')}\n";
}

echo "\n=== Avantages de cette approche ===\n";
echo "✅ Simplicité: Dispatch direct dans l'agrégat\n";
echo "✅ Responsabilité unique: Service purement métier\n";
echo "✅ Tests faciles: SpyListener au lieu de mocks\n";
echo "✅ Séparation des couches: Distribution = responsabilité infrastructure\n";
echo "✅ Flexibilité: Différentes stratégies selon la couche appelante\n";
echo "✅ Conformité: Suit les recommandations phariscope/event\n";

// 5. Nettoyage
EventDispatcher::tearDown();
