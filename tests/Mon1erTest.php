<?php

namespace Tests\Spark;

use PHPUnit\Framework\TestCase;

class Mon1erTest extends TestCase
{
    public function testLancerAssertionEgaleAFizzBuzz(): void
    {
        $sut = "FizzBuzz";
        $this->assertEquals("FizzBuzz", $sut);
    }
}
