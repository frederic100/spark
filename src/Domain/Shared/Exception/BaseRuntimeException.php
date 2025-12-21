<?php

declare(strict_types=1);

namespace Spark\Domain\Shared\Exception;

abstract class BaseRuntimeException extends \RuntimeException
{
    use LoggableExceptionTrait;

    final public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->logException();
    }
}
