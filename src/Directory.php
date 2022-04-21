<?php

namespace Fwhy\Blast;

/**
 * Directory
 *
 * @property string $base
 * @property-read string $assets
 * @property-read string $cache
 * @property-read string $contents
 * @property-read string $hooks
 * @property-read string $output
 * @property-read string $theme
 * @property-read string $relAssets
 * @property-read string $relCache
 * @property-read string $relContents
 * @property-read string $relHooks
 * @property-read string $relOutput
 * @property-read string $relTheme
 */
class Directory
{
    /** @var string */
    public string $base;
    /** @var string */
    private string $assets;
    /** @var string */
    private string $cache;
    /** @var string */
    private string $contents;
    /** @var string */
    private string $hooks;
    /** @var string */
    private string $output;
    /** @var string */
    private string $theme;

    /**
     * Constructor
     *
     * @param string $base
     * @param string $assets
     * @param string $cache
     * @param string $contents
     * @param string $hooks
     * @param string $output
     * @param string $theme
     */
    public function __construct(
        string $base,
        string $assets,
        string $cache,
        string $contents,
        string $hooks,
        string $output,
        string $theme
    )
    {
        $this->base = $base;
        $this->assets = $assets;
        $this->cache = $cache;
        $this->contents = $contents;
        $this->hooks = $hooks;
        $this->output = $output;
        $this->theme = $theme;
    }

    /**
     * Create directories
     *
     * @return void
     */
    public function createDirectories(): void
    {
        @mkdir("{$this->base}/{$this->theme}", 0777, true);
        @mkdir("{$this->get('assets')}", 0777, true);
        @mkdir("{$this->get('cache')}/theme", 0777, true);
        @mkdir("{$this->get('contents')}", 0777, true);
        @mkdir("{$this->get('hooks')}", 0777, true);
        @mkdir("{$this->get('output')}", 0777, true);
        @mkdir("{$this->get('theme')}", 0777, true);
    }

    /**
     * Clear output directory
     *
     * @return void
     */
    public function clearOutput(): void
    {
        $rdIterator = new \RecursiveDirectoryIterator($this->get('output'), \FilesystemIterator::SKIP_DOTS);
        $riIterator = new \RecursiveIteratorIterator($rdIterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($riIterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }
    }

    /**
     * Copy asset files
     *
     * @return void
     */
    public function copyAssets(): void
    {
        $rdIterator = new \RecursiveDirectoryIterator($this->get('assets'), \FilesystemIterator::SKIP_DOTS);
        $riIterator = new \RecursiveIteratorIterator($rdIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($riIterator as $fileInfo) {
            $outputAssets = str_replace($this->get('assets'), $this->get('output'), $fileInfo->getPathname());

            if ($fileInfo->isDir()) {
                @mkdir($outputAssets, 0777, true);
            } else {
                copy($fileInfo->getPathname(), $outputAssets);
            }
        }
    }

    /**
     * Get files
     *
     * @param string $dirName
     * @return string[]
     */
    public function getFiles(string $dirName): array
    {
        $dir = $this->get($dirName);
        $files = [];

        $rdIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

        foreach (new \RecursiveIteratorIterator($rdIterator) as $fileInfo) {
            $files[] = $fileInfo->getPathname();
        }

        return $files;
    }

    /**
     * Getter
     *
     * @param string $key
     * @return string
     */
    public function __get(string $key): string
    {
        return match ($key) {
            'theme' => $this->getTheme(),
            default => $this->get($key),
        };
    }

    /**
     * Getter
     *
     * @param string $key
     * @return string
     */
    private function get(string $key): string
    {
        if (preg_match('/^rel(.+)$/', $key, $matches)) {
            $k = lcfirst($matches[1]);

            return $this->$k;
        }

        return "{$this->base}/{$this->$key}";
    }

    /**
     * Get theme dir path
     *
     * @return string
     */
    private function getTheme(): string
    {
        $page = "{$this->get('theme')}/page.blade.php";

        return (file_exists($page)) ? $this->get('theme') : (__DIR__ . '/theme');
    }
}
