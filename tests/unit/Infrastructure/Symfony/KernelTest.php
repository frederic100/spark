<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Symfony;

use PHPUnit\Framework\TestCase;
use Spark\Infrastructure\Symfony\Kernel;

final class KernelTest extends TestCase
{
    public function test_get_project_dir_returns_correct_path(): void
    {
        // Arrange
        $kernel = new Kernel('test', false);
        $expectedPath = dirname(__DIR__, 4);

        // Act
        $projectDir = $kernel->getProjectDir();

        // Assert
        $this->assertSame($expectedPath, $projectDir);
        $this->assertDirectoryExists($projectDir);
        $this->assertFileExists($projectDir . '/composer.json');
    }

    public function test_can_be_instantiated_with_dev_environment(): void
    {
        // Arrange & Act
        $kernel = new Kernel('dev', true);

        // Assert
        $this->assertSame('dev', $kernel->getEnvironment());
        $this->assertTrue($kernel->isDebug());
    }

    public function test_can_be_instantiated_with_prod_environment(): void
    {
        // Arrange & Act
        $kernel = new Kernel('prod', false);

        // Assert
        $this->assertSame('prod', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    public function test_can_be_instantiated_with_test_environment(): void
    {
        // Arrange & Act
        $kernel = new Kernel('test', false);

        // Assert
        $this->assertSame('test', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    public function test_project_dir_points_to_project_root(): void
    {
        // Arrange
        $kernel = new Kernel('test', false);
        $projectDir = $kernel->getProjectDir();

        // Assert - Vérifier que le répertoire contient les fichiers/dossiers attendus du projet
        $this->assertFileExists($projectDir . '/composer.json');
        $this->assertDirectoryExists($projectDir . '/src');
        $this->assertDirectoryExists($projectDir . '/config');
        $this->assertDirectoryExists($projectDir . '/tests');
    }

    public function test_project_dir_is_absolute_path(): void
    {
        // Arrange
        $kernel = new Kernel('test', false);

        // Act
        $projectDir = $kernel->getProjectDir();

        // Assert
        $this->assertNotEmpty($projectDir);
        // Le chemin doit être absolu (commence par /) ou être un chemin valide
        $this->assertTrue(
            str_starts_with($projectDir, '/') ||
            (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:\\\\/', $projectDir)),
            'Project directory should be an absolute path'
        );
    }
}
