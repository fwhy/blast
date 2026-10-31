<?php

namespace Fwhy\Blast\Renderer;

use eftec\bladeone\BladeOne;
use Fwhy\Blast\Models\Page;

class BladeRenderer implements TemplateRendererInterface
{
    private BladeOne $blade;

    public function __construct(
        string $templatePath,
        string $cachePath,
        int $mode = BladeOne::MODE_AUTO,
        ?BladeOne $blade = null
    ) {
        $this->blade = $blade ?? new BladeOne($templatePath, $cachePath, $mode);
    }

    /**
     * Get underlying BladeOne instance
     *
     * @return BladeOne
     */
    public function getBlade(): BladeOne
    {
        return $this->blade;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function renderPage(Page $page, array $pages, array $config): string
    {
        return (string)$this->blade->run('page', [
            'page' => $page,
            'pages' => $pages,
            'config' => $config,
        ]);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function render(string $view, array $data = []): string
    {
        return (string)$this->blade->run($view, $data);
    }
}
