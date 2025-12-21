<?php

declare(strict_types=1);

namespace Tests\Support\Exception;

use RuntimeException;
use Spark\Domain\Shared\Exception\LoggableExceptionTrait;

/**
 * Exception qui hérite de RuntimeException native pour tester la détection 'runtime'
 */
final class TestNativeRuntimeExceptionWithTrait extends RuntimeException
{
    use LoggableExceptionTrait;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->logException();
    }
}
