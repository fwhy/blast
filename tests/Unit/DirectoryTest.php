<?php

use Fwhy\Blast\Directory;
use Fwhy\Blast\Filesystem\FilesystemManager;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/blast_test_' . uniqid();
    $this->fs = new FilesystemManager();
    $this->fs->ensureDirectoryExists($this->tempDir);

    $this->directory = new Directory(
        $this->tempDir,
        'assets',
        '.cache',
        'contents',
        'hooks',
        '.output',
        'theme'
    );
});

afterEach(function () {
    $this->fs->removeDirectory($this->tempDir);
});

test('paths resolution', function () {
    expect($this->directory->base)->toBe($this->tempDir)
        ->and($this->directory->assets)->toBe($this->tempDir . '/assets')
        ->and($this->directory->cache)->toBe($this->tempDir . '/.cache')
        ->and($this->directory->contents)->toBe($this->tempDir . '/contents')
        ->and($this->directory->hooks)->toBe($this->tempDir . '/hooks')
        ->and($this->directory->output)->toBe($this->tempDir . '/.output')
        ->and($this->directory->theme)->toEndWith('/src/theme')
        ->and($this->directory->relAssets)->toBe('assets')
        ->and($this->directory->relCache)->toBe('.cache')
        ->and($this->directory->relContents)->toBe('contents')
        ->and($this->directory->relHooks)->toBe('hooks')
        ->and($this->directory->relOutput)->toBe('.output')
        ->and($this->directory->relTheme)->toBe('theme');
});

test('theme fallback and custom theme', function () {
    $customThemeDir = $this->tempDir . '/theme';
    $this->fs->ensureDirectoryExists($customThemeDir);
    file_put_contents($customThemeDir . '/page.blade.php', '<html></html>');

    expect($this->directory->theme)->toBe($customThemeDir);
});

test('create directories', function () {
    $this->directory->createDirectories();

    expect(is_dir($this->directory->assets))->toBeTrue()
        ->and(is_dir($this->directory->cache . '/theme'))->toBeTrue()
        ->and(is_dir($this->directory->contents))->toBeTrue()
        ->and(is_dir($this->directory->hooks))->toBeTrue()
        ->and(is_dir($this->directory->output))->toBeTrue();
});

test('get files', function () {
    $this->directory->createDirectories();

    file_put_contents($this->directory->contents . '/file1.md', '# File 1');
    $this->fs->ensureDirectoryExists($this->directory->contents . '/sub');
    file_put_contents($this->directory->contents . '/sub/file2.md', '# File 2');

    $files = $this->directory->getFiles('contents');

    expect($files)->toHaveCount(2)
        ->and($files)->toContain($this->directory->contents . '/file1.md')
        ->and($files)->toContain($this->directory->contents . '/sub/file2.md');
});

test('clear output', function () {
    $this->directory->createDirectories();

    file_put_contents($this->directory->output . '/index.html', '<h1>Hello</h1>');
    $this->fs->ensureDirectoryExists($this->directory->output . '/nested');
    file_put_contents($this->directory->output . '/nested/about.html', '<h1>About</h1>');

    expect(file_exists($this->directory->output . '/index.html'))->toBeTrue()
        ->and(file_exists($this->directory->output . '/nested/about.html'))->toBeTrue();

    $this->directory->clearOutput();

    expect(is_dir($this->directory->output))->toBeTrue()
        ->and(file_exists($this->directory->output . '/index.html'))->toBeFalse()
        ->and(file_exists($this->directory->output . '/nested/about.html'))->toBeFalse()
        ->and(is_dir($this->directory->output . '/nested'))->toBeFalse();
});

test('copy assets', function () {
    $this->directory->createDirectories();

    $this->fs->ensureDirectoryExists($this->directory->assets . '/css');
    file_put_contents($this->directory->assets . '/css/style.css', 'body { color: red; }');
    file_put_contents($this->directory->assets . '/favicon.ico', 'icon');

    $this->directory->copyAssets();

    expect(file_exists($this->directory->output . '/css/style.css'))->toBeTrue()
        ->and(file_get_contents($this->directory->output . '/css/style.css'))->toBe('body { color: red; }')
        ->and(file_exists($this->directory->output . '/favicon.ico'))->toBeTrue()
        ->and(file_get_contents($this->directory->output . '/favicon.ico'))->toBe('icon');
});
