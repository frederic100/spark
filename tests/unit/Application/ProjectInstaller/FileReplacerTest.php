<?php

declare(strict_types=1);

namespace Tests\Unit\Application\ProjectInstaller;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Spark\Application\ProjectInstaller\FileReplacer;
use Spark\Domain\ProjectInstaller\ProjectInstaller;

final class FileReplacerTest extends TestCase
{
    private vfsStreamDirectory $root;
    private FileReplacer $fileReplacer;
    private ProjectInstaller $projectInstaller;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('project');
        $this->projectInstaller = new ProjectInstaller('my-project');
        $this->fileReplacer = new FileReplacer($this->projectInstaller);
    }

    public function test_should_replace_string_in_file(): void
    {
        $file = vfsStream::newFile('test.txt')
            ->withContent('Hello spark world')
            ->at($this->root);

        $this->fileReplacer->replaceInFile(
            $file->url(),
            'spark',
            'my-project'
        );

        $this->assertSame('Hello my-project world', $file->getContent());
    }

    public function test_should_replace_multiple_occurrences_in_file(): void
    {
        $file = vfsStream::newFile('test.txt')
            ->withContent('spark is great, spark is awesome')
            ->at($this->root);

        $this->fileReplacer->replaceInFile(
            $file->url(),
            'spark',
            'my-project'
        );

        $this->assertSame('my-project is great, my-project is awesome', $file->getContent());
    }

    public function test_should_replace_namespace_in_php_file(): void
    {
        $file = vfsStream::newFile('Test.php')
            ->withContent("<?php\n\nnamespace Spark\\Domain;\n\nclass Test {}")
            ->at($this->root);

        $this->fileReplacer->replaceNamespaceInPhpFiles(
            $this->root->url(),
            'Spark',
            'MyProject'
        );

        $this->assertStringContainsString('namespace MyProject\\Domain;', $file->getContent());
    }

    public function test_should_replace_namespace_in_multiple_php_files(): void
    {
        $file1 = vfsStream::newFile('Test1.php')
            ->withContent("<?php\n\nnamespace Spark\\Domain;\n\nclass Test1 {}")
            ->at($this->root);
        $file2 = vfsStream::newFile('Test2.php')
            ->withContent("<?php\n\nnamespace Spark\\Application;\n\nclass Test2 {}")
            ->at($this->root);

        $this->fileReplacer->replaceNamespaceInPhpFiles(
            $this->root->url(),
            'Spark',
            'MyProject'
        );

        $this->assertStringContainsString('namespace MyProject\\Domain;', $file1->getContent());
        $this->assertStringContainsString('namespace MyProject\\Application;', $file2->getContent());
    }

    public function test_should_update_composer_json_name(): void
    {
        $composerJson = vfsStream::newFile('composer.json')
            ->withContent('{"name": "logipro/spark", "type": "app"}')
            ->at($this->root);

        $this->fileReplacer->updateComposerJson(
            $composerJson->url(),
            'logipro/my-project'
        );

        $content = json_decode($composerJson->getContent(), true);
        $this->assertSame('logipro/my-project', $content['name']);
    }

    public function test_should_update_composer_json_namespace(): void
    {
        $composerJson = vfsStream::newFile('composer.json')
            ->withContent('{"autoload": {"psr-4": {"Spark\\\\": "src/"}}}')
            ->at($this->root);

        $this->fileReplacer->updateComposerJsonNamespace(
            $composerJson->url(),
            'MyProject'
        );

        $content = json_decode($composerJson->getContent(), true);
        $this->assertArrayHasKey('MyProject\\', $content['autoload']['psr-4']);
        $this->assertSame('src/', $content['autoload']['psr-4']['MyProject\\']);
    }

    public function test_should_throw_exception_when_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->fileReplacer->replaceInFile(
            '/non/existent/file.txt',
            'spark',
            'my-project'
        );
    }
}

