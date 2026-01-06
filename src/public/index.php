<?php

declare(strict_types=1);

use Phariscope\MultiTenant\ContextTransformer;
use Spark\Infrastructure\Symfony\Kernel;

if (!is_file(dirname(__DIR__, 2) . '/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

require_once dirname(__DIR__, 2) . '/vendor/autoload_runtime.php';

return function (array $context) {
    /** @var array<string, mixed> $context */
    $transformer = new ContextTransformer($context);
    $transformer->transformDataPath();
    $transformer->transformDatabaseUrl();

    /** @var string $appEnv */
    $appEnv = $context['APP_ENV'] ?? 'dev';
    $kernel = new Kernel($appEnv, (bool) ($context['APP_DEBUG'] ?? false));

    return $kernel;
};
