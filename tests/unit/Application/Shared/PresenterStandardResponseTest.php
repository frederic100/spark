<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Shared;

use PHPUnit\Framework\TestCase;
use Spark\Application\Shared\PresenterStandardResponse;
use Spark\Application\Shared\Response;
use Spark\Application\Shared\Exception\PresenterException;
use Spark\Domain\Shared\Logging\LoggerRegistry;
use Tests\Support\Logging\LoggerInMemory;

final class PresenterStandardResponseTest extends TestCase
{
    private LoggerInMemory $logger;

    protected function setUp(): void
    {
        $this->logger = new LoggerInMemory();
        LoggerRegistry::setLogger($this->logger);
    }

    protected function tearDown(): void
    {
        LoggerRegistry::reset();
    }

    public function test_can_write_and_read_response(): void
    {
        // Arrange
        $presenter = new PresenterStandardResponse();
        $response = $this->createMockResponse();

        // Act
        $presenter->write($response);
        $result = $presenter->read();

        // Assert
        $this->assertSame($response, $result);
    }


    public function test_can_overwrite_response(): void
    {
        // Arrange
        $presenter = new PresenterStandardResponse();
        $firstResponse = $this->createMockResponse();
        $secondResponse = $this->createMockResponse();

        // Act
        $presenter->write($firstResponse);
        $firstResult = $presenter->read();

        $presenter->write($secondResponse);
        $secondResult = $presenter->read();

        // Assert
        $this->assertSame($firstResponse, $firstResult);
        $this->assertSame($secondResponse, $secondResult);
        $this->assertNotSame($firstResult, $secondResult);
    }

    public function test_throws_exception_when_reading_without_writing(): void
    {
        // Arrange
        $presenter = new PresenterStandardResponse();

        // Act & Assert
        $this->expectException(PresenterException::class);
        $this->expectExceptionMessage('No response has been written to the presenter. Call write() before read().');

        $presenter->read();
    }



    private function createMockResponse(): Response
    {
        return new class implements Response {
            public function getTestData(): string
            {
                return 'mock-response-1';
            }
        };
    }
}
