<?php

namespace Tests\Features;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;

/**
 * Defines application features from the specific context.
 */
class TenantBasicsContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    #[Given('the application is installed')]
    public function theApplicationIsInstalled(): void
    {
        throw new PendingException();
    }

    #[Given('I am the tenant administrator')]
    public function iAmTheTenantAdministrator(): void
    {
        throw new PendingException();
    }

    #[Given('the tenant is not created')]
    public function theTenantIsNotCreated(): void
    {
        throw new PendingException();
    }

    #[When('I initiate the tenant creation')]
    public function iInitiateTheTenantCreation(): void
    {
        throw new PendingException();
    }

    #[Then('the tenant is created')]
    public function theTenantIsCreated(): void
    {
        throw new PendingException();
    }
}
