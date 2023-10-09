<?php

namespace Spark\S;

use Spark\S\Provider\LoggerDB;
use Spark\S\Provider\LoggerFile;

$file = "//monFichierDeLog";
$loggerFile = new LoggerFile($file);
$db = "//maBaseDeDonnées";
$loggerDb = new LoggerDB($db);

$client = new Client($loggerFile, $loggerDb);
