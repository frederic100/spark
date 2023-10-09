<?php

namespace Spark;

class Logger {
    private $file;
    private $db;
    private $stmt;

    public function __construct($file, $db) {
        $this->file = $file;
        $this->db = $db;
    }

    public function logToFile($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "$timestamp: $message\n";
        file_put_contents($this->file, $logMessage, FILE_APPEND);
    }

    public function logToDatabase($message) {
        $timestamp = date('Y-m-d H:i:s');
        $this->stmt = $this->db->prepare("INSERT INTO logs (timestamp, message) VALUES (?, ?)");
        $this->stmt->bind_param("ss", $timestamp, $message);
        $this->stmt->execute();
    }

    public function fetch() {
        if (isset($this->db)) {
            $this->db->fetch($this->stmt);
        }
    }
}
