<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function testBasicTest(): void
    {
        $haystack = "Is there 'needle' inside this integration string ?";
        $this->assertStringContainsString("needle", $haystack);
    }
}
