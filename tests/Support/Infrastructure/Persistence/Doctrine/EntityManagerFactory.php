<?php

declare(strict_types=1);

namespace Tests\Support\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Spark\Domain\Shared\BaseDir;
use Spark\Infrastructure\Persistence\Doctrine\Type\TenantIdType;

final class EntityManagerFactory
{
    private const PROXY_DIRECTORY_NAME = '/doctrine_proxies';
    private const PROXY_DIRECTORY_PERMISSIONS = 0777;
    private const MAPPING_PATH_RELATIVE = 'Infrastructure/Persistence/Doctrine/Mapping';
    private const PROXY_NAMESPACE = 'Spark\Infrastructure\Persistence\Doctrine\Proxies';

    /**
     * Crée un EntityManager configuré pour l'application
     *
     * @param array<string, mixed> $connectionParams Paramètres de connexion DBAL
     * @param BaseDir|null $baseDir Instance de BaseDir pour déterminer les chemins. Si null, crée une nouvelle instance
     * @param bool $createSchema Si true, crée automatiquement le schéma de base de données
     * @return EntityManagerInterface
     */
    public static function create(
        array $connectionParams,
        ?BaseDir $baseDir = null,
        bool $createSchema = false
    ): EntityManagerInterface {
        // Enregistrer les types personnalisés
        self::registerCustomTypes();

        // Configuration Doctrine
        $config = self::createConfiguration($baseDir);

        // Créer la connexion en utilisant ConnectionFactory qui parse automatiquement 'url'
        $connectionFactory = new ConnectionFactory([]);
        $connection = $connectionFactory->createConnection($connectionParams, $config);

        // Créer l'EntityManager
        $entityManager = new EntityManager($connection, $config);

        // Créer le schéma si demandé
        if ($createSchema) {
            self::createSchema($entityManager);
        }

        return $entityManager;
    }

    /**
     * Enregistre les types Doctrine personnalisés
     */
    private static function registerCustomTypes(): void
    {
        if (!Type::hasType('tenant_id')) {
            Type::addType('tenant_id', TenantIdType::class);
        }
    }

    /**
     * Crée la configuration Doctrine avec les mappings XML
     */
    private static function createConfiguration(?BaseDir $baseDir = null): Configuration
    {
        $baseDir = $baseDir ?? new BaseDir();
        $sourcePath = $baseDir->getSourcePath();
        $mappingPath = $sourcePath . '/' . self::MAPPING_PATH_RELATIVE;

        // Ignorer les erreurs de validation XML libxml
        $previousUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();


        $config = ORMSetup::createXMLMetadataConfiguration(
            paths: [$mappingPath],
            isDevMode: true
        );
        libxml_use_internal_errors($previousUseErrors);


        // Configurer les proxies
        $proxyDir = sys_get_temp_dir() . self::PROXY_DIRECTORY_NAME;
        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, self::PROXY_DIRECTORY_PERMISSIONS, true);
        }
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace(self::PROXY_NAMESPACE);
        $config->setAutoGenerateProxyClasses(true);

        return $config;
    }

    /**
     * Crée le schéma de base de données
     */
    public static function createSchema(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    /**
     * Crée un EntityManager pour les tests avec SQLite en mémoire
     *
     * @param BaseDir|null $baseDir Instance de BaseDir pour déterminer les chemins. Si null, crée une nouvelle instance
     * @return EntityManagerInterface
     */
    public static function createForTests(?BaseDir $baseDir = null): EntityManagerInterface
    {
        return self::create(
            connectionParams: [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            baseDir: $baseDir,
            createSchema: true
        );
    }

    /**
     * Supprime le schéma de base de données (utile pour les tests)
     */
    public static function dropSchema(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }
}
