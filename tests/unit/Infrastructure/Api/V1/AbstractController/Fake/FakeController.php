<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api\V1\AbstractController\Fake;

use Spark\Infrastructure\Api\V1\AbstractController\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class FakeController extends AbstractController
{
    public function publicWriteSuccessfulResponse(\stdClass $data, int $statusCode = 200): Response
    {
        return $this->writeSuccessfulResponse($data, $statusCode);
    }

    public function publicWriteUnsuccessfulResponse(): Response
    {
        $exception = new \Exception('Test exception message');
        return $this->writeUnsuccessfulResponse($exception);
    }

    public function publicWriteUnsuccessfulResponseWithCustomStatus(\Throwable $e, int $statusCode): Response
    {
        return $this->writeUnsuccessfulResponse($e, $statusCode);
    }
}
