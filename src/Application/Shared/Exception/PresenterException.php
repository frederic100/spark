<?php

declare(strict_types=1);

namespace Spark\Application\Shared\Exception;

use Spark\Domain\Shared\Exception\BaseRuntimeException;
use Psr\Log\LogLevel;

final class PresenterException extends BaseRuntimeException
{
    public static function noResponseWritten(): self
    {
        return (new self('No response has been written to the presenter. Call write() before read().'))
            ->withLogLevel(LogLevel::ERROR)
            ->withContext([
                'presenter_action' => 'read',
                'reason' => 'no_response_written',
                'solution' => 'call_write_method_first'
            ]);
    }
}
