<?php

use eftec\bladeone\BladeOne;
use Fwhy\Blast\Filesystem\FilesystemManager;
use Fwhy\Blast\Models\Page;
use Fwhy\Blast\Renderer\BladeRenderer;

beforeEach(function () {
    $this->templateDir = sys_get_temp_dir() . '/blast_test_theme_' . uniqid();
    $this->cacheDir = sys_get_temp_dir() . '/blast_test_cache_' . uniqid();
    $this->fs = new FilesystemManager();

    $this->fs->ensureDirectoryExists($this->templateDir);
    $this->fs->ensureDirectoryExists($this->cacheDir);
});

afterEach(function () {
    $this->fs->removeDirectory($this->templateDir);
    $this->fs->removeDirectory($this->cacheDir);
});

test('render page', function () {
    file_put_contents($this->templateDir . '/page.blade.php', '<h1>{{ $page->title }}</h1><div>{!! $page->content !!}</div>');

    $renderer = new BladeRenderer($this->templateDir, $this->cacheDir, BladeOne::MODE_DEBUG);

    $page = new Page(
        rawSlug: '/test',
        slug: '/test',
        title: 'Test Title',
        content: '<p>Test Body</p>'
    );

    $html = $renderer->renderPage($page, ['/test' => $page], ['site' => ['name' => 'TestSite']]);

    expect($html)->toContain('<h1>Test Title</h1>')
        ->and($html)->toContain('<div><p>Test Body</p></div>');
});

test('render custom view', function () {
    file_put_contents($this->templateDir . '/custom.blade.php', '<p>Hello, {{ $name }}!</p>');

    $renderer = new BladeRenderer($this->templateDir, $this->cacheDir, BladeOne::MODE_DEBUG);

    $html = $renderer->render('custom', ['name' => 'Blast']);

    expect(trim($html))->toBe('<p>Hello, Blast!</p>');
});

test('get blade instance', function () {
    $renderer = new BladeRenderer($this->templateDir, $this->cacheDir, BladeOne::MODE_DEBUG);
    expect($renderer->getBlade())->toBeInstanceOf(BladeOne::class);
});
