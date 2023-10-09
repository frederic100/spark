<?php

namespace Spark\L;

use Spark\L\Provider\LoggerDB;

$db = "//maBaseDeDonnées";
$logger = new LoggerDB($db);
$client = new ClientLiskovOk($logger);
