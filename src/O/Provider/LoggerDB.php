<?php

namespace Spark\O\Provider;

class LoggerDB extends LoggerAbstract
{
    private $db;

    private $stmt;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ecrire($message) {
        $timestamp = date('Y-m-d H:i:s');
        $this->stmt = $this->db->prepare("INSERT INTO logs (timestamp, message) VALUES (?, ?)");
        $this->stmt->bind_param("ss", $timestamp, $message);
        $this->stmt->execute();
    }

    public function fetch():void {
        if (isset($this->db)) {
            $this->db->fetch($this->stmt);
        }
    }
}
