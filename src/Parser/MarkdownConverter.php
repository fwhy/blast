<?php

namespace Fwhy\Blast\Parser;

use League\CommonMark\ConverterInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\Config\Exception\ConfigurationExceptionInterface;

class MarkdownConverter
{
    private ConverterInterface $converter;

    public function __construct(?ConverterInterface $converter = null)
    {
        $this->converter = $converter ?? new GithubFlavoredMarkdownConverter();
    }

    /**
     * Convert markdown string to HTML
     *
     * @param string $markdown
     * @return string
     * @throws CommonMarkException
     * @throws ConfigurationExceptionInterface
     */
    public function convert(string $markdown): string
    {
        return (string)$this->converter->convert($markdown);
    }
}
