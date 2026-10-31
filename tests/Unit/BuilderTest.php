<?php

use Fwhy\Blast\Builder;
use Fwhy\Blast\Filesystem\FilesystemManager;
use Fwhy\Blast\Hook;

class TestHook implements Hook
{
    public const int PRIORITY = 10;
    public static bool $executed = false;

    public static function timing(): int
    {
        return self::AFTER_BUILD_PAGES;
    }

    public static function execute(Builder &$builder): void
    {
        self::$executed = true;
    }
}

beforeEach(function () {
    $this->originalCwd = getcwd() ?: '';
    $this->tempDir = sys_get_temp_dir() . '/blast_builder_test_' . uniqid();
    $this->fs = new FilesystemManager();
    $this->fs->ensureDirectoryExists($this->tempDir);
    chdir($this->tempDir);

    TestHook::$executed = false;
});

afterEach(function () {
    chdir($this->originalCwd);
    $this->fs->removeDirectory($this->tempDir);
});

test('load default and custom config', function () {
    $customConfig = <<<YAML
site:
  name: Custom Test Site
output: { dir: dist }
YAML;
    file_put_contents($this->tempDir . '/blastrc.yml', $customConfig);

    $builder = new Builder();
    $builder->loadConfig();

    expect($builder->config['site']['name'])->toBe('Custom Test Site')
        ->and($builder->config['output']['dir'])->toBe('dist')
        ->and($builder->config['assets']['dir'])->toBe('assets');
});

test('build pages and execute hook', function () {
    $builder = new Builder();
    $builder->loadConfig();
    $builder->createDirectories();

    file_put_contents($builder->dir->contents . '/index.md', "# Welcome\n\nHome page.");
    file_put_contents($builder->dir->contents . '/about.md', "# About Us\n\nAbout page.");

    // Manually register hook to test execution
    $builder->hooks[Hook::AFTER_BUILD_PAGES] = [TestHook::class];

    $builder->buildPages();

    expect($builder->pages)->toHaveKey('/index/')
        ->and($builder->pages)->toHaveKey('/about/')
        ->and($builder->pages['/index/']->title)->toBe('Welcome')
        ->and($builder->pages['/about/']->title)->toBe('About Us')
        ->and(TestHook::$executed)->toBeTrue();
});

test('render and output', function () {
    $builder = new Builder();
    $builder->loadConfig();
    $builder->createDirectories();

    file_put_contents($builder->dir->contents . '/index.md', "# Home\n\nContent.");
    $this->fs->ensureDirectoryExists($builder->dir->assets . '/images');
    file_put_contents($builder->dir->assets . '/images/logo.png', 'fake-image-bytes');

    $builder->buildPages();
    $builder->render();

    expect($builder->pages)->toHaveKey('404.html')
        ->and($builder->pages['/']->html)->not->toBeEmpty()
        ->and($builder->pages['404.html']->html)->not->toBeEmpty();

    $builder->output();

    expect(file_exists($builder->dir->output . '/index.html'))->toBeTrue()
        ->and(file_exists($builder->dir->output . '/404.html'))->toBeTrue()
        ->and(file_exists($builder->dir->output . '/images/logo.png'))->toBeTrue();
});
