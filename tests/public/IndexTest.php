<?php

namespace Tests\Spark;

use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testEchoWelcome(): void
    {
        $this->expectOutputString('Welcome to Spark!');
        require getcwd() . '/src/public/index.php';
    }
}
