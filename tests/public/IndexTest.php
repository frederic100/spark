<?php

declare(strict_types=1);

namespace Tests\Spark;

use PHPUnit\Framework\TestCase;
use Spark\Domain\Shared\BaseDir;

final class IndexTest extends TestCase
{
    public function test_echo_welcome(): void
    {
        $this->expectOutputString('Welcome to Spark!');
        require BaseDir::getRootPath('src/public/index.php');
    }
}
