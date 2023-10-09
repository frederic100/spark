<?php

namespace Spark\O;

use Spark\FizzBuzz;
use Spark\O\Provider\LoggerAbstract;
use Spark\O\Provider\LoggerDB;

class Client
{
    private ?LoggerAbstract $logger = null;

    public function __construct(LoggerAbstract $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        $fizzBuzz = new FizzBuzz();
        for ($i = 1; $i <= 100; $i++) {
                $this->logger->ecrire($fizzBuzz->convert($i));
        }
        if ($this->logger instanceof LoggerDB) {
            $this->logger->fetch();
        }
    }
}