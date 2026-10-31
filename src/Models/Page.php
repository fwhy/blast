<?php

namespace Fwhy\Blast\Models;

use Fwhy\Blast\Parser\PageFactory;

/**
 * Page
 *
 * @property string $title
 * @property array<string, mixed> $meta
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
    /** @var array<string, mixed> */
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
     * @param string|null $slug
     * @param string $title
     * @param array<string, mixed> $meta
     * @param string $content
     * @param string $rawContent
     * @param string $fullPath
     * @param string $relPath
     * @param \DateTime|null $createDate
     * @param \DateTime|null $updateDate
     * @param string $html
     */
    public function __construct(
        string $rawSlug = '/',
        ?string $slug = null,
        string $title = '',
        array $meta = [],
        string $content = '',
        string $rawContent = '',
        string $fullPath = '',
        string $relPath = '',
        ?\DateTime $createDate = null,
        ?\DateTime $updateDate = null,
        string $html = ''
    ) {
        $this->rawSlug = ((str_starts_with($rawSlug, '/')) ? '' : '/') . $rawSlug;
        $this->slug = $slug ?? explode('/', $this->rawSlug)
            |> (fn($x) => array_map('rawurlencode', $x))
            |> (fn($x) => implode('/', $x));
        $this->title = $title;
        $this->meta = $meta;
        $this->content = $content;
        $this->rawContent = $rawContent;
        $this->fullPath = $fullPath;
        $this->relPath = $relPath;
        $this->createDate = $createDate;
        $this->updateDate = $updateDate;
        $this->html = $html;
    }

    /**
     * Build page (delegates to PageFactory for backwards compatibility)
     *
     * @param string $mdFile
     * @param string $basePath
     * @return Page
     * @throws \Exception
     */
    public static function build(string $mdFile, string $basePath): Page
    {
        $factory = new PageFactory();
        return $factory->createFromFile($mdFile, $basePath);
    }
}
