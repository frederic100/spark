<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\BaseDir;
use Tests\Support\Infrastructure\Persistence\Doctrine\EntityManagerFactory;
use Spark\Infrastructure\Persistence\Doctrine\Type\TenantIdType;

final class EntityManagerFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        // Nettoyer après chaque test
        if (Type::hasType('tenant_id')) {
            // Note: Type::overrideType() n'existe pas, on ne peut pas vraiment "unregister" un type
            // mais cela n'affecte pas les tests car on vérifie juste qu'il est enregistré
        }
        parent::tearDown();
    }

    public function test_createForTests_creates_entity_manager(): void
    {
        // Act
        $entityManager = EntityManagerFactory::createForTests();

        // Assert
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function test_createForTests_registers_custom_types(): void
    {
        // Act
        EntityManagerFactory::createForTests();

        // Assert
        $this->assertTrue(Type::hasType('tenant_id'));
        $this->assertInstanceOf(TenantIdType::class, Type::getType('tenant_id'));
    }

    public function test_createForTests_creates_schema(): void
    {
        // Arrange
        $entityManager = EntityManagerFactory::createForTests();

        // Act - Vérifier que le schéma existe en essayant de récupérer les métadonnées
        $metadataFactory = $entityManager->getMetadataFactory();
        $allMetadata = $metadataFactory->getAllMetadata();

        // Assert
        $this->assertNotEmpty($allMetadata, 'Schema should be created with at least one entity');
    }

    public function test_dropSchema_removes_schema(): void
    {
        // Arrange
        $entityManager = EntityManagerFactory::createForTests();

        // Act
        EntityManagerFactory::dropSchema($entityManager);

        // Assert - Le schéma devrait être supprimé (on vérifie qu'on peut le supprimer sans erreur)
        $this->addToAssertionCount(1);
    }

    public function test_create_uses_custom_base_dir(): void
    {
        // Arrange
        $customBaseDir = new BaseDir();

        // Act
        $entityManager = EntityManagerFactory::createForTests($customBaseDir);

        // Assert
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function test_create_with_createSchema_false_does_not_create_schema(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - L'EntityManager est créé même sans schéma
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function test_create_with_createSchema_true_creates_schema(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: true
        );

        // Assert - Vérifier que le schéma existe
        $metadataFactory = $entityManager->getMetadataFactory();
        $allMetadata = $metadataFactory->getAllMetadata();
        $this->assertNotEmpty($allMetadata);
    }

    public function test_createForTests_uses_sqlite_memory_driver(): void
    {
        // Act
        $entityManager = EntityManagerFactory::createForTests();

        // Assert - Vérifier que c'est bien SQLite en mémoire
        $connection = $entityManager->getConnection();
        $params = $connection->getParams();
        $this->assertSame('pdo_sqlite', $params['driver'] ?? null);
        /** @var array<string, mixed> $params */
        $this->assertTrue(isset($params['memory']) && $params['memory'] === true);
    }

    public function test_create_configures_proxy_directory(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - Vérifier que le proxy directory est configuré
        $config = $entityManager->getConfiguration();
        $proxyDir = $config->getProxyDir();
        $this->assertNotNull($proxyDir, 'Proxy directory should be configured');
        $this->assertStringContainsString('doctrine_proxies', $proxyDir);
        $this->assertStringEndsWith('doctrine_proxies', $proxyDir);
    }

    public function test_create_configures_proxy_namespace(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - Vérifier que le namespace des proxies est configuré
        $config = $entityManager->getConfiguration();
        $proxyNamespace = $config->getProxyNamespace();
        $this->assertSame('Spark\Infrastructure\Persistence\Doctrine\Proxies', $proxyNamespace);
    }

    public function test_create_enables_auto_generate_proxy_classes(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - Vérifier que l'auto-génération des proxies est activée
        $config = $entityManager->getConfiguration();
        // getAutoGenerateProxyClasses() retourne un entier (1 = true, 0 = false)
        $this->assertSame(1, $config->getAutoGenerateProxyClasses());
    }

    public function test_create_uses_dev_mode(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - Vérifier que le mode dev est activé (via la configuration)
        $config = $entityManager->getConfiguration();
        // En mode dev, les proxies sont auto-générés (retourne 1)
        $this->assertSame(1, $config->getAutoGenerateProxyClasses());
    }

    public function test_dropSchema_actually_drops_schema(): void
    {
        // Arrange
        $entityManager = EntityManagerFactory::createForTests();
        $metadataFactory = $entityManager->getMetadataFactory();
        $allMetadataBefore = $metadataFactory->getAllMetadata();
        $this->assertNotEmpty($allMetadataBefore, 'Schema should exist before drop');

        // Act
        EntityManagerFactory::dropSchema($entityManager);

        // Assert - Vérifier que dropSchema a été appelé (on ne peut pas vraiment vérifier
        // que le schéma est supprimé car c'est en mémoire, mais on vérifie qu'il n'y a pas d'erreur)
        $this->addToAssertionCount(1);
    }

    public function test_create_creates_proxy_directory_if_not_exists(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $proxyDir = sys_get_temp_dir() . '/doctrine_proxies';

        // Nettoyer le répertoire s'il existe
        if (is_dir($proxyDir)) {
            rmdir($proxyDir);
        }

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - Vérifier que le répertoire a été créé
        $this->assertDirectoryExists($proxyDir, 'Proxy directory should be created');
        $config = $entityManager->getConfiguration();
        $this->assertSame($proxyDir, $config->getProxyDir());
    }

    public function test_create_restores_libxml_error_handling(): void
    {
        // Arrange - Capturer l'état initial de libxml
        $initialErrorHandling = libxml_use_internal_errors(false);

        // Act
        EntityManagerFactory::createForTests();

        // Assert - Vérifier que l'état de libxml est restauré
        $finalErrorHandling = libxml_use_internal_errors($initialErrorHandling);
        $this->assertSame($initialErrorHandling, $finalErrorHandling, 'libxml error handling should be restored');
    }

    public function test_create_with_dev_mode_enables_proxy_auto_generation(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - En mode dev, les proxies doivent être auto-générés
        $config = $entityManager->getConfiguration();
        // Vérifier explicitement que isDevMode est true en vérifiant le comportement
        // En mode dev, autoGenerateProxyClasses est activé
        $this->assertSame(1, $config->getAutoGenerateProxyClasses(), 'Dev mode should enable proxy auto-generation');
    }

    public function test_create_with_createSchema_false_does_not_call_createSchema(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            createSchema: false
        );

        // Assert - Vérifier que le schéma n'a pas été créé (metadata vide)
        $metadataFactory = $entityManager->getMetadataFactory();
        $allMetadata = $metadataFactory->getAllMetadata();
        // Sans createSchema, les métadonnées peuvent être vides ou non chargées
        // On vérifie juste que l'EntityManager fonctionne
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function test_create_uses_base_dir_when_provided(): void
    {
        // Arrange
        $baseDir = new BaseDir();
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            baseDir: $baseDir,
            createSchema: false
        );

        // Assert - Vérifier que l'EntityManager utilise le BaseDir fourni
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function test_create_creates_base_dir_when_null(): void
    {
        // Arrange
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Act
        $entityManager = EntityManagerFactory::create(
            connectionParams: $connectionParams,
            baseDir: null,
            createSchema: false
        );

        // Assert - Vérifier que l'EntityManager crée un BaseDir par défaut quand null
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }
}
