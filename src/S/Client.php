<?php

namespace Spark\S;

use Spark\FizzBuzz;
use Spark\S\Provider\LoggerDB;
use Spark\S\Provider\LoggerFile;

class Client
{
    public function __construct(private LoggerFile $loggerFile, private ?LoggerDB $loggerDB = null)
    {
    }

    public function run()
    {
        $fizzBuzz = new FizzBuzz();
        for ($i = 1; $i <= 100; $i++) {    
            if ($this->loggerDB !== null) {
                $this->loggerDB->ecrire($fizzBuzz->convert($i));
            } else {
                $this->loggerFile->ecrire($fizzBuzz->convert($i));
            }
        }
        if ($this->loggerDB !== null) {
            $this->loggerDB->fetch();
        }
    }
}
