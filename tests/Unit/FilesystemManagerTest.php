<?php

use Fwhy\Blast\Exception\FileException;
use Fwhy\Blast\Filesystem\FilesystemManager;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/blast_fs_test_' . uniqid();
    $this->filesystem = new FilesystemManager();
    $this->filesystem->ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    $this->filesystem->removeDirectory($this->tempDir);
});

test('ensure directory exists', function () {
    $target = $this->tempDir . '/nested/dir/path';
    expect(is_dir($target))->toBeFalse();

    $this->filesystem->ensureDirectoryExists($target);
    expect(is_dir($target))->toBeTrue();

    // Call again should not fail
    $this->filesystem->ensureDirectoryExists($target);
    expect(is_dir($target))->toBeTrue();
});

test('write and read file', function () {
    $filePath = $this->tempDir . '/sub/file.txt';
    $content = 'Hello Filesystem!';

    $this->filesystem->writeFile($filePath, $content);
    expect(file_exists($filePath))->toBeTrue()
        ->and($this->filesystem->readFile($filePath))->toBe($content);
});

test('read non existent file throws exception', function () {
    expect(fn () => $this->filesystem->readFile($this->tempDir . '/non-existent.txt'))
        ->toThrow(FileException::class);
});

test('copy directory', function () {
    $src = $this->tempDir . '/src';
    $dst = $this->tempDir . '/dst';

    $this->filesystem->ensureDirectoryExists($src . '/sub');
    $this->filesystem->writeFile($src . '/file1.txt', 'file 1');
    $this->filesystem->writeFile($src . '/sub/file2.txt', 'file 2');

    $this->filesystem->copyDirectory($src, $dst);

    expect(file_exists($dst . '/file1.txt'))->toBeTrue()
        ->and(file_exists($dst . '/sub/file2.txt'))->toBeTrue()
        ->and(file_get_contents($dst . '/file1.txt'))->toBe('file 1')
        ->and(file_get_contents($dst . '/sub/file2.txt'))->toBe('file 2');
});

test('clear directory', function () {
    $target = $this->tempDir . '/clear_target';
    $this->filesystem->ensureDirectoryExists($target . '/nested');
    $this->filesystem->writeFile($target . '/nested/file.txt', 'test');
    $this->filesystem->writeFile($target . '/root.txt', 'test');

    $this->filesystem->clearDirectory($target);

    expect(is_dir($target))->toBeTrue()
        ->and(file_exists($target . '/root.txt'))->toBeFalse()
        ->and(is_dir($target . '/nested'))->toBeFalse();
});

test('get files', function () {
    $dir = $this->tempDir . '/files_list';
    $this->filesystem->writeFile($dir . '/a.txt', 'a');
    $this->filesystem->writeFile($dir . '/sub/b.txt', 'b');

    $files = $this->filesystem->getFiles($dir);

    expect($files)->toHaveCount(2)
        ->and($files)->toContain($dir . '/a.txt')
        ->and($files)->toContain($dir . '/sub/b.txt');
});
