<?php

namespace Tests\Features;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;

/**
 * Defines application features from the specific context.
 */
class BasicBehatScenarioContext implements Context
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

    #[Given('Spark is installed')]
    public function sparkIsInstalled(): void
    {
        throw new PendingException();
    }

    #[When('I run Behat')]
    public function iRunBehat(): void
    {
        throw new PendingException();
    }

    #[Then('This Scenario success')]
    public function thisScenarioSuccess(): void
    {
        throw new PendingException();
    }
}
