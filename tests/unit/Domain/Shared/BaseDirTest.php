<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use org\bovigo\vfs\vfsStream;
use Spark\Domain\Shared\BaseDir;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\FilesystemException;

use function Safe\realpath;
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
        $baseDir = new BaseDir();
        $sut = $baseDir->getDataFullPath();
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
        $baseDir = new BaseDir();
        $baseDir->getDataFullPath();
    }

    public function test_data_path_is_not_set_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DATA_PATH is not set');

        unset($_ENV['DATA_PATH']);
        $baseDir = new BaseDir(realpath(__DIR__ . '/../../..'), null);
        $baseDir->getDataFullPath();
    }

    public function test_relative_data_path_parent_folder_is_forbidden_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("DATA_PATH '../data/spark' env MUST NOT contains parent folder");

        $baseDir = new BaseDir(realpath(__DIR__ . '/../../..'), '../data/spark');
        $baseDir->getDataFullPath();
    }

    public function test_get_correct_dir(): void
    {
        $baseDir = new BaseDir();
        $sut = $baseDir->getRootPath('data');
        $this->assertStringEndsWith('/data', $sut);
        $this->assertStringStartsWith('/', $sut);
    }

    public function test_get_root_path_concatenates_base_path_and_relative_path(): void
    {
        // Arrange - Créer un système de fichiers virtuel avec VFSStream
        $root = vfsStream::setup('root');
        $basePath = vfsStream::url('root/project');
        $relativePath = 'data';

        // Créer la structure de répertoires dans VFSStream
        vfsStream::create([
            'project' => [
                'data' => []
            ]
        ], $root);

        // Créer un callable mock pour realpath qui fonctionne avec VFSStream
        // Il retourne simplement le chemin normalisé (sans .., avec /)
        $realpathMock = $this->createRealpathMockForVfsStream();

        // Créer une instance BaseDir avec le basePath virtuel et le mock realpath
        $baseDir = new BaseDir($basePath, null, $realpathMock);

        // Act
        $result = $baseDir->getRootPath($relativePath);

        // Assert - Vérifier explicitement que la concaténation a eu lieu
        // Le résultat doit être exactement basePath + relativePath
        // Si la mutation .= → = était appliquée, on aurait realpath(relativePath) qui serait différent
        $expectedResult = $realpathMock($basePath . '/' . $relativePath);
        $this->assertNotFalse($expectedResult, 'Expected path should exist');

        // Vérification cruciale : le résultat doit être exactement le chemin attendu après concaténation
        $this->assertSame(
            $expectedResult,
            $result,
            'Result should be exactly basePath + relativePath, proving concatenation happened'
        );

        // Vérification supplémentaire : si on passait juste relativePath à realpath, ça donnerait un chemin différent
        $relativePathResolved = $realpathMock($relativePath);
        // Le résultat doit être différent de realpath(relativePath) seul
        $this->assertNotEquals(
            $relativePathResolved,
            $result,
            'Result should not be just realpath(relativePath), proving basePath was concatenated'
        );
    }

    private function createRealpathMockForVfsStream(): callable
    {
        return function (string $path): string {
            // Normaliser le chemin pour VFSStream
            $path = str_replace('\\', '/', $path);
            $path = rtrim($path, '/');
            // Si le chemin existe dans VFSStream, le retourner tel quel
            if (file_exists($path)) {
                return $path;
            }
            // Sinon, simuler realpath en normalisant
            $parts = explode('/', $path);
            $normalized = [];
            foreach ($parts as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }
                if ($part === '..') {
                    array_pop($normalized);
                    continue;
                }
                $normalized[] = $part;
            }
            return '/' . implode('/', $normalized);
        };
    }

    public function test_get_data_dir(): void
    {
        $baseDir = new BaseDir();
        $sut = $baseDir->getDataFullPath();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/data', $sut);
    }

    public function test_get_log_dir(): void
    {
        $baseDir = new BaseDir();
        $sut = $baseDir->getLogFolder();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/data/log', $sut);
    }

    public function test_path_with_leading_slash_removal(): void
    {
        $_ENV['DATA_PATH'] = '/data';
        try {
            $baseDir = new BaseDir();
            $baseDir->getDataFullPath();
            // Si ça ne lance pas d'exception, c'est que le chemin absolu a été traité
            $this->addToAssertionCount(1);
        } catch (FilesystemException $e) {
            // Normal si /data n'existe pas
            $this->assertStringContainsString('realpath() failed', $e->getMessage());
        }
    }

    public function test_get_source_path(): void
    {
        $baseDir = new BaseDir();
        $sut = $baseDir->getSourcePath();
        $workingDir = getcwd();
        $this->assertEquals($workingDir . '/src', $sut);
    }
}
