<?php

namespace Fwhy\Blast\Models;

use cebe\markdown\GithubMarkdown;
use Pagerange\Markdown\MetaParsedown;

/**
 * Page
 *
 * @property string $title
 * @property array $meta
 * @property string $content
 * @property string $rawContent
 * @property string $fullPath
 * @property string $relPath
 * @property string $slug
 * @property string $rawSlug
 * @property string $html
 * @property \DateTime|null $createDate
 * @property \DateTime|null $updateDate
 */
class Page
{
    /** @var string */
    public string $title = '';
    /** @var array */
    public array $meta = [];
    /** @var string */
    public string $content = '';
    /** @var string */
    public string $rawContent = '';
    /** @var string */
    public string $fullPath = '';
    /** @var string */
    public string $relPath = '';
    /** @var string */
    public string $slug = '';
    /** @var string */
    public string $rawSlug = '';
    /** @var string */
    public string $html = '';
    /** @var \DateTime|null */
    public ?\DateTime $createDate = null;
    /** @var \DateTime|null */
    public ?\DateTime $updateDate = null;

    /**
     * Constructor
     *
     * @param string $rawSlug
     */
    public function __construct(string $rawSlug = '/')
    {
        $this->rawSlug = ((str_starts_with($rawSlug, '/')) ? '' : '/') . $rawSlug;
        $this->slug = implode('/', array_map('rawurlencode', explode('/', $this->rawSlug)));
    }

    /**
     * Build pages
     *
     * @param $mdFile
     * @param $basePath
     * @return Page
     * @throws \Exception
     */
    public static function build($mdFile, $basePath): Page
    {
        $page = new self();
        $page->fullPath = $mdFile;

        $search = '/' . preg_quote($basePath, '/') . '/';
        $page->relPath = preg_replace($search, '', $page->fullPath, 1);

        $mp = new MetaParsedown();
        $fullRawContent = file_get_contents($page->fullPath);
        $page->meta = (array)$mp->meta($fullRawContent);
        $page->rawContent = $mp->stripMeta($fullRawContent);

        $page->content = (new GithubMarkdown())->parse($page->rawContent);

        if (preg_match('/<h1>(.+)<\/h1>/', $page->content, $h1)) {
            $page->title = $h1[1];
        }

        ['dirname' => $page->rawSlug, 'filename' => $file] = pathinfo($page->relPath);

        if (strtolower($file) !== 'readme') {
            $page->rawSlug = $page->rawSlug . (($page->rawSlug !== '/') ? '/' : '') . $file;
        }

        $page->createDate = \DateTime::createFromFormat('U', filectime($page->fullPath)) ?: null;
        $page->updateDate = \DateTime::createFromFormat('U', filemtime($page->fullPath)) ?: null;

        $page->applyMeta();
        $page->rawSlug = ''
            . ((str_starts_with($page->rawSlug, '/')) ? '' : '/')
            . $page->rawSlug
            . ((str_ends_with($page->rawSlug, '/')) ? '' : '/');

        $page->slug = implode('/', array_map('rawurlencode', explode('/', $page->rawSlug)));

        return $page;
    }

    /**
     * Apply meta data
     *
     * @return void
     * @throws \Exception
     */
    private function applyMeta()
    {
        if (isset($this->meta['title'])) {
            $this->title = $this->meta['title'];
        }

        if (isset($this->meta['slug'])) {
            $this->rawSlug = $this->meta['slug'];
        }

        if (isset($this->meta['createDate'])) {
            $this->updateDate = @\DateTime::createFromFormat('U', $this->meta['createDate']);
        }

        if (isset($this->meta['updateDate'])) {
            $this->updateDate = @\DateTime::createFromFormat('U', $this->meta['updateDate']);
        }
    }
}
