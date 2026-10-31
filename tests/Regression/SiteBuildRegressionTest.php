<?php

use Fwhy\Blast\Builder;
use Fwhy\Blast\Filesystem\FilesystemManager;

beforeEach(function () {
    $this->originalCwd = getcwd() ?: '';
    $this->tempDir = sys_get_temp_dir() . '/blast_regression_test_' . uniqid();
    $this->fs = new FilesystemManager();
    $this->fs->ensureDirectoryExists($this->tempDir);
    chdir($this->tempDir);
});

afterEach(function () {
    chdir($this->originalCwd);
    $this->fs->removeDirectory($this->tempDir);
});

test('full site build workflow and output integrity', function () {
    // 1. Setup config
    $config = <<<YAML
site:
  name: Regression Blast Site
  root: /
assets: { dir: assets }
cache: { dir: .cache }
contents: { dir: contents }
hooks: { dir: hooks }
output: { dir: .output }
theme: { dir: theme }
YAML;
    file_put_contents($this->tempDir . '/blastrc.yml', $config);

    // 2. Setup assets
    $this->fs->ensureDirectoryExists($this->tempDir . '/assets/css');
    $this->fs->ensureDirectoryExists($this->tempDir . '/assets/js');
    file_put_contents($this->tempDir . '/assets/css/main.css', '/* test css */');
    file_put_contents($this->tempDir . '/assets/js/app.js', '// test js');

    // 3. Setup contents with hierarchical structure
    $this->fs->ensureDirectoryExists($this->tempDir . '/contents/posts');
    $this->fs->ensureDirectoryExists($this->tempDir . '/contents/docs/guide');

    // Home (index.md)
    file_put_contents($this->tempDir . '/contents/index.md', "# Welcome to Regression Blast\n\nThis is the root page.");

    // Post 1
    $post1 = <<<MARKDOWN
---
title: First Regression Post
createDate: 1704067200
---
# First Post Header

Content of post 1 with **bold** text.
MARKDOWN;
    file_put_contents($this->tempDir . '/contents/posts/first.md', $post1);

    // Nested Doc Guide (readme.md)
    $docGuide = <<<MARKDOWN
---
title: Guide Overview
slug: /docs/guide/
---
# Guide Overview Header

This is the guide overview.
MARKDOWN;
    file_put_contents($this->tempDir . '/contents/docs/guide/readme.md', $docGuide);

    // 4. Run Blast build lifecycle
    $builder = new Builder();
    $builder->loadConfig();
    $builder->createDirectories();
    $builder->loadHooks();
    $builder->buildPages();
    $builder->render();
    $builder->output();

    // 5. Verify output files existence
    $outputDir = $this->tempDir . '/.output';
    expect(file_exists($outputDir . '/index.html'))->toBeTrue()
        ->and(file_exists($outputDir . '/posts/first/index.html'))->toBeTrue()
        ->and(file_exists($outputDir . '/docs/guide/index.html'))->toBeTrue()
        ->and(file_exists($outputDir . '/404.html'))->toBeTrue()
        ->and(file_exists($outputDir . '/css/main.css'))->toBeTrue()
        ->and(file_exists($outputDir . '/js/app.js'))->toBeTrue();

    // 6. Verify HTML content integrity
    $homeHtml = file_get_contents($outputDir . '/index.html');
    expect($homeHtml)->toBeString()
        ->and($homeHtml)->toContain('<title>Index | Regression Blast Site</title>')
        ->and($homeHtml)->toContain('Regression Blast Site')
        ->and($homeHtml)->toContain('Welcome to Regression Blast');

    $postHtml = file_get_contents($outputDir . '/posts/first/index.html');
    expect($postHtml)->toBeString()
        ->and($postHtml)->toContain('<title>First Regression Post | Regression Blast Site</title>')
        ->and($postHtml)->toContain('<strong>bold</strong>');

    $guideHtml = file_get_contents($outputDir . '/docs/guide/index.html');
    expect($guideHtml)->toBeString()
        ->and($guideHtml)->toContain('<title>Guide Overview | Regression Blast Site</title>')
        ->and($guideHtml)->toContain('This is the guide overview.');

    $notFoundHtml = file_get_contents($outputDir . '/404.html');
    expect($notFoundHtml)->toBeString()
        ->and($notFoundHtml)->toContain('404 Not Found');
});
