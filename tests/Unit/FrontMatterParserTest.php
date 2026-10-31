<?php

use Fwhy\Blast\Parser\FrontMatterParser;

beforeEach(function () {
    $this->parser = new FrontMatterParser();
});

test('parse without front matter', function () {
    $markdown = "# Pure Markdown\n\nNo front matter here.";
    $result = $this->parser->parse($markdown);

    expect($result['meta'])->toBe([])
        ->and($result['content'])->toBe($markdown);
});

test('parse with front matter', function () {
    $raw = <<<MD
---
title: Sample Post
author: Alice
tags:
  - php
  - blast
---
# Main Content

This is content.
MD;

    $result = $this->parser->parse($raw);

    expect($result['meta']['title'])->toBe('Sample Post')
        ->and($result['meta']['author'])->toBe('Alice')
        ->and($result['meta']['tags'])->toBe(['php', 'blast'])
        ->and($result['content'])->toContain('# Main Content')
        ->and($result['content'])->not->toContain('Sample Post');
});
