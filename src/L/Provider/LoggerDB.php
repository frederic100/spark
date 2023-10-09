<?php

namespace Spark\L\Provider;

class LoggerDB implements LoggerInterface
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

    public function fetch() {
        $result = $this->stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }
}
