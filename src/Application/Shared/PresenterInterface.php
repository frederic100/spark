<?php

declare(strict_types=1);

namespace Spark\Application\Shared;

interface PresenterInterface
{
    /**
     * Stocke une réponse dans le presenter
     */
    public function write(Response $response): void;

    /**
     * Lit et retourne le contenu du presenter
     * Le type de retour dépend de l'implémentation du presenter
     */
    public function read(): mixed;
}
