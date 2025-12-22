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
    private const DIRECTORY_PATH = __DIR__ . '/../../..';

    public static function getRootPath(string $relativePath): string
    {
        $basePath = sprintf("%s/", realpath(self::DIRECTORY_PATH));
        $basePath .= $relativePath;
        return realpath($basePath);
    }

    public static function getDataFullPath(): string
    {
        if (!isset($_ENV['DATA_PATH'])) {
            throw new InvalidArgumentException('DATA_PATH is not set');
        }
        $dataPath = strval($_ENV['DATA_PATH']);
        if (str_contains($dataPath, '..')) {
            throw new InvalidArgumentException(
                "DATA_PATH '" . $dataPath . "' env MUST NOT contains parent folder"
            );
        }
        try {
            $path = '';
            $path = self::getRootPath($dataPath);
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

    public static function getLogFolder(): string
    {
        $fullPath = self::getDataFullPath() . "/log";
        return $fullPath;
    }
}
