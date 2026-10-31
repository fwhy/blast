<?php

use Fwhy\Blast\Mime;

test('common mime types exist', function () {
    expect(Mime::TYPES)->toHaveKey('html')
        ->and(Mime::TYPES['html'])->toBe('text/html')
        ->and(Mime::TYPES)->toHaveKey('css')
        ->and(Mime::TYPES['css'])->toBe('text/css')
        ->and(Mime::TYPES)->toHaveKey('js')
        ->and(Mime::TYPES['js'])->toBe('application/javascript')
        ->and(Mime::TYPES)->toHaveKey('png')
        ->and(Mime::TYPES['png'])->toBe('image/png')
        ->and(Mime::TYPES)->toHaveKey('json')
        ->and(Mime::TYPES['json'])->toBe('application/json');
});
