<?php

namespace Spark\S\Provider;

class LoggerDB {
    private $db;
    private $stmt;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ecrire($message) {
        $timestamp = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("INSERT INTO logs (timestamp, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $timestamp, $message);
        $stmt->execute();
    }

    public function fetch():void {
        if (isset($this->db)) {
            $this->db->fetch($this->stmt);
        }
    }
}
