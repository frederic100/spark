<?php

declare(strict_types=1);

namespace Spark\Domain\Shared;

use InvalidArgumentException;
use Safe\Exceptions\FilesystemException;

use function Safe\realpath;
use function SafePHP\strval;

final class BaseDir
{
    /**
     * Obtient le chemin complet du dossier de données basé sur DATA_PATH
     *
     * @throws InvalidArgumentException Si DATA_PATH n'est pas définie
     * @throws InvalidArgumentException Si DATA_PATH contient des dossiers parents (..)
     * @throws FilesystemException Si le chemin ne peut pas être résolu
     */
    public static function getDataFullPath(): string
    {
        if (!isset($_ENV['DATA_PATH'])) {
            throw new InvalidArgumentException('DATA_PATH is not set');
        }

        $dataPath = strval($_ENV['DATA_PATH']);

        // Vérifier que le chemin ne contient pas de références au dossier parent
        if (str_contains($dataPath, '..')) {
            throw new InvalidArgumentException(
                sprintf("DATA_PATH '%s' env MUST NOT contains parent folder", $dataPath)
            );
        }

        // Si le chemin est relatif, le résoudre par rapport à la racine du projet
        if (!str_starts_with($dataPath, '/')) {
            $projectRoot = dirname(__DIR__, 3); // Domain/Shared/BaseDir.php -> src -> racine
            $fullPath = $projectRoot . '/' . ltrim($dataPath, './');
        } else {
            $fullPath = $dataPath;
        }

        // Vérifier que le chemin existe et est accessible
        if (!is_dir($fullPath)) {
            // Essayer de résoudre avec realpath seulement si le dossier n'existe pas
            try {
                $realPath = realpath($fullPath);
                return $realPath;
            } catch (FilesystemException $e) {
                throw new FilesystemException(
                    sprintf("Bad data path '%s'. realpath() failed with parameter '%s'", $dataPath, $fullPath)
                );
            }
        }

        // Si le dossier existe, utiliser le chemin construit
        return $fullPath;
    }

    /**
     * Obtient un chemin relatif au projet
     */
    public static function getRootPath(string $subPath): string
    {
        // Utiliser le dossier parent de src pour être plus fiable
        $projectRoot = dirname(__DIR__, 3); // Domain/Shared/BaseDir.php -> src -> racine
        return $projectRoot . '/' . ltrim($subPath, '/');
    }

    /**
     * Obtient le chemin du dossier de logs
     * Par défaut dans le dossier data/log
     */
    public static function getLogFolder(): string
    {
        $dataPath = self::getDataFullPath();
        return $dataPath . '/log';
    }
}
