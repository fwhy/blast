<?php

namespace Fwhy\Blast\Server;

use Fwhy\Blast\Builder;
use Fwhy\Blast\Exception\ConfigException;
use Fwhy\Blast\Exception\DirectoryException;
use Fwhy\Blast\Mime;

class DevServerHandler
{
    private Builder $builder;
    private string $assetsDir;

    /**
     * @param Builder|null $builder
     * @throws ConfigException
     * @throws DirectoryException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function __construct(?Builder $builder = null)
    {
        if ($builder === null) {
            $builder = new Builder();
            $builder->loadConfig();
            $builder->createDirectories();
            $builder->loadHooks();
            $builder->buildPages();
            $builder->render();
        }

        $this->builder = $builder;
        $this->assetsDir = new \ReflectionProperty($builder, 'dir')->isInitialized($builder)
            ? rtrim($builder->dir->assets, '/')
            : '';
    }

    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Handle an incoming request path
     *
     * @param string $uri
     * @return ServerResponse
     */
    public function handle(string $uri): ServerResponse
    {
        // Parse URI path
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        
        // Normalize leading slash
        $path = '/' . ltrim($path, '/');

        // Check if page exists in builder
        if (array_key_exists($path, $this->builder->pages)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ($ext && isset(Mime::TYPES[$ext])) ? Mime::TYPES[$ext] : 'text/html';

            return new ServerResponse(
                statusCode: 200,
                headers: ['Content-Type' => $mime],
                body: (string)$this->builder->pages[$path]->html
            );
        }

        // Check for static asset in assets directory with path traversal protection
        $assetResponse = $this->handleAsset($path);
        if ($assetResponse !== null) {
            return $assetResponse;
        }

        // 404 fallback
        $notFoundHtml = $this->builder->pages['404.html']->html ?? '<h1>404 Not Found</h1>';
        return new ServerResponse(
            statusCode: 404,
            headers: ['Content-Type' => 'text/html'],
            body: $notFoundHtml
        );
    }

    /**
     * Handle static asset safely
     *
     * @param string $path
     * @return ServerResponse|null
     */
    private function handleAsset(string $path): ?ServerResponse
    {
        // Resolve target file path
        $realAssetsDir = realpath($this->assetsDir);
        if ($realAssetsDir === false || !is_dir($realAssetsDir)) {
            return null;
        }

        $targetPath = $realAssetsDir . $path;
        $realPath = realpath($targetPath);

        // Path traversal protection: Ensure realPath starts with realAssetsDir
        if ($realPath === false || !is_file($realPath) || !str_starts_with($realPath, $realAssetsDir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mime = ($ext && isset(Mime::TYPES[$ext])) ? Mime::TYPES[$ext] : 'application/octet-stream';

        $content = file_get_contents($realPath);
        if ($content === false) {
            return null;
        }

        return new ServerResponse(
            statusCode: 200,
            headers: ['Content-Type' => $mime],
            body: $content
        );
    }
}
