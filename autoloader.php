<?php

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/classes/';

    $namespacePrefix = 'WP_Notion_Sync\\';

    $len = strlen($namespacePrefix);
    if (strncmp($namespacePrefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);

    $file = $baseDir . 'class-wpns-' . strtolower(str_replace('_', '-', $relativeClass) . '.php');

    if (file_exists($file)) {
        require $file;
    }
});
