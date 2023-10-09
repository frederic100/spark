<?php

namespace Spark\O;

use Spark\O\Provider\LoggerDB;

$db = "//maBaseDeDonnées";
$logger = new LoggerDB($db);
$client = new Client($logger);
