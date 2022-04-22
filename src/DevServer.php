<?php

$autoloads = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'];

foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

use Fwhy\Blast\Builder;
use Fwhy\Blast\Hook;

$builder = new Builder();
$builder->loadConfig();
$builder->createDirectories();
$builder->loadHooks();
$builder->buildPages();
$builder->render();

if (!str_ends_with($_SERVER['SCRIPT_NAME'], '/')) {
    $file = "{$builder->dir->assets}{$_SERVER['SCRIPT_NAME']}";

    if (!file_exists($file)) {
        header('HTTP/1.1 404 Not Found');
        echo $builder->pages['404']->html;
        exit;
    }

    $mime =  mime_content_type($file);

    if ($mime === 'text/plain') {
        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'css':
                $mime = 'text/css';
                break;

            case 'js':
                $mime = 'text/javascript';
                break;

            default:
                break;
        }
    }

    header("Content-type: {$mime}");
    echo file_get_contents($file);
    exit;
}

if (!isset($builder->pages[$_SERVER['SCRIPT_NAME']])) {
    header('HTTP/1.1 404 Not Found');
    echo $builder->pages['404']->html;
    exit;
}

echo $builder->pages[$_SERVER['SCRIPT_NAME']]->html;
