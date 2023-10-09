<?php

namespace Spark\D;

use Spark\D\Provider\LoggerInterface;

interface LoggerFactoryInterface
{
    public function create():LoggerInterface;
}
