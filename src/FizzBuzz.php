<?php

namespace Spark;

class FizzBuzz
{
    public const FIZZ = 'Fizz';
    public const BUZZ = 'Buzz';
    public const FIZZBUZZ = 'FizzBuzz';

    public const NUMBER_FIZZ = 3;
    public const NUMBER_BUZZ = 5;

    public function convert(int $param): string
    {
        if ($param % self::NUMBER_FIZZ === 0 && $param % self::NUMBER_BUZZ === 0) {
            return self::FIZZBUZZ;
        }
        if ($param % self::NUMBER_FIZZ === 0) {
            return self::FIZZ;
        }
        if ($param % self::NUMBER_BUZZ === 0) {
            return self::BUZZ;
        }

        return (string)$param;
    }
}
