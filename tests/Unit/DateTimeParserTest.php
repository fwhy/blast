<?php

use Fwhy\Blast\Parser\DateTimeParser;

beforeEach(function () {
    $this->parser = new DateTimeParser();
});

test('parse null and empty', function () {
    expect($this->parser->parse(null))->toBeNull()
        ->and($this->parser->parse(''))->toBeNull();
});

test('parse DateTime objects', function () {
    $dt = new \DateTime('2024-01-15 10:00:00');
    expect($this->parser->parse($dt))->toBe($dt);

    $dti = new \DateTimeImmutable('2024-01-15 10:00:00');
    $parsed = $this->parser->parse($dti);
    expect($parsed)->toBeInstanceOf(\DateTime::class)
        ->and($parsed->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:00:00');
});

test('parse unix timestamp', function () {
    $timestamp = 1700000000;
    $dt = $this->parser->parse($timestamp);
    expect($dt)->not->toBeNull()
        ->and((int)$dt->format('U'))->toBe($timestamp);

    $dtStr = $this->parser->parse((string)$timestamp);
    expect($dtStr)->not->toBeNull()
        ->and((int)$dtStr->format('U'))->toBe($timestamp);
});

test('parse standard date formats', function () {
    $iso = '2024-05-20T15:30:00+00:00';
    $dtIso = $this->parser->parse($iso);
    expect($dtIso)->not->toBeNull()
        ->and($dtIso->format('Y-m-d'))->toBe('2024-05-20');

    $ymd = '2024-12-31';
    $dtYmd = $this->parser->parse($ymd);
    expect($dtYmd)->not->toBeNull()
        ->and($dtYmd->format('Y-m-d'))->toBe('2024-12-31');

    $slash = '2024/07/04 12:00:00';
    $dtSlash = $this->parser->parse($slash);
    expect($dtSlash)->not->toBeNull()
        ->and($dtSlash->format('Y-m-d'))->toBe('2024-07-04');
});

test('parse invalid string returns null', function () {
    expect($this->parser->parse('invalid-date-string-12345!'))->toBeNull();
});
