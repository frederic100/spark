<?php

namespace Tests\Spark\Features;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class BasicBehatScenario implements Context
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
        echo "Spark is installed!";
    }

    #[When('I run Behat')]
    public function iRunBehat(): void
    {
        echo "Behat is running";
    }

    #[Then('This Scenario success')]
    public function thisScenarioSuccess(): void
    {
        Assert::assertStringContainsString("succes", 'This Scenario success');
    }
}
