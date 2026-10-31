<?php

use Fwhy\Blast\Console\Application;

test('help option', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['blast', '--help']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0)
        ->and((string)$output)->toContain('Usage:')
        ->and((string)$output)->toContain('build')
        ->and((string)$output)->toContain('dev');
});

test('version option', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['blast', '--version']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0)
        ->and((string)$output)->toContain('Blast');
});

test('unknown command', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['blast', 'invalid-command']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(1);
});

test('build help', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['blast', 'build', '--help']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0)
        ->and((string)$output)->toContain('Usage: blast build');
});

test('dev help', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['blast', 'dev', '--help']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0)
        ->and((string)$output)->toContain('Usage: blast dev')
        ->and((string)$output)->toContain('-p, --port');
});
