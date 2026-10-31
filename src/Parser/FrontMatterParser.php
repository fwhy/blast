<?php

namespace Fwhy\Blast\Parser;

use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser as CommonMarkFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParserInterface;

class FrontMatterParser
{
    private FrontMatterParserInterface $parser;

    public function __construct(?FrontMatterParserInterface $parser = null)
    {
        $this->parser = $parser ?? new CommonMarkFrontMatterParser(new SymfonyYamlFrontMatterParser());
    }

    /**
     * Parse raw content and extract front matter and body markdown
     *
     * @param string $rawContent
     * @return array{meta: array<string, mixed>, content: string}
     */
    public function parse(string $rawContent): array
    {
        $parsed = $this->parser->parse($rawContent);

        $meta = (array)($parsed->getFrontMatter() ?? []);
        $content = $parsed->getContent();

        return [
            'meta' => $meta,
            'content' => $content,
        ];
    }
}
