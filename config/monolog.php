<?php

declare(strict_types=1);

use Spark\Domain\Shared\BaseDir;

/**
 * Configuration Monolog pour l'application Spark
 * Utilise BaseDir pour la gestion centralisée des chemins
 */

// Obtenir le dossier de logs via BaseDir
$logDir = BaseDir::getLogFolder();

return [
    'handlers' => [
        'default' => [
            'type' => 'rotating_file',
            'path' => $logDir . '/application.log',
            'level' => 'debug',
            'max_files' => 7,
            'formatter' => 'spark_custom'
        ],
        'error' => [
            'type' => 'rotating_file',
            'path' => $logDir . '/error.log',
            'level' => 'error',
            'max_files' => 14,
            'formatter' => 'spark_custom'
        ]
    ],
    'formatters' => [
        'spark_custom' => [
            'format' => "[%datetime%] %level_name%: %message% %context%\n\n",
            'date_format' => 'Y-m-d H:i:s',
            'allow_inline_line_breaks' => true,
            'ignore_empty_context_and_extra' => true
        ]
    ]
];
