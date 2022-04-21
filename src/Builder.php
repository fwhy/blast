<?php

namespace Fwhy\Blast;

use eftec\bladeone\BladeOne;
use FilesystemIterator;
use Fwhy\Blast\Models\Page;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Builder
 *
 * @property array $config
 * @property Page[] $pages
 * @property Directory $dir
 */
class Builder
{
    /** @var array */
    public array $config = [];
    /** @var Directory */
    public Directory $dir;
    /** @var array */
    public array $hooks = [];
    /** @var Page[] */
    public array $pages = [];

    /**
     * Build pages
     *
     * @return void
     * @throws \Exception
     */
    public function buildPages(): void
    {
        $mdFiles = $this->dir->getFiles('contents');

        foreach ($mdFiles as $mdFile) {
            $page = Page::build($mdFile, $this->dir->contents);
            $this->pages[$page->rawSlug] = $page;
        }

        ksort($this->pages);
        $this->executeHook(Hook::AFTER_BUILD_PAGES);
    }

    /**
     * Create directories
     *
     * @return void
     */
    public function createDirectories()
    {
        $this->dir->createDirectories();
    }

    /**
     * Load config
     *
     * @return void
     * @throws ParseException
     */
    public function loadConfig()
    {
        $baseDir = realpath('');
        $this->config = Yaml::parseFile(__DIR__ . '/blastrc.yml');
        $config = (file_exists("{$baseDir}/blastrc.yml")) ?
            (@Yaml::parseFile("{$baseDir}/blastrc.yml") ?? [])
            : [];
        $this->config = array_replace_recursive($this->config, $config);

        $this->dir = new Directory(
            $baseDir,
            $this->config['assets']['dir'],
            $this->config['cache']['dir'],
            $this->config['contents']['dir'],
            $this->config['hooks']['dir'],
            $this->config['output']['dir'],
            $this->config['theme']['dir'],
        );
    }

    /**
     * Load hooks
     *
     * @return void
     */
    public function loadHooks()
    {
        $hookFiles = $this->dir->getFiles('hooks');

        foreach ($hookFiles as $hookFile) {
            require_once $hookFile;
        }

        foreach (get_declared_classes() as $class) {
            if (!is_subclass_of($class, Hook::class)) {
                continue;
            }

            if (!isset($this->hooks[$class::timing()])) {
                $this->hooks[$class::timing()] = [];
            }

            $this->hooks[$class::timing()][] = $class;
        }

        $this->executeHook(Hook::AFTER_LOAD_HOOKS);
    }

    /**
     * Output HTML files
     *
     * @return void
     * @throws \Exception
     */
    public function output(): void
    {
        $this->dir->clearOutput();

        foreach ($this->pages as $name => $page) {
            $file = $this->dir->output . $page->rawSlug . ((str_ends_with($name, '/')) ? 'index' : '') . '.html';
            @mkdir(dirname($file), 0777, true);
            file_put_contents($file, $page->html);
        }

        $this->dir->copyAssets();
    }

    /**
     * Render HTML
     *
     * @param string|null $pageName
     * @param string $view
     * @return string
     * @throws \Exception
     */
    public function render(): void
    {
        $blade = new BladeOne($this->dir->theme, $this->dir->cache, BladeOne::MODE_AUTO);

        if (!isset($this->pages['/'])) {
            $this->pages['/'] = new Page();
            ksort($this->pages);
        }

        foreach ($this->pages as $page) {
            $page->html = $blade->run('page', [
                'page' => $page,
                'pages' => $this->pages,
                'config' => $this->config,
            ]);
        }

        $this->pages['404'] = new Page('404');
        $this->pages['404']->html = $blade->run('404', ['pages' => $this->pages, 'config' => $this->config]);

        $this->executeHook(Hook::AFTER_RENDER);
    }

    /**
     * Execute hook
     *
     * @param int $timing
     * @return void
     */
    private function executeHook(int $timing): void
    {
        if (!isset($this->hooks[$timing])) {
            return;
        }

        /** @var Hook $hook */
        foreach ($this->hooks[$timing] as $hook) {
            $hook::execute($this);
        }
    }
}
