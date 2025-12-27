<?php

declare(strict_types=1);

namespace Spark\Application\Shared;

use Spark\Application\Shared\Exception\PresenterException;

final class PresenterStandardResponse implements PresenterInterface
{
    private ?Response $response = null;

    public function write(Response $response): void
    {
        $this->response = $response;
    }

    public function read(): Response
    {
        if ($this->response === null) {
            throw PresenterException::noResponseWritten();
        }

        return $this->response;
    }
}
