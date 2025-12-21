<?php

declare(strict_types=1);

namespace Tests\Features;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PHPUnit\Framework\Assert;
use Spark\Application\Tenant\CreateTenant\CreateTenantRequest;
use Spark\Application\Tenant\CreateTenant\CreateTenantResponse;
use Spark\Application\Tenant\CreateTenant\CreateTenantService;
use Spark\Domain\Tenant\TenantId;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Spark\Infrastructure\Bootstrap\LoggingBootstrap;
use Spark\Infrastructure\Persistence\Tenant\TenantRepositoryInMemory;
use Tests\Support\Logging\LoggerInMemory;

/**
 * Defines application features from the specific context.
 */
class TenantBasicsContext implements Context
{
    private CreateTenantService $createTenantService;
    private CreateTenantRequest $tenantRequest;
    private CreateTenantResponse $tenantResponse;
    private LoggerInMemory $logger;
    private bool $applicationInstalled = false;
    private bool $isAdministrator = false;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->logger = new LoggerInMemory();
        $repository = new TenantRepositoryInMemory();
        $this->createTenantService = new CreateTenantService($repository);

        // Configurer le logger pour les tests Behat
        LoggerRegistry::setLogger($this->logger);
    }

    /**
     * @BeforeScenario
     */
    public function setUp(BeforeScenarioScope $scope): void
    {
        // S'assurer que chaque scénario démarre avec un état propre
        $this->logger->clear();
        LoggerRegistry::setLogger($this->logger);

        // Reset des variables d'état
        $this->applicationInstalled = false;
        $this->isAdministrator = false;
        unset($this->tenantRequest, $this->tenantResponse);
    }

    /**
     * @AfterScenario
     */
    public function tearDown(AfterScenarioScope $scope): void
    {
        // Nettoyer après chaque scénario
        LoggerRegistry::reset();
    }

    #[Given('the application is installed')]
    public function theApplicationIsInstalled(): void
    {
        // Initialiser le système de logging pour l'application
        LoggingBootstrap::initialize();

        // Marquer l'application comme installée
        $this->applicationInstalled = true;

        Assert::assertTrue($this->applicationInstalled, 'Application should be installed');
    }

    #[Given('I am the tenant administrator')]
    public function iAmTheTenantAdministrator(): void
    {
        // Dans un vrai scénario, ceci impliquerait une authentification
        // Pour nos tests, nous simulons simplement le rôle d'administrateur
        $this->isAdministrator = true;

        Assert::assertTrue($this->isAdministrator, 'User should be tenant administrator');
    }

    #[Given('the tenant is not created')]
    public function theTenantIsNotCreated(): void
    {
        // S'assurer qu'on part d'un état propre - pas de tenant pré-existant
        // Dans notre implémentation actuelle, CreateTenantService est stateless
        // donc cette étape valide simplement l'état initial

        Assert::assertFalse(isset($this->tenantResponse), 'No tenant should be created initially');
    }

    #[When('I initiate the tenant creation')]
    public function iInitiateTheTenantCreation(): void
    {
        // Préconditions : application installée et utilisateur administrateur
        Assert::assertTrue($this->applicationInstalled, 'Application must be installed');
        Assert::assertTrue($this->isAdministrator, 'User must be administrator');

        // Créer une requête de création de tenant avec des données de test
        $tenantId = new TenantId('tenant_test_001');
        $tenantName = 'Test Tenant Organization';

        $this->tenantRequest = new CreateTenantRequest($tenantId, $tenantName);

        // Exécuter la création du tenant
        $this->createTenantService->execute($this->tenantRequest);

        // Récupérer la réponse
        $this->tenantResponse = $this->createTenantService->getResponse();

        Assert::assertInstanceOf(CreateTenantResponse::class, $this->tenantResponse);
    }

    #[Then('the tenant is created')]
    public function theTenantIsCreated(): void
    {
        // Vérifier que la réponse existe
        Assert::assertInstanceOf(CreateTenantResponse::class, $this->tenantResponse);

        // Vérifier que le tenant a été créé avec les bonnes données
        $createdTenant = $this->tenantResponse->getTenant();
        Assert::assertEquals('tenant_test_001', (string) $createdTenant->getId());
        Assert::assertEquals('Test Tenant Organization', $createdTenant->getName());

        // Optionnel : Vérifier qu'aucune erreur n'a été loggée pendant la création
        $errorLogs = $this->logger->getLogsByLevel('error');
        Assert::assertEmpty($errorLogs, 'No errors should be logged during tenant creation');
    }
}
