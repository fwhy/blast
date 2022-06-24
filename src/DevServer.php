<?php

$autoloads = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'];

foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

use Fwhy\Blast\Builder;
use Fwhy\Blast\Mime;

$builder = new Builder();
$builder->loadConfig();
$builder->createDirectories();
$builder->loadHooks();
$builder->buildPages();
$builder->render();

$ext = strtolower(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_EXTENSION));
$mime = ($ext) ? Mime::TYPES[$ext] : 'text/html';

if (array_key_exists($_SERVER['SCRIPT_NAME'], $builder->pages)) {
    header("Content-type: {$mime}");
    echo $builder->pages[$_SERVER['SCRIPT_NAME']]->html;
    exit;
}

$file = "{$builder->dir->assets}{$_SERVER['SCRIPT_NAME']}";

if (file_exists($file)) {
    header("Content-type: {$mime}");
    echo file_get_contents($file);
    exit;
}

header('HTTP/1.1 404 Not Found');
echo $builder->pages['404']->html;
