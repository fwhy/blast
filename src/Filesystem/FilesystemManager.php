<?php

namespace Fwhy\Blast\Filesystem;

use Fwhy\Blast\Exception\DirectoryException;
use Fwhy\Blast\Exception\FileException;

class FilesystemManager
{
    /**
     * Ensure directory exists, creating it if necessary
     *
     * @param string $path
     * @param int $mode
     * @return void
     * @throws DirectoryException
     */
    public function ensureDirectoryExists(string $path, int $mode = 0777): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, $mode, true) && !is_dir($path)) {
            throw new DirectoryException("Failed to create directory: {$path}");
        }
    }

    /**
     * Clear all contents inside directory without removing the directory itself
     *
     * @param string $dir
     * @return void
     * @throws DirectoryException|FileException
     */
    public function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        try {
            $rdIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $riIterator = new \RecursiveIteratorIterator($rdIterator, \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($riIterator as $fileInfo) {
                if ($fileInfo->isDir()) {
                    if (!rmdir($fileInfo->getPathname())) {
                        throw new DirectoryException("Failed to remove directory: {$fileInfo->getPathname()}");
                    }
                } else {
                    if (!unlink($fileInfo->getPathname())) {
                        throw new FileException("Failed to delete file: {$fileInfo->getPathname()}");
                    }
                }
            }
        } catch (\UnexpectedValueException $e) {
            throw new DirectoryException("Error accessing directory: {$dir}", 0, $e);
        }
    }

    /**
     * Copy directory recursively
     *
     * @param string $source
     * @param string $destination
     * @return void
     * @throws DirectoryException
     * @throws FileException
     */
    public function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        $this->ensureDirectoryExists($destination);

        try {
            $rdIterator = new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS);
            $riIterator = new \RecursiveIteratorIterator($rdIterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($riIterator as $fileInfo) {
                $targetPath = str_replace($source, $destination, $fileInfo->getPathname());

                if ($fileInfo->isDir()) {
                    $this->ensureDirectoryExists($targetPath);
                } else {
                    $targetDir = dirname($targetPath);
                    $this->ensureDirectoryExists($targetDir);
                    if (!copy($fileInfo->getPathname(), $targetPath)) {
                        throw new FileException("Failed to copy file from {$fileInfo->getPathname()} to {$targetPath}");
                    }
                }
            }
        } catch (\UnexpectedValueException $e) {
            throw new DirectoryException("Error reading directory: {$source}", 0, $e);
        }
    }

    /**
     * Get all files recursively in a directory
     *
     * @param string $dir
     * @return string[]
     * @throws DirectoryException
     */
    public function getFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        try {
            $rdIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

            foreach (new \RecursiveIteratorIterator($rdIterator) as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        } catch (\UnexpectedValueException $e) {
            throw new DirectoryException("Error listing files in: {$dir}", 0, $e);
        }

        return $files;
    }

    /**
     * Read file contents with error handling
     *
     * @param string $path
     * @return string
     * @throws FileException
     */
    public function readFile(string $path): string
    {
        if (!file_exists($path)) {
            throw new FileException("File not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new FileException("Failed to read file: {$path}");
        }

        return $content;
    }

    /**
     * Write file contents with automatic directory creation
     *
     * @param string $path
     * @param string $content
     * @return void
     * @throws DirectoryException
     * @throws FileException
     */
    public function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        $this->ensureDirectoryExists($dir);

        if (file_put_contents($path, $content) === false) {
            throw new FileException("Failed to write file: {$path}");
        }
    }
    /**
     * Remove a directory and all its contents
     *
     * @param string $dir
     * @return void
     * @throws DirectoryException|FileException
     */
    public function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $this->clearDirectory($dir);
        if (!rmdir($dir)) {
            throw new DirectoryException("Failed to remove directory: {$dir}");
        }
    }
}
