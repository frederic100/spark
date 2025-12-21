<?php

declare(strict_types=1);

/**
 * Exemple de contrôleur montrant comment une couche supérieure
 * gère la distribution des événements.
 * 
 * Cette approche respecte la séparation des responsabilités :
 * - Service applicatif = logique métier pure
 * - Couche infrastructure = gestion des événements
 */

namespace Example\Infrastructure\Controller;

use Phariscope\Event\EventDispatcher;
use Spark\Application\Tenant\CreateTenant\CreateTenantRequest;
use Spark\Application\Tenant\CreateTenant\CreateTenantResponse;
use Spark\Application\Tenant\CreateTenant\CreateTenantService;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryInMemory;

/**
 * Exemple de contrôleur gérant la création de Tenant
 */
final class TenantController
{
    public function __construct(
        private readonly CreateTenantService $createTenantService,
        private readonly EventDispatcher $eventDispatcher
    ) {
    }

    /**
     * POST /api/tenants
     */
    public function createTenant(array $requestData): array
    {
        try {
            // 1. Créer la commande
            $request = new CreateTenantRequest(
                new \Spark\Domain\Tenant\TenantId($requestData['id']),
                $requestData['name']
            );

            // 2. 🎯 EXÉCUTER LA LOGIQUE MÉTIER (service purement métier)
            $this->createTenantService->execute($request);

            // 3. 🎯 GÉRER LA DISTRIBUTION (responsabilité infrastructure)
            if (!$this->eventDispatcher->isImmediateDistributionEnabled()) {
                $this->eventDispatcher->distribute();
            }

            // 4. Retourner la réponse
            $response = $this->createTenantService->getResponse();
            $tenant = $response->getTenant();

            return [
                'success' => true,
                'data' => [
                    'id' => (string) $tenant->getId(),
                    'name' => $tenant->getName()
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Exemple d'utilisation
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Configuration
    $dispatcher = EventDispatcher::instance();
    $repository = new TenantRepositoryInMemory();
    $service = new CreateTenantService($repository);
    $controller = new TenantController($service, $dispatcher);

    // Simulation d'une requête
    $requestData = [
        'id' => 'tenant-controller-example',
        'name' => 'Controller Example Corp'
    ];

    echo "=== Exemple de contrôleur avec gestion d'événements ===\n\n";
    
    $response = $controller->createTenant($requestData);
    
    if ($response['success']) {
        echo "✅ Tenant créé avec succès !\n";
        echo "ID: {$response['data']['id']}\n";
        echo "Nom: {$response['data']['name']}\n";
    } else {
        echo "❌ Erreur: {$response['error']}\n";
    }

    echo "\n=== Architecture respectée ===\n";
    echo "✅ Service applicatif: Logique métier pure\n";
    echo "✅ Contrôleur: Gestion infrastructure (événements)\n";
    echo "✅ Séparation claire des responsabilités\n";
    echo "✅ Testabilité optimale\n";

    // Nettoyage
    EventDispatcher::tearDown();
}
