<?php

namespace Spark\D;

use Spark\D\Provider\LoggerDB;
use Spark\D\Provider\LoggerInterface;

class LoggerDbFactory extends LoggerFactoryInterface
{
    public function create():LoggerInterface
    {
        $db = "//maBaseDeDonnées";
        return new LoggerDB($db);
    }
}