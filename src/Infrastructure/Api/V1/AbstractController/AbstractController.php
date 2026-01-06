<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Api\V1\AbstractController;

use Spark\Infrastructure\Api\ResponseData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as ControllerAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AbstractController extends ControllerAbstractController
{
    public const ERROR_CODE_SUCCESS = 200;
    public const ERROR_CODE_ERROR = 500;

    public const SIMULATED_EXCEPTION_KEY = 'simulatedException';
    public const SIMULATED_EXCEPTION_MESSAGE = 'Simulated exception';

    protected function writeSuccessfulResponse(\stdClass $data, int $statusCode = self::ERROR_CODE_SUCCESS): Response
    {
        $response = new ResponseData(true, $data);
        return new JsonResponse($response, status: $statusCode);
    }

    protected function writeUnsuccessfulResponse(\Throwable $e, int $statusCode = self::ERROR_CODE_ERROR): Response
    {
        $response = new ResponseData(
            false,
            error: $this->getShortClassName($e),
            error_message: $e->getMessage()
        );
        return new JsonResponse($response, status: $statusCode);
    }

    protected function tryToThrowSimulatedException(Request $tenantContent): void
    {
        /** @var array<string, mixed>|null $jsonContent */
        $jsonContent = json_decode($tenantContent->getContent(), true);
        if (
            is_array($jsonContent) &&
            isset($jsonContent[self::SIMULATED_EXCEPTION_KEY]) &&
            $jsonContent[self::SIMULATED_EXCEPTION_KEY] === true
        ) {
            throw new \Exception(self::SIMULATED_EXCEPTION_MESSAGE);
        }
    }

    private function getShortClassName(\Throwable $e): string
    {
        $reflection = new \ReflectionClass($e);
        return $reflection->getShortName();
    }
}
