<?php

declare(strict_types=1);

namespace Tests\Support\Exception;

use Spark\Domain\Shared\Exception\BaseRuntimeException;

final class TestRuntimeException extends BaseRuntimeException
{
    // Hérite directement le constructeur final - pas besoin de l'overrider
}
