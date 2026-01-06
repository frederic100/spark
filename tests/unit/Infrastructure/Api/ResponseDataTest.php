<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api;

use PHPUnit\Framework\TestCase;
use Spark\Infrastructure\Api\ResponseData;

final class ResponseDataTest extends TestCase
{
    public function test_can_create_successful_response_with_data(): void
    {
        // Arrange
        $data = new \stdClass();
        $data->tenantId = 'tenant-123';

        // Act
        $response = new ResponseData(true, $data);

        // Assert
        $this->assertTrue($response->success);
        $this->assertSame($data, $response->data);
        $this->assertNull($response->error);
        $this->assertNull($response->error_message);
    }

    public function test_can_create_unsuccessful_response_with_error(): void
    {
        // Arrange
        $error = 'ExceptionClassName';
        $errorMessage = 'Error message';

        // Act
        $response = new ResponseData(false, error: $error, error_message: $errorMessage);

        // Assert
        $this->assertFalse($response->success);
        $this->assertSame($error, $response->error);
        $this->assertSame($errorMessage, $response->error_message);
        $this->assertNull($response->data);
    }

    public function test_can_create_unsuccessful_response_without_error_message(): void
    {
        // Arrange
        $error = 'ExceptionClassName';

        // Act
        $response = new ResponseData(false, error: $error);

        // Assert
        $this->assertFalse($response->success);
        $this->assertSame($error, $response->error);
        $this->assertNull($response->error_message);
        $this->assertNull($response->data);
    }
}
