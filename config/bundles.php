<?php

declare(strict_types=1);

/**
 * Configuration des bundles Symfony
 * 
 * Ce fichier enregistre les bundles utilisés par l'application.
 * Les bundles sont activés pour tous les environnements ('all' => true).
 */

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Phariscope\EventStore\Bridge\Symfony\EventStoreBundle::class => ['all' => true],
    Phariscope\MultiTenant\MultiTenantBundle::class => ['all' => true],
];

