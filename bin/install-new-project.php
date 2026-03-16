#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Spark\Application\ProjectInstaller\FileReplacer;
use Spark\Domain\ProjectInstaller\ProjectInstaller;

function parseArguments(array $argv): array
{
    $params = [
        'name' => null,
        'port-nginx' => 35080,
        'port-swagger' => 35081,
        'namespace' => null,
        'composer-name' => null,
        'remove-git' => true,
    ];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if (str_starts_with($arg, '--')) {
            $parts = explode('=', $arg, 2);
            $key = substr($parts[0], 2);

            if (array_key_exists($key, $params)) {
                if (count($parts) === 2) {
                    $value = $parts[1];
                    // Convert string "true"/"false" to boolean for remove-git
                    if ($key === 'remove-git') {
                        $params[$key] = $value === 'true';
                    } else {
                        $params[$key] = $value;
                    }
                } else {
                    $params[$key] = true;
                }
            }
        }
    }

    return $params;
}

function validateParameters(array $params): void
{
    if (empty($params['name'])) {
        throw new InvalidArgumentException('--name is required');
    }
}

function main(array $argv): int
{
    try {
        $params = parseArguments($argv);
        validateParameters($params);

        $projectName = $params['name'];
        $portNginx = (int) $params['port-nginx'];
        $portSwagger = (int) $params['port-swagger'];
        $namespace = $params['namespace'];
        $composerName = $params['composer-name'];
        $removeGit = $params['remove-git'] === true;

        $projectInstaller = new ProjectInstaller($projectName);
        $projectInstaller->validatePort($portNginx);
        $projectInstaller->validatePort($portSwagger);

        if ($namespace === null) {
            $namespace = $projectInstaller->generateNamespace();
        }

        if ($composerName === null) {
            $composerName = 'logipro/' . $projectName;
        }

        $fileReplacer = new FileReplacer($projectInstaller);
        $baseDir = dirname(__DIR__);

        echo "Starting project installation...\n";
        echo "Project name: $projectName\n";
        echo "Namespace: $namespace\n";
        echo "Composer name: $composerName\n";
        echo "Ports: nginx=$portNginx, swagger=$portSwagger\n\n";

        // Update composer.json
        echo "Updating composer.json...\n";
        $composerJsonPath = $baseDir . '/composer.json';
        $fileReplacer->updateComposerJson($composerJsonPath, $composerName);
        $fileReplacer->updateComposerJsonNamespace($composerJsonPath, $namespace);

        // Replace namespaces in PHP files
        echo "Replacing namespaces in PHP files...\n";
        $fileReplacer->replaceNamespaceInPhpFiles($baseDir . '/src', 'Spark', $namespace);

        // Update docker-compose.yml
        echo "Updating docker-compose.yml...\n";
        $dockerComposePath = $baseDir . '/docker-compose.yml';
        $fileReplacer->replaceInFile($dockerComposePath, '35080:80', $portNginx . ':80');
        $fileReplacer->replaceInFile($dockerComposePath, '35081:8080', $portSwagger . ':8080');

        // Update nginx.conf
        echo "Updating nginx.conf...\n";
        $nginxConfPath = $baseDir . '/docker/nginx/nginx.conf';
        $fileReplacer->replaceInFile($nginxConfPath, '/var/spark/', '/var/' . $projectName . '/');

        // Update event_store.yaml
        echo "Updating event_store.yaml...\n";
        $eventStorePath = $baseDir . '/config/packages/event_store.yaml';
        $fileReplacer->replaceInFile($eventStorePath, '/var/spark/', '/var/' . $projectName . '/');

        // Update swagger doc.yaml
        echo "Updating swagger doc.yaml...\n";
        $swaggerDocPath = $baseDir . '/docker/swagger/v1/doc.yaml';
        $fileReplacer->replaceInFile($swaggerDocPath, 'Spark API', $namespace . ' API');
        $fileReplacer->replaceInFile($swaggerDocPath, ':35080', ':' . $portNginx);

        // Update README.md
        echo "Updating README.md...\n";
        $readmePath = $baseDir . '/README.md';
        $fileReplacer->replaceInFile($readmePath, '# Spark', '# ' . $namespace);
        $fileReplacer->replaceInFile($readmePath, './data/spark', './data/' . $projectName);
        $fileReplacer->replaceInFile($readmePath, '../data/spark', '../data/' . $projectName);

        // Create or update .env file
        echo "Creating/updating .env file...\n";
        $envPath = $baseDir . '/.env';
        $envContent = "COMPOSE_PROJECT_NAME=$projectName\n";
        $envContent .= "DATA_PATH=./data/$projectName\n";
        $envContent .= "DATA_PATH_STORE=../data/$projectName\n";

        if (file_exists($envPath)) {
            $existingContent = \Safe\file_get_contents($envPath);
            if (!str_contains($existingContent, 'COMPOSE_PROJECT_NAME')) {
                \Safe\file_put_contents($envPath, $envContent, FILE_APPEND);
            }
        } else {
            \Safe\file_put_contents($envPath, $envContent);
        }

        // Remove .git if requested
        if ($removeGit) {
            echo "Removing .git directory...\n";
            $gitPath = $baseDir . '/.git';
            if (is_dir($gitPath)) {
                removeDirectory($gitPath);
            }
        }

        echo "\nInstallation completed successfully!\n";
        return 0;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return 1;
    }
}

function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

exit(main($argv));

