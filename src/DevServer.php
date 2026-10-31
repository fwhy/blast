<?php

$autoloads = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'];

foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

use Fwhy\Blast\Server\DevServerHandler;

$handler = new DevServerHandler();
$response = $handler->handle($_SERVER['SCRIPT_NAME'] ?? '/');
$response->send();
