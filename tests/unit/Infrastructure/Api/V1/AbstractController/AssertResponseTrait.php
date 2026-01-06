<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api\V1\AbstractController;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function Safe\json_decode;

trait AssertResponseTrait
{
    protected function assertResponseSuccess(Response $response, \stdClass $expectedData): void
    {
        Assert::assertInstanceOf(JsonResponse::class, $response);
        Assert::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        Assert::assertNotFalse($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        Assert::assertTrue($decoded['success']);
        Assert::assertEquals($expectedData, (object) $decoded['data']);
    }

    protected function assertResponseFailure(Response $response, string $expectedError): void
    {
        Assert::assertInstanceOf(JsonResponse::class, $response);
        Assert::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        /** @var JsonResponse $response */
        $content = $response->getContent();
        Assert::assertNotFalse($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        Assert::assertFalse($decoded['success']);
        Assert::assertSame($expectedError, $decoded['error']);
    }
}
