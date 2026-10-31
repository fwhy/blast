<?php

namespace Fwhy\Blast\Filesystem;

readonly class DirectoryPaths
{
    public function __construct(
        public string $base,
        public string $assets = 'assets',
        public string $cache = '.cache',
        public string $contents = 'contents',
        public string $hooks = 'hooks',
        public string $output = '.output',
        public string $theme = 'theme',
    ) {
    }

    public function getAssetsPath(): string
    {
        return $this->resolvePath($this->assets);
    }

    public function getCachePath(): string
    {
        return $this->resolvePath($this->cache);
    }

    public function getContentsPath(): string
    {
        return $this->resolvePath($this->contents);
    }

    public function getHooksPath(): string
    {
        return $this->resolvePath($this->hooks);
    }

    public function getOutputPath(): string
    {
        return $this->resolvePath($this->output);
    }

    public function getThemePath(): string
    {
        $customTheme = $this->resolvePath($this->theme);
        $pageFile = "{$customTheme}/page.blade.php";

        return file_exists($pageFile) ? $customTheme : (dirname(__DIR__) . '/theme');
    }

    public function resolvePath(string $relativePath): string
    {
        return "{$this->base}/{$relativePath}";
    }
}
