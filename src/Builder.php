<?php

namespace Fwhy\Blast;

use Fwhy\Blast\Event\AfterBuildPagesEvent;
use Fwhy\Blast\Event\AfterLoadHooksEvent;
use Fwhy\Blast\Event\AfterRenderEvent;
use Fwhy\Blast\Event\BeforeRenderEvent;
use Fwhy\Blast\Event\EventDispatcher;
use Fwhy\Blast\Event\PostBuildEvent;
use Fwhy\Blast\Exception\ConfigException;
use Fwhy\Blast\Exception\DirectoryException;
use Fwhy\Blast\Models\Page;
use Fwhy\Blast\Parser\PageFactory;
use Fwhy\Blast\Renderer\BladeRenderer;
use Fwhy\Blast\Renderer\TemplateRendererInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Builder
 *
 * @property array<string, mixed> $config
 * @property Page[] $pages
 * @property Directory $dir
 */
class Builder
{
    /** @var array<string, mixed> */
    public array $config = [];
    /** @var Directory */
    public Directory $dir;
    /** @var array<int, array<class-string<Hook>>> */
    public array $hooks = [];
    /** @var Page[] */
    public array $pages = [];
    /** @var PageFactory */
    private PageFactory $pageFactory;
    /** @var EventDispatcherInterface */
    private EventDispatcherInterface $eventDispatcher;
    /** @var TemplateRendererInterface|null */
    private ?TemplateRendererInterface $renderer = null;

    /**
     * Constructor
     *
     * @param PageFactory|null $pageFactory
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param TemplateRendererInterface|null $renderer
     */
    public function __construct(
        ?PageFactory $pageFactory = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?TemplateRendererInterface $renderer = null
    ) {
        $this->pageFactory = $pageFactory ?? new PageFactory();
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->renderer = $renderer;
    }

    /**
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Set event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @return void
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get template renderer
     *
     * @return TemplateRendererInterface
     */
    public function getRenderer(): TemplateRendererInterface
    {
        if ($this->renderer === null) {
            $this->renderer = new BladeRenderer($this->dir->theme, $this->dir->cache);
        }

        return $this->renderer;
    }

    /**
     * Set template renderer
     *
     * @param TemplateRendererInterface $renderer
     * @return void
     */
    public function setRenderer(TemplateRendererInterface $renderer): void
    {
        $this->renderer = $renderer;
    }

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
            $page = $this->pageFactory->createFromFile($mdFile, $this->dir->contents);
            $this->pages[$page->rawSlug] = $page;
        }

        ksort($this->pages);
        $this->eventDispatcher->dispatch(new AfterBuildPagesEvent($this));
        $this->executeHook(Hook::AFTER_BUILD_PAGES);
    }

    /**
     * Create directories
     *
     * @return void
     * @throws DirectoryException
     */
    public function createDirectories(): void
    {
        $this->dir->createDirectories();
    }

    /**
     * Load config
     *
     * @return void
     * @throws ConfigException
     */
    public function loadConfig(): void
    {
        $baseDir = realpath('') ?: getcwd() ?: '';

        try {
            $defaultConfig = Yaml::parseFile(__DIR__ . '/blastrc.yml');
            $this->config = is_array($defaultConfig) ? $defaultConfig : [];
        } catch (ParseException $e) {
            throw new ConfigException("Failed to parse default config file: " . $e->getMessage(), 0, $e);
        }

        $userConfigFile = "{$baseDir}/blastrc.yml";
        if (file_exists($userConfigFile)) {
            try {
                $userConfig = Yaml::parseFile($userConfigFile);
                if (is_array($userConfig)) {
                    $this->config = array_replace_recursive($this->config, $userConfig);
                }
            } catch (ParseException $e) {
                throw new ConfigException("Failed to parse user config file: {$userConfigFile} - " . $e->getMessage(), 0, $e);
            }
        }

        $this->dir = new Directory(
            $baseDir,
            $this->config['assets']['dir'] ?? 'assets',
            $this->config['cache']['dir'] ?? '.cache',
            $this->config['contents']['dir'] ?? 'contents',
            $this->config['hooks']['dir'] ?? 'hooks',
            $this->config['output']['dir'] ?? '.output',
            $this->config['theme']['dir'] ?? 'theme',
        );
    }

    /**
     * Load hooks
     *
     * @return void
     * @throws DirectoryException
     */
    public function loadHooks(): void
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

        foreach ($this->hooks as &$hook) {
            usort($hook, fn ($a, $b) => $a::PRIORITY <=> $b::PRIORITY);
        }

        $this->eventDispatcher->dispatch(new AfterLoadHooksEvent($this));
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

        $filesystem = $this->dir->getFilesystem();
        foreach ($this->pages as $name => $page) {
            $file = $this->dir->output . $page->rawSlug . ((str_ends_with($name, '/')) ? 'index.html' : '');
            $filesystem->writeFile($file, $page->html);
        }

        $this->dir->copyAssets();

        $this->eventDispatcher->dispatch(new PostBuildEvent($this));
    }

    /**
     * Render HTML
     *
     * @throws \Exception
     */
    public function render(): void
    {
        $renderer = $this->getRenderer();

        if (!isset($this->pages['/'])) {
            $this->pages['/'] = new Page();
            ksort($this->pages);
        }

        $this->eventDispatcher->dispatch(new BeforeRenderEvent($this));

        foreach ($this->pages as $page) {
            $page->html = $renderer->renderPage($page, $this->pages, $this->config);
        }

        $this->pages['404.html'] = new Page('404.html');
        $this->pages['404.html']->html = $renderer->render('404', ['pages' => $this->pages, 'config' => $this->config]);

        $this->eventDispatcher->dispatch(new AfterRenderEvent($this));
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
