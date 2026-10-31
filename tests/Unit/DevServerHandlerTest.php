<?php

use Fwhy\Blast\Builder;
use Fwhy\Blast\Directory;
use Fwhy\Blast\Filesystem\FilesystemManager;
use Fwhy\Blast\Models\Page;
use Fwhy\Blast\Server\DevServerHandler;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/blast_devserver_test_' . uniqid();
    $this->fs = new FilesystemManager();
    $this->fs->ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    $this->fs->removeDirectory($this->tempDir);
});

test('handles root page', function () {
    $builder = new Builder();
    $builder->pages['/'] = new Page('/', '/', html: '<h1>Home</h1>');
    $builder->pages['404.html'] = new Page('404.html', '404.html', html: '<h1>404</h1>');

    $handler = new DevServerHandler($builder);
    $response = $handler->handle('/');

    expect($response->statusCode)->toBe(200)
        ->and($response->headers['Content-Type'])->toBe('text/html')
        ->and($response->body)->toBe('<h1>Home</h1>');
});

test('handles sub page', function () {
    $builder = new Builder();
    $builder->pages['/posts/first/'] = new Page('/posts/first/', '/posts/first/', html: '<h1>First Post</h1>');

    $handler = new DevServerHandler($builder);
    $response = $handler->handle('/posts/first/');

    expect($response->statusCode)->toBe(200)
        ->and($response->headers['Content-Type'])->toBe('text/html')
        ->and($response->body)->toBe('<h1>First Post</h1>');
});

test('handles 404', function () {
    $builder = new Builder();
    $builder->pages['404.html'] = new Page('404.html', '404.html', html: '<h1>Not Found</h1>');

    $handler = new DevServerHandler($builder);
    $response = $handler->handle('/non-existent');

    expect($response->statusCode)->toBe(404)
        ->and($response->body)->toBe('<h1>Not Found</h1>');
});

test('serves static assets', function () {
    $assetsDir = $this->tempDir . '/assets';
    $this->fs->ensureDirectoryExists($assetsDir . '/css');
    file_put_contents($assetsDir . '/css/style.css', 'body { color: red; }');

    $builder = new Builder();
    $builder->loadConfig();
    $builder->dir = new Directory(
        base: $this->tempDir,
        assets: 'assets',
        cache: 'cache',
        contents: 'contents',
        hooks: 'hooks',
        output: 'public',
        theme: 'theme'
    );

    $handler = new DevServerHandler($builder);
    $response = $handler->handle('/css/style.css');

    expect($response->statusCode)->toBe(200)
        ->and($response->headers['Content-Type'])->toBe('text/css')
        ->and($response->body)->toBe('body { color: red; }');
});

test('prevents path traversal', function () {
    $assetsDir = $this->tempDir . '/assets';
    $this->fs->ensureDirectoryExists($assetsDir);
    file_put_contents($this->tempDir . '/secret.txt', 'secret data');

    $builder = new Builder();
    $builder->loadConfig();
    $builder->dir = new Directory(
        base: $this->tempDir,
        assets: 'assets',
        cache: 'cache',
        contents: 'contents',
        hooks: 'hooks',
        output: 'public',
        theme: 'theme'
    );
    $builder->pages['404.html'] = new Page('404.html', '404.html', html: '<h1>404</h1>');

    $handler = new DevServerHandler($builder);

    // Attempt directory traversal
    $response = $handler->handle('/../secret.txt');

    expect($response->statusCode)->toBe(404)
        ->and($response->body)->not->toContain('secret data');
});
