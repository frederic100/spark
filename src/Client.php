<?php

namespace Spark;

class Client
{
    private $logger;

    public function __construct($file, $db, private bool $debugMode = false)
    {
        $this->logger = new Logger($file, $db);
    }

    public function run()
    {
        $fizzBuzz = new FizzBuzz();
        for ($i = 1; $i <= 100; $i++) {
            if ($this->debugMode) {
                $this->logger->logToFile($fizzBuzz->convert($i));
            } else {
                $this->logger->logToDatabase($fizzBuzz->convert($i));
            }
        }
    }
}
