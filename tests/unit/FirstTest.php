<?php

namespace Tests\Unit\Spark;

use PHPUnit\Framework\TestCase;

class FirstTest extends TestCase
{
    public function testBasicTest(): void
    {
        $haystack = "Is there 'needle' inside this string ?";
        $this->assertStringContainsString("needle", $haystack);
    }
}
