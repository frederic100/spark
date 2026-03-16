<?php

declare(strict_types=1);

namespace Spark\Application\ProjectInstaller;

use RuntimeException;
use Spark\Domain\ProjectInstaller\ProjectInstaller;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\glob;
use function Safe\json_decode;
use function Safe\json_encode;

final class FileReplacer
{
    public function __construct(
        private readonly ProjectInstaller $projectInstaller
    ) {
    }

    public function replaceInFile(string $filePath, string $search, string $replace): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException('File not found: ' . $filePath);
        }

        $content = file_get_contents($filePath);
        $newContent = str_replace($search, $replace, $content);
        file_put_contents($filePath, $newContent);
    }

    public function replaceNamespaceInPhpFiles(string $directory, string $oldNamespace, string $newNamespace): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            $newContent = str_replace(
                'namespace ' . $oldNamespace . '\\',
                'namespace ' . $newNamespace . '\\',
                $content
            );
            file_put_contents($filePath, $newContent);
        }
    }

    public function updateComposerJson(string $composerJsonPath, string $newComposerName): void
    {
        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException('composer.json not found: ' . $composerJsonPath);
        }

        $content = file_get_contents($composerJsonPath);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid composer.json format');
        }

        $data['name'] = $newComposerName;

        $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerJsonPath, $newContent . "\n");
    }

    public function updateComposerJsonNamespace(string $composerJsonPath, string $newNamespace): void
    {
        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException('composer.json not found: ' . $composerJsonPath);
        }

        $content = file_get_contents($composerJsonPath);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid composer.json format');
        }

        if (!isset($data['autoload']['psr-4'])) {
            throw new RuntimeException('psr-4 autoload not found in composer.json');
        }

        $psr4 = $data['autoload']['psr-4'];
        $newPsr4 = [];

        foreach ($psr4 as $namespace => $path) {
            if ($namespace === 'Spark\\') {
                $newPsr4[$newNamespace . '\\'] = $path;
            } else {
                $newPsr4[$namespace] = $path;
            }
        }

        $data['autoload']['psr-4'] = $newPsr4;

        $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($composerJsonPath, $newContent . "\n");
    }
}

