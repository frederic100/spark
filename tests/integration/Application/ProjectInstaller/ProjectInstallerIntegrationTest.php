<?php

declare(strict_types=1);

namespace Tests\Integration\Application\ProjectInstaller;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Spark\Application\ProjectInstaller\FileReplacer;
use Spark\Domain\ProjectInstaller\ProjectInstaller;

final class ProjectInstallerIntegrationTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('test-project');
        $this->projectDir = $this->root->url();
        $this->createMockProjectStructure();
    }

    private function createMockProjectStructure(): void
    {
        // Create composer.json
        vfsStream::newFile('composer.json')
            ->withContent('{"name": "logipro/spark", "autoload": {"psr-4": {"Spark\\\\": "src/"}}}')
            ->at($this->root);

        // Create src directory with PHP files
        $srcDir = vfsStream::newDirectory('src')->at($this->root);
        vfsStream::newFile('Domain/Test.php')
            ->withContent("<?php\n\nnamespace Spark\\Domain;\n\nclass Test {}")
            ->at($srcDir);
        vfsStream::newFile('Application/Service.php')
            ->withContent("<?php\n\nnamespace Spark\\Application;\n\nclass Service {}")
            ->at($srcDir);

        // Create docker-compose.yml
        vfsStream::newFile('docker-compose.yml')
            ->withContent("services:\n  nginx:\n    ports:\n      - '35080:80'\n  swagger:\n    ports:\n      - '35081:8080'")
            ->at($this->root);

        // Create nginx.conf
        $dockerDir = vfsStream::newDirectory('docker')->at($this->root);
        $nginxDir = vfsStream::newDirectory('nginx')->at($dockerDir);
        vfsStream::newFile('nginx.conf')
            ->withContent('root /var/spark/src/public;')
            ->at($nginxDir);

        // Create event_store.yaml
        $configDir = vfsStream::newDirectory('config')->at($this->root);
        $packagesDir = vfsStream::newDirectory('packages')->at($configDir);
        vfsStream::newFile('event_store.yaml')
            ->withContent("event_store:\n  dsn: 'sqlite:////var/spark/data/database/events.sqlite'")
            ->at($packagesDir);

        // Create swagger doc.yaml
        $swaggerDir = vfsStream::newDirectory('swagger')->at($dockerDir);
        $v1Dir = vfsStream::newDirectory('v1')->at($swaggerDir);
        vfsStream::newFile('doc.yaml')
            ->withContent("info:\n  title: Spark API\nservers:\n  - url: 'http://localhost:35080/api/v1'")
            ->at($v1Dir);

        // Create README.md
        vfsStream::newFile('README.md')
            ->withContent("# Spark\n\nData path: ./data/spark")
            ->at($this->root);
    }

    public function test_should_complete_full_installation_process(): void
    {
        $projectName = 'my-test-project';
        $portNginx = 36080;
        $portSwagger = 36081;

        $projectInstaller = new ProjectInstaller($projectName);
        $projectInstaller->validatePort($portNginx);
        $projectInstaller->validatePort($portSwagger);

        $namespace = $projectInstaller->generateNamespace();
        $composerName = 'logipro/' . $projectName;

        $fileReplacer = new FileReplacer($projectInstaller);

        // Update composer.json
        $composerJsonPath = $this->projectDir . '/composer.json';
        $fileReplacer->updateComposerJson($composerJsonPath, $composerName);
        $fileReplacer->updateComposerJsonNamespace($composerJsonPath, $namespace);

        // Replace namespaces in PHP files
        $fileReplacer->replaceNamespaceInPhpFiles($this->projectDir . '/src', 'Spark', $namespace);

        // Update docker-compose.yml
        $dockerComposePath = $this->projectDir . '/docker-compose.yml';
        $fileReplacer->replaceInFile($dockerComposePath, '35080:80', $portNginx . ':80');
        $fileReplacer->replaceInFile($dockerComposePath, '35081:8080', $portSwagger . ':8080');

        // Update nginx.conf
        $nginxConfPath = $this->projectDir . '/docker/nginx/nginx.conf';
        $fileReplacer->replaceInFile($nginxConfPath, '/var/spark/', '/var/' . $projectName . '/');

        // Update event_store.yaml
        $eventStorePath = $this->projectDir . '/config/packages/event_store.yaml';
        $fileReplacer->replaceInFile($eventStorePath, '/var/spark/', '/var/' . $projectName . '/');

        // Update swagger doc.yaml
        $swaggerDocPath = $this->projectDir . '/docker/swagger/v1/doc.yaml';
        $fileReplacer->replaceInFile($swaggerDocPath, 'Spark API', $namespace . ' API');
        $fileReplacer->replaceInFile($swaggerDocPath, ':35080', ':' . $portNginx);

        // Update README.md
        $readmePath = $this->projectDir . '/README.md';
        $fileReplacer->replaceInFile($readmePath, '# Spark', '# ' . $namespace);
        $fileReplacer->replaceInFile($readmePath, './data/spark', './data/' . $projectName);

        // Assertions
        $composerContent = json_decode(file_get_contents($composerJsonPath), true);
        $this->assertSame($composerName, $composerContent['name']);
        $this->assertArrayHasKey($namespace . '\\', $composerContent['autoload']['psr-4']);

        $domainFile = $this->projectDir . '/src/Domain/Test.php';
        $this->assertStringContainsString('namespace ' . $namespace . '\\Domain;', file_get_contents($domainFile));

        $appFile = $this->projectDir . '/src/Application/Service.php';
        $this->assertStringContainsString('namespace ' . $namespace . '\\Application;', file_get_contents($appFile));

        $dockerComposeContent = file_get_contents($dockerComposePath);
        $this->assertStringContainsString($portNginx . ':80', $dockerComposeContent);
        $this->assertStringContainsString($portSwagger . ':8080', $dockerComposeContent);

        $nginxContent = file_get_contents($nginxConfPath);
        $this->assertStringContainsString('/var/' . $projectName . '/', $nginxContent);

        $eventStoreContent = file_get_contents($eventStorePath);
        $this->assertStringContainsString('/var/' . $projectName . '/', $eventStoreContent);

        $swaggerContent = file_get_contents($swaggerDocPath);
        $this->assertStringContainsString($namespace . ' API', $swaggerContent);
        $this->assertStringContainsString(':' . $portNginx, $swaggerContent);

        $readmeContent = file_get_contents($readmePath);
        $this->assertStringContainsString('# ' . $namespace, $readmeContent);
        $this->assertStringContainsString('./data/' . $projectName, $readmeContent);
    }
}

