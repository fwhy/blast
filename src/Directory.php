<?php

namespace Fwhy\Blast;

use Fwhy\Blast\Exception\DirectoryException;
use Fwhy\Blast\Filesystem\DirectoryPaths;
use Fwhy\Blast\Filesystem\FilesystemManager;

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

    private DirectoryPaths $paths;
    private FilesystemManager $filesystem;

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
     * @param FilesystemManager|null $filesystem
     */
    public function __construct(
        string $base,
        string $assets,
        string $cache,
        string $contents,
        string $hooks,
        string $output,
        string $theme,
        ?FilesystemManager $filesystem = null
    ) {
        $this->base = $base;
        $this->assets = $assets;
        $this->cache = $cache;
        $this->contents = $contents;
        $this->hooks = $hooks;
        $this->output = $output;
        $this->theme = $theme;

        $this->paths = new DirectoryPaths(
            base: $base,
            assets: $assets,
            cache: $cache,
            contents: $contents,
            hooks: $hooks,
            output: $output,
            theme: $theme
        );
        $this->filesystem = $filesystem ?? new FilesystemManager();
    }

    /**
     * Create directories
     *
     * @return void
     * @throws DirectoryException
     */
    public function createDirectories(): void
    {
        $this->filesystem->ensureDirectoryExists($this->paths->resolvePath($this->theme));
        $this->filesystem->ensureDirectoryExists($this->paths->getAssetsPath());
        $this->filesystem->ensureDirectoryExists($this->paths->getCachePath() . '/theme');
        $this->filesystem->ensureDirectoryExists($this->paths->getContentsPath());
        $this->filesystem->ensureDirectoryExists($this->paths->getHooksPath());
        $this->filesystem->ensureDirectoryExists($this->paths->getOutputPath());
        $this->filesystem->ensureDirectoryExists($this->paths->getThemePath());
    }

    /**
     * Clear output directory
     *
     * @return void
     * @throws DirectoryException
     */
    public function clearOutput(): void
    {
        $this->filesystem->clearDirectory($this->paths->getOutputPath());
    }

    /**
     * Copy asset files
     *
     * @return void
     * @throws DirectoryException
     * @throws \Fwhy\Blast\Exception\FileException
     */
    public function copyAssets(): void
    {
        $this->filesystem->copyDirectory(
            $this->paths->getAssetsPath(),
            $this->paths->getOutputPath()
        );
    }

    /**
     * Get files
     *
     * @param string $dirName
     * @return string[]
     * @throws DirectoryException
     */
    public function getFiles(string $dirName): array
    {
        $dir = $this->get($dirName);
        return $this->filesystem->getFiles($dir);
    }

    /**
     * Get underlying DirectoryPaths instance
     *
     * @return DirectoryPaths
     */
    public function getPaths(): DirectoryPaths
    {
        return $this->paths;
    }

    /**
     * Get underlying FilesystemManager instance
     *
     * @return FilesystemManager
     */
    public function getFilesystem(): FilesystemManager
    {
        return $this->filesystem;
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
            'theme' => $this->paths->getThemePath(),
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
}
