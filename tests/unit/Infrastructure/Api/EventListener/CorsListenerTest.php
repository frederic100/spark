<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api\EventListener;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Spark\Infrastructure\Api\EventListener\CorsListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CorsListenerTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function test_adds_cors_headers_when_origin_is_allowed(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081', 'http://localhost:3000'];
        $listener = new CorsListener($allowedOrigins);

        $request = Request::create('/api/v1/tenant', 'POST');
        $request->headers->set('Origin', 'http://localhost:35081');

        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Act
        $listener->onKernelResponse($event);

        // Assert
        $this->assertSame('http://localhost:35081', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame(
            'Content-Type, Authorization, X-Requested-With',
            $response->headers->get('Access-Control-Allow-Headers')
        );
        $this->assertSame('GET, POST, PUT, DELETE, PATCH', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_does_not_add_origin_header_when_origin_is_not_allowed(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins);

        $request = Request::create('/api/v1/tenant', 'POST');
        $request->headers->set('Origin', 'http://malicious-site.com');

        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Act
        $listener->onKernelResponse($event);

        // Assert
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertSame(
            'Content-Type, Authorization, X-Requested-With',
            $response->headers->get('Access-Control-Allow-Headers')
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_intercepts_options_request_in_onKernelRequest(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins);

        $request = Request::create('/api/v1/tenant', 'OPTIONS');
        $request->headers->set('Origin', 'http://localhost:35081');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Act
        $listener->onKernelRequest($event);

        // Assert
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('http://localhost:35081', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame(
            'Content-Type, Authorization, X-Requested-With',
            $response->headers->get('Access-Control-Allow-Headers')
        );
        $this->assertSame('GET, POST, PUT, DELETE, PATCH', $response->headers->get('Access-Control-Allow-Methods'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_does_not_intercept_non_options_requests(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins);

        $request = Request::create('/api/v1/tenant', 'POST');
        $request->headers->set('Origin', 'http://localhost:35081');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Act
        $listener->onKernelRequest($event);

        // Assert
        $this->assertFalse($event->hasResponse());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_adds_credentials_header_when_enabled(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins, allowCredentials: true);

        $request = Request::create('/api/v1/tenant', 'POST');
        $request->headers->set('Origin', 'http://localhost:35081');

        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Act
        $listener->onKernelResponse($event);

        // Assert
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_does_not_add_credentials_header_when_disabled(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins, allowCredentials: false);

        $request = Request::create('/api/v1/tenant', 'POST');
        $request->headers->set('Origin', 'http://localhost:35081');

        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Act
        $listener->onKernelResponse($event);

        // Assert
        $this->assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_handles_request_without_origin_header(): void
    {
        // Arrange
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins);

        $request = Request::create('/api/v1/tenant', 'POST');
        // Pas d'en-tête Origin

        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Act
        $listener->onKernelResponse($event);

        // Assert
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertSame(
            'Content-Type, Authorization, X-Requested-With',
            $response->headers->get('Access-Control-Allow-Headers')
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_does_not_add_credentials_header_when_using_default_value(): void
    {
        // Arrange - Créer un CorsListener sans passer allowCredentials (valeur par défaut = false)
        $allowedOrigins = ['http://localhost:35081'];
        $listener = new CorsListener($allowedOrigins); // Pas de paramètre allowCredentials

        $request = Request::create('/api/v1/tenant', 'POST');
        $request->headers->set('Origin', 'http://localhost:35081');

        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Act
        $listener->onKernelResponse($event);

        // Assert - Le header credentials ne doit pas être présent avec la valeur par défaut
        $this->assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
        $this->assertSame('http://localhost:35081', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
