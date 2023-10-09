<?php

namespace Spark\L;

use Spark\FizzBuzz;
use Spark\L\Provider\LoggerInterface;

class ClientLiskovOk
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function run()
    {
        $fizzBuzz = new FizzBuzz();
        for ($i = 1; $i <= 100; $i++) {
            $this->logger->ecrire($fizzBuzz->convert($i));
        }
    }
}