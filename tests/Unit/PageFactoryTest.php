<?php

use Fwhy\Blast\Filesystem\FilesystemManager;
use Fwhy\Blast\Parser\PageFactory;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/blast_page_factory_test_' . uniqid();
    $this->fs = new FilesystemManager();
    $this->fs->ensureDirectoryExists($this->tempDir);
    $this->factory = new PageFactory();
});

afterEach(function () {
    $this->fs->removeDirectory($this->tempDir);
});

test('create from file standard', function () {
    $mdPath = $this->tempDir . '/posts/intro.md';
    $this->fs->ensureDirectoryExists(dirname($mdPath));
    file_put_contents($mdPath, "# Introduction\n\nWelcome to our website.");

    $page = $this->factory->createFromFile($mdPath, $this->tempDir);

    expect($page->title)->toBe('Introduction')
        ->and($page->rawSlug)->toBe('/posts/intro/')
        ->and($page->slug)->toBe('/posts/intro/')
        ->and($page->fullPath)->toBe($mdPath)
        ->and($page->relPath)->toBe('/posts/intro.md')
        ->and($page->content)->toContain('<h1>Introduction</h1>')
        ->and($page->createDate)->not->toBeNull()
        ->and($page->updateDate)->not->toBeNull();
});

test('create from content with front matter', function () {
    $raw = <<<MD
---
title: Custom Title
slug: /custom/path/
createDate: 2024-03-15
updateDate: 2024-03-20
author: John
---
# Original Header Title

Post body.
MD;

    $page = $this->factory->createFromContent($raw);

    expect($page->title)->toBe('Custom Title')
        ->and($page->rawSlug)->toBe('/custom/path/')
        ->and($page->slug)->toBe('/custom/path/')
        ->and($page->createDate?->format('Y-m-d'))->toBe('2024-03-15')
        ->and($page->updateDate?->format('Y-m-d'))->toBe('2024-03-20')
        ->and($page->meta['author'])->toBe('John');
});

test('create from file readme slug', function () {
    $mdPath = $this->tempDir . '/docs/readme.md';
    $this->fs->ensureDirectoryExists(dirname($mdPath));
    file_put_contents($mdPath, "# Readme Page\n\nDocs readme.");

    $page = $this->factory->createFromFile($mdPath, $this->tempDir);

    expect($page->rawSlug)->toBe('/docs/')
        ->and($page->slug)->toBe('/docs/');
});
