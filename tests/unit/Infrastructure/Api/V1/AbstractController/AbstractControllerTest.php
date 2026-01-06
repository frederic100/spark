<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api\V1\AbstractController;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Unit\Infrastructure\Api\V1\AbstractController\Fake\FakeController;

use function Safe\json_decode;

final class AbstractControllerTest extends TestCase
{
    use AssertResponseTrait;

    public function test_write_successful_response(): void
    {
        // Arrange
        $controller = new FakeController();
        $data = new \stdClass();
        $data->test = 'test';

        // Act
        $response = $controller->publicWriteSuccessfulResponse($data);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertResponseSuccess($response, $data);
    }

    public function test_write_successful_response_with_custom_status_code(): void
    {
        // Arrange
        $controller = new FakeController();
        $data = new \stdClass();
        $data->tenantId = 'tenant-123';

        // Act
        $response = $controller->publicWriteSuccessfulResponse($data, Response::HTTP_CREATED);

        // Assert
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertTrue($decoded['success']);
        $this->assertEquals($data, (object) $decoded['data']);
    }

    public function test_write_unsuccessful_response(): void
    {
        // Arrange
        $controller = new FakeController();

        // Act
        $response = $controller->publicWriteUnsuccessfulResponse();

        // Assert
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertResponseFailure($response, 'Exception');
    }

    public function test_write_unsuccessful_response_with_custom_status_code(): void
    {
        // Arrange
        $controller = new FakeController();
        $exception = new \InvalidArgumentException('Invalid argument');

        // Act
        $response = $controller->publicWriteUnsuccessfulResponseWithCustomStatus(
            $exception,
            Response::HTTP_BAD_REQUEST
        );

        // Assert
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);

        /** @var JsonResponse $response */
        $content = $response->getContent();
        $this->assertNotFalse($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $this->assertFalse($decoded['success']);
        $this->assertSame('InvalidArgumentException', $decoded['error']);
        $this->assertSame('Invalid argument', $decoded['error_message']);
    }
}
