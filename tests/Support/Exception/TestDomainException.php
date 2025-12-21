<?php

declare(strict_types=1);

namespace Tests\Support\Exception;

use Spark\Domain\Shared\Exception\BaseDomainException;

final class TestDomainException extends BaseDomainException
{
    // Hérite directement le constructeur final - pas besoin de l'overrider
}
