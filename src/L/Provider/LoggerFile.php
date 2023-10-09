<?php

namespace Spark\L\Provider;

class LoggerFile implements LoggerInterface {
    private $file;

    public function __construct($file) {
        $this->file = $file;
    }

    public function ecrire($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "$timestamp: $message\n";
        file_put_contents($this->file, $logMessage, FILE_APPEND);
    }
}