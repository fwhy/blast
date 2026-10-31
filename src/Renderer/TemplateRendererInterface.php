<?php

namespace Fwhy\Blast\Renderer;

use Fwhy\Blast\Models\Page;

interface TemplateRendererInterface
{
    /**
     * Render a page with layout and variables
     *
     * @param Page $page
     * @param array<string, Page> $pages
     * @param array<string, mixed> $config
     * @return string
     */
    public function renderPage(Page $page, array $pages, array $config): string;

    /**
     * Render a generic template view with data
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return string
     */
    public function render(string $view, array $data = []): string;
}
