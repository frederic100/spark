<?php

namespace Spark\Tests;

use PHPUnit\Framework\TestCase;
use Spark\FizzBuzz;

class FizzBuzzTest extends TestCase
{
    public function testFizzBuzzUnit(): void
    {
        $fizzBuzz = new FizzBuzz();
        $testResult = $fizzBuzz->convert(1);
        $this->assertEquals('1', $testResult);
    }
    public function testFizzBuzzUnit2(): void
    {
        $fizzBuzz = new FizzBuzz();
        $testResult = $fizzBuzz->convert(2);
        $this->assertEquals('2', $testResult);
    }
    public function testFizz(): void
    {
        $fizzBuzz = new FizzBuzz();
        $testResult = $fizzBuzz->convert(3);
        $this->assertEquals(FizzBuzz::FIZZ, $testResult);
    }
    public function testFizz2(): void
    {
        $fizzBuzz = new FizzBuzz();
        $testResult = $fizzBuzz->convert(6);
        $this->assertEquals(FizzBuzz::FIZZ, $testResult);
    }
    public function testBuzz(): void
    {
        $fizzBuzz = new FizzBuzz();
        $testResult = $fizzBuzz->convert(5);
        $this->assertEquals(FizzBuzz::BUZZ, $testResult);
    }

    public function testFizzBuzz(): void
    {
        $fizzBuzz = new FizzBuzz();
        $testResult = $fizzBuzz->convert(15);
        $this->assertEquals(FizzBuzz::FIZZBUZZ, $testResult);
    }
}
