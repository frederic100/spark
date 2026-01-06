<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Api;

final readonly class ResponseData
{
    public function __construct(
        public bool $success,
        public ?\stdClass $data = null,
        public ?string $error = null,
        public ?string $error_message = null
    ) {
    }
}
