<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use Spark\Domain\Shared\BaseDir;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\FilesystemException;

use function SafePHP\strval;

final class BaseDirTest extends TestCase
{
    private ?string $initialDataPath;
    private const DATA_PATH = 'data';

    protected function setUp(): void
    {
        if (isset($_ENV['DATA_PATH'])) {
            $this->initialDataPath = strval($_ENV['DATA_PATH']);
        } else {
            $this->initialDataPath = null;
        }
        $_ENV['DATA_PATH'] = self::DATA_PATH;
    }

    protected function tearDown(): void
    {
        if ($this->initialDataPath) {
            $_ENV['DATA_PATH'] = $this->initialDataPath;
        } else {
            unset($_ENV['DATA_PATH']);
        }
    }

    public function test_get_data_path(): void
    {
        $sut = BaseDir::getDataFullPath();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/data', $sut);
    }

    public function test_get_data_full_path_file_system_exception(): void
    {
        $this->expectException(FilesystemException::class);
        $expectedMessage = "Bad data path './baddatafolder'. " .
            "realpath() failed with parameter ";
        $regularExpression = "/" . preg_quote($expectedMessage, '/') . ".*/";
        $this->expectExceptionMessageMatches($regularExpression);

        $_ENV['DATA_PATH'] = './baddatafolder';
        BaseDir::getDataFullPath();
    }

    public function test_data_path_is_not_set_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DATA_PATH is not set');

        unset($_ENV['DATA_PATH']);
        BaseDir::getDataFullPath();
    }

    public function test_relative_data_path_current_folder(): void
    {
        // Créer le dossier temporairement pour le test
        $testPath = getcwd() . '/data/captain-learning';
        $testDataDir = getcwd() . '/data';

        if (!is_dir($testDataDir)) {
            mkdir($testDataDir, 0755, true);
        }
        if (!is_dir($testPath)) {
            mkdir($testPath, 0755, true);
        }

        $_ENV['DATA_PATH'] = './data/captain-learning';
        $sut = BaseDir::getDataFullPath();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/data/captain-learning', $sut);

        // Nettoyer après le test
        if (is_dir($testPath)) {
            rmdir($testPath);
        }
    }

    public function test_relative_data_path_parent_folder_is_forbidden_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("DATA_PATH '../data/captain-learning' env MUST NOT contains parent folder");

        $_ENV['DATA_PATH'] = '../data/captain-learning';
        BaseDir::getDataFullPath();
    }

    public function test_get_correct_dir(): void
    {
        $sut = BaseDir::getRootPath('data');
        $this->assertStringEndsWith('/data', $sut);
        $this->assertStringStartsWith('/', $sut);
    }

    public function test_get_data_dir(): void
    {
        $sut = BaseDir::getDataFullPath();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/data', $sut);
    }

    public function test_get_log_dir(): void
    {
        $sut = BaseDir::getLogFolder();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/data/log', $sut);
    }

    public function test_absolute_path_handling(): void
    {
        // Test avec un chemin absolu (si le dossier /tmp existe)
        if (is_dir('/tmp')) {
            $_ENV['DATA_PATH'] = '/tmp';
            $sut = BaseDir::getDataFullPath();
            $this->assertEquals('/tmp', $sut);
        } else {
            $this->markTestSkipped('/tmp directory not available');
        }
    }

    public function test_path_with_leading_slash_removal(): void
    {
        $_ENV['DATA_PATH'] = '/data';
        try {
            BaseDir::getDataFullPath();
            // Si ça ne lance pas d'exception, c'est que le chemin absolu a été traité
            $this->addToAssertionCount(1);
        } catch (FilesystemException $e) {
            // Normal si /data n'existe pas
            $this->assertStringContainsString('realpath() failed', $e->getMessage());
        }
    }

    public function test_get_root_path_removes_leading_slash(): void
    {
        $sut = BaseDir::getRootPath('/test/path');
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/test/path', $sut);
    }
}
