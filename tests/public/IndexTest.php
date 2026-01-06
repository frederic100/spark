<?php

declare(strict_types=1);

namespace Tests\Spark;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\BaseDir;
use Spark\Infrastructure\Symfony\Kernel;

final class IndexTest extends TestCase
{
    public function test_returns_callable_that_creates_kernel(): void
    {
        // Arrange
        $baseDir = new BaseDir();
        $indexPath = $baseDir->getRootPath('src/public/index.php');

        // Act
        /** @var callable(array<string, mixed>): Kernel $result */
        $result = require $indexPath;

        // Assert - $result is already typed as callable in PHPDoc
        /** @phpstan-ignore-next-line */
        $this->assertIsCallable($result);
    }

    public function test_callable_returns_kernel_instance(): void
    {
        // Arrange
        $baseDir = new BaseDir();
        $indexPath = $baseDir->getRootPath('src/public/index.php');
        /** @var callable(array<string, mixed>): Kernel $callable */
        $callable = require $indexPath;

        // Act
        $kernel = $callable(['APP_ENV' => 'test', 'APP_DEBUG' => '0']);

        // Assert
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertSame('test', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    public function test_callable_uses_default_environment_when_not_provided(): void
    {
        // Arrange
        $baseDir = new BaseDir();
        $indexPath = $baseDir->getRootPath('src/public/index.php');
        /** @var callable(array<string, mixed>): Kernel $callable */
        $callable = require $indexPath;

        // Act
        $kernel = $callable([]);

        // Assert
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertSame('dev', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    public function test_callable_handles_debug_flag(): void
    {
        // Arrange
        $baseDir = new BaseDir();
        $indexPath = $baseDir->getRootPath('src/public/index.php');
        /** @var callable(array<string, mixed>): Kernel $callable */
        $callable = require $indexPath;

        // Act
        $kernel = $callable(['APP_ENV' => 'dev', 'APP_DEBUG' => '1']);

        // Assert
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertSame('dev', $kernel->getEnvironment());
        $this->assertTrue($kernel->isDebug());
    }
}
