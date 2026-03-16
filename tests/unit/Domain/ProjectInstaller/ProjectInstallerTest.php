<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ProjectInstaller;

use PHPUnit\Framework\TestCase;
use Spark\Domain\ProjectInstaller\ProjectInstaller;

final class ProjectInstallerTest extends TestCase
{
    public function test_should_validate_project_name_is_not_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project name cannot be empty');

        new ProjectInstaller('');
    }

    public function test_should_validate_project_name_contains_only_valid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project name contains invalid characters');

        new ProjectInstaller('invalid name with spaces');
    }

    public function test_should_accept_valid_project_name(): void
    {
        $installer = new ProjectInstaller('my-project');

        $this->assertSame('my-project', $installer->getProjectName());
    }

    public function test_should_generate_namespace_from_project_name(): void
    {
        $installer = new ProjectInstaller('my-project');

        $this->assertSame('MyProject', $installer->generateNamespace());
    }

    public function test_should_generate_namespace_with_multiple_words(): void
    {
        $installer = new ProjectInstaller('my-awesome-project');

        $this->assertSame('MyAwesomeProject', $installer->generateNamespace());
    }

    public function test_should_detect_spark_as_source_project(): void
    {
        $installer = new ProjectInstaller('spark');

        $this->assertTrue($installer->isSourceProjectSpark());
    }

    public function test_should_not_detect_non_spark_project_as_source(): void
    {
        $installer = new ProjectInstaller('my-project');

        $this->assertFalse($installer->isSourceProjectSpark());
    }

    public function test_should_validate_port_is_positive_integer(): void
    {
        $installer = new ProjectInstaller('my-project');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Port must be a positive integer');

        $installer->validatePort(-1);
    }

    public function test_should_validate_port_is_within_valid_range(): void
    {
        $installer = new ProjectInstaller('my-project');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Port must be between 1024 and 65535');

        $installer->validatePort(100);
    }

    public function test_should_accept_valid_port(): void
    {
        $installer = new ProjectInstaller('my-project');

        $installer->validatePort(35080);

        $this->assertTrue(true); // No exception thrown
    }
}

