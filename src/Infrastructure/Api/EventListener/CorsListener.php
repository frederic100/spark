<?php

declare(strict_types=1);

namespace Spark\Infrastructure\Api\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CorsListener implements EventSubscriberInterface
{
    private const HIGHEST_PRIORITY_TO_INTERCEPT_OPTIONS_BEFORE_ROUTER = 9999;
    /**
     * @param array<string> $allowedOrigins
     */
    public function __construct(
        private readonly array $allowedOrigins,
        private readonly bool $allowCredentials = false
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', self::HIGHEST_PRIORITY_TO_INTERCEPT_OPTIONS_BEFORE_ROUTER],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Intercepter les requêtes preflight OPTIONS avant qu'elles n'atteignent le routeur
        if ($request->getMethod() !== 'OPTIONS') {
            return;
        }

        $origin = $request->headers->get('Origin');

        // Créer une réponse immédiate pour OPTIONS
        $response = new Response();
        $response->setStatusCode(204);

        // Ajouter les headers CORS
        $this->addCorsHeaders($response, $origin);

        // Arrêter la propagation pour que la requête n'atteigne pas le routeur
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $origin = $request->headers->get('Origin');

        // Ajouter les headers CORS à toutes les réponses
        $this->addCorsHeaders($response, $origin);
    }

    private function addCorsHeaders(Response $response, ?string $origin): void
    {
        // Si une origine est demandée et qu'elle est autorisée
        if ($origin !== null && in_array($origin, $this->allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);

            if ($this->allowCredentials) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        }

        // En-têtes CORS standards
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}
