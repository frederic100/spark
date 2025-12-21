<?php

declare(strict_types=1);

namespace Tests\Support\Exception;

use DomainException;
use Spark\Domain\Shared\Exception\LoggableExceptionTrait;

/**
 * Exception qui hérite de DomainException native pour tester la détection 'domain'
 */
final class TestNativeDomainExceptionWithTrait extends DomainException
{
    use LoggableExceptionTrait;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->logException();
    }
}
