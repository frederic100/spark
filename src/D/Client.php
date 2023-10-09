<?php

namespace Spark\D;

use Spark\FizzBuzz;

class Client
{
    public function __construct(private LoggerFactoryInterface $loggerFactory)
    {
    }

    public function run():void
    {
        $logger = $this->loggerFactory->create();
        $fizzBuzz = new FizzBuzz();
        for ($i = 1; $i <= 100; $i++) {
            $logger->ecrire($fizzBuzz->convert($i));
        }
    }
}