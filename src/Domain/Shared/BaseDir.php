<?php

declare(strict_types=1);

namespace Spark\Domain\Shared;

use InvalidArgumentException;
use Safe\Exceptions\FilesystemException;

use function Safe\getcwd;
use function Safe\realpath;
use function SafePHP\strval;

final class BaseDir
{
    public const ENV_DATA_PATH = 'DATA_PATH';

    private readonly string $basePath;
    private readonly ?string $dataPath;
    /** @var callable(string): string */
    private readonly mixed $realpathCallable;

    public function __construct(
        ?string $basePath = null,
        ?string $dataPath = null,
        ?callable $realpathCallable = null
    ) {
        $this->basePath = $basePath ?? realpath(__DIR__ . '/../../..');
        $this->dataPath = $dataPath ?? (isset($_ENV[self::ENV_DATA_PATH]) ? strval($_ENV[self::ENV_DATA_PATH]) : null);
        $this->realpathCallable = $realpathCallable ?? 'Safe\realpath';
    }

    public function getRootPath(string $relativePath): string
    {
        $realpath = $this->realpathCallable;
        $resolvedBasePath = $realpath($this->basePath);
        $basePath = sprintf("%s/", strval($resolvedBasePath));
        $basePath .= $relativePath;
        $resolvedPath = $realpath($basePath);
        return strval($resolvedPath);
    }

    public function getDataFullPath(): string
    {
        if ($this->dataPath === null) {
            throw new InvalidArgumentException(self::ENV_DATA_PATH . ' is not set');
        }
        $dataPath = $this->dataPath;
        if (str_contains($dataPath, '..')) {
            throw new InvalidArgumentException(
                self::ENV_DATA_PATH . " '" . $dataPath . "' env MUST NOT contains parent folder"
            );
        }
        try {
            $path = '';
            $path = $this->getRootPath($dataPath);
            return $path;
        } catch (FilesystemException $e) {
            throw new FilesystemException(
                sprintf(
                    "Bad data path '%s'. realpath() failed with parameter '%s'. __DIR__: %s. wd: %s",
                    $dataPath,
                    $path,
                    __DIR__,
                    getcwd()
                )
            );
        }
    }

    public function getLogFolder(): string
    {
        $fullPath = $this->getDataFullPath() . "/log";
        return $fullPath;
    }

    public function getSourcePath(): string
    {
        return $this->getRootPath('src');
    }
}
