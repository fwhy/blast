<?php

use Fwhy\Blast\Filesystem\FilesystemManager;
use Fwhy\Blast\Models\Page;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/blast_page_test_' . uniqid();
    $this->fs = new FilesystemManager();
    $this->fs->ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    $this->fs->removeDirectory($this->tempDir);
});

test('default constructor', function () {
    $page = new Page();
    expect($page->rawSlug)->toBe('/')
        ->and($page->slug)->toBe('/');
});

test('constructor with custom slug', function () {
    $page = new Page('about/index');
    expect($page->rawSlug)->toBe('/about/index')
        ->and($page->slug)->toBe('/about/index');

    $page2 = new Page('/日本語/ページ');
    expect($page2->rawSlug)->toBe('/日本語/ページ')
        ->and($page2->slug)->toBe('/' . rawurlencode('日本語') . '/' . rawurlencode('ページ'));
});

test('build standard markdown', function () {
    $mdPath = $this->tempDir . '/posts/first-post.md';
    $this->fs->ensureDirectoryExists(dirname($mdPath));
    $markdown = "# Hello World\n\nThis is a sample post.";
    file_put_contents($mdPath, $markdown);

    $page = Page::build($mdPath, $this->tempDir);

    expect($page->title)->toBe('Hello World')
        ->and($page->fullPath)->toBe($mdPath)
        ->and($page->relPath)->toBe('/posts/first-post.md')
        ->and($page->rawSlug)->toBe('/posts/first-post/')
        ->and($page->slug)->toBe('/posts/first-post/')
        ->and($page->content)->toContain('<h1>Hello World</h1>')
        ->and($page->content)->toContain('<p>This is a sample post.</p>')
        ->and($page->createDate)->not->toBeNull()
        ->and($page->updateDate)->not->toBeNull();
});

test('build readme markdown', function () {
    $mdPath = $this->tempDir . '/docs/readme.md';
    $this->fs->ensureDirectoryExists(dirname($mdPath));
    $markdown = "# Documentation Guide\n\nRead this guide.";
    file_put_contents($mdPath, $markdown);

    $page = Page::build($mdPath, $this->tempDir);

    expect($page->title)->toBe('Documentation Guide')
        ->and($page->rawSlug)->toBe('/docs/')
        ->and($page->slug)->toBe('/docs/');
});

test('build with front matter', function () {
    $mdPath = $this->tempDir . '/guide.md';
    $timestamp = 1700000000;
    $markdown = <<<MARKDOWN
---
title: Custom Title
slug: /custom-slug/
createDate: {$timestamp}
updateDate: {$timestamp}
author: Blast User
---
# Ignored Header Title

Some content here.
MARKDOWN;
    file_put_contents($mdPath, $markdown);

    $page = Page::build($mdPath, $this->tempDir);

    expect($page->title)->toBe('Custom Title')
        ->and($page->rawSlug)->toBe('/custom-slug/')
        ->and($page->slug)->toBe('/custom-slug/')
        ->and($page->meta)->toHaveKey('author')
        ->and($page->meta['author'])->toBe('Blast User')
        ->and((int)$page->createDate?->format('U'))->toBe($timestamp)
        ->and((int)$page->updateDate?->format('U'))->toBe($timestamp);
});
