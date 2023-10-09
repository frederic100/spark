<?php

namespace Spark\Tests;

use PHPUnit\Framework\TestCase;
use Spark\Logger;

class LoggerTest extends TestCase
{
    private $logger;
    private $file;
    private $db;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'LoggerTest');
        $this->db = new \mysqli('localhost', 'user', 'password', 'test');
        $this->logger = new Logger($this->file, $this->db);
    }

    protected function tearDown(): void
    {
        unlink($this->file);
        $this->db->close();
    }

    public function testLogToFile()
    {
        $this->logger->logToFile('test message');
        $this->assertStringContainsString('test message', file_get_contents($this->file));
    }

    public function testLogToDatabase()
    {
        $this->logger->logToDatabase('test message');
        $result = $this->db->query('SELECT * FROM logs');
        $this->assertEquals(1, $result->num_rows);
        $row = $result->fetch_assoc();
        $this->assertEquals('test message', $row['message']);
    }

    public function testLogToFileWithTimestamp()
    {
        $this->logger->logToFile('test message');
        $logContent = file_get_contents($this->file);
        $this->assertStringContainsString(date('Y-m-d H:i:s'), $logContent);
        $this->assertStringContainsString('test message', $logContent);
    }

    public function testLogToDatabaseWithTimestamp()
    {
        $this->logger->logToDatabase('test message');
        $result = $this->db->query('SELECT * FROM logs');
        $this->assertEquals(1, $result->num_rows);
        $row = $result->fetch_assoc();
        $this->assertEquals(date('Y-m-d H:i:s'), $row['timestamp']);
        $this->assertEquals('test message', $row['message']);
    }

    public function testLogToFileMultipleMessages()
    {
        $this->logger->logToFile('test message 1');
        $this->logger->logToFile('test message 2');
        $logContent = file_get_contents($this->file);
        $this->assertStringContainsString('test message 1', $logContent);
        $this->assertStringContainsString('test message 2', $logContent);
    }

    public function testLogToDatabaseMultipleMessages()
    {
        $this->logger->logToDatabase('test message 1');
        $this->logger->logToDatabase('test message 2');
        $result = $this->db->query('SELECT * FROM logs');
        $this->assertEquals(2, $result->num_rows);
        $row1 = $result->fetch_assoc();
        $row2 = $result->fetch_assoc();
        $this->assertEquals(date('Y-m-d H:i:s'), $row1['timestamp']);
        $this->assertEquals('test message 1', $row1['message']);
        $this->assertEquals(date('Y-m-d H:i:s'), $row2['timestamp']);
        $this->assertEquals('test message 2', $row2['message']);
    }
}
