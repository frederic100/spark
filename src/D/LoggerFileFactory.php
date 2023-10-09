<?php

namespace Spark\D;

use Spark\D\Provider\LoggerFile;
use Spark\D\Provider\LoggerInterface;

class LoggerFileFactory extends LoggerFactoryInterface
{
    public function create():LoggerInterface
    {
        $file = "//monFichierDeLog";
        return new LoggerFile($file);
    }
}