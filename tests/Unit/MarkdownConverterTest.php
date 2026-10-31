<?php

use Fwhy\Blast\Parser\MarkdownConverter;

beforeEach(function () {
    $this->converter = new MarkdownConverter();
});

test('convert markdown to html', function () {
    $markdown = "# Hello Blast\n\nThis is **bold** text and [link](https://example.com).";
    $html = $this->converter->convert($markdown);

    expect($html)->toContain('<h1>Hello Blast</h1>')
        ->and($html)->toContain('<strong>bold</strong>')
        ->and($html)->toContain('<a href="https://example.com">link</a>');
});

test('convert github flavored markdown table', function () {
    $markdown = <<<MD
| Header 1 | Header 2 |
| --- | --- |
| Val 1 | Val 2 |
MD;
    $html = $this->converter->convert($markdown);

    expect($html)->toContain('<table>')
        ->and($html)->toContain('<th>Header 1</th>')
        ->and($html)->toContain('<td>Val 1</td>');
});
