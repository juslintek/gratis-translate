<?php declare(strict_types=1);

defined('ABSPATH') || exit;

spl_autoload_register(function (string $className): void {
    $prefix = 'Gratis\\Translate\\';
    if (strncmp($className, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($className, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
