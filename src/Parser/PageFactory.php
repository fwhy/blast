<?php

namespace Fwhy\Blast\Parser;

use Fwhy\Blast\Exception\FileException;
use Fwhy\Blast\Filesystem\FilesystemManager;
use Fwhy\Blast\Models\Page;

class PageFactory
{
    private FrontMatterParser $frontMatterParser;
    private MarkdownConverter $markdownConverter;
    private DateTimeParser $dateTimeParser;
    private FilesystemManager $filesystem;

    public function __construct(
        ?FrontMatterParser $frontMatterParser = null,
        ?MarkdownConverter $markdownConverter = null,
        ?DateTimeParser $dateTimeParser = null,
        ?FilesystemManager $filesystem = null
    ) {
        $this->frontMatterParser = $frontMatterParser ?? new FrontMatterParser();
        $this->markdownConverter = $markdownConverter ?? new MarkdownConverter();
        $this->dateTimeParser = $dateTimeParser ?? new DateTimeParser();
        $this->filesystem = $filesystem ?? new FilesystemManager();
    }

    /**
     * Create Page from Markdown file path
     *
     * @param string $mdFile
     * @param string $basePath
     * @return Page
     * @throws FileException
     */
    public function createFromFile(string $mdFile, string $basePath): Page
    {
        $rawContent = $this->filesystem->readFile($mdFile);

        $ctime = filectime($mdFile);
        $mtime = filemtime($mdFile);

        $defaultCreateDate = ($ctime !== false) ? $this->dateTimeParser->parse($ctime) : null;
        $defaultUpdateDate = ($mtime !== false) ? $this->dateTimeParser->parse($mtime) : null;

        return $this->createFromContent(
            rawContent: $rawContent,
            fullPath: $mdFile,
            basePath: $basePath,
            defaultCreateDate: $defaultCreateDate,
            defaultUpdateDate: $defaultUpdateDate
        );
    }

    /**
     * Create Page from raw content string
     *
     * @param string $rawContent
     * @param string $fullPath
     * @param string $basePath
     * @param \DateTime|null $defaultCreateDate
     * @param \DateTime|null $defaultUpdateDate
     * @return Page
     */
    public function createFromContent(
        string $rawContent,
        string $fullPath = '',
        string $basePath = '',
        ?\DateTime $defaultCreateDate = null,
        ?\DateTime $defaultUpdateDate = null
    ): Page {
        $parsed = $this->frontMatterParser->parse($rawContent);
        $meta = $parsed['meta'];
        $bodyMarkdown = $parsed['content'];

        $htmlContent = $this->markdownConverter->convert($bodyMarkdown);

        $title = '';
        if (preg_match('/<h1>(.+)<\/h1>/', $htmlContent, $h1)) {
            $title = $h1[1];
        }

        $relPath = '';
        $rawSlug = '/';
        if ($fullPath !== '') {
            if ($basePath !== '') {
                $search = '/' . preg_quote($basePath, '/') . '/';
                $relPath = (string)preg_replace($search, '', $fullPath, 1);
            } else {
                $relPath = $fullPath;
            }

            $pathInfo = pathinfo($relPath);
            $rawSlug = $pathInfo['dirname'] ?? '';
            $file = $pathInfo['filename'];

            if (strtolower($file) !== 'readme') {
                $rawSlug = $rawSlug . (($rawSlug !== '/') ? '/' : '') . $file;
            }
        }

        $createDate = $defaultCreateDate;
        $updateDate = $defaultUpdateDate;

        if (isset($meta['title']) && is_string($meta['title'])) {
            $title = $meta['title'];
        }

        if (isset($meta['slug']) && is_string($meta['slug'])) {
            $rawSlug = $meta['slug'];
        }

        if (isset($meta['createDate'])) {
            $parsedDate = $this->dateTimeParser->parse($meta['createDate']);
            if ($parsedDate !== null) {
                $createDate = $parsedDate;
            }
        }

        if (isset($meta['updateDate'])) {
            $parsedDate = $this->dateTimeParser->parse($meta['updateDate']);
            if ($parsedDate !== null) {
                $updateDate = $parsedDate;
            }
        }

        $rawSlug = ''
            . ((str_starts_with($rawSlug, '/')) ? '' : '/')
            . $rawSlug
            . ((str_ends_with($rawSlug, '/')) ? '' : '/');

        $slug = implode('/', array_map('rawurlencode', explode('/', $rawSlug)));

        return new Page(
            rawSlug: $rawSlug,
            slug: $slug,
            title: $title,
            meta: $meta,
            content: $htmlContent,
            rawContent: $bodyMarkdown,
            fullPath: $fullPath,
            relPath: $relPath,
            createDate: $createDate,
            updateDate: $updateDate
        );
    }
}
